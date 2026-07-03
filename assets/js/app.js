import { POLL_INTERVAL_MS } from './config.js';
import { initNavigation } from './navigation.js';
import {
    refreshAll,
    setSearchQuery,
    handleToggle,
    handleBlock,
    handleUnblock,
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
        const blockBtn = e.target.closest('.device-block');
        if (blockBtn) handleBlock(blockBtn);

        const unblockBtn = e.target.closest('.device-unblock');
        if (unblockBtn) handleUnblock(unblockBtn);
    });

    try {
        await refreshAll();
    } catch (err) {
        console.error('Erreur chargement initial:', err);
    }

    setInterval(async () => {
        try {
            const { loadMetrics, loadLogs, loadAgentStatus, loadDevices } = await import('./dashboard.js');
            await Promise.all([loadMetrics(), loadLogs(), loadAgentStatus(), loadDevices()]);
        } catch (_) { /* polling silencieux */ }
    }, POLL_INTERVAL_MS);
}

document.addEventListener('DOMContentLoaded', init);
