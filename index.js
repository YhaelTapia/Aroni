document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('pageshow', function(event) {
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
    const modal = document.getElementById('tournament-details-modal');
    const closeModalBtn = modal.querySelector('.close-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalJoinBtn = document.getElementById('modal-join-btn');

    function updateRules(selectedMode) {
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
                if (index === 0) {
                   input.checked = true;
                   reglasDescripcionContent.innerHTML = ruleDescriptions[key] || 'No hay descripción disponible.';
                   reglasDescripcionContent.style.display = 'block';
                }
                label.appendChild(input);
                label.appendChild(checkmark);
                label.appendChild(document.createTextNode(` ${value}`));
                reglasRecomendadasContent.appendChild(label);
            });
        } else {
             reglasRecomendadasWrapper.style.display = 'none';
        }
    }

    if (gameModeSelect) {
        if (gameModes[currentGameSlug]) {
            gameModeSelect.innerHTML = '';
            const modes = gameModes[currentGameSlug];
            modes.forEach(mode => {
                const option = document.createElement('option');
                const modeSlug = mode.toLowerCase().replace(/ /g, '-');
                option.value = modeSlug;
                option.textContent = mode;
                gameModeSelect.appendChild(option);
            });
            gameModeSelect.addEventListener('change', (e) => updateRules(e.target.value));
            if (gameModeSelect.value) updateRules(gameModeSelect.value);
        }
        reglasRecomendadasContent.addEventListener('change', (e) => {
            if (e.target.name === 'rule_set' && e.target.type === 'radio') {
                const ruleKey = e.target.value;
                const description = ruleDescriptions[ruleKey] || 'No hay descripción disponible.';
                reglasDescripcionContent.innerHTML = description;
                reglasDescripcionContent.style.display = 'block';
            }
        });
        if (personalizadoRadio) {
            personalizadoRadio.addEventListener('change', (e) => {
                if (e.target.checked) {
                    reglasRecomendadasWrapper.style.display = 'none';
                    reglasDescripcionContent.style.display = 'none';
                    reglasPersonalizadasForm.style.display = 'block';
                    recomendadoLabel.style.display = 'flex';
                }
            });
        }
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
        if (saveCustomRuleBtn) {
            saveCustomRuleBtn.addEventListener('click', () => {
                const title = document.getElementById('custom-rule-title').value.trim();
                const desc = document.getElementById('custom-rule-desc').value.trim();
                if (title && desc) {
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
                    reglasRecomendadasContent.appendChild(label);
                    reglasDescripcionContent.textContent = desc;
                    reglasDescripcionContent.style.display = 'block';
                    document.getElementById('custom-rule-title').value = '';
                    document.getElementById('custom-rule-desc').value = '';
                    reglasPersonalizadasForm.style.display = 'none';
                    recomendadoLabel.style.display = 'none';
                    personalizadoRadio.checked = false;
                    reglasRecomendadasWrapper.style.display = 'block';
                } else {
                    alert('Por favor, completa el título y la descripción de la regla.');
                }
            });
        }
        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const tournamentItem = e.target.closest('.tournament-item');
                document.querySelectorAll('.tournament-item').forEach(item => item.classList.remove('selected-for-modal'));
                tournamentItem.classList.add('selected-for-modal');
                const details = JSON.parse(tournamentItem.dataset.details);
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
                modal.style.display = 'flex';
            });
        });

        function hideModal() { modal.style.display = 'none'; }
        closeModalBtn.addEventListener('click', hideModal);
        modalCloseBtn.addEventListener('click', hideModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });
    }

    document.querySelectorAll('.game-item').forEach(item => {
        item.addEventListener('click', () => {
            const gameSlug = item.dataset.game;
            window.location.href = gameSlug === '' ? 'index.php' : `index.php?game=${gameSlug}`;
        });
    });

    const toggleBtn = document.getElementById('toggle-games-btn');
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

    const eventTypeSelect = document.getElementById('event-type');
    if(eventTypeSelect) {
        eventTypeSelect.addEventListener('change', (e) => {
            document.getElementById('torneo-options').classList.toggle('hidden', e.target.value === 'liga');
            document.getElementById('liga-options').classList.toggle('hidden', e.target.value !== 'liga');
        });
    }

    const ligaSizeGrid = document.querySelector('.liga-size-grid');
    if(ligaSizeGrid) {
        ligaSizeGrid.addEventListener('click', (e) => {
            if (e.target.classList.contains('liga-size-item')) {
                ligaSizeGrid.querySelectorAll('.liga-size-item').forEach(item => item.classList.remove('selected'));
                e.target.classList.add('selected');
                document.getElementById('liga-size').value = e.target.dataset.value;
            }
        });
    }

    if (modalJoinBtn) {
        modalJoinBtn.addEventListener('click', () => {
            const tournamentItem = document.querySelector('.tournament-item.selected-for-modal');
            if (tournamentItem) joinTournament(tournamentItem.dataset.id);
        });
    }

    function joinTournament(torneo_id) {
        const formData = new FormData();
        formData.append('torneo_id', torneo_id);
        fetch('includes/unirse_torneo.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al intentar unirse al torneo.');
        });
    }

    // --- Bracket Generation Logic ---
    const backButton = document.getElementById('bracket-back-btn');
    if (backButton) {
        backButton.addEventListener('click', () => window.location.reload());
    }

    const loggedInUserId = <?php echo json_encode(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null); ?>;
    const currentGameName = <?php echo json_encode($game_name); ?>;
    
    document.querySelectorAll('.view-bracket-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const tournamentItem = e.target.closest('.tournament-item');
            const tournamentId = tournamentItem.dataset.id;
            fetch(`includes/get_inscritos.php?torneo_id=${tournamentId}&_=${new Date().getTime()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showBracketView(tournamentId, data);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error fetching bracket data:', error));
        });
    });

    function showBracketView(tournamentId, data) {
        document.querySelector('#pending-tournaments .tournament-list').style.display = 'none';
        document.querySelector('#tournament-view-container > h3').style.display = 'none';
        document.querySelector('#tournament-view-container > .tabs').style.display = 'none';
        const bracketView = document.getElementById('bracket-view');
        bracketView.style.display = 'block';
        document.getElementById('bracket-back-btn').style.display = 'inline-block';

        document.getElementById('bracket-title').textContent = 'Torneo de ' + currentGameName;
        const format = (data.torneo.modalidad || 'No especificado').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        let gameMode = data.torneo.titulo || 'No especificado';
        if (gameMode.toLowerCase().startsWith('torneo de ')) gameMode = gameMode.substring(10);
        gameMode = gameMode.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const rulesContent = data.torneo.reglas_personalizadas ? data.torneo.reglas_personalizadas.replace(/\n/g, '<br>') : (ruleDescriptions[data.torneo.reglas] || 'No hay reglas definidas.');
        document.getElementById('bracket-rules').innerHTML = `
            <p><strong style="color: var(--text-secondary);">Formato:</strong> ${format}</p>
            <p><strong style="color: var(--text-secondary);">Modo de Juego:</strong> ${gameMode}</p>
            <p><strong style="color: var(--text-secondary);">Reglas:</strong></p>
            <div>${rulesContent}</div>
        `;

        const deleteButton = document.getElementById('delete-tournament-btn');
        if (loggedInUserId && data.torneo.organizador_id == loggedInUserId) {
            deleteButton.style.display = 'inline-block';
            deleteButton.onclick = () => deleteTournament(tournamentId);
        } else {
            deleteButton.style.display = 'none';
        }

        if (data.torneo.estado === 'en_curso' || data.torneo.estado === 'finalizado') {
            document.getElementById('start-tournament-btn').style.display = 'none';
            document.querySelector('.participant-header').style.display = 'none';
            document.getElementById('bracket-container').style.display = 'none';
            document.getElementById('full-bracket-container').classList.remove('hidden');
            renderFullBracket(data, tournamentId);
        } else {
            document.querySelector('.participant-header').style.display = 'flex';
            document.getElementById('bracket-container').style.display = 'grid';
            document.getElementById('full-bracket-container').classList.add('hidden');
            const startButton = document.getElementById('start-tournament-btn');
            if (loggedInUserId && data.torneo.organizador_id == loggedInUserId) {
                startButton.style.display = 'inline-block';
                startButton.onclick = () => startTournament(tournamentId);
            } else {
                startButton.style.display = 'none';
            }
            const controls = document.querySelector('.bracket-view-controls');
            if(controls) controls.style.display = 'none';
            renderBracket(data.inscritos, data.torneo.cupo, document.getElementById('bracket-container'));
        }
    }

    function startTournament(torneo_id) {
        if (!confirm('¿Estás seguro de que quieres iniciar el torneo? Una vez iniciado, no se podrán unir más participantes.')) return;
        const formData = new FormData();
        formData.append('torneo_id', torneo_id);
        fetch('includes/iniciar_torneo.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                showBracketView(torneo_id, data.data);
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
        if (teamsInRound === 2) return 'Final';
        if (teamsInRound === 4) return 'Semifinales';
        if (teamsInRound === 8) return 'Cuartos de Final';
        if (teamsInRound === 16) return 'Octavos de Final';
        if (teamsInRound > 16) return `${teamsInRound / 2}avos de Final`;
        return 'Ronda';
    }

    function createMatchElement(matchData, participantsMap, matchesMap) {
        const match = document.createElement('div');
        match.className = 'match';
        match.dataset.matchId = matchData.id;
        const matchLabel = document.createElement('div');
        matchLabel.className = 'match-label';
        matchLabel.textContent = matchData.label;
        match.appendChild(matchLabel);
        let p1 = participantsMap.get(parseInt(matchData.participante1_id));
        if (!p1 && matchData.fuente_partido1_id) {
            const sourceMatch = matchesMap.get(parseInt(matchData.fuente_partido1_id));
            if (sourceMatch) p1 = { id: -1, nombre_usuario: `Ganador de ${sourceMatch.label}` };
        }
        let p2 = participantsMap.get(parseInt(matchData.participante2_id));
        if (!p2 && matchData.fuente_partido2_id) {
            const sourceMatch = matchesMap.get(parseInt(matchData.fuente_partido2_id));
            if (sourceMatch) p2 = { id: -1, nombre_usuario: `Ganador de ${sourceMatch.label}` };
        } else if (!p2 && matchData.participante1_id && !matchData.fuente_partido2_id) {
            p2 = { id: -1, nombre_usuario: 'BYE' };
        }
        match.appendChild(createParticipantElement(p1));
        match.appendChild(createParticipantElement(p2));
        return match;
    }

    function getExcelColumnName(number) {
        let columnName = "";
        let num = number;
        while (num >= 0) {
            columnName = String.fromCharCode(65 + (num % 26)) + columnName;
            num = Math.floor(num / 26) - 1;
        }
        return columnName;
    }

    function renderFullBracket(data, tournamentId) {
        const container = document.getElementById('full-bracket-container');
        container.innerHTML = '';
        
        const title = document.createElement('h3');
        title.className = 'bracket-area-title';
        title.textContent = 'PARTICIPANTES';
        container.appendChild(title);

        const controls = document.querySelector('.bracket-view-controls');
        if (controls) {
            container.appendChild(controls);
            controls.style.display = 'flex';
        }

        const deleteButton = document.getElementById('delete-tournament-btn');
        if (deleteButton) {
            container.appendChild(deleteButton);
        }

        feather.replace();

        const totalParticipants = parseInt(data.torneo.cupo, 10) || data.inscritos.length;
        const participantsMap = new Map((data.inscritos || []).map(p => [p.id, p]));
        const matchesMap = new Map();
        (data.partidos || []).forEach((p, index) => {
            p.label = `Grupo ${getExcelColumnName(index)}`;
            matchesMap.set(p.id, p);
        });
        const rounds = (data.partidos || []).reduce((acc, match) => {
            acc[match.ronda] = acc[match.ronda] || [];
            acc[match.ronda].push(match);
            return acc;
        }, {});
        const totalRounds = Object.keys(rounds).length;
        const scrollWrapper = document.createElement('div');
        scrollWrapper.className = 'bracket-scroll-wrapper';
        if (totalParticipants < 16) {
            const renderArea = document.createElement('div');
            renderArea.className = 'bracket-render-area';
            for (let i = 1; i <= totalRounds; i++) {
                const roundColumn = document.createElement('div');
                roundColumn.className = 'round-column';
                const roundTitle = document.createElement('div');
                roundTitle.className = 'round-title';
                roundTitle.textContent = getRoundName(totalParticipants, i - 1);
                roundColumn.appendChild(roundTitle);
                const matchesInThisRound = rounds[i] || [];
                matchesInThisRound.forEach(matchData => roundColumn.appendChild(createMatchElement(matchData, participantsMap, matchesMap)));
                renderArea.appendChild(roundColumn);
            }
            scrollWrapper.appendChild(renderArea);
        } else {
            const splitView = document.createElement('div');
            splitView.className = 'bracket-split-view';
            const leftSide = document.createElement('div');
            leftSide.className = 'bracket-side left';
            const finalRoundContainer = document.createElement('div');
            finalRoundContainer.className = 'bracket-side final';
            const rightSide = document.createElement('div');
            rightSide.className = 'bracket-side right';
            for (let i = 1; i <= totalRounds; i++) {
                const roundName = getRoundName(totalParticipants, i - 1);
                const matchesInThisRound = rounds[i] || [];
                if (roundName === 'Final') {
                    const roundColumn = document.createElement('div');
                    roundColumn.className = 'round-column';
                    const roundTitle = document.createElement('div');
                    roundTitle.className = 'round-title';
                    roundTitle.textContent = roundName;
                    roundColumn.appendChild(roundTitle);
                    matchesInThisRound.forEach(matchData => roundColumn.appendChild(createMatchElement(matchData, participantsMap, matchesMap)));
                    finalRoundContainer.appendChild(roundColumn);
                } else {
                    const leftColumn = document.createElement('div');
                    leftColumn.className = 'round-column';
                    const rightColumn = document.createElement('div');
                    rightColumn.className = 'round-column';
                    const leftTitle = document.createElement('div');
                    leftTitle.className = 'round-title';
                    leftTitle.textContent = roundName;
                    leftColumn.appendChild(leftTitle);
                    const rightTitle = document.createElement('div');
                    rightTitle.className = 'round-title';
                    rightTitle.textContent = roundName;
                    rightColumn.appendChild(rightTitle);
                    const half = Math.ceil(matchesInThisRound.length / 2);
                    const leftMatches = matchesInThisRound.slice(0, half);
                    const rightMatches = matchesInThisRound.slice(half);
                    leftMatches.forEach(matchData => leftColumn.appendChild(createMatchElement(matchData, participantsMap, matchesMap)));
                    rightMatches.forEach(matchData => rightColumn.appendChild(createMatchElement(matchData, participantsMap, matchesMap)));
                    leftSide.appendChild(leftColumn);
                    rightSide.appendChild(rightColumn);
                }
            }
            splitView.appendChild(leftSide);
            splitView.appendChild(finalRoundContainer);
            splitView.appendChild(rightSide);
            scrollWrapper.appendChild(splitView);
        }
        container.appendChild(scrollWrapper);
        setupViewControls();
        setTimeout(() => alignBracketConnectors(), 50);
    }

    function alignBracketConnectors() {
        const renderArea = document.querySelector('.bracket-render-area, .bracket-split-view');
        if (!renderArea) return;
        renderArea.querySelectorAll('.connector-group').forEach(c => c.remove());

        const processSide = (side) => {
            if (!side) return 0;
            const columns = Array.from(side.children).filter(c => c.classList.contains('round-column'));
            if (columns.length < 1) return 0;

            const isRightSide = side.classList.contains('right');
            const MATCH_HEIGHT = 82; // Height of a match box
            const MATCH_VERTICAL_GAP = 40; // Vertical space between matches
            const TITLE_GAP = 40; // Space between title and first match
            const INITIAL_TOP_OFFSET = 20; // General offset from the container top

            columns.forEach(col => col.style.position = 'relative');

            // Position all matches with absolute positioning based on fixed math
            for (let i = 0; i < columns.length; i++) {
                const col = columns[i];
                const matches = col.querySelectorAll('.match');
                const title = col.querySelector('.round-title');

                if (i === 0) {
                    let topOffset = INITIAL_TOP_OFFSET + TITLE_GAP;
                    matches.forEach(match => {
                        match.style.position = 'absolute';
                        match.style.top = `${topOffset}px`;
                        topOffset += MATCH_HEIGHT + MATCH_VERTICAL_GAP;
                    });
                } else {
                    const prevColMatches = columns[i - 1].querySelectorAll('.match');
                    matches.forEach((match, index) => {
                        match.style.position = 'absolute';
                        const parent1 = prevColMatches[index * 2];
                        const parent2 = prevColMatches[(index * 2) + 1];
                        if (parent1 && parent2) {
                            const p1_top = parseInt(parent1.style.top, 10);
                            const p2_top = parseInt(parent2.style.top, 10);
                            const p1_center = p1_top + (MATCH_HEIGHT / 2);
                            const p2_center = p2_top + (MATCH_HEIGHT / 2);
                            const newTop = (p1_center + p2_center) / 2 - (MATCH_HEIGHT / 2);
                            match.style.top = `${newTop}px`;
                        }
                    });
                }
            }
            
            // Position all titles and draw connectors
            for (let i = 0; i < columns.length; i++) {
                const col = columns[i];
                const title = col.querySelector('.round-title');
                const matches = col.querySelectorAll('.match');
                if (matches.length === 0) continue;

                let topMostMatchY = Infinity;
                matches.forEach(m => {
                    topMostMatchY = Math.min(topMostMatchY, parseInt(m.style.top, 10));
                });

                if (title) {
                    title.style.position = 'absolute';
                    title.style.top = `${topMostMatchY - TITLE_GAP}px`;
                }
                
                if (i > 0) {
                    const prevColMatches = columns[i-1].querySelectorAll('.match');
                     matches.forEach((match, index) => {
                        const parent1 = prevColMatches[index * 2];
                        const parent2 = prevColMatches[(index * 2) + 1];
                        if(parent1 && parent2) {
                            const p1_center = parseInt(parent1.style.top, 10) + (parent1.offsetHeight / 2);
                            const p2_center = parseInt(parent2.style.top, 10) + (parent2.offsetHeight / 2);
                            
                            const connectorGroup = document.createElement('div');
                            connectorGroup.className = 'connector-group';
                            connectorGroup.style.cssText = `position:absolute; z-index:0; top:${p1_center}px; height:${p2_center - p1_center}px; width:40px;`;
                            const vLine = document.createElement('div');
                            vLine.style.cssText = 'position:absolute; width:2px; height:100%; background:#4b5563; top:0;';
                            const hLine1 = document.createElement('div');
                            hLine1.style.cssText = 'position:absolute; height:2px; width:100%; background:#4b5563; top:0;';
                            const hLine2 = document.createElement('div');
                            hLine2.style.cssText = 'position:absolute; height:2px; width:100%; background:#4b5563; bottom:0;';
                            const childLine = document.createElement('div');
                            childLine.className = 'connector-group';
                            childLine.style.cssText = `position:absolute; z-index:-1; top:50%; height:2px; width:40px; background:#4b5563; transform:translateY(-50%);`;

                            if (isRightSide) {
                                connectorGroup.style.right = '100%';
                                vLine.style.right = '0'; hLine1.style.right = '0'; hLine2.style.right = '0';
                                childLine.style.left = '100%';
                            } else {
                                connectorGroup.style.left = '100%';
                                vLine.style.left = '0'; hLine1.style.left = '0'; hLine2.style.left = '0';
                                childLine.style.right = '100%';
                            }
                            connectorGroup.appendChild(vLine);
                            connectorGroup.appendChild(hLine1);
                            connectorGroup.appendChild(hLine2);
                            columns[i-1].appendChild(connectorGroup);
                            match.appendChild(childLine);
                        }
                    });
                }
            }
            let maxSideHeight = 0;
            columns.forEach(col => {
                const lastMatch = Array.from(col.querySelectorAll('.match')).pop();
                if (lastMatch) {
                    const h = parseInt(lastMatch.style.top, 10) + lastMatch.offsetHeight;
                    if (h > maxSideHeight) {
                        maxSideHeight = h;
                    }
                }
            });
            return maxSideHeight;
        };

        const leftHeight = processSide(renderArea.querySelector('.left'));
        const rightHeight = processSide(renderArea.querySelector('.right'));
        const finalHeight = processSide(renderArea.querySelector('.final'));
        const singleHeight = processSide(renderArea.querySelector('.bracket-render-area'));

        const maxHeight = Math.max(leftHeight, rightHeight, finalHeight, singleHeight, 400);
        renderArea.style.height = `${maxHeight + INITIAL_TOP_OFFSET}px`;
    }

    function deleteTournament(torneo_id) {
        if (confirm('¿Estás seguro de que quieres eliminar este torneo? Esta acción no se puede deshacer.')) {
            const formData = new FormData();
            formData.append('torneo_id', torneo_id);
            fetch('includes/eliminar_torneo.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al intentar eliminar el torneo.');
            });
        }
    }

    function renderBracket(participants, cupo, container) {
        container.innerHTML = '';
        const numSlots = parseInt(cupo, 10);
        for (let i = 0; i < numSlots; i++) {
            const participant = participants[i] || null;
            container.appendChild(createParticipantElement(participant));
        }
    }
    
    function createParticipantElement(participant) {
        const el = document.createElement('div');
        el.className = 'participant';
        if (!participant) {
            el.textContent = 'Esperando...';
            return el;
        }
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

    function setupViewControls() {
        const viewControls = document.querySelector('.bracket-view-controls');
        if (viewControls) {
            const normalBtn = document.getElementById('view-normal-btn');
            const fullBtn = document.getElementById('view-full-btn');
            const wideBtn = document.getElementById('view-wide-btn');
            const allControlBtns = viewControls.querySelectorAll('button');
            const setActive = (btn) => {
                allControlBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            };
            const getBracketContent = () => {
                const scrollWrapper = document.querySelector('.bracket-scroll-wrapper');
                if (!scrollWrapper) return { scrollWrapper: null, contentArea: null };
                const contentArea = scrollWrapper.querySelector('.bracket-render-area, .bracket-split-view');
                return { scrollWrapper, contentArea };
            };
            normalBtn.addEventListener('click', () => {
                const { scrollWrapper, contentArea } = getBracketContent();
                if (!scrollWrapper) return;
                setActive(normalBtn);
                scrollWrapper.style.overflow = 'auto';
                if (contentArea) {
                    contentArea.style.transform = 'scale(1)';
                    scrollWrapper.style.height = 'auto'; 
                    scrollWrapper.style.width = 'auto';
                }
            });
            fullBtn.addEventListener('click', () => {
                const { scrollWrapper, contentArea } = getBracketContent();
                if (!scrollWrapper || !contentArea) return;
                setActive(fullBtn);
                scrollWrapper.style.overflow = 'hidden';
                const scale = Math.min(scrollWrapper.clientWidth / contentArea.scrollWidth, scrollWrapper.clientHeight / contentArea.scrollHeight);
                contentArea.style.transformOrigin = 'top left';
                contentArea.style.transform = `scale(${scale})`;
                scrollWrapper.style.height = `${contentArea.scrollHeight * scale}px`;
                scrollWrapper.style.width = `${contentArea.scrollWidth * scale}px`;
            });
            wideBtn.addEventListener('click', () => {
                const { scrollWrapper, contentArea } = getBracketContent();
                if (!scrollWrapper) return;
                setActive(wideBtn);
                scrollWrapper.style.maxHeight = 'none';
                scrollWrapper.style.overflow = 'auto';
                if (contentArea) {
                    contentArea.style.transform = 'scale(1)';
                    scrollWrapper.style.height = 'auto';
                    scrollWrapper.style.width = 'auto';
                }
            });
        }
    }
    setupViewControls(); // Call it once on load for any controls already in the DOM
    feather.replace();
});