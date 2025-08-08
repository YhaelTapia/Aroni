// Contendrá la lógica para la interacción general de la interfaz de usuario.

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
        'clásico-bermuda': { 'victoria-por-puntos': 'Victoria por Puntos (Booyah)', 'mas-kills': 'Victoria por Mayor Cantidad de Kills' },
        'duelo-de-escuadras': { 'victoria-por-daño': 'Victoria por Daño Total', 'victoria-por-kills-de': 'Victoria por Kills (Duelo de Escuadras)' },
        'lobo-solitario': { 'rey-de-la-colina': 'Rey de la Colina (Control de Zona)', 'solo-headshots': 'Duelo a Headshots' }
    },
    'fortnite': {
         'battle-royale': { 'victoria-magistral': 'Victoria Magistral (Último en Pie)', 'mas-eliminaciones-br': 'Victoria por Eliminaciones (Battle Royale)' },
        'zero-build': { 'victoria-sin-construccion': 'Victoria Magistral (Cero Construcción)', 'dominio-de-terreno': 'Dominio de Terreno (Puntos de Interés)' }
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

document.querySelectorAll('.game-item').forEach(item => {
    item.addEventListener('click', () => {
        const gameSlug = item.dataset.game;
        window.location.href = gameSlug === '' ? 'index.php' : `index.php?game=${gameSlug}`;
    });
});

const toggleBtn = document.getElementById('toggle-games-btn');
if (toggleBtn) {
    const allGames = Array.from(document.querySelectorAll('.game-list .game-item'));
    const extraGames = allGames.slice(5);
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
}


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
