<?php
session_start();
require_once 'includes/db.php';

// --- Game Selection & View Logic ---
$is_all_tournaments_view = !isset($_GET['game']) || $_GET['game'] == '';
$selected_game_slug = !$is_all_tournaments_view ? $_GET['game'] : '';
$game_id = null;
$game_name = "Todos los Juegos";

if (!$is_all_tournaments_view) {
    // Fetch the game details from the database based on the slug
    $stmt_game = $conn->prepare("SELECT id, nombre FROM juegos WHERE data_game = ?");
    $stmt_game->bind_param("s", $selected_game_slug);
    $stmt_game->execute();
    $result_game = $stmt_game->get_result();
    if ($result_game->num_rows > 0) {
        $game_row = $result_game->fetch_assoc();
        $game_id = $game_row['id'];
        $game_name = $game_row['nombre'];
    }
    $stmt_game->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - MEENTNOVA</title>
    <link rel="stylesheet" href="css/index.css?v=1.3">
    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        window.pageData = {
            gameSlug: '<?php echo $selected_game_slug; ?>',
            gameName: <?php echo json_encode($game_name); ?>,
            loggedInUserId: <?php echo json_encode(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null); ?>
        };
    </script>
</head>
<body>

    <div class="main-container">
        <div class="top-nav-container">
            <nav class="top-nav">
                <a href="index.php" class="nav-logo">MEENTNOVA</a>
                <ul class="nav-links">
            <li><a href="index.php" class="active btn-primary">INICIO</a></li>
                    <li><a href="perfil.php">PERFIL</a></li>
                    <li><a href="tienda.php">TIENDA</a></li>
                    <li><a href="#">AMIGOS</a></li>
                </ul>
                <button class="btn-primary" onclick="location.href='logout.php'">CERRAR SESIÓN</button>
            </nav>
        </div>

        <div class="page-container">
            <!-- Columna Izquierda para Juegos -->
            <aside class="left-column">
            <h3>PRINCIPAL</h3>
            <div class="game-list">
                <?php
                    $all_tournaments_selected = $is_all_tournaments_view ? 'selected' : '';
                    echo '<div class="game-item ' . $all_tournaments_selected . '" data-game="">'; // Empty data-game for "all"
                    echo '    <img src="img/torneos/principal.png" alt="Todos los Torneos">';
                    echo '</div>';
                ?>
            </div>

            <h3 style="margin-top: 20px;">Juegos</h3>
            <div class="game-list">
                <?php
                    $games = [
                        ['data_game' => 'freefire', 'alt' => 'Free Fire', 'src' => 'img/torneos/freefire.png'],
                        ['data_game' => 'fortnite', 'alt' => 'Fortnite', 'src' => 'img/torneos/fornite.png'],
                        ['data_game' => 'roblox', 'alt' => 'Roblox', 'src' => 'img/torneos/roblox.png'],
                        ['data_game' => 'dota2', 'alt' => 'Dota 2', 'src' => 'img/torneos/dota2.png'],
                        ['data_game' => 'mobilelegends', 'alt' => 'Mobile Legends', 'src' => 'img/torneos/mobillegends.png'],
                        ['data_game' => 'wildrift', 'alt' => 'Wild Rift', 'src' => 'img/torneos/wildrift.png'],
                        ['data_game' => '8ballpool', 'alt' => '8 Ball Pool', 'src' => 'img/torneos/8ball.png'],
                        ['data_game' => 'lol', 'alt' => 'LOL', 'src' => 'img/torneos/lol.png'],
                        ['data_game' => 'fc25', 'alt' => 'FC 25', 'src' => 'img/torneos/fc25.png'],
                        ['data_game' => 'efootball25', 'alt' => 'eFootball 25', 'src' => 'img/torneos/efooball.png'],
                    ];

                    foreach ($games as $game) {
                        $selected_class = ($selected_game_slug === $game['data_game']) ? 'selected' : '';
                        echo '<div class="game-item ' . $selected_class . '" data-game="' . $game['data_game'] . '">';
                        echo '    <img src="' . $game['src'] . '" alt="' . $game['alt'] . '">';
                        echo '</div>';
                    }
                ?>
            </div>
            <button id="toggle-games-btn" class="btn-primary">Ver Más</button>
        </aside>

        <!-- Columna Central para Contenido del Torneo -->
        <main class="center-column">
            <div id="tournament-view-container">
                <h3>Torneos de <?php echo htmlspecialchars($game_name); ?></h3>
                <div class="tabs">
                    <button class="tab-link active" data-tab="join-tournament">Unirte</button>
                    <button class="tab-link" data-tab="created-tournaments">Creados</button>
                    <?php if (!$is_all_tournaments_view): ?>
                        <button class="tab-link" data-tab="create-tournament">Crear Torneo</button>
                    <?php endif; ?>
                    <button class="tab-link" data-tab="pending-tournaments">Pendientes</button>
                </div>

                <div id="join-tournament" class="tab-content active">
                    <div class="tournament-list">
                        <?php
                        $sql = "SELECT id, titulo, modalidad, cupo, reglas, reglas_personalizadas FROM torneos WHERE estado = 'abierto'";
                        if (!$is_all_tournaments_view) {
                            $sql .= " AND juego_id = ?";
                        }
                        $sql .= " ORDER BY fecha DESC";

                        if($stmt_join = $conn->prepare($sql)) {
                            if (!$is_all_tournaments_view) {
                                $stmt_join->bind_param("i", $game_id);
                            }
                            $stmt_join->execute();
                            $result = $stmt_join->get_result();

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $rule_title = '';
                                    if (!empty($row['reglas_personalizadas'])) {
                                        $rule_title = 'Reglas Personalizadas';
                                    } elseif (!empty($row['reglas'])) {
                                        $rule_title = 'Regla: ' . htmlspecialchars($row['reglas']);
                                    }

                                    echo '<div class="tournament-item" data-id="' . $row['id'] . '" data-details=\'' . json_encode($row) . '\'>';
                                    echo '    <div class="tournament-details">';
                                    echo '        <h4>' . htmlspecialchars($row["titulo"]) . '</h4>';
                                    echo '        <p>Modalidad: ' . htmlspecialchars($row["modalidad"]) . ' | Cupo: ' . htmlspecialchars($row["cupo"]) . '</p>';
                                    echo '        <p class="rule-title">' . $rule_title . '</p>';
                                    echo '    </div>';
                                    echo '    <button class="btn-secondary view-details-btn">Ver Detalles</button>';
                                    echo '</div>';
                                }
                            } else {
                                $message = $is_all_tournaments_view ? "No hay torneos abiertos en este momento." : "No hay torneos abiertos para " . htmlspecialchars($game_name) . " en este momento.";
                                echo "<p>$message</p>";
                            }
                            $stmt_join->close();
                        }
                        ?>
                    </div>
                </div>

                <div id="created-tournaments" class="tab-content">
                    <div class="tournament-list">
                        <?php
                        $organizador_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
                        $sql_created = "SELECT titulo, modalidad, cupo FROM torneos WHERE organizador_id = ?";
                        if (!$is_all_tournaments_view) {
                            $sql_created .= " AND juego_id = ?";
                        }
                        $sql_created .= " ORDER BY fecha DESC";

                        if ($stmt_created = $conn->prepare($sql_created)) {
                            if (!$is_all_tournaments_view) {
                                $stmt_created->bind_param("ii", $organizador_id, $game_id);
                            } else {
                                $stmt_created->bind_param("i", $organizador_id);
                            }
                            $stmt_created->execute();
                            $result_created = $stmt_created->get_result();

                            if ($result_created->num_rows > 0) {
                                while($row = $result_created->fetch_assoc()) {
                                    echo '<div class="tournament-item">';
                                    echo '    <div class="tournament-details">';
                                    echo '        <h4>' . htmlspecialchars($row["titulo"]) . '</h4>';
                                    echo '        <p>Modalidad: ' . htmlspecialchars($row["modalidad"]) . ' | Cupo: ' . htmlspecialchars($row["cupo"]) . '</p>';
                                    echo '    </div>';
                                    echo '    <button class="btn-secondary">Gestionar</button>';
                                    echo '</div>';
                                }
                            } else {
                                $message = $is_all_tournaments_view ? "No has creado ningún torneo todavía." : "No has creado ningún torneo para " . htmlspecialchars($game_name) . ".";
                                echo "<p>$message</p>";
                            }
                            $stmt_created->close();
                        }
                        ?>
                    </div>
                </div>

                <div id="pending-tournaments" class="tab-content">
                    <div class="tournament-list">
                        <?php
                        $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0;
                        $sql_pending = "SELECT t.id, t.titulo, t.modalidad, t.cupo, t.reglas, t.reglas_personalizadas
                                        FROM torneos t
                                        JOIN inscripciones i ON t.id = i.torneo_id
                                        WHERE i.usuario_id = ?";
                        if (!$is_all_tournaments_view) {
                            $sql_pending .= " AND t.juego_id = ?";
                        }
                        $sql_pending .= " ORDER BY t.fecha DESC";

                        if ($stmt_pending = $conn->prepare($sql_pending)) {
                            if (!$is_all_tournaments_view) {
                                $stmt_pending->bind_param("ii", $usuario_id, $game_id);
                            } else {
                                $stmt_pending->bind_param("i", $usuario_id);
                            }
                            $stmt_pending->execute();
                            $result_pending = $stmt_pending->get_result();

                            if ($result_pending->num_rows > 0) {
                                while($row = $result_pending->fetch_assoc()) {
                                    echo '<div class="tournament-item pending-item" data-id="' . $row['id'] . '">';
                                    echo '    <div class="tournament-details">';
                                    echo '        <h4>' . htmlspecialchars($row["titulo"]) . '</h4>';
                                    echo '        <p>Modalidad: ' . htmlspecialchars($row["modalidad"]) . ' | Cupo: ' . htmlspecialchars($row["cupo"]) . '</p>';
                                    echo '    </div>';
                                    echo '    <button class="btn-secondary view-bracket-btn">Ver Bracket</button>';
                                    echo '</div>';
                                }
                            } else {
                                echo "<p>No te has unido a ningún torneo todavía.</p>";
                            }
                            $stmt_pending->close();
                        }
                        ?>
                    </div>
                    <div id="bracket-view" class="hidden">
                        <div class="bracket-header">
                            <button id="bracket-back-btn" class="btn-primary">&larr; Regresar</button>
                            <h2 id="bracket-title" style="text-align: center; flex-grow: 1;"></h2>
                            <div class="bracket-view-controls">
                                <button id="view-normal-btn" class="btn-secondary active" title="Vista Normal"><i data-feather="zoom-in"></i></button>
                                <button id="view-full-btn" class="btn-secondary" title="Vista Completa"><i data-feather="zoom-out"></i></button>
                                <button id="view-wide-btn" class="btn-secondary" title="Vista Ancha"><i data-feather="maximize-2"></i></button>
                            </div>
                            <button id="delete-tournament-btn" class="btn-primary" data-danger="true" style="display: none;">Eliminar Torneo</button>
                        </div>
                        <div id="bracket-rules"></div>
                        <div class="participant-header">
                            <h3>PARTICIPANTES</h3>
                            <button id="start-tournament-btn" class="btn-primary" style="display: none;">Iniciar Torneo</button>
                        </div>

                        <div id="bracket-container">
                            <!-- El bracket inicial de participantes (grid) se renderiza aquí -->
                        </div>
                        <div id="full-bracket-container" class="hidden">
                            <!-- El bracket completo con rondas se renderizará aquí -->
                        </div>
                    </div>
                </div>

                <?php if (!$is_all_tournaments_view): ?>
                <div id="create-tournament" class="tab-content">
                    <form class="create-form" action="includes/funciones_torneo.php" method="POST">
                        <div class="form-stage">
                            <h4>Etapa 1: Modo de Juego</h4>
                            <label for="game-mode">Selecciona el modo de juego:</label>
                            <select id="game-mode" name="game-mode">
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>

                        <div class="form-stage">
                            <h4>Etapa 2: Reglas</h4>
                             <div class="form-group">
                                <div id="reglas-recomendadas-wrapper">
                                    <p>Recomendadas para este modo de juego:</p>
                                    <div id="reglas-recomendadas-content" class="radio-group">
                                        <!-- Las reglas recomendadas se cargarán aquí -->
                                    </div>
                                    <div id="reglas-descripcion-content" style="display: none; margin-top: 10px;">
                                        <!-- La descripción de la regla seleccionada se mostrará aquí -->
                                    </div>
                                </div>

                                <div id="reglas-personalizadas-wrapper" style="margin-top: 15px;">
                                    <div class="radio-group">
                                        <label id="recomendado-label" style="display: none;">
                                            <input type="radio" id="recomendado-radio" name="rules_type" value="recomendado"> Recomendado
                                            <span class="checkmark"></span>
                                        </label>
                                        <label>
                                            <input type="radio" id="personalizado-radio" name="rules_type" value="personalizado"> Personalizado
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>

                                    <div id="reglas-personalizadas-form" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #444; border-radius: 5px;">
                                        <input type="text" id="custom-rule-title" placeholder="Título de la regla" class="form-control">
                                        <textarea id="custom-rule-desc" rows="2" placeholder="Descripción de la regla" class="form-control" style="margin-top: 5px;"></textarea>
                                        <button type="button" id="save-custom-rule-btn" class="btn-primary" style="margin-top: 10px;">Guardar Regla</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-stage">
                            <h4>Etapa 3: Formato del Evento</h4>
                            <label for="event-type">Selecciona el tipo de evento:</label>
                            <select id="event-type" name="event-type">
                                <option value="torneo">Torneo</option>
                                <option value="liga">Liga</option>
                            </select>
                        </div>

                        <div class="form-stage" id="torneo-options">
                            <label for="torneo-size">Selecciona el tamaño del torneo:</label>
                            <select id="torneo-size" name="torneo-size">
                                <option value="1v1">1 vs 1</option>
                                <option value="2">Semifinal</option>
                                <option value="4">Cuartos de Final</option>
                                <option value="8">Octavos de Final</option>
                                <option value="16">16avos de Final</option>
                                <option value="32">32avos de Final</option>
                            </select>
                        </div>

                        <div class="form-stage hidden" id="liga-options">
                            <label>Número de participantes en la Liga:</label>
                            <div class="liga-size-grid">
                                <?php for ($i = 2; $i <= 20; $i++): ?>
                                    <div class="liga-size-item" data-value="<?= $i ?>"><?= $i ?></div>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" id="liga-size" name="liga-size" value="">
                        </div>

                        <input type="hidden" name="game_slug" value="<?php echo htmlspecialchars($selected_game_slug); ?>">
                        <button type="submit" class="btn-primary">Crear Partida</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Columna Derecha para Rankings -->
        <aside class="right-column">
            <div class="ranking-container">
                <h3>🏆 Torneos Ganados</h3>
                <ol class="ranking-list">
                    <li><span>1.</span><span>PlayerOne</span><span>15 wins</span></li>
                    <li><span>2.</span><span>PlayerTwo</span><span>12 wins</span></li>
                    <li><span>3.</span><span>PlayerThree</span><span>10 wins</span></li>
                    <li><span>4.</span><span>PlayerFour</span><span>9 wins</span></li>
                    <li><span>5.</span><span>PlayerFive</span><span>8 wins</span></li>
                </ol>
            </div>
            <div class="ranking-container">
                <h3>💰 Monedas Ganadas</h3>
                <ol class="ranking-list">
                    <li><span>1.</span><span>RichPlayer</span><span>1,250,000</span></li>
                    <li><span>2.</span><span>MoneyMaker</span><span>980,000</span></li>
                    <li><span>3.</span><span>CoinKing</span><span>760,000</span></li>
                    <li><span>4.</span><span>LuckyUser</span><span>650,000</span></li>
                    <li><span>5.</span><span>HighRoller</span><span>500,000</span></li>
                </ol>
            </div>
        </aside>
    </div>

    <script src="js/ui.js" defer></script>
    <script src="js/tournament-form.js" defer></script>
    <script src="js/tournament-modal.js" defer></script>
    <script src="js/bracket.js" defer></script>
    <script src="js/index.js" defer></script>

    <!-- Tournament Details Modal -->
    <div id="tournament-details-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2 id="modal-title">Detalles del Torneo</h2>
            <div id="modal-body">
                <p><strong>Juego:</strong> <span id="modal-game"></span></p>
                <p><strong>Modalidad:</strong> <span id="modal-mode"></span></p>
                <p><strong>Cupo:</strong> <span id="modal-cupo"></span></p>
                <hr>
                <h4>Reglas del Torneo</h4>
                <div id="modal-rules"></div>
            </div>
            <div class="modal-actions">
                <button id="modal-join-btn" class="btn-primary">Unirse al Torneo</button>
                <button id="modal-close-btn" class="btn-secondary">Cerrar</button>
            </div>
        </div>
    </div>
</body>
</html>
