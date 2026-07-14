// assets/js/login.js — Pte_Hernandez_LaPazBCS — Login con Sesión PHP

document.addEventListener('DOMContentLoaded', () => {
    const form      = document.getElementById('login-form');
    const errorEl   = document.getElementById('login-error');
    const submitBtn = document.getElementById('login-submit');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.textContent = '';
        submitBtn.disabled = true;

        const usuario  = document.getElementById('usuario').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('api/auth_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ usuario, password }),
            });

            const result = await response.json();

            if (result.status !== 'success') {
                errorEl.textContent = result.message || 'No se pudo iniciar sesión.';
                submitBtn.disabled = false;
                return;
            }

            // TODO: apuntar a dashboard.php una vez maquetado (FASE 1 — pendiente).
            window.location.href = 'index.html';
        } catch {
            errorEl.textContent = 'No se pudo contactar al servidor.';
            submitBtn.disabled = false;
        }
    });
});
