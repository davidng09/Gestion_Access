#!/usr/bin/env python3
"""Blocage réseau optionnel via iptables (nécessite root / su)."""

from __future__ import annotations

import shutil
import subprocess


def _run_iptables(args: list[str]) -> bool:
    if not shutil.which("iptables"):
        print("[block_root] iptables introuvable")
        return False

    cmd = ["iptables", *args]
    try:
        subprocess.run(cmd, check=True, capture_output=True, text=True)
        return True
    except subprocess.CalledProcessError as exc:
        print(f"[block_root] Échec: {exc.stderr or exc}")
        return False


def block_mac(mac: str) -> bool:
    ok = _run_iptables(["-C", "FORWARD", "-m", "mac", "--mac-source", mac, "-j", "DROP"])
    if ok:
        print(f"[block_root] Règle déjà présente pour {mac}")
        return True
    return _run_iptables(["-A", "FORWARD", "-m", "mac", "--mac-source", mac, "-j", "DROP"])


def unblock_mac(mac: str) -> bool:
    return _run_iptables(["-D", "FORWARD", "-m", "mac", "--mac-source", mac, "-j", "DROP"])
