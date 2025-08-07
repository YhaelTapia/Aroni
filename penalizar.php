<?php
include 'includes/auth.php';
include 'includes/db.php';

$usuario_id = $_SESSION['usuario_id']; // penalizas al actual (luego se puede aplicar a otros usuarios)

$motivo = $_GET['motivo'] ?? 'fraude';
$penalizacion = 0;

switch ($motivo) {
    case 'insulto':
        $penalizacion = 15;
        break;
    case 'mentira':
        $penalizacion = 34;
        break;
    case 'fraude':
        $penalizacion = 50;
        break;
    default:
        $penalizacion = 10;
}

$conn->query("UPDATE usuarios SET conducta = GREATEST(conducta - $penalizacion, 0) WHERE id = $usuario_id");

// Si conducta llegó a 0, deshabilitar al usuario (opcional: bloquear login, etc.)
header("Location: perfil.php");
exit;
