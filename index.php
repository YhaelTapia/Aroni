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
    <link rel="stylesheet" href="css/index.css?v=1.1">
    <script src="https://unpkg.com/feather-icons"></script>
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
                                        // This is a placeholder. We'll get the real title with JS later.
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
                            <!-- SOLUCIÓN 1: Cambiamos la clase para que coincida con "Cerrar Sesión" -->
                            <button id="bracket-back-btn" class="btn-primary">&larr; Regresar</button>
                            <h2 id="bracket-title" style="text-align: center; flex-grow: 1;"></h2>
                        </div>
                        <div id="bracket-rules"></div>
                        <div class="participant-header">
                            <h3 style="text-align: center;">PARTICIPANTES:</h3>
                            <button id="delete-tournament-btn" class="btn-primary" style="display: none;">Eliminar Torneo</button>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // SOLUCIÓN DEFINITIVA: Detectar si la página se carga desde el Back-Forward Cache del navegador.
            // Si es así, forzar una recarga completa para obtener siempre el estado más reciente del torneo.
            window.addEventListener('pageshow', function(event) {
                // event.persisted es true si la página se restauró de la caché.
                if (event.persisted) {
                    window.location.reload();
                }
            });


            const gameModes = {
                'freefire': ['Clásico - Bermuda', 'Duelo de Escuadras', 'Lobo Solitario'],
                'fortnite': ['Battle Royale', 'Zero Build', 'Creative'],
                'roblox': ['Jailbreak', 'Adopt Me!', 'Tower of Hell'],
                'dota2': ['All Pick', 'Turbo', 'Captain\'s Mode'],
                'mobilelegends': ['Classic', 'Ranked', 'Brawl'],
                'wildrift': ['Normal (PVP)', 'Ranked', 'ARAM'],
                '8ballpool': ['1 vs 1', 'Torneos', '9 Ball'],
                'lol': ['Summoner\'s Rift', 'ARAM', 'Teamfight Tactics'],
                'fc25': ['Ultimate Team', 'Career Mode', 'Volta'],
                'efootball25': ['eFootball League', 'Friend Match', 'Co-op']
            };

            const gameRules = {
                'freefire': {
                    'clásico-bermuda': {
                        'victoria-por-puntos': 'Victoria por Puntos (Booyah)',
                        'mas-kills': 'Victoria por Mayor Cantidad de Kills'
                    },
                    'duelo-de-escuadras': {
                        'victoria-por-daño': 'Victoria por Daño Total',
                        'victoria-por-kills-de': 'Victoria por Kills (Duelo de Escuadras)'
                    },
                    'lobo-solitario': {
                        'rey-de-la-colina': 'Rey de la Colina (Control de Zona)',
                        'solo-headshots': 'Duelo a Headshots'
                    }
                },
                'fortnite': {
                     'battle-royale': {
                        'victoria-magistral': 'Victoria Magistral (Último en Pie)',
                        'mas-eliminaciones-br': 'Victoria por Eliminaciones (Battle Royale)'
                    },
                    'zero-build': {
                        'victoria-sin-construccion': 'Victoria Magistral (Cero Construcción)',
                        'dominio-de-terreno': 'Dominio de Terreno (Puntos de Interés)'
                    }
                }
            };

            const ruleDescriptions = {
                'victoria-por-puntos': "El ganador es el último jugador o equipo en pie (Booyah).<br><br><b>Desempate:</b><br>1. Mayor número de kills.<br>2. Mayor daño infligido.",
                'mas-kills': "El ganador es el jugador o equipo que consiga la mayor cantidad de kills al final de la partida.<br><br><b>Desempate:</b><br>1. Mayor daño infligido.<br>2. Menor tiempo de supervivencia (el que lo logró más rápido).",
                'victoria-por-daño': "El ganador es el equipo que inflija la mayor cantidad de daño acumulado al equipo contrario al final de todas las rondas.<br><br><b>Desempate:</b><br>1. Mayor número de kills totales.<br>2. Equipo que ganó la ronda más rápida.",
                'victoria-por-kills-de': "El ganador es el equipo que consiga más kills en total, sumando todas las rondas.<br><br><b>Desempate:</b><br>1. Mayor daño infligido.<br>2. Equipo con más headshots.",
                'rey-de-la-colina': "Se designará una zona. El ganador es quien controle la zona por más tiempo acumulado.<br><br><b>Desempate:</b><br>1. Kills realizadas dentro de la zona.<br>2. Daño infligido dentro de la zona.",
                'solo-headshots': "Solo las kills por headshot cuentan para la puntuación. Gana el que más headshots consiga.<br><br><b>Desempate:</b><br>1. Mayor daño total.<br>2. Kills totales (incluyendo no headshots).",
                'victoria-magistral': "El único objetivo es ser el último jugador o equipo en pie.<br><br><b>Desempate:</b><br>No aplica. La partida continúa hasta que solo quede un ganador.",
                'mas-eliminaciones-br': "Gana el jugador o equipo con más eliminaciones al final de la partida.<br><br><b>Desempate:</b><br>1. Posición final en la partida (el que haya quedado más alto).<br>2. Daño a jugadores.",
                'victoria-sin-construccion': "El único objetivo es ser el último jugador o equipo en pie en el modo Cero Construcción.<br><br><b>Desempate:</b><br>No aplica.",
                'dominio-de-terreno': "Gana puntos por cada punto de interés controlado al final de cada círculo de la tormenta. El que más puntos tenga al final, gana.<br><br><b>Desempate:</b><br>1. Eliminaciones totales.<br>2. Daño a jugadores."
            };

            const currentGameSlug = '<?php echo $selected_game_slug; ?>';
            const gameModeSelect = document.getElementById('game-mode');
            const reglasRecomendadasContent = document.getElementById('reglas-recomendadas-content');
            const reglasDescripcionContent = document.getElementById('reglas-descripcion-content');
            const reglasRecomendadasWrapper = document.getElementById('reglas-recomendadas-wrapper');
            const reglasPersonalizadasForm = document.getElementById('reglas-personalizadas-form');
            const personalizadoRadio = document.getElementById('personalizado-radio');
            const recomendadoRadio = document.getElementById('recomendado-radio');
            const recomendadoLabel = document.getElementById('recomendado-label');
            const saveCustomRuleBtn = document.getElementById('save-custom-rule-btn');

            // --- Modal Elements ---
            const modal = document.getElementById('tournament-details-modal');
            const closeModalBtn = modal.querySelector('.close-modal');
            const modalCloseBtn = document.getElementById('modal-close-btn');
            const modalJoinBtn = document.getElementById('modal-join-btn');

            function updateRules(selectedMode) {
                // Limpiar contenido anterior
                reglasRecomendadasContent.innerHTML = '';
                reglasDescripcionContent.innerHTML = '';
                reglasDescripcionContent.style.display = 'none';

                const rulesForMode = gameRules[currentGameSlug]?.[selectedMode];
                
                if (rulesForMode && Object.keys(rulesForMode).length > 0) {
                     reglasRecomendadasWrapper.style.display = 'block';
                    Object.entries(rulesForMode).forEach(([key, value], index) => {
                        const label = document.createElement('label');
                        const input = document.createElement('input');
                        const checkmark = document.createElement('span');
                        checkmark.className = 'checkmark';

                        input.type = 'radio';
                        input.name = 'rule_set';
                        input.value = key;
                        // No need for data-description anymore, we use the key
                        
                        if (index === 0) {
                           input.checked = true; // Seleccionar la primera por defecto
                           reglasDescripcionContent.innerHTML = ruleDescriptions[key] || 'No hay descripción disponible.';
                           reglasDescripcionContent.style.display = 'block';
                        }
                        
                        label.appendChild(input);
                        label.appendChild(checkmark);
                        label.appendChild(document.createTextNode(` ${value}`)); // Usar el título completo
                        reglasRecomendadasContent.appendChild(label);
                    });
                } else {
                     reglasRecomendadasWrapper.style.display = 'none';
                }
            }

            if (gameModeSelect) {
                // Llenar modos de juego y configurar listener
                if (gameModes[currentGameSlug]) {
                    gameModeSelect.innerHTML = ''; // Clear existing options
                    const modes = gameModes[currentGameSlug];
                    modes.forEach(mode => {
                        const option = document.createElement('option');
                        const modeSlug = mode.toLowerCase().replace(/ /g, '-');
                        option.value = modeSlug;
                        option.textContent = mode;
                        gameModeSelect.appendChild(option);
                    });
                    
                    // Listener para cambio de modo
                    gameModeSelect.addEventListener('change', (e) => {
                        updateRules(e.target.value);
                    });

                    // Carga inicial de reglas para el modo por defecto
                    if (gameModeSelect.value) {
                        updateRules(gameModeSelect.value);
                    }
                }

                // Listener para los radio buttons de reglas (delegación de eventos)
                reglasRecomendadasContent.addEventListener('change', (e) => {
                    if (e.target.name === 'rule_set' && e.target.type === 'radio') {
                        const ruleKey = e.target.value;
                        const description = ruleDescriptions[ruleKey] || 'No hay descripción disponible.';
                        reglasDescripcionContent.innerHTML = description;
                        reglasDescripcionContent.style.display = 'block';
                    }
                });

                // Listener para el radio button "Personalizado"
                if (personalizadoRadio) {
                    personalizadoRadio.addEventListener('change', (e) => {
                        if (e.target.checked) {
                            reglasRecomendadasWrapper.style.display = 'none';
                            reglasDescripcionContent.style.display = 'none';
                            reglasPersonalizadasForm.style.display = 'block';
                            recomendadoLabel.style.display = 'flex'; // Usar flex para alinear como los demás
                        }
                    });
                }

                // Listener para el radio button "Recomendado"
                if (recomendadoRadio) {
                    recomendadoRadio.addEventListener('change', (e) => {
                        if (e.target.checked) {
                            personalizadoRadio.checked = false;
                            reglasPersonalizadasForm.style.display = 'none';
                            recomendadoLabel.style.display = 'none';
                            reglasRecomendadasWrapper.style.display = 'block';

                            const checkedRecommended = reglasRecomendadasContent.querySelector('input[name="rule_set"]:checked');
                            if (checkedRecommended) {
                                const ruleKey = checkedRecommended.value;
                                reglasDescripcionContent.innerHTML = ruleDescriptions[ruleKey] || 'No hay descripción disponible.';
                                reglasDescripcionContent.style.display = 'block';
                            }
                        }
                    });
                }
                
                // Listener para guardar regla personalizada
                if (saveCustomRuleBtn) {
                    saveCustomRuleBtn.addEventListener('click', () => {
                        const title = document.getElementById('custom-rule-title').value.trim();
                        const desc = document.getElementById('custom-rule-desc').value.trim();

                        if (title && desc) {
                            // Crear el nuevo radio button para la regla
                            const label = document.createElement('label');
                            const input = document.createElement('input');
                            const checkmark = document.createElement('span');
                            checkmark.className = 'checkmark';

                            const customValue = `custom:${title}|${desc}`;
                            input.type = 'radio';
                            input.name = 'rule_set';
                            input.value = customValue;
                            input.dataset.description = desc;
                            input.checked = true;

                            label.appendChild(input);
                            label.appendChild(checkmark);
                            label.appendChild(document.createTextNode(` ${title}`));
                            
                            // Añadirlo a la lista y seleccionarlo
                            reglasRecomendadasContent.appendChild(label);
                            
                            // Mostrar su descripción
                            reglasDescripcionContent.textContent = desc;
                            reglasDescripcionContent.style.display = 'block';
                            
                            // Limpiar y ocultar el formulario
                            document.getElementById('custom-rule-title').value = '';
                            document.getElementById('custom-rule-desc').value = '';
                            reglasPersonalizadasForm.style.display = 'none';
                            recomendadoLabel.style.display = 'none';
                            personalizadoRadio.checked = false;
                            
                            // Volver a la vista de "Recomendado"
                            reglasRecomendadasWrapper.style.display = 'block';
                        } else {
                            alert('Por favor, completa el título y la descripción de la regla.');
                        }
                    });
                }
                
                // --- Tournament Details Modal Logic ---
                document.querySelectorAll('.view-details-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const tournamentItem = e.target.closest('.tournament-item');
                        document.querySelectorAll('.tournament-item').forEach(item => item.classList.remove('selected-for-modal'));
                        tournamentItem.classList.add('selected-for-modal');
                        const details = JSON.parse(tournamentItem.dataset.details);

                        // Populate modal
                        modal.querySelector('#modal-title').textContent = details.titulo;
                        modal.querySelector('#modal-game').textContent = '<?php echo htmlspecialchars($game_name); ?>';
                        modal.querySelector('#modal-mode').textContent = details.modalidad;
                        modal.querySelector('#modal-cupo').textContent = details.cupo;
                        
                        let rulesHtml = '';
                        if(details.reglas_personalizadas) {
                            rulesHtml = details.reglas_personalizadas.replace(/\n/g, '<br>');
                        } else if (details.reglas && ruleDescriptions[details.reglas]) {
                            rulesHtml = ruleDescriptions[details.reglas];
                        } else {
                            rulesHtml = 'No hay reglas específicas disponibles.';
                        }
                        modal.querySelector('#modal-rules').innerHTML = rulesHtml;

                        // Show modal
                        modal.style.display = 'flex';
                    });
                });

                // --- Listeners to close the modal ---
                function hideModal() {
                    modal.style.display = 'none';
                }
                closeModalBtn.addEventListener('click', hideModal);
                modalCloseBtn.addEventListener('click', hideModal);
                
                // Close modal if clicking outside the content
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        hideModal();
                    }
                });

            }

            // --- Game click logic to navigate ---
            const gameItems = document.querySelectorAll('.game-item');
            gameItems.forEach(item => {
                item.addEventListener('click', () => {
                    const gameSlug = item.dataset.game;
                    if (gameSlug === '') {
                        window.location.href = 'index.php';
                    } else {
                        window.location.href = `index.php?game=${gameSlug}`;
                    }
                });
            });

            // --- "Show More/Less" button logic ---
            const toggleBtn = document.getElementById('toggle-games-btn');
            const allGames = Array.from(document.querySelectorAll('.game-list .game-item'));
            const extraGames = allGames.slice(5); // Now slice after the 5th item (1 "all" + 4 games)

            if (extraGames.length > 0) {
                extraGames.forEach(game => game.classList.add('hidden'));

                toggleBtn.addEventListener('click', () => {
                    const isHidden = extraGames[0].classList.contains('hidden');
                    extraGames.forEach(game => game.classList.toggle('hidden'));
                    toggleBtn.textContent = isHidden ? 'Ver Menos' : 'Ver Más';
                });
            } else {
                toggleBtn.style.display = 'none';
            }


            // --- Tab switching logic ---
            const tabs = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });

            // --- Conditional Form Logic ---
            const eventTypeSelect = document.getElementById('event-type');
            const torneoOptions = document.getElementById('torneo-options');
            const ligaOptions = document.getElementById('liga-options');

            if(eventTypeSelect) {
                eventTypeSelect.addEventListener('change', (e) => {
                    if (e.target.value === 'liga') {
                        torneoOptions.classList.add('hidden');
                        ligaOptions.classList.remove('hidden');
                    } else {
                        torneoOptions.classList.remove('hidden');
                        ligaOptions.classList.add('hidden');
                    }
                });
            }

            // --- League Size Grid Logic ---
            const ligaSizeGrid = document.querySelector('.liga-size-grid');
            const ligaSizeInput = document.getElementById('liga-size');
            
            if(ligaSizeGrid) {
                ligaSizeGrid.addEventListener('click', (e) => {
                    if (e.target.classList.contains('liga-size-item')) {
                        ligaSizeGrid.querySelectorAll('.liga-size-item').forEach(item => item.classList.remove('selected'));
                        e.target.classList.add('selected');
                        if(ligaSizeInput) {
                            ligaSizeInput.value = e.target.dataset.value;
                        }
                    }
                });
            }

            // --- Join tournament logic ---
            if (modalJoinBtn) {
                modalJoinBtn.addEventListener('click', () => {
                    const tournamentItem = document.querySelector('.tournament-item.selected-for-modal');
                    if (tournamentItem) {
                        const tournamentId = tournamentItem.dataset.id;
                        joinTournament(tournamentId);
                    }
                });
            }

            function joinTournament(torneo_id) {
                const formData = new FormData();
                formData.append('torneo_id', torneo_id);

                fetch('includes/unirse_torneo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload(); // Reload to see the tournament in "Pendientes"
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al intentar unirse al torneo.');
                });
            }

            // --- Bracket Generation Logic ---
            const backButton = document.getElementById('bracket-back-btn');
            if (backButton) {
                // SOLUCIÓN: El botón "Regresar" ahora recargará la página para asegurar un estado limpio.
                // Esto soluciona el problema de que la vista no se actualice correctamente.
                backButton.addEventListener('click', () => window.location.reload());
            }

            const loggedInUserId = <?php echo json_encode(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null); ?>;
            const currentGameName = <?php echo json_encode($game_name); ?>;
            
            document.querySelectorAll('.view-bracket-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const tournamentItem = e.target.closest('.tournament-item');
                    const tournamentId = tournamentItem.dataset.id;
                    const tournamentList = document.querySelector('#pending-tournaments .tournament-list');
                    const bracketView = document.getElementById('bracket-view');
                    const bracketTitle = document.getElementById('bracket-title');
                    const bracketRules = document.getElementById('bracket-rules');
                    const bracketContainer = document.getElementById('bracket-container');

                    // SOLUCIÓN DEFINITIVA: Añadir un parámetro único para forzar la recarga (cache busting)
                    fetch(`includes/get_inscritos.php?torneo_id=${tournamentId}&_=${new Date().getTime()}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Ocultar la lista de torneos y mostrar la vista del bracket
                                tournamentList.style.display = 'none';
                                document.querySelector('#tournament-view-container > h3').style.display = 'none';
                                document.querySelector('#tournament-view-container > .tabs').style.display = 'none';
                                bracketView.style.display = 'block';
                                backButton.style.display = 'inline-block';

                                // Poblar datos comunes
                                bracketTitle.textContent = 'Torneo de ' + currentGameName;
                                const format = (data.torneo.modalidad || 'No especificado').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                let gameMode = data.torneo.titulo || 'No especificado';
                                if (gameMode.toLowerCase().startsWith('torneo de ')) {
                                    gameMode = gameMode.substring(10);
                                }
                                gameMode = gameMode.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                const rulesContent = data.torneo.reglas_personalizadas ? data.torneo.reglas_personalizadas.replace(/\n/g, '<br>') : (ruleDescriptions[data.torneo.reglas] || 'No hay reglas definidas.');

                                bracketRules.innerHTML = `
                                    <p style="margin: 0 0 5px 0;"><strong style="color: var(--text-secondary);">Formato:</strong> ${format}</p>
                                    <p style="margin: 0 0 5px 0;"><strong style="color: var(--text-secondary);">Modo de Juego:</strong> ${gameMode}</p>
                                    <p style="margin: 0 0 5px 0;"><strong style="color: var(--text-secondary);">Reglas:</strong></p>
                                    <div>${rulesContent}</div>
                                `;

                                // SOLUCIÓN: Decidir qué vista mostrar basado en el estado del torneo
                                if (data.torneo.estado === 'en_curso' || data.torneo.estado === 'finalizado') {
                                    // Si el torneo ya inició, mostrar el bracket completo
                                    document.getElementById('start-tournament-btn').style.display = 'none';
                                    document.getElementById('delete-tournament-btn').style.display = 'none';
                                    document.querySelector('.participant-header').style.display = 'none';
                                    bracketContainer.style.display = 'none';
                                    renderFullBracket(data);
                                } else { // El torneo está 'abierto'
                                    // Mostrar la lista de participantes y los botones de acción para el creador
                                    document.querySelector('.participant-header').style.display = 'flex';
                                    bracketContainer.style.display = 'grid';
                                    document.getElementById('full-bracket-container').classList.add('hidden');
                                    
                                    const startButton = document.getElementById('start-tournament-btn');
                                    const deleteButton = document.getElementById('delete-tournament-btn');
                                    if (loggedInUserId && data.torneo.organizador_id == loggedInUserId) {
                                        startButton.style.display = 'inline-block';
                                        deleteButton.style.display = 'inline-block';
                                    } else {
                                        startButton.style.display = 'none';
                                        deleteButton.style.display = 'none';
                                    }
                                    renderBracket(data.inscritos, data.torneo.cupo, bracketContainer);
                                    deleteButton.onclick = () => deleteTournament(tournamentId);
                                    startButton.onclick = () => startTournament(tournamentId);
                                }

                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => console.error('Error fetching bracket data:', error));
                });
            });

            function startTournament(torneo_id) {
                if (!confirm('¿Estás seguro de que quieres iniciar el torneo? Una vez iniciado, no se podrán unir más participantes.')) {
                    return;
                }

                const formData = new FormData();
                formData.append('torneo_id', torneo_id);

                fetch('includes/iniciar_torneo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Ocultar botones y la vista de participantes
                        document.getElementById('start-tournament-btn').style.display = 'none';
                        document.getElementById('delete-tournament-btn').style.display = 'none';
                        document.querySelector('.participant-header').style.display = 'none';
                        document.getElementById('bracket-container').style.display = 'none';
                        // Ya no necesitamos un segundo fetch. Usamos los datos que nos devolvió iniciar_torneo.php
                        renderFullBracket(data.data);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al iniciar el torneo.');
                });
            }

            function getRoundName(totalTeams, roundIndex) {
                const teamsInRound = totalTeams / Math.pow(2, roundIndex);
                if (teamsInRound <= 1) return 'Final';
                if (teamsInRound === 2) return 'Semifinal';
                if (teamsInRound === 4) return 'Cuartos de Final';
                if (teamsInRound === 8) return 'Octavos de Final';
                return `${teamsInRound / 2}avos de Final`;
            }

            function renderFullBracket(data) {
                const container = document.getElementById('full-bracket-container');
                container.innerHTML = ''; // Limpiar
                container.classList.remove('hidden');

                const scrollWrapper = document.createElement('div');
                scrollWrapper.className = 'bracket-scroll-wrapper';

                const renderArea = document.createElement('div');
                renderArea.className = 'bracket-render-area';

                // Mapear participantes y partidos por ID para un acceso rápido y eficiente.
                const participantsMap = new Map((data.inscritos || []).map(p => [p.id, p]));
                
                // Pre-procesar partidos para añadirles la etiqueta de Grupo (A, B, C...)
                const matchesMap = new Map();
                let labelCounter = 0;
                (data.partidos || []).forEach(p => {
                    p.label = `Grupo ${String.fromCharCode(65 + labelCounter)}`;
                    matchesMap.set(p.id, p);
                    labelCounter++;
                });

                // Agrupar partidos por ronda
                const rounds = (data.partidos || []).reduce((acc, match) => {
                    acc[match.ronda] = acc[match.ronda] || [];
                    acc[match.ronda].push(match);
                    return acc;
                }, {});

                const totalRounds = Object.keys(rounds).length;
                const totalParticipants = parseInt(data.torneo.cupo, 10) || data.inscritos.length;

                for (let i = 1; i <= totalRounds; i++) {
                    const roundColumn = document.createElement('div');
                    roundColumn.className = 'round-column';

                    const roundTitle = document.createElement('div');
                    roundTitle.className = 'round-title';
                    roundTitle.textContent = getRoundName(totalParticipants, i - 1);
                    roundColumn.appendChild(roundTitle);

                    const matchesInThisRound = rounds[i] || [];

                    matchesInThisRound.forEach(matchData => {
                        const match = document.createElement('div');
                        match.className = 'match';
                        match.dataset.matchId = matchData.id;

                        // Añadir la etiqueta del Grupo (A, B, C...)
                        const matchLabel = document.createElement('div');
                        matchLabel.className = 'match-label';
                        matchLabel.textContent = matchData.label;
                        match.appendChild(matchLabel);

                                                // Determinar Participante 1
                                                let p1 = participantsMap.get(parseInt(matchData.participante1_id));
                        if (!p1 && matchData.fuente_partido1_id) {
                            const sourceMatch = matchesMap.get(parseInt(matchData.fuente_partido1_id));
                            // SOLUCIÓN: Verificar que el partido fuente existe antes de intentar usarlo.
                            if (sourceMatch) {
                                p1 = { id: -1, nombre_usuario: `Ganador de ${sourceMatch.label}` };
                            }
                        }

                        // Determinar Participante 2
                        let p2 = participantsMap.get(parseInt(matchData.participante2_id));
                        if (!p2 && matchData.fuente_partido2_id) {
                            const sourceMatch = matchesMap.get(parseInt(matchData.fuente_partido2_id));
                            if (sourceMatch) {
                                p2 = { id: -1, nombre_usuario: `Ganador de ${sourceMatch.label}` };
                            }
                        } else if (!p2 && matchData.participante1_id && !matchData.fuente_partido2_id) {
                            p2 = { id: -1, nombre_usuario: 'BYE' };
                        }



                        match.appendChild(createParticipantElement(p1));
                        match.appendChild(createParticipantElement(p2));
                        roundColumn.appendChild(match);
                    });

                    renderArea.appendChild(roundColumn);
                }
                scrollWrapper.appendChild(renderArea);
                container.appendChild(scrollWrapper);
            }

            function deleteTournament(torneo_id) {
                if (confirm('¿Estás seguro de que quieres eliminar este torneo? Esta acción no se puede deshacer.')) {
                    const formData = new FormData();
                    formData.append('torneo_id', torneo_id);

                    fetch('includes/eliminar_torneo.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.success) {
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ocurrió un error al intentar eliminar el torneo.');
                    });
                }
            }

            function renderBracket(participants, cupo, container) {
                container.innerHTML = ''; // Clear previous content
                const numSlots = parseInt(cupo, 10);

                for (let i = 0; i < numSlots; i++) {
                    const participant = participants[i] || null; // Ensure it's null if undefined
                    const participantEl = createParticipantElement(participant);
                    container.appendChild(participantEl);
                }
            }
            
            function createParticipantElement(participant) {
                // This function handles both filled and empty slots
                if (!participant) {
                    const el = document.createElement('div');
                    el.className = 'participant';
                    el.textContent = 'Esperando...';
                    return el;
                }
                
                const el = document.createElement('div');
                el.className = 'participant';
                if (participant.id == <?php echo json_encode($_SESSION['usuario_id']); ?>) {
                    el.classList.add('current-user');
                }
                const img = document.createElement('img');
                img.src = participant.foto_perfil || 'img/avatars/default.png';
                const name = document.createElement('span');
                name.textContent = participant.nombre_usuario;
                el.appendChild(img);
                el.appendChild(name);
                return el;
            }


            feather.replace();
        });
    </script>

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
