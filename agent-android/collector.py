#!/usr/bin/env python3
"""Lecture de la table ARP Android/Linux et envoi vers le dashboard PC."""

from __future__ import annotations

import configparser
import re
import sys
import time
from pathlib import Path

import requests

ARP_PATH = Path("/proc/net/arp")
REACHABLE_FLAG = 0x2


def load_config() -> configparser.ConfigParser:
    config = configparser.ConfigParser()
    paths = [Path("config.ini"), Path(__file__).with_name("config.ini")]
    for path in paths:
        if path.is_file():
            config.read(path)
            return config
    raise FileNotFoundError(
        "config.ini introuvable. Copiez config.example.ini vers config.ini et adaptez pc_url / api_key."
    )


def normalize_mac(raw: str) -> str | None:
    clean = re.sub(r"[^a-fA-F0-9]", "", raw).upper()
    if len(clean) != 12 or clean == "000000000000":
        return None
    return ":".join(clean[i : i + 2] for i in range(0, 12, 2))


def read_arp_clients() -> list[dict[str, str]]:
    if not ARP_PATH.is_file():
        return []

    clients: list[dict[str, str]] = []
    lines = ARP_PATH.read_text(encoding="utf-8", errors="ignore").splitlines()[1:]

    for line in lines:
        parts = line.split()
        if len(parts) < 4:
            continue

        ip = parts[0]
        flags = int(parts[2], 16) if parts[2].startswith("0x") else int(parts[2])
        mac = normalize_mac(parts[3])

        if not mac or (flags & REACHABLE_FLAG) == 0:
            continue
        if ip.startswith("127.") or ip.endswith(".255"):
            continue

        hostname = f"Client_{mac.replace(':', '')[-6]}_{ip}"
        clients.append({"ip": ip, "mac": mac, "hostname": hostname})

    return clients


def post_clients(config: configparser.ConfigParser, clients: list[dict[str, str]]) -> None:
    pc_url = config.get("agent", "pc_url", fallback="").rstrip("/")
    api_key = config.get("agent", "api_key", fallback="")
    agent_id = config.get("agent", "agent_id", fallback="android-collector")

    if not pc_url or not api_key:
        raise ValueError("pc_url et api_key sont requis dans config.ini")

    endpoint = f"{pc_url}/api/collector.php"
    response = requests.post(
        endpoint,
        json={"clients": clients, "agent_id": agent_id},
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        timeout=15,
    )
    response.raise_for_status()
    print(f"[collector] {len(clients)} client(s) envoyé(s) -> {response.json()}")


def main() -> int:
    config = load_config()
    interval = config.getint("agent", "scan_interval", fallback=10)

    print("[collector] Démarrage — lecture ARP et envoi vers le PC")
    while True:
        try:
            clients = read_arp_clients()
            post_clients(config, clients)
        except Exception as exc:  # noqa: BLE001 — boucle de service
            print(f"[collector] Erreur: {exc}", file=sys.stderr)
        time.sleep(max(3, interval))


if __name__ == "__main__":
    raise SystemExit(main())
