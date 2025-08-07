<?php
include 'db.php';
header('Content-Type: application/json');

if (!isset($_GET['torneo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de torneo no proporcionado.']);
    exit();
}

$torneo_id = (int)$_GET['torneo_id'];

$sql_torneo = "SELECT * FROM torneos WHERE id = ?";
$stmt_torneo = $conn->prepare($sql_torneo);
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$result_torneo = $stmt_torneo->get_result();
$torneo = $result_torneo->fetch_assoc();
$stmt_torneo->close();

if (!$torneo) {
    echo json_encode(['success' => false, 'message' => 'Torneo no encontrado.']);
    exit();
}

// Si el torneo ya inició, también obtenemos los partidos
$partidos = [];
if ($torneo['estado'] === 'en_curso' || $torneo['estado'] === 'finalizado') {
    $stmt_partidos = $conn->prepare("SELECT * FROM partidos WHERE torneo_id = ? ORDER BY ronda, numero_partido_ronda");
    $stmt_partidos->bind_param("i", $torneo_id);
    $stmt_partidos->execute();
    $partidos = $stmt_partidos->get_result()->fetch_all(MYSQLI_ASSOC);
}

$sql_inscritos = "SELECT u.id, u.nombre_usuario, u.foto_perfil 
        FROM usuarios u
        JOIN inscripciones i ON u.id = i.usuario_id
        WHERE i.torneo_id = ?";

if ($stmt_inscritos = $conn->prepare($sql_inscritos)) {
    $stmt_inscritos->bind_param("i", $torneo_id);
    $stmt_inscritos->execute();
    $result_inscritos = $stmt_inscritos->get_result();
    $inscritos = $result_inscritos->fetch_all(MYSQLI_ASSOC);
    $stmt_inscritos->close();
    // Devolvemos todo: torneo, inscritos y los partidos (si existen)
    echo json_encode(['success' => true, 'torneo' => $torneo, 'inscritos' => $inscritos, 'partidos' => $partidos]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
}

$conn->close();
?>
