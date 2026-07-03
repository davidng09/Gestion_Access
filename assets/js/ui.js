export function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const color = type === 'error'
        ? 'bg-error-container border-error'
        : 'bg-surface-container-high border-secondary';

    toast.className = `${color} border p-3 rounded-lg shadow-2xl flex items-center gap-3 fade-in-entry min-w-[200px]`;
    toast.innerHTML = `
        <span class="material-symbols-outlined text-sm ${type === 'error' ? 'text-error' : 'text-secondary'}">
            ${type === 'error' ? 'report' : 'check_circle'}
        </span>
        <span class="text-xs font-bold">${escapeHtml(message)}</span>
    `;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'transition-opacity', 'duration-500');
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

export function deviceTypeIcon(type) {
    const icons = {
        laptop: 'laptop',
        mobile: 'smartphone',
        desktop: 'desktop_windows',
        unknown: 'device_unknown',
    };
    return icons[type] || 'devices';
}

export function signalIcon(level) {
    if (level >= 4) return { icon: 'signal_cellular_4_bar', class: 'text-green-400' };
    if (level === 3) return { icon: 'signal_cellular_3_bar', class: 'text-on-surface-variant' };
    if (level === 2) return { icon: 'signal_cellular_2_bar', class: 'text-outline' };
    return { icon: 'signal_cellular_1_bar', class: 'text-error' };
}

export function statusBadge(status, isOnline = true) {
    if (status === 'blocked') {
        return {
            label: 'Bloqué',
            class: 'bg-error/10 text-error border border-error/20',
        };
    }
    if (!isOnline) {
        return {
            label: 'Inactif',
            class: 'bg-outline-variant/30 text-outline border border-outline-variant',
        };
    }
    if (status === 'guest') {
        return {
            label: 'Invité',
            class: 'bg-orange-500/10 text-orange-400 border border-orange-500/20',
        };
    }
    return {
        label: 'Autorisé',
        class: 'bg-green-500/10 text-green-400 border border-green-500/20',
    };
}

const EVENT_TYPE_FR = {
    'New connection': 'Nouvelle connexion',
    'Blocked access': 'Accès bloqué',
    'Peak Traffic Detected': 'Pic de trafic détecté',
    'Status Reconnected': 'Reconnexion',
    'Status Offline': 'Hors ligne',
    'Security Block': 'Blocage sécurité',
    'ALERT': 'ALERTE',
    'Nouvelle connexion': 'Nouvelle connexion',
    'Accès bloqué': 'Accès bloqué',
    'Pic de trafic détecté': 'Pic de trafic détecté',
    'Reconnexion': 'Reconnexion',
    'Hors ligne': 'Hors ligne',
    'Blocage sécurité': 'Blocage sécurité',
    'Déblocage': 'Déblocage',
    'Scan réseau': 'Scan réseau',
    'ALERTE': 'ALERTE',
};

export function translateEventType(type) {
    return EVENT_TYPE_FR[type] || type;
}

export function translateNetworkStatus(status) {
    const map = {
        online: 'En ligne',
        degraded: 'Dégradé',
        offline: 'Hors ligne',
    };
    return map[status] || status;
}

export function logSeverityColor(severity) {
    if (severity === 'error') return 'bg-error';
    if (severity === 'warning') return 'bg-secondary';
    return 'bg-green-500';
}

export function formatTraffic(mbps) {
    if (mbps >= 1000) return '1.2k';
    return String(mbps);
}
