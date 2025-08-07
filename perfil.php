<?php
include 'includes/db.php';
include 'includes/auth.php';

$usuario_id = $_SESSION['usuario_id'];

// Actualización si se enviaron datos del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nuevo_nombre = trim($_POST['nombre_usuario']);
    $nueva_frase = trim($_POST['frase']);
    $nueva_nacionalidad = trim($_POST['nacionalidad']);
    if (!empty($_POST['dia']) && !empty($_POST['mes']) && !empty($_POST['anio'])) {
      $dia = str_pad($_POST['dia'], 2, "0", STR_PAD_LEFT);
      $mes = str_pad($_POST['mes'], 2, "0", STR_PAD_LEFT);
      $anio = $_POST['anio'];
      $nueva_fecha = "$anio-$mes-$dia";
  } else {
      $nueva_fecha = null;
  }
  $nueva_foto = $_POST['foto_perfil'] ?? null;

    // Si se seleccionó un nuevo avatar, se incluye en la consulta
    if ($nueva_foto) {
        $sql = "UPDATE usuarios SET nombre_usuario=?, frase=?, nacionalidad=?, fecha_nacimiento=?, foto_perfil=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nuevo_nombre, $nueva_frase, $nueva_nacionalidad, $nueva_fecha, $nueva_foto, $usuario_id);
    } else {
        // Si no se seleccionó avatar, se actualiza el resto de la información
        $sql = "UPDATE usuarios SET nombre_usuario=?, frase=?, nacionalidad=?, fecha_nacimiento=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nuevo_nombre, $nueva_frase, $nueva_nacionalidad, $nueva_fecha, $usuario_id);
    }

    $stmt->execute();
    header("Location: perfil.php"); // Redirige para ver cambios
    exit;
}

// Obtener datos actuales
$sql = "SELECT nombre_usuario, meentcoins, conducta, frase, nacionalidad, fecha_nacimiento, victorias, derrotas, monedas_ganadas, foto_perfil 
        FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
// Calcular edad
$edad = null;
if (!empty($usuario['fecha_nacimiento']) && $usuario['fecha_nacimiento'] !== '0000-00-00') {
    $nacimiento = new DateTime($usuario['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}


// ¿Modo edición?
$modo_editar = isset($_GET['editar']);

$paises_latam = [
    'Argentina', 'Bolivia', 'Brasil', 'Chile', 'Colombia', 'Costa Rica',
    'Cuba', 'Ecuador', 'El Salvador', 'Guatemala', 'Honduras', 'México',
    'Nicaragua', 'Panamá', 'Paraguay', 'Perú', 'Puerto Rico', 'República Dominicana',
    'Uruguay', 'Venezuela'
];

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil - MEENTNOVA</title>
    <link rel="stylesheet" href="css/index.css?v=1.1"> <!-- Unificado con el estilo principal -->
</head>
<body>

    <nav class="top-nav">
        <a href="#" class="nav-logo">MEENTNOVA</a>
        <ul class="nav-links">
            <li><a href="index.php">INICIO</a></li>
            <li><a href="perfil.php" class="active btn-primary" >PERFIL</a></li>
            <li><a href="tienda.php">TIENDA</a></li>
            <li><a href="#">AMIGOS</a></li>
        </ul>
        <button class="btn-primary" onclick="location.href='logout.php'">CERRAR SESIÓN</button>
    </nav>

    <div class="main-container">
        <div class="center-column" style="flex-grow: 1;"> <!-- Reutilizando estilos de index.css -->
            <main class="profile-container">
                <header class="profile-header">
                    <div class="profile-avatar">
                        <img src="img/<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de perfil">
                    </div>
                    <div class="profile-info">
                        <h1><?= htmlspecialchars($usuario['nombre_usuario']) ?></h1>
                        <p class="user-phrase">( <?= htmlspecialchars($usuario['frase']) ?: 'Añade una frase...' ?> )</p>
                        <div class="user-details">
                            <span><strong>País:</strong> <?= htmlspecialchars($usuario['nacionalidad']) ?: 'No especificado' ?></span>
                            <span><strong>Edad:</strong> <?= $edad ?: 'N/A' ?> años</span>
                        </div>
                    </div>
                    <div class="profile-social">
                        <!-- Placeholder for social media links -->
                        <a href="#">Twitch</a>
                        <a href="#">Twitter</a>
                        <a href="#">YouTube</a>
                    </div>
                    <div class="profile-edit-btn-container">
                        <button id="edit-profile-btn" class="btn-primary">Editar Perfil</button>
                    </div>
                </header>

                <section class="profile-body">
                    <div class="stats-column">
                        <div class="panel stats-panel">
                            <h3>Estadísticas de Jugador</h3>
                            <div class="stat-item">
                                <span>Nivel</span>
                                <span>80</span>
                            </div>
                            <div class="stat-item">
                                <span>Rango</span>
                                <span>Diamante III</span>
                            </div>
                            <div class="stat-item">
                                <span>Victorias</span>
                                <span><?= $usuario['victorias'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span>Derrotas</span>
                                <span><?= $usuario['derrotas'] ?></span>
                            </div>
                            <div class="conducta-section">
                                <p><strong>Conducta:</strong> <?= $usuario['conducta'] ?>%</p>
                                <div class="barra-conducta">
                                    <div class="barra-interna" style="width: <?= $usuario['conducta'] ?>%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="panel achievements">
                            <h3>Logros Destacados</h3>
                            <div class="achievements-grid">
                                <div class="achievement-item"></div>
                                <div class="achievement-item"></div>
                            </div>
                        </div>
                    </div>

                    <div class="content-column">
                        <div class="panel match-history">
                            <h3>Historial de Partidas</h3>
                            <ul class="history-list">
                                <li>
                                    <img src="img/slide1.jpg" alt="Game image">
                                    <span>01/08/2025 - 17:00</span>
                                </li>
                                <li>
                                    <img src="img/slide2.jpg" alt="Game image">
                                    <span>01/08/2025 - 16:30</span>
                                </li>
                                <li>
                                    <img src="img/slide3.jpg" alt="Game image">
                                    <span>01/08/2025 - 16:00</span>
                                </li>
                                <li>
                                    <img src="img/slide4.jpg" alt="Game image">
                                    <span>01/08/2025 - 15:30</span>
                                </li>
                                <li>
                                    <img src="img/slide5.jpg" alt="Game image">
                                    <span>01/08/2025 - 15:00</span>
                                </li>
                                <li>
                                    <img src="img/slide6.jpg" alt="Game image">
                                    <span>01/08/2025 - 14:30</span>
                                </li>
                            </ul>
                        </div>
                        <div class="panel tournament-stats">
                            <h3>Estadísticas de Torneos</h3>
                            <div class="stat-item">
                                <span>Torneos Jugados</span>
                                <span>15</span>
                            </div>
                            <div class="stat-item">
                                <span>Torneos Ganados</span>
                                <span>3</span>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="edit-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <form action="perfil.php" method="post" enctype="multipart/form-data">
                <h2>Editar Perfil</h2>
                <label for="nombre_usuario">Nombre de usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>">

                <label for="frase">Frase:</label>
                <input type="text" id="frase" name="frase" value="<?= htmlspecialchars($usuario['frase']) ?>">

                <label for="nacionalidad">Nacionalidad:</label>
                <select id="nacionalidad" name="nacionalidad">
                    <option value="">Selecciona un país</option>
                    <?php foreach ($paises_latam as $pais): ?>
                        <option value="<?= $pais ?>" <?= ($usuario['nacionalidad'] == $pais) ? 'selected' : '' ?>>
                            <?= $pais ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Fecha de Nacimiento:</label>
                <div class="date-picker">
                    <select name="dia">
                        <option value="">Día</option>
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?= $i ?>" <?= (!empty($usuario['fecha_nacimiento']) && date('d', strtotime($usuario['fecha_nacimiento'])) == $i) ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="mes">
                        <option value="">Mes</option>
                        <?php foreach ($meses as $num => $nombre): ?>
                            <option value="<?= $num ?>" <?= (!empty($usuario['fecha_nacimiento']) && date('m', strtotime($usuario['fecha_nacimiento'])) == $num) ? 'selected' : '' ?>>
                                <?= $nombre ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="anio">
                        <option value="">Año</option>
                        <?php for ($i = date('Y'); $i >= 1900; $i--): ?>
                            <option value="<?= $i ?>" <?= (!empty($usuario['fecha_nacimiento']) && date('Y', strtotime($usuario['fecha_nacimiento'])) == $i) ? 'selected' : '' ?>>
                                <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <label>Avatar de perfil:</label>
                <div class="avatar-selector">
                    <label>
                        <input type="radio" name="foto_perfil" value="avatar1.png" <?= ($usuario['foto_perfil'] == 'avatar1.png') ? 'checked' : '' ?>>
                        <img src="img/avatar1.png" alt="Avatar 1">
                    </label>
                    <label>
                        <input type="radio" name="foto_perfil" value="avatar2.png" <?= ($usuario['foto_perfil'] == 'avatar2.png') ? 'checked' : '' ?>>
                        <img src="img/avatar2.png" alt="Avatar 2">
                    </label>
                    <label>
                        <input type="radio" name="foto_perfil" value="avatar3.png" <?= ($usuario['foto_perfil'] == 'avatar3.png') ? 'checked' : '' ?>>
                        <img src="img/avatar3.png" alt="Avatar 3">
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" name="guardar" class="btn-primary">Guardar Cambios</button>
                    <button type="button" id="cancel-edit-btn" class="btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function setEqualPanelHeights() {
            const panels = document.querySelectorAll('.panel');
            let maxHeight = 0;

            // Reset heights to auto to get the natural height
            panels.forEach(panel => {
                panel.style.height = 'auto';
            });

            // Find the max height
            panels.forEach(panel => {
                if (panel.offsetHeight > maxHeight) {
                    maxHeight = panel.offsetHeight;
                }
            });

            // Set all panels to the max height
            panels.forEach(panel => {
                panel.style.height = `${maxHeight}px`;
            });
        }

        window.addEventListener('load', setEqualPanelHeights);
        window.addEventListener('resize', setEqualPanelHeights);

        const editProfileBtn = document.getElementById('edit-profile-btn');
        const modal = document.getElementById('edit-modal');
        const closeModalBtn = document.querySelector('.close-modal');
        const cancelEditBtn = document.getElementById('cancel-edit-btn');

        editProfileBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        cancelEditBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>

</body>
</html>
