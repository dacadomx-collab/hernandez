// assets/js/modulo_1_cuentas.js — Pte_Hernandez_LaPazBCS — Módulo 1: Cuentas Contables

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const obraSelect       = document.getElementById('obra-select');
    const gastoForm        = document.getElementById('gasto-form');
    const gastoError       = document.getElementById('gasto-error');
    const gastoSubmit      = document.getElementById('gasto-submit');
    const gastosLista      = document.getElementById('gastos-lista');
    const semaforoGrupos   = document.querySelectorAll('.semaforo-grupo');
    const presupuestoSelect = document.getElementById('id_presupuesto');
    const presupuestoEstado = document.getElementById('presupuesto-estado');

    let idObraActual = null;

    const ETAPA_ETIQUETAS = {
        obras_base: 'Obras Base',
        obra_negra: 'Obra Negra',
        terminacion: 'Terminación',
    };

    function formatearMonto(valor) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor);
    }

    async function cargarObras() {
        try {
            const res  = await fetch('api/obras_listar.php', { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success') {
                obraSelect.innerHTML = '<option>No se pudieron cargar las obras</option>';
                return;
            }

            obraSelect.innerHTML = data.data.obras
                .map((obra) => `<option value="${obra.id}">${obra.nombre}</option>`)
                .join('');

            if (data.data.obras.length > 0) {
                idObraActual = data.data.obras[0].id;
                await Promise.all([cargarSemaforos(), cargarGastos(), cargarPresupuestos()]);
            }
        } catch {
            obraSelect.innerHTML = '<option>Error de conexión</option>';
        }
    }

    async function cargarPresupuestos() {
        if (!idObraActual) {
            return;
        }
        try {
            const res  = await fetch(`api/presupuestos_backend.php?accion=listar&id_obra=${idObraActual}`, { credentials: 'include' });
            const data = await res.json();

            if (data.status !== 'success' || data.data.presupuestos.length === 0) {
                presupuestoSelect.innerHTML = '<option value="">Sin conceptos de presupuesto</option>';
                presupuestoEstado.innerHTML = '<p>Esta obra aún no tiene conceptos de presupuesto registrados.</p>';
                return;
            }

            const presupuestos = data.data.presupuestos;

            presupuestoSelect.innerHTML = presupuestos
                .map((p) => `<option value="${p.id}">${ETAPA_ETIQUETAS[p.etapa] || p.etapa} — ${p.concepto}</option>`)
                .join('');

            const porEtapa = presupuestos.reduce((acc, p) => {
                (acc[p.etapa] ||= []).push(p);
                return acc;
            }, {});

            presupuestoEstado.innerHTML = Object.keys(ETAPA_ETIQUETAS)
                .filter((etapa) => porEtapa[etapa])
                .map((etapa) => `
                    <div class="presupuesto-etapa">
                        <h3 class="presupuesto-etapa__titulo">${ETAPA_ETIQUETAS[etapa]}</h3>
                        ${porEtapa[etapa].map((p) => {
                            const pct = p.monto_objetivo > 0 ? (p.monto_gastado / p.monto_objetivo) * 100 : 0;
                            const estadoClase = p.monto_gastado > p.monto_objetivo
                                ? 'is-over'
                                : (pct >= 90 ? 'is-warning' : 'is-ok');
                            const anchoBarra = Math.min(pct, 100);
                            return `
                                <div class="presupuesto-item">
                                    <div class="presupuesto-item__header">
                                        <span class="presupuesto-item__concepto">${p.concepto}</span>
                                        <span class="presupuesto-item__montos">${formatearMonto(p.monto_gastado)} / ${formatearMonto(p.monto_objetivo)}</span>
                                    </div>
                                    <div class="barra-progreso">
                                        <div class="barra-progreso__fill ${estadoClase}" style="--progreso: ${anchoBarra}%"></div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `)
                .join('');
        } catch {
            presupuestoEstado.innerHTML = '<p>No se pudo cargar el estado de presupuesto.</p>';
        }
    }

    async function cargarSemaforos() {
        if (!idObraActual) {
            return;
        }
        const res  = await fetch(`api/permisos_get.php?id_obra=${idObraActual}`, { credentials: 'include' });
        const data = await res.json();

        if (data.status !== 'success') {
            return;
        }

        semaforoGrupos.forEach((grupo) => {
            const campo = grupo.dataset.campo;
            const valorActual = data.data[campo];
            grupo.querySelectorAll('.semaforo-dot').forEach((dot) => {
                dot.classList.toggle('is-activo', dot.dataset.valor === valorActual);
            });
        });
    }

    async function actualizarSemaforo(campo, valor) {
        await fetch('api/permisos_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id_obra: idObraActual, campo, valor }),
        });
        await cargarSemaforos();
    }

    async function cargarGastos() {
        if (!idObraActual) {
            return;
        }
        const res  = await fetch(`api/gastos_listar.php?id_obra=${idObraActual}`, { credentials: 'include' });
        const data = await res.json();

        if (data.status !== 'success') {
            gastosLista.innerHTML = '<li>No se pudieron cargar los gastos.</li>';
            return;
        }

        if (data.data.gastos.length === 0) {
            gastosLista.innerHTML = '<li>Sin gastos registrados.</li>';
            return;
        }

        gastosLista.innerHTML = data.data.gastos
            .map((g) => `<li>${g.fecha_gasto} — ${g.concepto} — $${g.monto} (${g.registrado_por})</li>`)
            .join('');
    }

    obraSelect.addEventListener('change', async (event) => {
        idObraActual = event.target.value;
        await Promise.all([cargarSemaforos(), cargarGastos(), cargarPresupuestos()]);
    });

    semaforoGrupos.forEach((grupo) => {
        const campo = grupo.dataset.campo;
        grupo.querySelectorAll('.semaforo-dot').forEach((dot) => {
            dot.addEventListener('click', () => actualizarSemaforo(campo, dot.dataset.valor));
        });
    });

    gastoForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        gastoError.textContent = '';

        if (!idObraActual) {
            gastoError.textContent = 'Selecciona una obra primero.';
            return;
        }

        gastoSubmit.disabled = true;

        const formData = new FormData(gastoForm);
        formData.append('id_obra', idObraActual);

        try {
            const res  = await fetch('api/gastos_create.php', {
                method: 'POST',
                credentials: 'include',
                body: formData,
            });
            const data = await res.json();

            if (data.status !== 'success') {
                gastoError.textContent = data.message || 'No se pudo registrar el gasto.';
                gastoSubmit.disabled = false;
                return;
            }

            gastoForm.reset();
            await Promise.all([cargarGastos(), cargarPresupuestos()]);
        } catch {
            gastoError.textContent = 'No se pudo contactar al servidor.';
        } finally {
            gastoSubmit.disabled = false;
        }
    });

    cargarObras();
});
