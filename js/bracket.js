// Contendrá la lógica para la generación y manejo de brackets.

// --- Bracket Generation Logic ---
const backButton = document.getElementById('bracket-back-btn');
if (backButton) {
    backButton.addEventListener('click', () => window.location.reload());
}

const loggedInUserId = window.pageData.loggedInUserId;
const currentGameName = window.pageData.gameName;

document.addEventListener('click', function(e) {
    const bracketButton = e.target.closest('.view-bracket-btn');
    if (bracketButton) {
        const tournamentItem = bracketButton.closest('.tournament-item');
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
    }
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
    setTimeout(() => alignBracketConnectors(), 0);
}

function alignBracketConnectors() {
    const renderArea = document.querySelector('.bracket-render-area, .bracket-split-view');
    if (!renderArea) return;
    renderArea.querySelectorAll('.connector-group').forEach(c => c.remove());
    const isSplit = renderArea.classList.contains('bracket-split-view');
    const sides = isSplit ? [renderArea.querySelector('.left'), renderArea.querySelector('.right')] : [renderArea];
    let maxHeight = 0;
    sides.forEach(side => {
        if (!side) return;
        const columns = Array.from(side.children).filter(c => c.classList.contains('round-column'));
        if (columns.length < 1) return;
        const isRightSide = side.classList.contains('right');
        columns.forEach(col => col.style.position = 'relative');

        // Reset titles before positioning
        columns.forEach(col => {
            const title = col.querySelector('.round-title');
            if(title) {
                title.style.position = 'relative';
                title.style.top = 'auto';
            }
        });

        columns[0].querySelectorAll('.match').forEach(match => {
            match.style.position = 'relative';
            match.style.top = 'auto';
            match.style.marginBottom = '70px';
        });

        for (let i = 1; i < columns.length; i++) {
            const prevColMatches = columns[i - 1].querySelectorAll('.match');
            const currentColMatches = columns[i].querySelectorAll('.match');
            const currentTitle = columns[i].querySelector('.round-title');
            let topMostMatchY = Infinity;

            currentColMatches.forEach((match, index) => {
                match.style.position = 'absolute';
                const parent1 = prevColMatches[index * 2];
                const parent2 = prevColMatches[(index * 2) + 1];
                if (parent1 && parent2) {
                    const p1_center = parent1.offsetTop + (parent1.offsetHeight / 2);
                    const p2_center = parent2.offsetTop + (parent2.offsetHeight / 2);
                    const newTop = (p1_center + p2_center) / 2 - (match.offsetHeight / 2);
                    match.style.top = `${newTop}px`;
                    if (newTop < topMostMatchY) {
                        topMostMatchY = newTop;
                    }
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
                        vLine.style.right = '0';
                        hLine1.style.right = '0';
                        hLine2.style.right = '0';
                        childLine.style.left = '100%';
                    } else {
                        connectorGroup.style.left = '100%';
                        vLine.style.left = '0';
                        hLine1.style.left = '0';
                        hLine2.style.left = '0';
                        childLine.style.right = '100%';
                    }
                    connectorGroup.appendChild(vLine);
                    connectorGroup.appendChild(hLine1);
                    connectorGroup.appendChild(hLine2);
                    columns[i-1].appendChild(connectorGroup);
                    match.appendChild(childLine);
                }
            });

            if (currentTitle && topMostMatchY !== Infinity) {
                currentTitle.style.position = 'absolute';
                currentTitle.style.top = `${topMostMatchY - 60}px`; // Increased gap to 60px
            }
        }
        let sideHeight = 0;
        const firstColumnMatches = columns[0].querySelectorAll('.match');
        if(firstColumnMatches.length > 0) {
            const lastMatch = firstColumnMatches[firstColumnMatches.length - 1];
            sideHeight = lastMatch.offsetTop + lastMatch.offsetHeight;
        }
        if (sideHeight > maxHeight) maxHeight = sideHeight;
    });
    if (maxHeight > 0) {
        sides.forEach(side => { if (side) side.style.height = `${maxHeight}px`; });
        const finalContainer = renderArea.querySelector('.bracket-side.final');
        if (finalContainer) {
            finalContainer.style.height = `${maxHeight}px`;
            finalContainer.style.display = 'flex';
            finalContainer.style.alignItems = 'center';
            finalContainer.style.justifyContent = 'center';
        }
    }
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
    if (participant.id == window.pageData.loggedInUserId) {
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
