import { POLL_INTERVAL_MS } from './config.js';
import { initNavigation } from './navigation.js';
import {
    refreshAll,
    setSearchQuery,
    handleToggle,
    handleBlock,
    handleSimulateTraffic,
    handleSimulateIntrusion,
} from './dashboard.js';

async function init() {
    initNavigation();

    document.querySelectorAll('.device-search').forEach((input) => {
        input.addEventListener('input', (e) => {
            setSearchQuery(e.target.value);
            document.querySelectorAll('.device-search').forEach((el) => {
                if (el !== e.target) el.value = e.target.value;
            });
        });
    });

    document.body.addEventListener('change', (e) => {
        if (e.target.classList.contains('device-toggle')) {
            handleToggle(e.target);
        }
    });

    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.device-block');
        if (btn) handleBlock(btn);
    });

    document.getElementById('btn-sim-traffic')?.addEventListener('click', handleSimulateTraffic);
    document.getElementById('btn-sim-intrusion')?.addEventListener('click', handleSimulateIntrusion);

    document.getElementById('btn-sim-traffic-sims')?.addEventListener('click', handleSimulateTraffic);
    document.getElementById('btn-sim-intrusion-sims')?.addEventListener('click', handleSimulateIntrusion);

    try {
        await refreshAll();
    } catch (err) {
        console.error('Erreur chargement initial:', err);
    }

    setInterval(async () => {
        try {
            const { loadMetrics, loadLogs } = await import('./dashboard.js');
            await Promise.all([loadMetrics(), loadLogs()]);
        } catch (_) { /* polling silencieux */ }
    }, POLL_INTERVAL_MS);
}

document.addEventListener('DOMContentLoaded', init);
