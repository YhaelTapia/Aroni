<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// 1. Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Debes iniciar sesión.']);
    exit();
}

// 2. Verificar que se envió el ID del torneo
if (!isset($_POST['torneo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de torneo no proporcionado.']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$torneo_id = (int)$_POST['torneo_id'];

// 3. Verificar que el usuario es el organizador del torneo
$stmt_check = $conn->prepare("SELECT organizador_id FROM torneos WHERE id = ?");
$stmt_check->bind_param("i", $torneo_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$torneo = $result_check->fetch_assoc();
$stmt_check->close();

if (!$torneo || $torneo['organizador_id'] != $usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para eliminar este torneo.']);
    exit();
}

// 4. Proceder con la eliminación (usando una transacción)
$conn->begin_transaction();

try {
    // 1. (NUEVO) Eliminar partidos asociados al torneo para evitar datos fantasma
    $stmt_partidos = $conn->prepare("DELETE FROM partidos WHERE torneo_id = ?");
    $stmt_partidos->bind_param("i", $torneo_id);
    $stmt_partidos->execute();
    $stmt_partidos->close();

    // 2. Eliminar inscripciones asociadas
    $stmt_insc = $conn->prepare("DELETE FROM inscripciones WHERE torneo_id = ?");
    $stmt_insc->bind_param("i", $torneo_id);
    $stmt_insc->execute();
    $stmt_insc->close();

    // 3. Eliminar el torneo principal
    $stmt_torneo = $conn->prepare("DELETE FROM torneos WHERE id = ?");
    $stmt_torneo->bind_param("i", $torneo_id);
    $stmt_torneo->execute();
    $stmt_torneo->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Torneo eliminado exitosamente.']);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el torneo: ' . $e->getMessage()]);
}
$conn->close();
