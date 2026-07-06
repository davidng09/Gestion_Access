# Agent Android — Monitor_Ω

Scripts Termux pour la collecte des clients Wi-Fi et l'exécution des actions block/unblock.

**Documentation complète** : voir le [README principal](../README.md) (installation, configuration, tests, roadmap).

## Démarrage rapide

```bash
pkg update -y && pkg install -y python
pip install requests
cp config.example.ini config.ini
# Éditer pc_url et api_key (identiques au PC)
python collector.py        # terminal 1
python action_listener.py  # terminal 2
```

## Fichiers

| Fichier | Rôle |
|---------|------|
| `collector.py` | Lit `/proc/net/arp`, POST vers `api/collector.php` |
| `action_listener.py` | Poll `api/phone_actions.php`, exécute block/unblock |
| `block_no_root.py` | Blocage logique (défaut, sans root) |
| `block_root.py` | Blocage iptables (option, root requis) |
| `config.ini` | URL PC, clé API, intervalles |

## `config.ini` type

```ini
[agent]
pc_url = http://192.168.43.10:8080
api_key = gestion-access-dev-key-change-me
scan_interval = 10
poll_interval = 5
agent_id = android-collector
```
