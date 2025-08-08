document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    // The other scripts are loaded before this one, so their event listeners are already set up.
    // We just need to call feather.replace() here, as it's used across different modules.
    feather.replace();
});
