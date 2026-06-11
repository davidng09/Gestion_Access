<div>
    <div class="flex items-center gap-2 mb-4">
        <div class="w-1 h-4 bg-secondary"></div>
        <h3 class="font-headline-md text-on-surface">Vue d'ensemble du réseau</h3>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="text-on-surface-variant font-label-caps text-[10px] uppercase">1. Statut réseau</span>
                <span class="material-symbols-outlined text-secondary opacity-50">router</span>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <span class="network-status-metric text-3xl font-data-mono text-green-400">En ligne</span>
                <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse network-status-pulse"></div>
            </div>
            <p class="text-[10px] text-outline">Tous les systèmes opérationnels</p>
        </div>
        <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="text-on-surface-variant font-label-caps text-[10px] uppercase">2. Appareils</span>
                <span class="material-symbols-outlined text-secondary opacity-50">devices</span>
            </div>
            <div class="flex items-end gap-2 mt-2">
                <span class="active-users-metric text-3xl font-data-mono text-on-surface">—</span>
                <span class="text-sm font-body-sm text-on-surface-variant mb-1">utilisateurs actifs</span>
            </div>
            <p class="text-[10px] text-outline devices-detail-metric">Chargement...</p>
        </div>
        <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl flex flex-col gap-2">
            <div class="flex justify-between items-start">
                <span class="text-on-surface-variant font-label-caps text-[10px] uppercase">3. Trafic</span>
                <span class="material-symbols-outlined text-secondary opacity-50">bar_chart</span>
            </div>
            <div class="flex items-end gap-2 mt-2">
                <span class="traffic-metric text-3xl font-data-mono text-on-surface" id="traffic-value">—</span>
                <span class="text-sm font-body-sm text-secondary mb-1">Mbps</span>
            </div>
            <div class="flex gap-4 text-[10px]">
                <span class="text-green-400 traffic-up-metric">↑ Montée : — Mbps</span>
                <span class="text-error traffic-down-metric">↓ Descente : — Mbps</span>
            </div>
        </div>
    </div>
</div>
