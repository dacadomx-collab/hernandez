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
    const bioDesbloquearBtn = document.getElementById('bio-desbloquear-btn');
    const bioError          = document.getElementById('bio-error');

    let bioTieneCredencial = false;

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

    // ── Desbloqueo Biométrico (WebAuthn) ─────────────────────────────────────
    // Fricción Cero: si el dispositivo no soporta WebAuthn, o el usuario
    // cancela/falla la verificación, el flujo cae automáticamente al modal
    // de PIN — nunca deja al usuario sin forma de desbloquear.

    function base64UrlToBuffer(base64url) {
        const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        const padded = base64 + '='.repeat((4 - base64.length % 4) % 4);
        const binario = window.atob(padded);
        const buffer = new Uint8Array(binario.length);
        for (let i = 0; i < binario.length; i += 1) {
            buffer[i] = binario.charCodeAt(i);
        }
        return buffer.buffer;
    }

    function bufferToBase64Url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binario = '';
        bytes.forEach((b) => { binario += String.fromCharCode(b); });
        return window.btoa(binario).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    async function actualizarEstadoBiometria() {
        if (!window.PublicKeyCredential) {
            bioDesbloquearBtn.classList.add('is-hidden');
            return;
        }

        try {
            const res  = await fetch('api/biometria_backend.php?accion=estado', { credentials: 'include' });
            const data = await res.json();

            bioTieneCredencial = data.status === 'success' && data.data.tiene_credencial;
            bioDesbloquearBtn.textContent = bioTieneCredencial
                ? '🔒 Desbloquear con FaceID/Huella'
                : '🔒 Registrar FaceID/Huella';
            bioDesbloquearBtn.classList.remove('is-hidden');
        } catch {
            bioDesbloquearBtn.classList.add('is-hidden');
        }
    }

    async function registrarBiometria() {
        bioError.textContent = '';
        try {
            const res  = await fetch('api/biometria_backend.php?accion=registro_challenge', { credentials: 'include' });
            const data = await res.json();
            if (data.status !== 'success') {
                bioError.textContent = data.message || 'No se pudo iniciar el registro biométrico.';
                return;
            }

            const options = data.data.publicKey;
            options.challenge = base64UrlToBuffer(options.challenge);
            options.user.id   = base64UrlToBuffer(options.user.id);
            if (options.excludeCredentials) {
                options.excludeCredentials = options.excludeCredentials.map((c) => ({ ...c, id: base64UrlToBuffer(c.id) }));
            }

            const credencial = await navigator.credentials.create({ publicKey: options });

            const res2 = await fetch('api/biometria_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    accion: 'guardar_credencial',
                    clientDataJSON: bufferToBase64Url(credencial.response.clientDataJSON),
                    attestationObject: bufferToBase64Url(credencial.response.attestationObject),
                }),
            });
            const data2 = await res2.json();

            if (data2.status !== 'success') {
                bioError.textContent = data2.message || 'No se pudo guardar la credencial biométrica.';
                return;
            }

            await actualizarEstadoBiometria();
        } catch {
            bioError.textContent = 'No se pudo registrar la biometría en este dispositivo.';
        }
    }

    async function desbloquearBiometria() {
        bioError.textContent = '';
        try {
            const res  = await fetch('api/biometria_backend.php?accion=login_challenge', { credentials: 'include' });
            const data = await res.json();
            if (data.status !== 'success') {
                abrirModalPin(); // fallback: no se pudo iniciar la verificación biométrica
                return;
            }

            const options = data.data.publicKey;
            options.challenge = base64UrlToBuffer(options.challenge);
            if (options.allowCredentials) {
                options.allowCredentials = options.allowCredentials.map((c) => ({ ...c, id: base64UrlToBuffer(c.id) }));
            }

            const asercion = await navigator.credentials.get({ publicKey: options });

            const res2 = await fetch('api/biometria_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    accion: 'verificar',
                    credential_id: bufferToBase64Url(asercion.rawId),
                    clientDataJSON: bufferToBase64Url(asercion.response.clientDataJSON),
                    authenticatorData: bufferToBase64Url(asercion.response.authenticatorData),
                    signature: bufferToBase64Url(asercion.response.signature),
                }),
            });
            const data2 = await res2.json();

            if (data2.status !== 'success') {
                bioError.textContent = data2.message || 'Verificación biométrica fallida.';
                abrirModalPin(); // fallback: la firma no fue válida
                return;
            }

            await cargarPendientes();
        } catch {
            // Cancelado por el usuario o el dispositivo falló — cae al PIN sin fricción.
            abrirModalPin();
        }
    }

    bioDesbloquearBtn.addEventListener('click', () => {
        if (bioTieneCredencial) {
            desbloquearBiometria();
        } else {
            registrarBiometria();
        }
    });

    actualizarEstadoBiometria();

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
