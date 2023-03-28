#!/usr/bin/env python3

from pathlib import Path


def time_units_to_ms(
    hours: int = 0, minutes: int = 0, seconds: int = 0, milliseconds: int = 0
) -> int:
    hours_ms = hours * 60 * 60 * 1000
    minutes_ms = minutes * 60 * 1000
    seconds_ms = seconds * 1000
    return sum([hours_ms, minutes_ms, seconds_ms, milliseconds])


def mkdir(dir: str) -> None:
    Path(dir).mkdir(parents=True, exist_ok=True)
