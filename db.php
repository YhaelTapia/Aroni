<?php
// Habilita la notificación de errores de MySQLi para que se conviertan en excepciones de PHP.
// Esto hace que el manejo de errores sea más robusto y predecible.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db = "meentnova_db";

$conn = new mysqli($host, $user, $pass, $db);

// Es una buena práctica verificar la conexión, aunque mysqli_report ya lo haría.
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Establece el conjunto de caracteres a utf8mb4.
// Esto previene problemas de codificación con caracteres especiales o emojis.
$conn->set_charset("utf8mb4");

?>
