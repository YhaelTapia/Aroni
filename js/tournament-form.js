// Contendrá la lógica para el formulario de creación de torneos.
const gameModeSelect = document.getElementById('game-mode');

if (gameModeSelect) {
    const currentGameSlug = window.pageData.gameSlug;
    const reglasRecomendadasContent = document.getElementById('reglas-recomendadas-content');
    const reglasDescripcionContent = document.getElementById('reglas-descripcion-content');
    const reglasRecomendadasWrapper = document.getElementById('reglas-recomendadas-wrapper');
    const reglasPersonalizadasForm = document.getElementById('reglas-personalizadas-form');
    const personalizadoRadio = document.getElementById('personalizado-radio');
    const recomendadoRadio = document.getElementById('recomendado-radio');
    const recomendadoLabel = document.getElementById('recomendado-label');
    const saveCustomRuleBtn = document.getElementById('save-custom-rule-btn');
    const eventTypeSelect = document.getElementById('event-type');
    const ligaSizeGrid = document.querySelector('.liga-size-grid');


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

    if(eventTypeSelect) {
        eventTypeSelect.addEventListener('change', (e) => {
            document.getElementById('torneo-options').classList.toggle('hidden', e.target.value === 'liga');
            document.getElementById('liga-options').classList.toggle('hidden', e.target.value !== 'liga');
        });
    }

    if(ligaSizeGrid) {
        ligaSizeGrid.addEventListener('click', (e) => {
            if (e.target.classList.contains('liga-size-item')) {
                ligaSizeGrid.querySelectorAll('.liga-size-item').forEach(item => item.classList.remove('selected'));
                e.target.classList.add('selected');
                document.getElementById('liga-size').value = e.target.dataset.value;
            }
        });
    }
}
