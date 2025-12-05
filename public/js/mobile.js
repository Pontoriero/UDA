/**
 * Mobile Menu Handler
 * Gestisce apertura/chiusura della sidebar su dispositivi mobili
 */

document.addEventListener('DOMContentLoaded', function() {
    // Crea il pulsante menu mobile se non esiste
    if (!document.querySelector('.mobile-menu-toggle')) {
        const menuToggle = document.createElement('button');
        menuToggle.className = 'mobile-menu-toggle';
        menuToggle.innerHTML = '☰';
        menuToggle.setAttribute('aria-label', 'Toggle menu');
        document.body.appendChild(menuToggle);
    }

    // Crea l'overlay mobile se non esiste
    if (!document.querySelector('.mobile-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        document.body.appendChild(overlay);
    }

    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (!menuToggle || !sidebar || !overlay) {
        return; // Non siamo in una pagina con sidebar
    }

    // Toggle menu mobile
    function toggleMobileMenu() {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');

        // Previeni scroll del body quando menu aperto
        if (sidebar.classList.contains('mobile-open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }

    // Chiudi menu mobile
    function closeMobileMenu() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Event listeners
    menuToggle.addEventListener('click', toggleMobileMenu);
    overlay.addEventListener('click', closeMobileMenu);

    // Chiudi menu quando si clicca su un link (solo su mobile)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        });
    });

    // Chiudi menu su resize se diventa desktop
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        }, 250);
    });

    // Gestione swipe per chiudere menu
    let touchStartX = 0;
    let touchEndX = 0;

    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, false);

    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, false);

    function handleSwipe() {
        if (touchEndX < touchStartX - 50) {
            // Swipe left
            closeMobileMenu();
        }
    }
});
