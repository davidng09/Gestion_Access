#!/usr/bin/env python3
"""Polling des actions block/unblock depuis le dashboard PC."""

from __future__ import annotations

import configparser
import shutil
import sys
import time
from pathlib import Path

import requests

from block_no_root import block_mac as block_no_root, unblock_mac as unblock_no_root

try:
    from block_root import block_mac as block_root, unblock_mac as unblock_root
except ImportError:  # pragma: no cover
    block_root = None
    unblock_root = None


def load_config() -> configparser.ConfigParser:
    config = configparser.ConfigParser()
    paths = [Path("config.ini"), Path(__file__).with_name("config.ini")]
    for path in paths:
        if path.is_file():
            config.read(path)
            return config
    raise FileNotFoundError("config.ini introuvable")


def has_root() -> bool:
    return shutil.which("su") is not None and block_root is not None and unblock_root is not None


def execute_action(action: str, mac: str) -> tuple[bool, str | None]:
    try:
        if action == "block":
            if has_root():
                ok = block_root(mac)
                if ok:
                    return True, None
            block_no_root(mac)
            return True, None

        if action == "unblock":
            if has_root():
                unblock_root(mac)
            unblock_no_root(mac)
            return True, None

        return False, f"Action inconnue: {action}"
    except Exception as exc:  # noqa: BLE001
        return False, str(exc)


def ack(pc_url: str, api_key: str, action_id: int, status: str, error: str | None = None) -> None:
    endpoint = f"{pc_url.rstrip('/')}/api/phone_actions.php"
    payload = {"action_id": action_id, "status": status}
    if error:
        payload["error"] = error
    response = requests.post(
        endpoint,
        json=payload,
        headers={"X-API-Key": api_key, "Content-Type": "application/json"},
        timeout=15,
    )
    response.raise_for_status()


def poll_once(config: configparser.ConfigParser) -> None:
    pc_url = config.get("agent", "pc_url", fallback="").rstrip("/")
    api_key = config.get("agent", "api_key", fallback="")
    endpoint = f"{pc_url}/api/phone_actions.php?status=pending"

    response = requests.get(
        endpoint,
        headers={"X-API-Key": api_key},
        timeout=15,
    )
    response.raise_for_status()
    actions = response.json().get("actions", [])

    for item in actions:
        action_id = int(item["id"])
        mac = item["mac_address"]
        action = item["action"]
        ok, error = execute_action(action, mac)
        ack(pc_url, api_key, action_id, "done" if ok else "failed", error)
        print(f"[listener] action #{action_id} {action} {mac} -> {'done' if ok else 'failed'}")


def main() -> int:
    config = load_config()
    interval = config.getint("agent", "poll_interval", fallback=5)
    mode = "root+no_root" if has_root() else "no_root"
    print(f"[listener] Démarrage — mode {mode}")

    while True:
        try:
            poll_once(config)
        except Exception as exc:  # noqa: BLE001
            print(f"[listener] Erreur: {exc}", file=sys.stderr)
        time.sleep(max(2, interval))


if __name__ == "__main__":
    raise SystemExit(main())
