// assets/js/main.js — [NOMBRE_DEL_PROYECTO]

document.addEventListener('DOMContentLoaded', async () => {
    const statusEl = document.getElementById('status-check');
    if (!statusEl) {
        return;
    }

    try {
        const response = await fetch('api/status_check.php');
        const result = await response.json();
        statusEl.textContent = result.message;
    } catch {
        statusEl.textContent = 'No se pudo contactar al servidor.';
    }
});
