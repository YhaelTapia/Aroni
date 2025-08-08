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
// --- MODIFICADO: Obtener también el 'cupo' del torneo ---
$stmt_check = $conn->prepare("SELECT organizador_id, estado, cupo FROM torneos WHERE id = ?");
$stmt_check->bind_param("i", $torneo_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$torneo = $result_check->fetch_assoc();
$cupo = (int)($torneo['cupo'] ?? 0);

if (!$torneo || $torneo['organizador_id'] != $usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para iniciar este torneo.']);
    exit();
}
if ($torneo['estado'] !== 'abierto') {
    echo json_encode(['success' => false, 'message' => 'Este torneo ya no está abierto.']);
    exit();
}
// --- NUEVO: Validar que el cupo sea una potencia de 2 para un bracket de eliminación simple ---
if ($cupo <= 1 || ($cupo & ($cupo - 1)) !== 0) {
    echo json_encode(['success' => false, 'message' => 'El cupo del torneo debe ser una potencia de 2 (ej. 2, 4, 8, 16...).']);
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

// --- Lógica de Patrones de Emparejamiento ---
$patrones = ['aleatorio', 'clasificado'];
$patron_elegido = $patrones[array_rand($patrones)];
$participantes_para_bracket = [];

switch ($patron_elegido) {
    case 'clasificado':
        usort($inscritos, function ($a, $b) {
            $ratio_a = ($a['victorias'] + $a['derrotas'] > 0) ? $a['victorias'] / ($a['victorias'] + $a['derrotas']) : 0;
            $ratio_b = ($b['victorias'] + $b['derrotas'] > 0) ? $b['victorias'] / ($b['victorias'] + $b['derrotas']) : 0;
            if ($ratio_a == $ratio_b) return 0;
            return ($ratio_a > $ratio_b) ? -1 : 1;
        });
        $participantes_para_bracket = $inscritos;
        break;
    
    default: // 'aleatorio'
        shuffle($inscritos);
        $participantes_para_bracket = $inscritos;
        break;
}

// --- NUEVO: Rellenar la lista de participantes hasta el tamaño del cupo con nulls ---
$full_participant_list = array_pad($participantes_para_bracket, $cupo, null);


$conn->begin_transaction();

try {
    // 4. Limpiar partidos existentes para este torneo por si acaso
    $stmt_clean = $conn->prepare("DELETE FROM partidos WHERE torneo_id = ?");
    $stmt_clean->bind_param("i", $torneo_id);
    $stmt_clean->execute();

    // 5. Actualizar estado del torneo a 'en_curso'
    $stmt_update = $conn->prepare("UPDATE torneos SET estado = 'en_curso' WHERE id = ? AND estado = 'abierto'");
    $stmt_update->bind_param("i", $torneo_id);
    $stmt_update->execute();

    if ($stmt_update->affected_rows !== 1) {
        throw new Exception("No se pudo actualizar el estado del torneo. Puede que ya estuviera iniciado.");
    }

    // 6. Generar la estructura completa del bracket
    $partidos_ronda_anterior = [];

    // --- RECONSTRUIDO: Ronda 1 basada en el CUPO ---
    $stmt_ronda1 = $conn->prepare("INSERT INTO partidos (torneo_id, ronda, numero_partido_ronda, participante1_id, participante2_id, ganador_id) VALUES (?, 1, ?, ?, ?, ?)");
    $numero_de_partidos_ronda1 = $cupo / 2;

    for ($i = 0; $i < $numero_de_partidos_ronda1; $i++) {
        // Emparejamiento estándar: 1 vs N, 2 vs N-1, etc.
        $p1_data = $full_participant_list[$i];
        $p2_data = $full_participant_list[$cupo - 1 - $i];
        
        $p1_id = $p1_data ? $p1_data['usuario_id'] : null;
        $p2_id = $p2_data ? $p2_data['usuario_id'] : null;
        
        $ganador_id = null;
        if ($p1_id && !$p2_id) {
            $ganador_id = $p1_id; // p1 gana por BYE
        }
        if (!$p1_id && $p2_id) {
            $ganador_id = $p2_id; // p2 gana por BYE (caso raro, pero posible)
        }
        
        $num_partido_ronda = $i + 1;
        $stmt_ronda1->bind_param("iiiii", $torneo_id, $num_partido_ronda, $p1_id, $p2_id, $ganador_id);
        $stmt_ronda1->execute();
        $partidos_ronda_anterior[] = $conn->insert_id;
    }
    $stmt_ronda1->close();

    // --- RECONSTRUIDO: Rondas Siguientes con manejo de ganadores por BYE ---
    $ronda_actual = 2;
    $stmt_rondas_siguientes = $conn->prepare("INSERT INTO partidos (torneo_id, ronda, numero_partido_ronda, participante1_id, participante2_id, ganador_id, fuente_partido1_id, fuente_partido2_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    while (count($partidos_ronda_anterior) > 1) {
        $partidos_ronda_actual = [];
        for ($i = 0; $i < count($partidos_ronda_anterior); $i += 2) {
            $fuente1_id = $partidos_ronda_anterior[$i];
            $fuente2_id = isset($partidos_ronda_anterior[$i + 1]) ? $partidos_ronda_anterior[$i + 1] : null;
            $num_partido_ronda = ($i / 2) + 1;

            // Obtener ganadores de partidos anteriores si ya existen (por BYE)
            $stmt_check_winner = $conn->prepare("SELECT ganador_id FROM partidos WHERE id = ?");
            
            $p1_new = null;
            $stmt_check_winner->bind_param("i", $fuente1_id);
            $stmt_check_winner->execute();
            $result1 = $stmt_check_winner->get_result()->fetch_assoc();
            if ($result1) $p1_new = $result1['ganador_id'];

            $p2_new = null;
            if ($fuente2_id) {
                $stmt_check_winner->bind_param("i", $fuente2_id);
                $stmt_check_winner->execute();
                $result2 = $stmt_check_winner->get_result()->fetch_assoc();
                if ($result2) $p2_new = $result2['ganador_id'];
            }
            
            $ganador_id_new = null;
            if ($p1_new && !$fuente2_id) { // BYE estructural en esta ronda
                $ganador_id_new = $p1_new;
            }

            // Insertar el nuevo partido placeholder
            $stmt_rondas_siguientes->bind_param("iiiiiiii", $torneo_id, $ronda_actual, $num_partido_ronda, $p1_new, $p2_new, $ganador_id_new, $fuente1_id, $fuente2_id);
            $stmt_rondas_siguientes->execute();
            $partidos_ronda_actual[] = $conn->insert_id;
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
