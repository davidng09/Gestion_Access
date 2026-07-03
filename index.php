<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth_guard.php';

$adminName = htmlspecialchars($currentAdmin['full_name'] ?? 'Administrateur', ENT_QUOTES, 'UTF-8');
$adminRole = htmlspecialchars($currentAdmin['role_level'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminAvatar = htmlspecialchars($currentAdmin['avatar_url'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html class="dark" lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Monitor_Ω — Tableau de bord réseau</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=JetBrains+Mono:wght@400;500&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "on-background": "#e5e2e2", "outline-variant": "#30363D", "on-error": "#690005",
              "tertiary": "#c6c6c6", "secondary": "#adc7ff", "on-secondary": "#002e68",
              "on-primary-container": "#797b83", "background": "#0B0E14", "error-container": "#93000a",
              "surface-container-lowest": "#0e0e0f", "on-surface": "#e5e2e2", "surface-container-high": "#2a2a2a",
              "secondary-container": "#4a8eff", "surface": "#131314", "on-surface-variant": "#c6c6cb",
              "on-error-container": "#ffdad6", "surface-dim": "#131314", "primary": "#c4c6cf",
              "primary-container": "#0b0e14", "surface-container": "#201f20", "error": "#ffb4ab",
              "surface-container-low": "#1c1b1c", "outline": "#909095"
            },
            spacing: { "stack-sm": "8px", "margin-mobile": "16px", "stack-md": "16px", "sidebar-width": "72px" },
            fontFamily: {
              "body-md": ["Inter"], "label-caps": ["Inter"], "body-sm": ["Inter"],
              "headline-md": ["Inter"], "data-mono": ["JetBrains Mono"]
            },
            fontSize: {
              "body-md": ["16px", {"lineHeight": "24px"}], "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "700"}],
              "body-sm": ["14px", {"lineHeight": "20px"}], "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
              "data-mono": ["13px", {"lineHeight": "18px", "letterSpacing": "-0.02em"}]
            }
          }
        }
      }
    </script>
    <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="font-body-md overflow-hidden">

<nav class="fixed left-0 top-0 h-full w-sidebar-width z-50 flex flex-col bg-background border-r border-outline-variant">
    <div class="h-[72px] w-[72px] flex items-center justify-center bg-black border-b border-outline-variant">
        <span class="text-secondary font-bold text-lg font-data-mono omega-glow">Ω</span>
    </div>
    <div class="flex flex-col items-center py-stack-md gap-stack-md flex-grow">
        <button type="button" class="nav-btn flex flex-col items-center gap-1 group w-full py-2 active:scale-95 duration-150" data-view="dashboard">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="font-label-caps text-[10px] uppercase">Accueil</span>
        </button>
        <button type="button" class="nav-btn flex flex-col items-center gap-1 group w-full py-2 active:scale-95 duration-150" data-view="wifi">
            <span class="material-symbols-outlined">router</span>
            <span class="font-label-caps text-[10px] uppercase">Wi-Fi</span>
        </button>
        <button type="button" class="nav-btn flex flex-col items-center gap-1 group w-full py-2 active:scale-95 duration-150" data-view="health">
            <span class="material-symbols-outlined">monitor_heart</span>
            <span class="font-label-caps text-[10px] uppercase">Santé</span>
        </button>
        <button type="button" class="nav-btn flex flex-col items-center gap-1 group w-full py-2 active:scale-95 duration-150" data-view="logs">
            <span class="material-symbols-outlined">warning</span>
            <span class="font-label-caps text-[10px] uppercase">Journaux</span>
        </button>
        <button type="button" class="nav-btn flex flex-col items-center gap-1 group w-full mt-auto py-2 active:scale-95 duration-150" data-view="admin">
            <span class="material-symbols-outlined">settings</span>
            <span class="font-label-caps text-[10px] uppercase">Admin</span>
        </button>
    </div>
</nav>

<main class="ml-sidebar-width h-screen overflow-y-auto flex flex-col bg-background pb-10">
    <header class="flex justify-between items-center w-full h-14 pl-margin-mobile pr-margin-mobile sticky top-0 bg-surface-dim/80 backdrop-blur-md z-40 border-b border-outline-variant">
        <div class="flex items-center gap-stack-sm">
            <span class="material-symbols-outlined text-on-surface-variant">menu</span>
            <h1 id="page-title" class="font-headline-md text-on-surface ml-2">Accueil</h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex flex-col items-end hidden sm:flex">
                <span class="font-label-caps text-on-surface"><?= $adminName ?></span>
                <span class="text-[10px] text-secondary font-bold tracking-widest uppercase"><?= $adminRole ?></span>
            </div>
            <div class="w-8 h-8 rounded-full border border-secondary p-[2px]">
                <?php if ($adminAvatar): ?>
                <img alt="Avatar" class="w-full h-full rounded-full" src="<?= $adminAvatar ?>"/>
                <?php else: ?>
                <div class="w-full h-full rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="p-margin-mobile flex flex-col gap-6 max-w-7xl mx-auto w-full">

        <div id="alert-banner" class="hidden alert-banner bg-error-container/40 border border-error rounded-xl p-3 flex items-center gap-3">
            <span class="material-symbols-outlined text-error">gpp_maybe</span>
            <span class="text-sm font-bold text-error">Alerte intrusion active — vérifiez les logs et appareils inconnus</span>
        </div>

        <!-- VIEW: Accueil — vue d'ensemble -->
        <div id="view-dashboard" class="view-section active gap-6">
            <?php include __DIR__ . '/includes/partials/overview-cards.php'; ?>

            <p class="text-on-surface-variant text-body-sm">
                Utilisez les onglets à gauche pour accéder à la gestion Wi-Fi, la santé du réseau, les journaux et l'administration.
            </p>

            <div id="agent-android-card" class="bg-surface-container-low border border-outline-variant rounded-xl p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">smartphone</span>
                        <h4 class="font-body-md font-semibold">Agent Android (hotspot)</h4>
                    </div>
                    <span id="agent-status-badge" class="px-2 py-0.5 rounded text-[10px] uppercase bg-outline-variant/30 text-outline">Chargement...</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">Dernier signal</span>
                        <p id="agent-last-ping" class="font-data-mono text-on-surface mt-1">—</p>
                    </div>
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">IP agent</span>
                        <p id="agent-ip" class="font-data-mono text-secondary mt-1">—</p>
                    </div>
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">Clients détectés</span>
                        <p id="agent-clients-count" class="font-data-mono text-on-surface mt-1">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: Wi-Fi — tableau des appareils -->
        <div id="view-wifi" class="view-section gap-6">
            <p class="text-on-surface-variant text-body-sm">Gérez les connexions Wi-Fi : autorisez, désactivez ou bloquez chaque appareil du réseau.</p>
            <?php include __DIR__ . '/includes/partials/device-table.php'; ?>
        </div>

        <!-- VIEW: Santé — métriques réseau -->
        <div id="view-health" class="view-section gap-6">
            <p class="text-on-surface-variant text-body-sm">Indicateurs de performance et d'état du réseau en temps réel.</p>
            <?php include __DIR__ . '/includes/partials/overview-cards.php'; ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl">
                    <span class="text-[10px] uppercase text-outline font-label-caps">Statut réseau</span>
                    <p id="health-status" class="text-2xl font-data-mono text-green-400 mt-2">EN LIGNE</p>
                </div>
                <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl">
                    <span class="text-[10px] uppercase text-outline font-label-caps">Trafic actuel</span>
                    <p id="health-traffic" class="text-2xl font-data-mono text-on-surface mt-2">850 Mbps</p>
                </div>
                <div class="bg-surface-container-low border border-outline-variant p-4 rounded-xl">
                    <span class="text-[10px] uppercase text-outline font-label-caps">Alertes actives</span>
                    <p id="health-alert" class="text-2xl font-data-mono text-secondary mt-2">Aucune</p>
                </div>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-xl p-4">
                <h4 class="font-body-md font-semibold mb-3">Répartition des appareils connectés</h4>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">Total actifs</span>
                        <p id="health-active-users" class="text-2xl font-data-mono text-on-surface mt-1">—</p>
                    </div>
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">Portables</span>
                        <p id="health-laptops" class="text-2xl font-data-mono text-secondary mt-1">—</p>
                    </div>
                    <div>
                        <span class="text-outline text-[10px] uppercase font-label-caps">Mobiles</span>
                        <p id="health-mobiles" class="text-2xl font-data-mono text-secondary mt-1">—</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: Journaux — timeline complète -->
        <div id="view-logs" class="view-section gap-6">
            <p class="text-on-surface-variant text-body-sm">Historique chronologique des connexions, alertes et événements réseau.</p>
            <?php include __DIR__ . '/includes/partials/timeline.php'; ?>
        </div>

        <!-- VIEW: Admin -->
        <div id="view-admin" class="view-section gap-6">
            <div class="flex items-center gap-2">
                <div class="w-1 h-4 bg-secondary"></div>
                <h3 class="font-headline-md text-on-surface">Paramètres administrateur</h3>
            </div>
            <div class="bg-surface-container-low border border-outline-variant rounded-xl p-6 flex flex-col gap-4 max-w-lg">
                <div class="flex items-center gap-4">
                    <?php if ($adminAvatar): ?>
                    <img src="<?= $adminAvatar ?>" alt="Avatar" class="w-16 h-16 rounded-full border border-secondary"/>
                    <?php endif; ?>
                    <div>
                        <p class="font-semibold text-lg"><?= $adminName ?></p>
                        <p class="text-secondary text-sm"><?= $adminRole ?></p>
                        <p class="text-outline text-xs mt-1">Utilisateur : <?= htmlspecialchars($currentAdmin['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <a href="logout.php" class="inline-flex items-center justify-center gap-2 bg-error-container text-on-error-container border border-error px-4 py-2 rounded-lg font-label-caps text-sm hover:opacity-90 w-fit">
                    <span class="material-symbols-outlined text-sm">logout</span>
                    Déconnexion
                </a>
            </div>
        </div>

    </div>
</main>

<div class="fixed bottom-6 right-6 z-[60] flex flex-col gap-2" id="toast-container"></div>
<script type="module" src="assets/js/app.js"></script>
</body>
</html>
