// assets/js/admin_obras.js — Pte_Hernandez_LaPazBCS — Alta y edición de Obras

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const form        = document.getElementById('obra-form');
    const formTitulo   = document.getElementById('obra-form-titulo');
    const errorEl       = document.getElementById('obra-error');
    const idInput       = document.getElementById('obra-id');
    const submitBtn     = document.getElementById('obra-submit');
    const cancelarBtn   = document.getElementById('obra-cancelar');
    const obrasLista     = document.getElementById('obras-lista');

    function entrarModoEdicion(obra) {
        idInput.value = obra.id;
        document.getElementById('nombre').value = obra.nombre;
        document.getElementById('descripcion').value = obra.descripcion || '';
        document.getElementById('ubicacion').value = obra.ubicacion || '';
        document.getElementById('estatus').value = obra.estatus;
        formTitulo.textContent = `Editando: ${obra.nombre}`;
        submitBtn.textContent = 'Actualizar Obra';
        cancelarBtn.classList.remove('is-hidden');
    }

    function salirModoEdicion() {
        idInput.value = '';
        form.reset();
        formTitulo.textContent = 'Nueva Obra';
        submitBtn.textContent = 'Guardar Obra';
        cancelarBtn.classList.add('is-hidden');
    }

    async function cargarObras() {
        try {
            const res  = await fetch('api/admin_obras_backend.php?accion=listar', { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success' || data.data.obras.length === 0) {
                obrasLista.innerHTML = '<li>Sin obras registradas.</li>';
                return;
            }

            obrasLista.innerHTML = data.data.obras
                .map((o) => `
                    <li data-obra='${JSON.stringify(o).replace(/'/g, '&#39;')}'>
                        <span>${o.nombre} — ${o.estatus}</span>
                        <button type="button" class="btn-editar-obra">Editar</button>
                    </li>
                `)
                .join('');

            obrasLista.querySelectorAll('.btn-editar-obra').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    const obra = JSON.parse(event.target.closest('li').dataset.obra);
                    entrarModoEdicion(obra);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        } catch {
            obrasLista.innerHTML = '<li>No se pudieron cargar las obras.</li>';
        }
    }

    cancelarBtn.addEventListener('click', salirModoEdicion);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorEl.textContent = '';
        submitBtn.disabled = true;

        const esEdicion = idInput.value !== '';
        const body = {
            accion: esEdicion ? 'actualizar' : 'crear',
            nombre: document.getElementById('nombre').value,
            descripcion: document.getElementById('descripcion').value,
            ubicacion: document.getElementById('ubicacion').value,
            estatus: document.getElementById('estatus').value,
        };
        if (esEdicion) {
            body.id = Number(idInput.value);
        }

        try {
            const res  = await fetch('api/admin_obras_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(body),
            });
            const data = await res.json();

            if (data.status !== 'success') {
                errorEl.textContent = data.message || 'No se pudo guardar la obra.';
                return;
            }

            salirModoEdicion();
            await cargarObras();
        } catch {
            errorEl.textContent = 'No se pudo contactar al servidor.';
        } finally {
            submitBtn.disabled = false;
        }
    });

    cargarObras();
});
