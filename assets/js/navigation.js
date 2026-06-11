const VIEWS = {
    dashboard: 'view-dashboard',
    wifi: 'view-wifi',
    health: 'view-health',
    logs: 'view-logs',
    sims: 'view-sims',
    admin: 'view-admin',
};

const VIEW_TITLES = {
    dashboard: 'Accueil',
    wifi: 'Gestion Wi-Fi',
    health: 'Santé du réseau',
    logs: 'Journaux & alertes',
    sims: 'Simulations',
    admin: 'Administration',
};

const VIEW_LOADERS = {
    dashboard: async () => {
        const { loadMetrics } = await import('./dashboard.js');
        await loadMetrics();
    },
    wifi: async () => {
        const { loadDevices } = await import('./dashboard.js');
        await loadDevices();
    },
    health: async () => {
        const { loadMetrics, loadDevices } = await import('./dashboard.js');
        await Promise.all([loadMetrics(), loadDevices()]);
    },
    logs: async () => {
        const { loadLogs } = await import('./dashboard.js');
        await loadLogs();
    },
    sims: async () => {
        const { loadMetrics, loadLogs } = await import('./dashboard.js');
        await Promise.all([loadMetrics(), loadLogs()]);
    },
    admin: async () => {},
};

let currentView = 'dashboard';

export function initNavigation() {
    const buttons = document.querySelectorAll('.nav-btn');

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.view;
            if (view) showView(view);
        });
    });

    const hash = window.location.hash.replace('#', '');
    if (hash && VIEWS[hash]) {
        showView(hash);
    } else {
        showView('dashboard');
    }

    window.addEventListener('hashchange', () => {
        const h = window.location.hash.replace('#', '');
        if (h && VIEWS[h] && h !== currentView) {
            showView(h, false);
        }
    });
}

export function showView(viewKey, updateHash = true) {
    const sectionId = VIEWS[viewKey];
    if (!sectionId) return;

    currentView = viewKey;

    document.querySelectorAll('.view-section').forEach((el) => {
        const isActive = el.id === sectionId;
        el.classList.toggle('active', isActive);
    });

    document.querySelectorAll('.nav-btn').forEach((btn) => {
        const isActive = btn.dataset.view === viewKey;
        btn.classList.toggle('active', isActive);
        btn.classList.toggle('bg-secondary-container/20', isActive);
        btn.classList.toggle('text-secondary', isActive);
        btn.classList.toggle('border-l-2', isActive);
        btn.classList.toggle('border-secondary', isActive);
        btn.classList.toggle('text-outline', !isActive);

        const icon = btn.querySelector('.material-symbols-outlined');
        if (icon) {
            icon.style.fontVariationSettings = isActive ? "'FILL' 1" : "'FILL' 0";
        }
    });

    const titleEl = document.getElementById('page-title');
    if (titleEl) {
        titleEl.textContent = VIEW_TITLES[viewKey] || 'Monitor_Ω';
    }

    if (updateHash) {
        const newHash = `#${viewKey}`;
        if (window.location.hash !== newHash) {
            history.replaceState(null, '', newHash);
        }
    }

    const main = document.querySelector('main');
    if (main) main.scrollTop = 0;

    const loader = VIEW_LOADERS[viewKey];
    if (loader) {
        loader().catch((err) => console.error('Erreur chargement vue:', err));
    }
}

export function getCurrentView() {
    return currentView;
}
