<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Torneos</title>
    <link rel="stylesheet" href="css/torneo.css">
</head>
<body>

<?php
include 'includes/auth.php';
include 'includes/db.php';
include 'includes/bracket_logic.php'; // Incluimos la lógica del bracket

$mensaje = "";

// Si se especifica un ID de torneo, mostramos el bracket
if (isset($_GET['id'])) {
    $torneo_id = intval($_GET['id']);
    
    // Obtener información del torneo
    $stmt = $conn->prepare("SELECT t.*, j.nombre as juego_nombre FROM torneos t JOIN juegos j ON t.juego_id = j.id WHERE t.id = ?");
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $torneo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($torneo) {
        echo "<h1>Bracket del Torneo: " . htmlspecialchars($torneo['titulo']) . "</h1>";
        echo "<h2>Juego: " . htmlspecialchars($torneo['juego_nombre']) . "</h2>";

        $rondas = generar_bracket($torneo_id);

        if (empty($rondas)) {
            echo "<p>Aún no hay suficientes participantes para generar el bracket.</p>";
        } else {
            echo '<div class="bracket">';
            foreach ($rondas as $i => $ronda) {
                echo '<div class="ronda">';
                echo '<h3>Ronda ' . ($i + 1) . '</h3>';
                foreach ($ronda as $partido) {
                    echo '<div class="partido">';
                    $jugador1 = $partido['jugador1'] ? htmlspecialchars($partido['jugador1']['nombre_usuario']) : 'BYE';
                    $jugador2 = $partido['jugador2'] ? htmlspecialchars($partido['jugador2']['nombre_usuario']) : 'BYE';
                    echo '<div class="jugador">' . $jugador1 . '</div>';
                    echo '<div class="vs">vs</div>';
                    echo '<div class="jugador">' . $jugador2 . '</div>';
                    echo '</div>';
                }
                echo '</div>';
            }
            echo '</div>';
        }
    } else {
        echo "<p>Torneo no encontrado.</p>";
    }

} else {
    // --- Lógica para crear y unirse a torneos (la que ya tenías) ---

    // Unirse a torneo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unirse'])) {
        // ... (tu código para unirse sin cambios)
    }

    // Crear torneo
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear'])) {
        // ... (tu código para crear sin cambios)
    }

    // Mostrar torneos disponibles
    $torneos = $conn->query("SELECT t.*, j.nombre AS juego_nombre FROM torneos t JOIN juegos j ON t.juego_id = j.id WHERE t.estado = 'abierto'");
?>

    <h2>Crear Torneo</h2>
    <form method="POST">
        <!-- ... (tu formulario de creación sin cambios) ... -->
    </form>

    <p><?= $mensaje ?></p>
    <hr>

    <h2>Torneos Disponibles</h2>
    <ul>
        <?php while ($t = $torneos->fetch_assoc()) : ?>
            <li>
                <strong><a href="torneo.php?id=<?= $t['id'] ?>"><?= htmlspecialchars($t['titulo']) ?></a></strong> 
                (<?= htmlspecialchars($t['juego_nombre']) ?> - <?= htmlspecialchars($t['modalidad']) ?>)
                - Fecha: <?= $t['fecha'] ?> | Cupo: <?= $t['cupo'] ?>
                <?php if ($t['organizador_id'] != $_SESSION['usuario_id']) : ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="unirse_id" value="<?= $t['id'] ?>">
                        <button type="submit" name="unirse">Unirse</button>
                    </form>
                <?php else : ?>
                    <em>Tu torneo</em>
                <?php endif; ?>
            </li>
        <?php endwhile; ?>
    </ul>

<?php 
} // Fin del else para mostrar lista de torneos
?>

</body>
</html>
