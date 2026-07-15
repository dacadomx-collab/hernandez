// assets/js/modulo_5_sud.js — Pte_Hernandez_LaPazBCS — Módulo 5
// Persistencia real (pendientes_sud) + PIN de privacidad + bloqueo por
// inactividad de 2 minutos para la columna 'urgente_confidencial'.
//
// Nota de diseño: el desbloqueo por PIN vence 2 minutos después de verificado
// (ventana fija en el servidor, ver api/modulo_5_backend.php) — no se extiende
// por actividad. El bloqueo por inactividad del cliente es una segunda capa
// visual independiente para ocultar contenido ya cargado en el DOM.

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const form           = document.getElementById('sud-form');
    const errorEl         = document.getElementById('sud-error');
    const pinModal         = document.getElementById('pin-modal');
    const pinInput         = document.getElementById('pin-input');
    const pinError         = document.getElementById('pin-error');
    const pinDesbloquearBtn = document.getElementById('pin-desbloquear-btn');
    const pinConfirmarBtn   = document.getElementById('pin-confirmar-btn');
    const pinCancelarBtn    = document.getElementById('pin-cancelar-btn');

    const INACTIVIDAD_MS = 2 * 60 * 1000;
    let inactividadTimer = null;
    let confidencialVisible = false;

    function bloquearPorInactividad() {
        confidencialVisible = false;
        cargarPendientes();
    }

    function reiniciarTimerInactividad() {
        if (inactividadTimer) {
            clearTimeout(inactividadTimer);
        }
        if (confidencialVisible) {
            inactividadTimer = setTimeout(bloquearPorInactividad, INACTIVIDAD_MS);
        }
    }

    ['mousemove', 'keydown', 'touchstart', 'click'].forEach((evento) => {
        document.addEventListener(evento, reiniciarTimerInactividad, { passive: true });
    });

    async function cargarPendientes() {
        try {
            const res  = await fetch('api/modulo_5_backend.php?accion=listar', { credentials: 'include' });
            const data = await res.json();

            document.querySelectorAll('.sud-lista').forEach((ul) => { ul.innerHTML = ''; });

            if (data.status !== 'success') {
                return;
            }

            confidencialVisible = data.data.pin_desbloqueado;
            pinDesbloquearBtn.classList.toggle('is-hidden', confidencialVisible);
            reiniciarTimerInactividad();

            data.data.pendientes.forEach((p) => {
                const columna = document.querySelector(`.pendientes-columna[data-categoria="${p.categoria}"] .sud-lista`);
                if (!columna) {
                    return;
                }

                const li = document.createElement('li');
                li.className = p.bloqueado ? 'is-bloqueado' : '';
                li.dataset.id = p.id;

                const acciones = !p.bloqueado && p.estatus === 'pendiente'
                    ? '<button type="button" class="btn-completar-sud">✓ Completar</button>'
                    : '';

                li.innerHTML = `
                    <div>${p.titulo}</div>
                    ${p.descripcion ? `<div>${p.descripcion}</div>` : ''}
                    ${acciones}
                `;
                columna.appendChild(li);
            });

            document.querySelectorAll('.btn-completar-sud').forEach((btn) => {
                btn.addEventListener('click', async (event) => {
                    const id = Number(event.target.closest('li').dataset.id);
                    await fetch('api/modulo_5_backend.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ accion: 'completar', id }),
                    });
                    await cargarPendientes();
                });
            });
        } catch {
            errorEl.textContent = 'No se pudieron cargar los pendientes.';
        }
    }

    // ── Modal de PIN ──────────────────────────────────────────────────────────
    function abrirModalPin() {
        pinError.textContent = '';
        pinInput.value = '';
        pinModal.classList.remove('is-hidden');
        pinInput.focus();
    }

    function cerrarModalPin() {
        pinModal.classList.add('is-hidden');
    }

    pinDesbloquearBtn.addEventListener('click', abrirModalPin);
    pinCancelarBtn.addEventListener('click', cerrarModalPin);

    pinConfirmarBtn.addEventListener('click', async () => {
        pinError.textContent = '';

        try {
            const res  = await fetch('api/modulo_5_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ accion: 'verificar_pin', pin: pinInput.value }),
            });
            const data = await res.json();

            if (data.status !== 'success') {
                pinError.textContent = data.message || 'PIN incorrecto.';
                return;
            }

            cerrarModalPin();
            await cargarPendientes();
        } catch {
            pinError.textContent = 'No se pudo contactar al servidor.';
        }
    });

    // ── Registro de nuevo asunto ─────────────────────────────────────────────
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.textContent = '';

        try {
            const res  = await fetch('api/modulo_5_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    accion: 'crear',
                    titulo: document.getElementById('titulo').value,
                    descripcion: document.getElementById('descripcion').value,
                    categoria: document.getElementById('categoria').value,
                }),
            });
            const data = await res.json();

            if (data.status !== 'success') {
                errorEl.textContent = data.message || 'No se pudo registrar el asunto.';
                return;
            }

            form.reset();
            await cargarPendientes();
        } catch {
            errorEl.textContent = 'No se pudo contactar al servidor.';
        }
    });

    cargarPendientes();
});
