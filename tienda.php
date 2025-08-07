<?php
include 'includes/db.php';
include 'includes/auth.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";

// Obtener saldo actual
$usuario = null;
$saldo = 0;
$stmt = $conn->prepare("SELECT nombre_usuario, meentcoins FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
if ($stmt->execute()) {
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $saldo = $usuario['meentcoins'];
    }
}
$stmt->close();

// Procesar canje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['premio_id'])) {
    $premio_id = intval($_POST['premio_id']);

    // Obtener info del premio
    $premio = $conn->prepare("SELECT * FROM premios WHERE id = ?");
    $premio->bind_param("i", $premio_id);
    $premio->execute();
    $res = $premio->get_result();
    $p = $res->fetch_assoc();

    if ($saldo >= $p['costo']) {
        // Descontar y registrar canje
        try {
            $conn->begin_transaction();

            // Usar consultas preparadas para evitar inyección SQL
            $stmt_update = $conn->prepare("UPDATE usuarios SET meentcoins = meentcoins - ? WHERE id = ?");
            $stmt_update->bind_param("ii", $p['costo'], $usuario_id);
            $stmt_update->execute();

            $stmt_insert = $conn->prepare("INSERT INTO canjes (usuario_id, premio_id) VALUES (?, ?)");
            $stmt_insert->bind_param("ii", $usuario_id, $premio_id);
            $stmt_insert->execute();

            $conn->commit();
            $mensaje = "Canje exitoso: " . htmlspecialchars($p['nombre']);
            $saldo -= $p['costo']; // Actualizar variable local
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $mensaje = "Error al procesar el canje. Inténtalo de nuevo.";
            // Opcional: registrar el error $e->getMessage()
        }
    } else {
        $mensaje = "No tienes suficientes MEENTCOINS.";
    }
}

// Obtener premios disponibles
// $premios = $conn->query("SELECT * FROM premios");

$juegos = [
    'freefire' => 'Free Fire',
    'fortnite' => 'Fortnite',
    'mobilelegends' => 'Mobile Legends',
    'wildrift' => 'Wild Rift',
    'dota2' => 'Dota 2',
    '8ballpool' => '8 Ball Pool',
    'lol' => 'League of Legends',
    'roblox' => 'Roblox',
];

$premios_por_juego = [
    'freefire' => [
        ['id' => 1, 'nombre' => '100 Diamantes', 'costo' => 100],
        ['id' => 2, 'nombre' => '310 Diamantes', 'costo' => 300],
        ['id' => 3, 'nombre' => '520 Diamantes', 'costo' => 500],
        ['id' => 4, 'nombre' => '1060 Diamantes', 'costo' => 1000],
        ['id' => 5, 'nombre' => '2180 Diamantes', 'costo' => 2000],
        ['id' => 6, 'nombre' => '5600 Diamantes', 'costo' => 5000],
    ],
    'fortnite' => [
        ['id' => 7, 'nombre' => '1000 V-Bucks', 'costo' => 1000],
        ['id' => 8, 'nombre' => '2800 V-Bucks', 'costo' => 2500],
        ['id' => 9, 'nombre' => '5000 V-Bucks', 'costo' => 4500],
        ['id' => 10, 'nombre' => '13500 V-Bucks', 'costo' => 12000],
    ],
    'roblox' => [
        ['id' => 11, 'nombre' => '400 Robux', 'costo' => 500],
        ['id' => 12, 'nombre' => '800 Robux', 'costo' => 1000],
        ['id' => 13, 'nombre' => '1700 Robux', 'costo' => 2000],
        ['id' => 14, 'nombre' => '4500 Robux', 'costo' => 5000],
        ['id' => 15, 'nombre' => '10000 Robux', 'costo' => 10000],
    ],
    'dota2' => [
        ['id' => 16, 'nombre' => 'Suscripción Mensual Dota Plus', 'costo' => 400],
        ['id' => 17, 'nombre' => 'Suscripción 6 Meses Dota Plus', 'costo' => 2200],
        ['id' => 18, 'nombre' => 'Suscripción 12 Meses Dota Plus', 'costo' => 4000],
    ],
    'mobilelegends' => [
        ['id' => 19, 'nombre' => '250 Diamonds', 'costo' => 500],
        ['id' => 20, 'nombre' => '500 Diamonds', 'costo' => 1000],
        ['id' => 21, 'nombre' => '1000 Diamonds', 'costo' => 2000],
    ],
    'wildrift' => [
        ['id' => 22, 'nombre' => '500 Wild Cores', 'costo' => 500],
        ['id' => 23, 'nombre' => '1000 Wild Cores', 'costo' => 1000],
        ['id' => 24, 'nombre' => '2000 Wild Cores', 'costo' => 2000],
    ],
    '8ballpool' => [
        ['id' => 25, 'nombre' => '10000 Pool Coins', 'costo' => 500],
        ['id' => 26, 'nombre' => '25000 Pool Coins', 'costo' => 1000],
        ['id' => 27, 'nombre' => '60000 Pool Coins', 'costo' => 2000],
    ],
    'lol' => [
        ['id' => 28, 'nombre' => '650 Riot Points', 'costo' => 500],
        ['id' => 29, 'nombre' => '1380 Riot Points', 'costo' => 1000],
        ['id' => 30, 'nombre' => '2800 Riot Points', 'costo' => 2000],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda - MEENTNOVA</title>
    <link rel="stylesheet" href="css/perfil.css?v=3.0">
    <link rel="stylesheet" href="css/tienda.css?v=8.0"> <!-- Cache busting -->
</head>
<body>
    <nav class="top-nav">
        <a href="#" class="nav-logo">MEENTNOVA</a>
        <ul class="nav-links">
            <li><a href="index.php">INICIO</a></li>
            <li><a href="perfil.php">PERFIL</a></li>
            <li><a href="tienda.php" class="active">TIENDA</a></li>
            <li><a href="#">AMIGOS</a></li>
        </ul>
        <button class="btn-primary" onclick="location.href='logout.php'">CERRAR SESIÓN</button>
    </nav>

    <div class="page-header">
        <div class="header-placeholder"></div>
        <h1 class="tienda-title">TIENDA OFICIAL</h1>
        <div class="balance-container">
            <p><strong>Tu saldo:</strong> <span class="meentcoin-gold"><?= $saldo ?> MEENTCOINS</span></p>
        </div>
    </div>

    <div class="main-container">
        <div class="game-selector-container">
            <h3>Selección de juego</h3>
            <div class="game-selector">
                <?php foreach ($juegos as $key => $nombre) : ?>
                    <div class="game-item <?= $key === 'freefire' ? 'active' : '' ?>" data-game="<?= $key ?>">
                        <span class="game-name"><?= $nombre ?></span>
                        <img src="img/games/<?= $key ?>.jpg" alt="<?= $nombre ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="mensaje"><?= $mensaje ?></p>

        <div class="prizes-container">
            <div class="tienda">
                <?php foreach ($premios_por_juego as $juego_key => $premios) : ?>
                    <?php foreach ($premios as $premio) : ?>
                        <div class="item" data-game="<?= $juego_key ?>">
                            <img src="img/premios/<?= $premio['id'] ?>.png" alt="Premio" width="120">
                            <h3><?= $premio['nombre'] ?></h3>
                            <p><strong class="meentcoin-gold"><?= $premio['costo'] ?> MEENTCOINS</strong></p>
                            <form method="POST">
                                <input type="hidden" name="premio_id" value="<?= $premio['id'] ?>">
                                <button type="submit">Canjear</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <!-- Pagination controls are now INSIDE the grid -->
                <div class="pagination-controls">
                    <button id="page-btn">Ver más</button>
                </div>
            </div>
        </div>
        
        <?php include 'includes/auth.php'; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const gameItems = document.querySelectorAll('.game-item');
        const storeItems = document.querySelectorAll('.item');
        const pageBtn = document.getElementById('page-btn');
        const paginationControls = document.querySelector('.pagination-controls');
        const tiendaContainer = document.querySelector('.tienda');

        const itemsPerPage = 4;
        let currentPage = 0;
        let activeItems = [];
        let totalPages = 0;

        function showPage(page) {
            // Hide all items to start
            activeItems.forEach(item => item.style.display = 'none');
            
            const start = page * itemsPerPage;
            const end = start + itemsPerPage;
            const pageItems = activeItems.slice(start, end);

            pageItems.forEach(item => item.style.display = 'block');

            const isLastPage = (page + 1) >= totalPages;
            pageBtn.textContent = isLastPage ? 'Ver menos' : 'Ver más';
            
            if (totalPages > 1) {
                paginationControls.style.display = 'block';
                // Move the controls to be the last element in the grid for proper layout
                tiendaContainer.appendChild(paginationControls);
            } else {
                paginationControls.style.display = 'none';
            }
        }

        function filterItems(selectedGame) {
            // First, hide ALL items to prevent mixing between games
            storeItems.forEach(item => item.style.display = 'none');

            activeItems = Array.from(storeItems).filter(item => item.dataset.game === selectedGame);
            totalPages = Math.ceil(activeItems.length / itemsPerPage);
            
            gameItems.forEach(item => {
                item.classList.toggle('active', item.dataset.game === selectedGame);
            });

            currentPage = 0;
            showPage(currentPage);
        }

        pageBtn.addEventListener('click', () => {
            const isLastPage = (currentPage + 1) >= totalPages;

            if (isLastPage) {
                // If on the last page, "Ver menos" goes to the first page
                currentPage = 0;
            } else {
                // "Ver más" goes to the next page
                currentPage++;
            }
            showPage(currentPage);
        });

        gameItems.forEach(item => {
            item.addEventListener('click', function() {
                filterItems(this.dataset.game);
            });
        });

        // Initial filter
        filterItems('freefire');

        // Auto-hide error message
        const mensajeElem = document.querySelector('.mensaje');
        if (mensajeElem && mensajeElem.textContent.includes('No tienes suficientes')) {
            setTimeout(() => {
                mensajeElem.style.display = 'none';
            }, 5000);
        }

        // Modal Logic
        const modal = document.getElementById('redeem-modal');
        const closeModalBtn = document.querySelector('.modal-close');
        const redeemBtns = document.querySelectorAll('.item button[type="submit"]');

        redeemBtns.forEach(btn => {
            btn.addEventListener('click', function(event) {
                event.preventDefault(); // Stop form from submitting immediately

                // Get data from the prize item
                const item = event.target.closest('.item');
                const prizeName = item.querySelector('h3').textContent;
                const prizeCost = item.querySelector('p strong').textContent;
                const gameKey = item.dataset.game;
                const gameName = document.querySelector(`.game-item[data-game="${gameKey}"] .game-name`).textContent;
                const prizeId = item.querySelector('input[name="premio_id"]').value;

                // Populate modal
                document.getElementById('modal-game-name').textContent = gameName;
                document.getElementById('modal-prize-name').textContent = prizeName;
                document.getElementById('modal-prize-cost').textContent = prizeCost;
                document.getElementById('modal-premio-id').value = prizeId;
                
                // Show modal
                modal.style.display = 'flex';
            });
        });

        function closeModal() {
            modal.style.display = 'none';
        }

        closeModalBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });
    </script>

    <!-- Redemption Modal -->
    <div id="redeem-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Confirmar Canje</h2>
            <div id="modal-prize-info">
                <p><strong>Juego:</strong> <span id="modal-game-name"></span></p>
                <p><strong>Premio:</strong> <span id="modal-prize-name"></span></p>
                <p><strong>Costo:</strong> <span id="modal-prize-cost"></span> MEENTCOINS</p>
            </div>
            <form id="redeem-form" method="POST">
                <input type="hidden" id="modal-premio-id" name="premio_id">
                <div class="form-group">
                    <label for="player_id">ID de Jugador:</label>
                    <input type="text" id="player_id" name="player_id" required>
                </div>
                <div class="form-group">
                    <label for="player_username">Nombre de Usuario (en el juego):</label>
                    <input type="text" id="player_username" name="player_username" required>
                </div>
                <div class="form-group terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">Acepto los <a target="_blank">términos y condiciones</a></label>
                </div>
                <button type="submit" class="btn-primary">Confirmar Canje</button>
            </form>
        </div>
    </div>
</body>
</html>
