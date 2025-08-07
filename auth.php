<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT conducta FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row && $row['conducta'] <= 0) {
    // Redirigir a una página de baneo en lugar de imprimir aquí
    header("Location: prohibido.php");
    exit;
}
