// assets/js/modulo_2_planificacion.js — Pte_Hernandez_LaPazBCS — Módulo 2
// Persistencia real (tareas) + generación dinámica de enlaces wa.me.

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const obraSelect     = document.getElementById('obra-select');
    const fechaSelect     = document.getElementById('fecha-select');
    const asignadoSelect = document.getElementById('id_usuario_asignado');
    const tareaForm       = document.getElementById('tarea-form');
    const tareaError      = document.getElementById('tarea-error');
    const tareaSubmit     = document.getElementById('tarea-submit');
    const tareasLista     = document.getElementById('tareas-lista');

    let idObraActual = null;

    fechaSelect.value = new Date().toISOString().slice(0, 10);

    async function cargarObras() {
        try {
            const res  = await fetch('api/obras_listar.php', { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success' || data.data.obras.length === 0) {
                obraSelect.innerHTML = '<option>Sin obras disponibles</option>';
                return;
            }

            obraSelect.innerHTML = data.data.obras
                .map((obra) => `<option value="${obra.id}">${obra.nombre}</option>`)
                .join('');

            idObraActual = data.data.obras[0].id;
            await cargarTareas();
        } catch {
            obraSelect.innerHTML = '<option>Error de conexión</option>';
        }
    }

    async function cargarUsuarios() {
        try {
            const res  = await fetch('api/usuarios_listar.php', { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success') {
                return;
            }

            asignadoSelect.innerHTML = data.data.usuarios
                .map((u) => `<option value="${u.id}">${u.nombre} (${u.rol})</option>`)
                .join('');
        } catch {
            asignadoSelect.innerHTML = '<option>Error de conexión</option>';
        }
    }

    function botonWhatsapp(tarea) {
        if (!tarea.telefono_contacto) {
            return '';
        }
        const telefono = tarea.telefono_contacto.replace(/\D/g, '');
        const mensaje   = encodeURIComponent(`Tarea asignada: ${tarea.descripcion}`);
        const url       = `https://wa.me/52${telefono}?text=${mensaje}`;
        return `<a class="button-like btn-whatsapp" href="${url}" target="_blank" rel="noopener noreferrer">WhatsApp</a>`;
    }

    async function cargarTareas() {
        if (!idObraActual) {
            return;
        }
        const fecha = fechaSelect.value;
        const res   = await fetch(`api/modulo_2_backend.php?accion=listar&id_obra=${idObraActual}&fecha=${fecha}`, { credentials: 'include' });
        const data  = await res.json();

        if (data.status !== 'success') {
            tareasLista.innerHTML = '<li>No se pudieron cargar las tareas.</li>';
            return;
        }

        if (data.data.tareas.length === 0) {
            tareasLista.innerHTML = '<li>Sin tareas para esta fecha.</li>';
            return;
        }

        tareasLista.innerHTML = data.data.tareas
            .map((t) => `
                <li class="tarea-item" data-id="${t.id}">
                    <div class="tarea-item__row">
                        <strong>${t.asignado_a}</strong>
                        <span>${t.estatus}</span>
                    </div>
                    <span>${t.descripcion}</span>
                    <div class="tarea-item__acciones">
                        <select class="tarea-estatus-select" data-id="${t.id}">
                            <option value="pendiente" ${t.estatus === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="en_proceso" ${t.estatus === 'en_proceso' ? 'selected' : ''}>En proceso</option>
                            <option value="completada" ${t.estatus === 'completada' ? 'selected' : ''}>Completada</option>
                        </select>
                        ${botonWhatsapp(t)}
                    </div>
                </li>
            `)
            .join('');

        tareasLista.querySelectorAll('.tarea-estatus-select').forEach((select) => {
            select.addEventListener('change', async (event) => {
                await fetch('api/modulo_2_backend.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        accion: 'cambiar_estatus',
                        id: Number(event.target.dataset.id),
                        estatus: event.target.value,
                    }),
                });
                await cargarTareas();
            });
        });
    }

    obraSelect.addEventListener('change', async (event) => {
        idObraActual = event.target.value;
        await cargarTareas();
    });
    fechaSelect.addEventListener('change', cargarTareas);

    tareaForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        tareaError.textContent = '';

        if (!idObraActual) {
            tareaError.textContent = 'Selecciona una obra primero.';
            return;
        }

        tareaSubmit.disabled = true;

        try {
            const res  = await fetch('api/modulo_2_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    accion: 'crear',
                    id_obra: idObraActual,
                    id_usuario_asignado: asignadoSelect.value,
                    descripcion: document.getElementById('descripcion').value,
                    telefono_contacto: document.getElementById('telefono_contacto').value,
                    fecha_asignacion: fechaSelect.value,
                }),
            });
            const data = await res.json();

            if (data.status !== 'success') {
                tareaError.textContent = data.message || 'No se pudo asignar la tarea.';
                return;
            }

            tareaForm.reset();
            await cargarTareas();
        } catch {
            tareaError.textContent = 'No se pudo contactar al servidor.';
        } finally {
            tareaSubmit.disabled = false;
        }
    });

    cargarObras();
    cargarUsuarios();
});
