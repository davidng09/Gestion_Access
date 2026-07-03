# Agent Android — Gestion_Access

Scripts Termux pour collecter les clients du hotspot Android et exécuter les actions block/unblock envoyées par le dashboard PC.

## Prérequis

- Téléphone Android avec **hotspot Wi-Fi / 4G** activé
- [Termux](https://termux.dev/) installé
- PC connecté au Wi-Fi du téléphone (ex. sous-réseau `192.168.43.x`)
- Serveur PHP du dashboard lancé sur le PC : `php -S 0.0.0.0:8080`

## Installation Termux

```bash
pkg update && pkg install python
pip install -r requirements.txt
cp config.example.ini config.ini
```

Éditez `config.ini` :

```ini
[agent]
pc_url = http://192.168.43.10:8080
api_key = gestion-access-dev-key-change-me
scan_interval = 10
poll_interval = 5
agent_id = android-collector
```

- `pc_url` : IP locale du PC sur le hotspot (pas `localhost`)
- `api_key` : identique à `config/agent.php` ou `config/agent.local.php` sur le PC

## Lancement

Dans deux sessions Termux (ou via `tmux`) :

```bash
cd ~/Gestion_Access/agent-android
python collector.py
```

```bash
cd ~/Gestion_Access/agent-android
python action_listener.py
```

## Fonctionnement

1. `collector.py` lit `/proc/net/arp` et envoie IP/MAC au endpoint `POST /api/collector.php`
2. Le dashboard affiche les appareils avec le badge **Réel**
3. L'admin bloque un appareil → file `phone_actions_queue`
4. `action_listener.py` récupère les actions et les exécute

## Blocage sans root vs avec root

| Mode | Comportement |
|------|--------------|
| **Sans root** (défaut) | Statut bloqué en BDD + log local ; suffisant pour la soutenance |
| **Avec root** | `block_root.py` tente une règle `iptables` en plus du mode logique |

Le listener détecte automatiquement `su` et utilise iptables si disponible.

## Dépannage

- **401 Clé API invalide** : vérifiez `api_key` des deux côtés
- **403 IP non autorisée** : mettez `hotspot_subnet = '*'` dans `config/agent.local.php` pour les tests locaux, ou vérifiez le sous-réseau hotspot
- **Aucun client détecté** : pinguez le PC depuis le téléphone pour peupler la table ARP
- **PC inaccessible** : utilisez l'IP du PC sur le hotspot, pas `127.0.0.1`

## Test rapide depuis le PC (sans téléphone)

```bash
curl -X POST http://127.0.0.1:8080/api/collector.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: gestion-access-dev-key-change-me" \
  -d "{\"clients\":[{\"ip\":\"192.168.43.50\",\"mac\":\"AA:BB:CC:DD:EE:01\",\"hostname\":\"Test_PC\"}]}"
```
