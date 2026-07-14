// assets/js/dashboard.js — Pte_Hernandez_LaPazBCS
// Menú hamburguesa + Botón de Pánico (Modo Oculto) + Banner de instalación PWA.

document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn  = document.getElementById('hamburger-toggle');
    const hamburgerMenu = document.getElementById('hamburger-menu');

    if (hamburgerBtn && hamburgerMenu) {
        hamburgerBtn.addEventListener('click', () => {
            const isOpen = hamburgerMenu.classList.toggle('is-open');
            hamburgerBtn.setAttribute('aria-expanded', String(isOpen));
        });
    }

    // ── Botón de Pánico (Modo Oculto: Módulos 3, 4 y 5) ─────────────────────
    const panicBtn = document.getElementById('panic-btn');
    if (panicBtn) {
        const modulosPrivados = document.querySelectorAll('.modulo-privado');
        let modoOculto = false;
        const DURACION_ANIMACION_MS = 250;

        panicBtn.addEventListener('click', () => {
            modoOculto = !modoOculto;

            modulosPrivados.forEach((modulo) => {
                if (modoOculto) {
                    modulo.classList.add('is-hiding');
                    setTimeout(() => modulo.classList.add('is-hidden'), DURACION_ANIMACION_MS);
                } else {
                    modulo.classList.remove('is-hidden');
                    // Forzar reflow antes de quitar 'is-hiding' para que la transición se reproduzca.
                    void modulo.offsetWidth;
                    modulo.classList.remove('is-hiding');
                }
            });
        });
    }

    // ── Banner de instalación PWA ────────────────────────────────────────────
    const installBanner  = document.getElementById('pwa-install-banner');
    const installDismiss = document.getElementById('pwa-install-dismiss');
    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        if (installBanner) {
            installBanner.classList.remove('is-hidden');
        }
    });

    if (installBanner) {
        installBanner.addEventListener('click', async (event) => {
            if (event.target === installDismiss || !deferredPrompt) {
                return;
            }
            installBanner.classList.add('is-hidden');
            await deferredPrompt.prompt();
            deferredPrompt = null;
        });
    }

    if (installDismiss) {
        installDismiss.addEventListener('click', (event) => {
            event.stopPropagation();
            installBanner.classList.add('is-hidden');
        });
    }

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        if (installBanner) {
            installBanner.classList.add('is-hidden');
        }
    });
});
