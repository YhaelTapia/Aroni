<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para unirte a un torneo.']);
    exit();
}

// Check if torneo_id is provided
if (!isset($_POST['torneo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de torneo no proporcionado.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$torneo_id = (int)$_POST['torneo_id'];

// --- Check if tournament is full ---
$stmt_cupo = $conn->prepare("SELECT cupo FROM torneos WHERE id = ?");
$stmt_cupo->bind_param("i", $torneo_id);
$stmt_cupo->execute();
$result_cupo = $stmt_cupo->get_result();
$torneo = $result_cupo->fetch_assoc();
$cupo_maximo = $torneo['cupo'];
$stmt_cupo->close();

$stmt_inscritos = $conn->prepare("SELECT COUNT(*) as inscritos FROM inscripciones WHERE torneo_id = ?");
$stmt_inscritos->bind_param("i", $torneo_id);
$stmt_inscritos->execute();
$result_inscritos = $stmt_inscritos->get_result();
$inscritos = $result_inscritos->fetch_assoc()['inscritos'];
$stmt_inscritos->close();

if ($inscritos >= $cupo_maximo) {
    echo json_encode(['success' => false, 'message' => 'El torneo ya está lleno.']);
    exit();
}

// --- Check if user is already registered ---
$stmt_check = $conn->prepare("SELECT id FROM inscripciones WHERE torneo_id = ? AND usuario_id = ?");
$stmt_check->bind_param("ii", $torneo_id, $usuario_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya estás inscrito en este torneo.']);
    exit();
}
$stmt_check->close();

// --- Register user for the tournament ---
$sql_insert = "INSERT INTO inscripciones (torneo_id, usuario_id) VALUES (?, ?)";
if ($stmt_insert = $conn->prepare($sql_insert)) {
    $stmt_insert->bind_param("ii", $torneo_id, $usuario_id);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'message' => '¡Inscripción exitosa!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al inscribirte en el torneo.']);
    }
    $stmt_insert->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
}

$conn->close();
?>
