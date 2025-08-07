<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// 1. Validaciones iniciales
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit();
}
if (!isset($_POST['torneo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de torneo no proporcionado.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$torneo_id = (int)$_POST['torneo_id'];

// 2. Verificar permisos y estado del torneo
$stmt_check = $conn->prepare("SELECT organizador_id, estado FROM torneos WHERE id = ?");
$stmt_check->bind_param("i", $torneo_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$torneo = $result_check->fetch_assoc();

if (!$torneo || $torneo['organizador_id'] != $usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para iniciar este torneo.']);
    exit();
}
if ($torneo['estado'] !== 'abierto') {
    echo json_encode(['success' => false, 'message' => 'Este torneo ya no está abierto.']);
    exit();
}

// 3. Obtener y mezclar participantes
$stmt_inscritos = $conn->prepare(
    "SELECT i.usuario_id, u.victorias, u.derrotas 
     FROM inscripciones i 
     JOIN usuarios u ON i.usuario_id = u.id 
     WHERE i.torneo_id = ?"
);
$stmt_inscritos->bind_param("i", $torneo_id);
$stmt_inscritos->execute();
$result_inscritos = $stmt_inscritos->get_result();
$inscritos = $result_inscritos->fetch_all(MYSQLI_ASSOC);

if (count($inscritos) < 2) {
    echo json_encode(['success' => false, 'message' => 'Se necesitan al menos 2 participantes para iniciar.']);
    exit();
}

// --- NUEVO: Lógica de Patrones de Emparejamiento ---

// Seleccionar un patrón de emparejamiento al azar para dar variedad a los torneos.
$patrones = ['aleatorio', 'clasificado'];
$patron_elegido = $patrones[array_rand($patrones)];

$participantes_ordenados = [];

switch ($patron_elegido) {
    case 'clasificado':
        // Ordenar por "seed" (ej: ratio de victorias) de mejor a peor.
        // Esto emparejará a los jugadores más fuertes con los más débiles en la primera ronda.
        usort($inscritos, function ($a, $b) {
            // Calcular ratio de victorias, manejar división por cero.
            $ratio_a = ($a['victorias'] + $a['derrotas'] > 0) ? $a['victorias'] / ($a['victorias'] + $a['derrotas']) : 0;
            $ratio_b = ($b['victorias'] + $b['derrotas'] > 0) ? $b['victorias'] / ($b['victorias'] + $b['derrotas']) : 0;
            
            if ($ratio_a == $ratio_b) return 0;
            return ($ratio_a > $ratio_b) ? -1 : 1; // Orden descendente.
        });
        
        // Reordenar la lista para emparejar el primero con el último, el segundo con el penúltimo, etc.
        $jugadores = $inscritos;
        while(count($jugadores) > 0) {
            $participantes_ordenados[] = array_shift($jugadores); // Saca el mejor.
            if (count($jugadores) > 0) {
                $participantes_ordenados[] = array_pop($jugadores); // Saca el peor.
            }
        }
        break;
    
    default: // 'aleatorio'
        shuffle($inscritos); // Mezcla completamente al azar.
        $participantes_ordenados = $inscritos;
        break;
}

$conn->begin_transaction();

try {
    // 4. (NUEVO) Limpiar partidos existentes para este torneo por si acaso
    $stmt_clean = $conn->prepare("DELETE FROM partidos WHERE torneo_id = ?");
    $stmt_clean->bind_param("i", $torneo_id);
    $stmt_clean->execute();

    // 5. Actualizar estado del torneo a 'en_curso'
    $stmt_update = $conn->prepare("UPDATE torneos SET estado = 'en_curso' WHERE id = ? AND estado = 'abierto'");
    $stmt_update->bind_param("i", $torneo_id);
    $stmt_update->execute();

    // VERIFICACIÓN ADICIONAL: Asegurarse de que la fila fue actualizada.
    // Si affected_rows no es 1, significa que el estado no cambió (quizás otro admin lo inició).
    if ($stmt_update->affected_rows !== 1) {
        throw new Exception("No se pudo actualizar el estado del torneo. Puede que ya estuviera iniciado.");
    }

    // 6. Generar la estructura completa del bracket
    $partidos_ronda_anterior = [];

    // --- Ronda 1: Crear partidos con los participantes reales ---
    $stmt_ronda1 = $conn->prepare("INSERT INTO partidos (torneo_id, ronda, numero_partido_ronda, participante1_id, participante2_id) VALUES (?, 1, ?, ?, ?)");
    for ($i = 0; $i < count($participantes_ordenados); $i += 2) {
        $p1_id = $participantes_ordenados[$i]['usuario_id'];
        $p2_id = isset($participantes_ordenados[$i + 1]) ? $participantes_ordenados[$i + 1]['usuario_id'] : null;
        $num_partido_ronda = ($i / 2) + 1;
        $stmt_ronda1->bind_param("iiii", $torneo_id, $num_partido_ronda, $p1_id, $p2_id);
        $stmt_ronda1->execute();
        $partidos_ronda_anterior[] = $conn->insert_id; // Guardar el ID del partido creado
    }
    $stmt_ronda1->close();

    // --- Rondas Siguientes: Crear partidos placeholder vinculados ---
    $ronda_actual = 2;
    $stmt_rondas_siguientes = $conn->prepare("INSERT INTO partidos (torneo_id, ronda, numero_partido_ronda, fuente_partido1_id, fuente_partido2_id) VALUES (?, ?, ?, ?, ?)");

    while (count($partidos_ronda_anterior) > 1) {
        $partidos_ronda_actual = [];
        for ($i = 0; $i < count($partidos_ronda_anterior); $i += 2) {
            $fuente1_id = $partidos_ronda_anterior[$i];
            // Si hay un número impar de partidos en la ronda anterior, el último pasa con BYE
            $fuente2_id = isset($partidos_ronda_anterior[$i + 1]) ? $partidos_ronda_anterior[$i + 1] : null;
            $num_partido_ronda = ($i / 2) + 1;

            $stmt_rondas_siguientes->bind_param("iiiii", $torneo_id, $ronda_actual, $num_partido_ronda, $fuente1_id, $fuente2_id);
            $stmt_rondas_siguientes->execute();
            $nuevo_partido_id = $conn->insert_id; // Guardar el ID del nuevo partido en una variable
            $partidos_ronda_actual[] = $nuevo_partido_id;

            // Si un partido tuvo un BYE, su ganador avanza automáticamente a la siguiente ronda
            if ($fuente2_id === null) {
                $stmt_get_ganador_bye = $conn->prepare("SELECT participante1_id FROM partidos WHERE id = ?");
                $stmt_get_ganador_bye->bind_param("i", $fuente1_id);
                $stmt_get_ganador_bye->execute();
                $ganador_bye_id = $stmt_get_ganador_bye->get_result()->fetch_assoc()['participante1_id'];

                $stmt_avanzar_ganador = $conn->prepare("UPDATE partidos SET participante1_id = ? WHERE id = ?");
                $stmt_avanzar_ganador->bind_param("ii", $ganador_bye_id, $nuevo_partido_id); // Usar la variable aquí
                $stmt_avanzar_ganador->execute();
            }
        }
        $partidos_ronda_anterior = $partidos_ronda_actual;
        $ronda_actual++;
    }
    $stmt_rondas_siguientes->close();

    $conn->commit();

    // Después de iniciar, obtener todos los datos actualizados para devolverlos
    $stmt_torneo_data = $conn->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmt_torneo_data->bind_param("i", $torneo_id);
    $stmt_torneo_data->execute();
    $torneo_data = $stmt_torneo_data->get_result()->fetch_assoc();

    $stmt_inscritos_data = $conn->prepare("SELECT u.id, u.nombre_usuario, u.foto_perfil FROM usuarios u JOIN inscripciones i ON u.id = i.usuario_id WHERE i.torneo_id = ?");
    $stmt_inscritos_data->bind_param("i", $torneo_id);
    $stmt_inscritos_data->execute();
    $inscritos_data = $stmt_inscritos_data->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt_partidos_data = $conn->prepare("SELECT * FROM partidos WHERE torneo_id = ? ORDER BY ronda, numero_partido_ronda");
    $stmt_partidos_data->bind_param("i", $torneo_id);
    $stmt_partidos_data->execute();
    $partidos_data = $stmt_partidos_data->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true, 
        'message' => '¡Torneo iniciado!',
        'data' => ['torneo' => $torneo_data, 'inscritos' => $inscritos_data, 'partidos' => $partidos_data]
    ]);

} catch (Exception $e) { // Captura cualquier tipo de excepción (mysqli_sql_exception o la nuestra)
    $conn->rollback();
    // Devolvemos un mensaje de error genérico o el mensaje de la excepción
    echo json_encode(['success' => false, 'message' => 'Error al iniciar el torneo: ' . $e->getMessage()]);
}
$conn->close();
