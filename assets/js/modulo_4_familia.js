// assets/js/modulo_4_familia.js — Pte_Hernandez_LaPazBCS — Módulo 4
// Agenda familiar con Alerta de Colisión contra tareas de obra (Módulo 2).

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const form         = document.getElementById('evento-form');
    const errorEl       = document.getElementById('evento-error');
    const colisionEl    = document.getElementById('evento-colision');
    const submitBtn     = document.getElementById('evento-submit');
    const eventosLista   = document.getElementById('eventos-lista');

    function formatearFecha(fechaIso) {
        return new Date(fechaIso).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
    }

    async function cargarEventos() {
        try {
            const res  = await fetch('api/modulo_4_backend.php?accion=listar', { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success' || data.data.eventos.length === 0) {
                eventosLista.innerHTML = '<li>Sin actividades agendadas.</li>';
                return;
            }

            eventosLista.innerHTML = data.data.eventos
                .map((ev) => `
                    <li data-id="${ev.id}">
                        <strong>${ev.titulo}</strong> — ${formatearFecha(ev.fecha_inicio)} a ${formatearFecha(ev.fecha_fin)}
                        <span>(${ev.estatus})</span>
                        ${ev.estatus === 'programada' ? '<button type="button" class="btn-completar-evento">Marcar completada</button>' : ''}
                    </li>
                `)
                .join('');

            eventosLista.querySelectorAll('.btn-completar-evento').forEach((btn) => {
                btn.addEventListener('click', async (event) => {
                    const id = Number(event.target.closest('li').dataset.id);
                    await fetch('api/modulo_4_backend.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ accion: 'completar', id }),
                    });
                    await cargarEventos();
                });
            });
        } catch {
            eventosLista.innerHTML = '<li>No se pudieron cargar las actividades.</li>';
        }
    }

    async function guardarEvento(forzar) {
        const body = {
            accion: 'crear',
            titulo: document.getElementById('titulo').value,
            descripcion: document.getElementById('descripcion').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            fecha_fin: document.getElementById('fecha_fin').value,
            forzar,
        };

        const res  = await fetch('api/modulo_4_backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(body),
        });
        return res.json();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.textContent = '';
        colisionEl.classList.add('is-hidden');
        submitBtn.disabled = true;

        try {
            let data = await guardarEvento(false);

            if (data.status !== 'success') {
                errorEl.textContent = data.message || 'No se pudo agendar el evento.';
                return;
            }

            if (data.data.colision) {
                colisionEl.textContent = `${data.data.mensaje} ¿Deseas agendarlo de todas formas?`;
                colisionEl.classList.remove('is-hidden');

                const confirmar = window.confirm(data.data.mensaje + '\n\n¿Agendar de todas formas?');
                if (!confirmar) {
                    return;
                }

                data = await guardarEvento(true);
                if (data.status !== 'success') {
                    errorEl.textContent = data.message || 'No se pudo agendar el evento.';
                    return;
                }
            }

            form.reset();
            colisionEl.classList.add('is-hidden');
            await cargarEventos();
        } catch {
            errorEl.textContent = 'No se pudo contactar al servidor.';
        } finally {
            submitBtn.disabled = false;
        }
    });

    cargarEventos();
});
