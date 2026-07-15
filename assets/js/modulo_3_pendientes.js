// assets/js/modulo_3_pendientes.js — Pte_Hernandez_LaPazBCS — Módulo 3
// Persistencia real (pendientes) + dictado por voz con guardado automático.

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const micBtn         = document.getElementById('mic-btn');
    const input           = document.getElementById('pendiente-texto');
    const categoriaSelect = document.getElementById('categoria');
    const form             = document.getElementById('pendiente-form');
    const errorEl          = document.getElementById('pendiente-error');

    async function cargarPendientes() {
        try {
            const res  = await fetch('api/modulo_3_backend.php?accion=listar', { credentials: 'include' });
            const data = await res.json();

            document.querySelectorAll('.pendientes-lista').forEach((ul) => { ul.innerHTML = ''; });

            if (data.status !== 'success') {
                return;
            }

            data.data.pendientes.forEach((p) => {
                const columna = document.querySelector(`.pendientes-columna[data-categoria="${p.categoria}"] .pendientes-lista`);
                if (!columna) {
                    return;
                }

                const li = document.createElement('li');
                li.className = `pendiente-item${p.estatus === 'completado' ? ' is-completado' : ''}`;
                li.dataset.id = p.id;
                li.innerHTML = `
                    <span>${p.titulo}</span>
                    <span class="pendiente-item__acciones">
                        ${p.estatus === 'pendiente' ? '<button type="button" class="btn-completar">✓</button>' : ''}
                        <button type="button" class="btn-eliminar">✕</button>
                    </span>
                `;
                columna.appendChild(li);
            });

            document.querySelectorAll('.btn-completar').forEach((btn) => {
                btn.addEventListener('click', async (event) => {
                    const id = Number(event.target.closest('.pendiente-item').dataset.id);
                    await fetch('api/modulo_3_backend.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ accion: 'completar', id }),
                    });
                    await cargarPendientes();
                });
            });

            document.querySelectorAll('.btn-eliminar').forEach((btn) => {
                btn.addEventListener('click', async (event) => {
                    const id = Number(event.target.closest('.pendiente-item').dataset.id);
                    await fetch('api/modulo_3_backend.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ accion: 'eliminar', id }),
                    });
                    await cargarPendientes();
                });
            });
        } catch {
            errorEl.textContent = 'No se pudieron cargar los pendientes.';
        }
    }

    async function crearPendiente() {
        errorEl.textContent = '';
        const titulo = input.value.trim();

        if (titulo === '') {
            return;
        }

        try {
            const res  = await fetch('api/modulo_3_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ accion: 'crear', titulo, categoria: categoriaSelect.value }),
            });
            const data = await res.json();

            if (data.status !== 'success') {
                errorEl.textContent = data.message || 'No se pudo guardar el pendiente.';
                return;
            }

            const categoriaPrevia = categoriaSelect.value;
            form.reset();
            categoriaSelect.value = categoriaPrevia; // conserva la categoría elegida entre dictados sucesivos
            await cargarPendientes();
        } catch {
            errorEl.textContent = 'No se pudo contactar al servidor.';
        }
    }

    const SpeechRecognitionApi = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (micBtn) {
        if (!SpeechRecognitionApi) {
            micBtn.disabled = true;
            micBtn.title = 'Dictado por voz no disponible en este navegador';
        } else {
            const recognition = new SpeechRecognitionApi();
            recognition.lang = 'es-MX';
            recognition.continuous = false;
            recognition.interimResults = false;

            let escuchando = false;

            micBtn.addEventListener('click', () => {
                if (escuchando) {
                    recognition.stop();
                    return;
                }
                recognition.start();
            });

            recognition.addEventListener('start', () => {
                escuchando = true;
                micBtn.classList.add('is-listening');
            });

            recognition.addEventListener('end', () => {
                escuchando = false;
                micBtn.classList.remove('is-listening');
            });

            // Fricción Cero: al terminar de hablar, guarda automáticamente sin
            // requerir un toque adicional en "Agregar".
            recognition.addEventListener('result', async (event) => {
                const texto = event.results[0][0].transcript;
                input.value = texto;
                await crearPendiente();
            });

            recognition.addEventListener('error', () => {
                escuchando = false;
                micBtn.classList.remove('is-listening');
            });
        }
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            await crearPendiente();
        });
    }

    cargarPendientes();
});
