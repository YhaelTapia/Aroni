<?php
session_start();
include 'db.php';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Gather data from form ---
    
    // --- Gather data from form ---
    $game_slug = $_POST['game_slug'];
    $organizador_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1; // Placeholder
    $modo_juego = $_POST['game-mode'];
    $modalidad = $_POST['event-type'];
    $rules_type = $_POST['rules_type'] ?? 'recomendado'; // Default to 'recomendado' if not set

    $reglas = null;
    $reglas_personalizadas = null;

    if ($rules_type === 'personalizado') {
        // This part now expects the frontend to send the custom rule in 'rule_set'
        if (isset($_POST['rule_set']) && strpos($_POST['rule_set'], 'custom:') === 0) {
            list($title, $desc) = explode('|', str_replace('custom:', '', $_POST['rule_set']));
            $reglas_personalizadas = "Título: " . $title . "\nDescripción: " . $desc;
        }
    } else {
        // It's a recommended rule
        $reglas = $_POST['rule_set'] ?? null;
    }

    // Determine cupo
    $cupo = 0;
    if ($modalidad === 'liga') {
        $cupo = $_POST['liga-size'];
    } else { // Es un torneo
        $torneo_size = $_POST['torneo-size'];
        if ($torneo_size === '1v1') {
            $cupo = 2; // Un partido 1 vs 1 tiene 2 participantes.
        } else {
            // Para Semifinal (2), Cuartos (4), etc., el valor es el número de partidos.
            // El número de participantes (cupo) es el doble.
            $cupo = intval($torneo_size) * 2;
        }
    }

    // Create title
    $titulo = "Torneo de " . htmlspecialchars($modo_juego);
    
    // Set defaults
    $fecha = date('Y-m-d H:i:s');
    $estado = 'abierto';

    // --- Get juego_id from slug ---
    $juego_id = null;
    $stmt_game = $conn->prepare("SELECT id FROM juegos WHERE data_game = ?");
    $stmt_game->bind_param("s", $game_slug);
    $stmt_game->execute();
    $result_game = $stmt_game->get_result();
    if ($result_game->num_rows > 0) {
        $game_row = $result_game->fetch_assoc();
        $juego_id = $game_row['id'];
    }
    $stmt_game->close();

    if ($juego_id === null) {
        die("Error: Juego no válido.");
    }

    // --- Prepare and execute SQL INSERT statement ---
    $sql = "INSERT INTO torneos (juego_id, organizador_id, titulo, reglas, reglas_personalizadas, fecha, modalidad, cupo, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind variables
        $stmt->bind_param("iisssssis", $juego_id, $organizador_id, $titulo, $reglas, $reglas_personalizadas, $fecha, $modalidad, $cupo, $estado);
        
        // Execute statement
        if ($stmt->execute()) {
            header("Location: ../index.php?game=" . urlencode($game_slug) . "&creation=success");
            exit();
        } else {
            echo "Error: No se pudo ejecutar la consulta: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: No se pudo preparar la consulta: " . $conn->error;
    }

    // Close connection
    $conn->close();
} else {
    // If the form was not submitted, redirect to the index page
    header("Location: index.php");
    exit();
}
?>
