<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="fr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Connexion — Monitor_Ω</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Material+Symbols+Outlined&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#0B0E14',
                        secondary: '#adc7ff',
                        'on-secondary': '#002e68',
                        'outline-variant': '#30363D',
                        'on-surface': '#e5e2e2',
                        error: '#ffb4ab',
                        'surface-container-low': '#1c1b1c',
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="assets/css/dashboard.css"/>
</head>
<body class="bg-background text-on-surface font-[Inter] min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-surface-container-low border border-outline-variant rounded-xl p-8 flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3">
            <h1 class="text-xl font-semibold">Monitor_Ω</h1>
            <p class="text-xs text-gray-400 text-center">Système de surveillance de réseau — Administration</p>
        </div>

        <form id="login-form" class="flex flex-col gap-4">
            <div>
                <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest" for="username">Identifiant</label>
                <input id="username" name="username" type="text" required autocomplete="username"
                    class="mt-1 w-full bg-background border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-secondary outline-none"/>
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold text-gray-400 tracking-widest" for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="mt-1 w-full bg-background border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-secondary outline-none"/>
            </div>
            <p id="login-error" class="text-error text-xs hidden"></p>
            <button type="submit"
                class="bg-secondary text-on-secondary py-2.5 rounded-lg font-bold text-sm uppercase tracking-wide hover:opacity-90 active:scale-95 transition-all">
                Se connecter
            </button>
        </form>

        <p class="text-[10px] text-center text-gray-500">Démo : jeremie / admin123</p>
    </div>

    <script type="module">
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorEl = document.getElementById('login-error');
            errorEl.classList.add('hidden');

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password }),
                });
                const data = await res.json();

                if (!res.ok) {
                    errorEl.textContent = data.error || 'Erreur de connexion';
                    errorEl.classList.remove('hidden');
                    return;
                }

                window.location.href = 'index.php';
            } catch {
                errorEl.textContent = 'Impossible de contacter le serveur';
                errorEl.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
