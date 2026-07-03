import { API_BASE } from './config.js';

async function request(path, options = {}) {
    const res = await fetch(`${API_BASE}/${path}`, {
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        ...options,
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        throw new Error(data.error || `Erreur HTTP ${res.status}`);
    }

    return data;
}

export function login(username, password) {
    return request('auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
    });
}

export function getDevices() {
    return request('devices.php');
}

export function toggleDevice(id, isOnline) {
    return request(`devices.php?id=${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ is_online: isOnline }),
    });
}

export function blockDevice(id) {
    return request(`devices.php?id=${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ action: 'block' }),
    });
}

export function unblockDevice(id) {
    return request(`devices.php?id=${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ action: 'unblock' }),
    });
}

export function getAgentStatus() {
    return request('agent.php');
}

export function getMetrics() {
    return request('metrics.php');
}

export function getLogs(limit = 50) {
    return request(`logs.php?limit=${limit}`);
}
