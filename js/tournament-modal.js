// Contendrá la lógica para el modal de detalles del torneo.

const modal = document.getElementById('tournament-details-modal');
if (modal) {
    const closeModalBtn = modal.querySelector('.close-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalJoinBtn = document.getElementById('modal-join-btn');

    document.addEventListener('click', function(e) {
        const detailsButton = e.target.closest('.view-details-btn');
        if (detailsButton) {
            const tournamentItem = detailsButton.closest('.tournament-item');
            document.querySelectorAll('.tournament-item').forEach(item => item.classList.remove('selected-for-modal'));
            tournamentItem.classList.add('selected-for-modal');
            const details = JSON.parse(tournamentItem.dataset.details);
            modal.querySelector('#modal-title').textContent = details.titulo;
            modal.querySelector('#modal-game').textContent = window.pageData.gameName;
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
        }
    });

    function hideModal() { modal.style.display = 'none'; }
    if(closeModalBtn) closeModalBtn.addEventListener('click', hideModal);
    if(modalCloseBtn) modalCloseBtn.addEventListener('click', hideModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) hideModal(); });

    if (modalJoinBtn) {
        modalJoinBtn.addEventListener('click', () => {
            const tournamentItem = document.querySelector('.tournament-item.selected-for-modal');
            if (tournamentItem) joinTournament(tournamentItem.dataset.id);
        });
    }
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
