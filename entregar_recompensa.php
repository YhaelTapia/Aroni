<?php
include 'includes/auth.php';
include 'includes/db.php';

$ganador_id = $_SESSION['usuario_id']; // simulamos que el usuario actual ganó
$recompensa = 50;

// Actualizar sus MEENTCOINS
$sql = "UPDATE usuarios SET meentcoins = meentcoins + ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $recompensa, $ganador_id);


if ($stmt->execute()) {
    echo "¡Ganaste $recompensa MEENTCOINS!";
} else {
    echo "Error al asignar recompensa.";
}

