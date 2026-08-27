"""Strict parser for the first allowlisted TShark CSV operation."""

from __future__ import annotations

import csv
from io import StringIO
from typing import Any


def parse_packet_summary(raw_output: str, max_rows: int = 100_000) -> list[dict[str, Any]]:
    reader = csv.DictReader(StringIO(raw_output))
    required = {"frame.number", "frame.time_epoch", "ip.src", "ip.dst", "_ws.col.Protocol", "frame.len"}
    if reader.fieldnames is None or not required.issubset(set(reader.fieldnames)):
        raise ValueError("Unexpected TShark packet-summary CSV header")
    records: list[dict[str, Any]] = []
    for row in reader:
        if len(records) >= max_rows:
            raise ValueError("TShark packet-summary result exceeds parser row limit")
        try:
            frame_number = int(row["frame.number"])
            frame_length = int(row["frame.len"])
        except (TypeError, ValueError) as error:
            raise ValueError("TShark packet-summary contains an invalid frame number or length") from error
        records.append({
            "frame_number": frame_number,
            "timestamp": row["frame.time_epoch"],
            "source_ip": row["ip.src"] or None,
            "destination_ip": row["ip.dst"] or None,
            "protocol": row["_ws.col.Protocol"] or "Unknown",
            "frame_length": frame_length,
        })
    return records
