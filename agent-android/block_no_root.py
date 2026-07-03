#!/usr/bin/env python3
"""Blocage logique sans root : journalise et mémorise les MAC bloquées."""

from __future__ import annotations

BLOCKED: set[str] = set()


def block_mac(mac: str) -> None:
    BLOCKED.add(mac.upper())
    print(f"[block_no_root] MAC {mac} marquée bloquée (logique — sans root)")


def unblock_mac(mac: str) -> None:
    BLOCKED.discard(mac.upper())
    print(f"[block_no_root] MAC {mac} débloquée (logique — sans root)")


def is_blocked(mac: str) -> bool:
    return mac.upper() in BLOCKED
