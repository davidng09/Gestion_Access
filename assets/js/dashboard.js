import * as api from './api.js';
import {
    showToast,
    escapeHtml,
    deviceTypeIcon,
    signalIcon,
    statusBadge,
    logSeverityColor,
    formatTraffic,
    translateEventType,
    translateNetworkStatus,
} from './ui.js';

let devices = [];
let searchQuery = '';

function dataSourceBadge(source) {
    if (source === 'real') {
        return { class: 'bg-green-500/10 text-green-400 border border-green-500/20', label: 'Réel' };
    }
    return { class: 'text-outline', label: '—' };
}

export async function loadAgentStatus() {
    const status = await api.getAgentStatus();
    renderAgentStatus(status);
    return status;
}

function renderAgentStatus(status) {
    const badge = document.getElementById('agent-status-badge');
    const lastPing = document.getElementById('agent-last-ping');
    const ip = document.getElementById('agent-ip');
    const clients = document.getElementById('agent-clients-count');

    if (!badge) return;

    if (status.connected) {
        badge.textContent = 'Connecté';
        badge.className = 'px-2 py-0.5 rounded text-[10px] uppercase bg-green-500/10 text-green-400 border border-green-500/20';
    } else {
        badge.textContent = 'Déconnecté';
        badge.className = 'px-2 py-0.5 rounded text-[10px] uppercase bg-error/10 text-error border border-error/20';
    }

    if (lastPing) {
        if (status.last_ping) {
            const secs = status.seconds_since_ping ?? 0;
            lastPing.textContent = `${status.last_ping} (il y a ${secs}s)`;
        } else {
            lastPing.textContent = 'Aucun signal';
        }
    }
    if (ip) ip.textContent = status.ip_address || '—';
    if (clients) clients.textContent = String(status.clients_count ?? 0);
}

export function setSearchQuery(query) {
    searchQuery = query.toLowerCase().trim();
    renderDeviceTable();
}

export async function loadDevices() {
    devices = await api.getDevices();
    renderDeviceTable();
    return devices;
}

export async function loadMetrics() {
    const metrics = await api.getMetrics();
    renderMetrics(metrics);
    return metrics;
}

export async function loadLogs() {
    const logs = await api.getLogs();
    renderLogs(logs);
    return logs;
}

function renderMetrics(m) {
    const isOnline = m.network_status === 'online';
    const statusText = translateNetworkStatus(m.network_status);
    const statusClass = `text-3xl font-data-mono ${isOnline ? 'text-green-400' : 'text-error'}`;
    const alertEl = document.getElementById('alert-banner');

    document.querySelectorAll('.network-status-metric').forEach((el) => {
        el.textContent = statusText;
        el.className = `network-status-metric ${statusClass}`;
    });

    document.querySelectorAll('.active-users-metric').forEach((el) => {
        el.textContent = m.active_users;
    });

    document.querySelectorAll('.devices-detail-metric').forEach((el) => {
        el.textContent = `${m.laptops_count} portables, ${m.mobile_count} mobiles`;
    });

    document.querySelectorAll('.traffic-metric').forEach((el) => {
        el.textContent = formatTraffic(m.traffic_mbps);
    });

    document.querySelectorAll('.traffic-up-metric').forEach((el) => {
        el.textContent = `↑ Montée : ${m.traffic_up_mbps} Mbps`;
    });

    document.querySelectorAll('.traffic-down-metric').forEach((el) => {
        el.textContent = `↓ Descente : ${m.traffic_down_mbps} Mbps`;
    });

    if (alertEl) {
        alertEl.classList.toggle('hidden', !m.alert_active);
    }

    const healthStatus = document.getElementById('health-status');
    const healthTraffic = document.getElementById('health-traffic');
    const healthAlert = document.getElementById('health-alert');
    const healthActive = document.getElementById('health-active-users');
    const healthLaptops = document.getElementById('health-laptops');
    const healthMobiles = document.getElementById('health-mobiles');

    if (healthStatus) healthStatus.textContent = statusText.toUpperCase();
    if (healthTraffic) healthTraffic.textContent = `${m.traffic_mbps} Mbps`;
    if (healthAlert) healthAlert.textContent = m.alert_active ? 'ACTIVE' : 'Aucune';
    if (healthActive) healthActive.textContent = m.active_users;
    if (healthLaptops) healthLaptops.textContent = m.laptops_count;
    if (healthMobiles) healthMobiles.textContent = m.mobile_count;
}

function filteredDevices() {
    if (!searchQuery) return devices;
    return devices.filter((d) =>
        d.hostname.toLowerCase().includes(searchQuery) ||
        d.ip_address.toLowerCase().includes(searchQuery) ||
        d.mac_address.toLowerCase().includes(searchQuery)
    );
}

function renderDeviceTable() {
    const tbodies = document.querySelectorAll('.device-table-body');
    if (!tbodies.length) return;

    const list = filteredDevices();
    const html = list.length === 0
        ? `<tr><td colspan="8" class="px-4 py-6 text-center text-outline text-sm">Aucun appareil détecté — lancez l'agent Android sur le hotspot</td></tr>`
        : list.map((d) => {
        const badge = statusBadge(d.status, d.is_online);
        const source = dataSourceBadge(d.data_source || 'real');
        const sig = signalIcon(d.signal_level);
        const isBlocked = d.status === 'blocked';
        const checked = d.is_online && !isBlocked ? 'checked' : '';
        const actionBtn = isBlocked
            ? `<button class="text-[10px] text-green-400 hover:bg-green-500/10 px-2 py-1 rounded transition-colors uppercase font-bold border border-green-500/20 device-unblock" data-id="${d.id}" data-hostname="${escapeHtml(d.hostname)}">Débloquer</button>`
            : `<button class="text-[10px] text-error hover:bg-error/10 px-2 py-1 rounded transition-colors uppercase font-bold border border-error/20 device-block" data-id="${d.id}" data-hostname="${escapeHtml(d.hostname)}">Bloquer</button>`;

        return `
            <tr class="hover:bg-surface-container-high/20 transition-colors" data-device-id="${d.id}">
                <td class="px-4 py-3">
                    <span class="material-symbols-outlined text-outline">${deviceTypeIcon(d.device_type)}</span>
                </td>
                <td class="px-4 py-3 font-semibold">${escapeHtml(d.hostname)}</td>
                <td class="px-4 py-3 font-data-mono text-secondary">${escapeHtml(d.ip_address)}</td>
                <td class="px-4 py-3 font-data-mono">${escapeHtml(d.mac_address)}</td>
                <td class="px-4 py-3">
                    <span class="material-symbols-outlined ${sig.class}">${sig.icon}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="status-badge px-2 py-0.5 rounded text-[10px] ${badge.class}">${badge.label}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded text-[10px] ${source.class}">${source.label}</span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <label class="relative inline-flex items-center cursor-pointer ${isBlocked ? 'opacity-50 pointer-events-none' : ''}">
                            <input type="checkbox" class="sr-only peer device-toggle" data-id="${d.id}" ${checked} />
                            <div class="w-7 h-4 bg-outline-variant rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-secondary after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                        </label>
                        ${actionBtn}
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    tbodies.forEach((tbody) => { tbody.innerHTML = html; });
}

function renderLogs(logs) {
    const containers = document.querySelectorAll('.timeline-container');
    if (!containers.length) return;

    const html = logs.map((log) => {
        const color = logSeverityColor(log.severity);
        const msgClass = log.severity === 'error' ? 'text-error' : log.severity === 'warning' ? 'text-secondary' : 'text-on-surface';
        return `
            <div class="flex gap-3 relative before:absolute before:left-[7px] before:top-4 before:bottom-0 before:w-[1px] before:bg-outline-variant last:before:hidden fade-in-entry">
                <div class="w-4 h-4 rounded-full ${color} border-2 border-background z-10"></div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-on-surface text-xs font-data-mono">${escapeHtml(log.event_time)}</span>
                        <span class="text-on-surface-variant text-[10px]">- ${escapeHtml(translateEventType(log.event_type))}</span>
                    </div>
                    <p class="text-[10px] ${msgClass} font-semibold">${escapeHtml(log.message)}</p>
                    <span class="text-[8px] text-outline mt-1 italic">Mise à jour temps réel</span>
                </div>
            </div>
        `;
    }).join('');

    containers.forEach((c) => { c.innerHTML = html; });
}

export async function handleToggle(checkbox) {
    const id = parseInt(checkbox.dataset.id, 10);
    const isOnline = checkbox.checked;

    try {
        await api.toggleDevice(id, isOnline);
        const hostname = devices.find((d) => d.id === id)?.hostname || `Appareil #${id}`;
        showToast(isOnline ? `${hostname} reconnecté` : `${hostname} hors ligne`);
        await Promise.all([loadDevices(), loadMetrics(), loadLogs()]);
    } catch (err) {
        checkbox.checked = !isOnline;
        showToast(err.message, 'error');
    }
}

export async function handleBlock(btn) {
    const id = parseInt(btn.dataset.id, 10);
    const hostname = btn.dataset.hostname || `Appareil #${id}`;

    try {
        await api.blockDevice(id);
        showToast(`Protocole sécurité : ${hostname} bloqué`, 'error');
        await Promise.all([loadDevices(), loadMetrics(), loadLogs()]);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

export async function handleUnblock(btn) {
    const id = parseInt(btn.dataset.id, 10);
    const hostname = btn.dataset.hostname || `Appareil #${id}`;

    try {
        await api.unblockDevice(id);
        showToast(`${hostname} débloqué`);
        await Promise.all([loadDevices(), loadMetrics(), loadLogs()]);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

export async function refreshAll() {
    await Promise.all([loadDevices(), loadMetrics(), loadLogs(), loadAgentStatus()]);
}
