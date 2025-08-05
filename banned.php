<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta Baneada</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        h1 { color: #dc3545; }
    </style>
</head>
<body>
    <h1>Cuenta Baneada</h1>
    <p>Tu cuenta ha sido baneada por mala conducta.</p>
    <p>Por favor, contacta al soporte para más información.</p>
    <a href="login.php">Volver al inicio de sesión</a>
</body>
</html>
