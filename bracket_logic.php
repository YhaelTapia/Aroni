
<?php
function get_round_name($total_teams, $round_index) {
    $teams_in_round = $total_teams / pow(2, $round_index);
    if ($teams_in_round <= 1) return 'Final';
    if ($teams_in_round == 2) return 'Semifinal';
    if ($teams_in_round == 4) return 'Cuartos de Final';
    if ($teams_in_round == 8) return 'Octavos de Final';
    if ($teams_in_round == 16) return '16avos de Final';
    if ($teams_in_round == 32) return '32avos de Final';
    return 'Ronda ' . ($round_index + 1);
}

function generar_bracket($torneo_id) {
    global $conn;

    // 1. Obtener los inscritos
    $stmt = $conn->prepare("SELECT u.id, u.nombre_usuario FROM inscripciones i JOIN usuarios u ON i.usuario_id = u.id WHERE i.torneo_id = ? ORDER BY i.fecha_inscripcion");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $inscritos = [];
    while ($row = $resultado->fetch_assoc()) {
        $inscritos[] = $row;
    }
    $stmt->close();

    // 2. Generar rondas
    $num_participantes = count($inscritos);
    if ($num_participantes < 2) {
        return []; // No se puede generar un bracket con menos de 2 participantes
    }

    // Mezclar aleatoriamente a los participantes
    shuffle($inscritos);

    $rondas = [];
    $ronda_actual = $inscritos;

    // Calcular el número de rondas
    $num_rondas = ceil(log($num_participantes, 2));

    for ($i = 0; $i < $num_rondas; $i++) {
        $siguiente_ronda = [];
        $ronda = [];
        for ($j = 0; $j < count($ronda_actual); $j += 2) {
            $partido = [
                'jugador1' => $ronda_actual[$j],
                'jugador2' => isset($ronda_actual[$j + 1]) ? $ronda_actual[$j + 1] : null, // Manejar byes
                'ganador' => null
            ];
            $ronda[] = $partido;
        }
        $rondas[] = $ronda;

        // Preparar para la siguiente ronda (simulado, aquí iría la lógica de ganadores)
        foreach ($ronda as $partido) {
            // Por ahora, el ganador es aleatorio para el ejemplo
            $ganador = rand(0, 1) == 0 ? $partido['jugador1'] : $partido['jugador2'];
             if ($partido['jugador2'] === null) {
                $ganador = $partido['jugador1'];
            }
            $siguiente_ronda[] = $ganador;
        }
        $ronda_actual = $siguiente_ronda;
    }

    return $rondas;
}
?>