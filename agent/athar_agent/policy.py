"""Allowlisted CAINE tool-operation policy for the ATHAR Forensic Agent."""

from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from typing import Mapping


class PolicyError(ValueError):
    """Raised when a job is outside the approved execution policy."""


@dataclass(frozen=True)
class Operation:
    tool_id: str
    operation_key: str
    binary: str
    timeout_seconds: int
    max_output_bytes: int


TSHARK_PACKET_SUMMARY = Operation(
    tool_id="tshark",
    operation_key="packet_summary",
    binary="/usr/bin/tshark",
    timeout_seconds=300,
    max_output_bytes=10 * 1024 * 1024,
)

OPERATIONS: Mapping[tuple[str, str], Operation] = {
    (TSHARK_PACKET_SUMMARY.tool_id, TSHARK_PACKET_SUMMARY.operation_key): TSHARK_PACKET_SUMMARY,
}


def build_command(job: Mapping[str, object], evidence_root: Path) -> tuple[Operation, list[str]]:
    """Return a validated argument array for one allowlisted job.

    The function intentionally has no general command parameter and never
    constructs a shell string. The caller must use subprocess with shell=False.
    """

    tool_id = str(job.get("tool_id", ""))
    operation_key = str(job.get("operation_key", ""))
    operation = OPERATIONS.get((tool_id, operation_key))
    if operation is None:
        raise PolicyError("Tool or operation is not allowlisted")

    evidence_reference = job.get("evidence_reference")
    if not isinstance(evidence_reference, str) or not evidence_reference:
        raise PolicyError("An evidence reference is required")

    root = evidence_root.resolve()
    evidence_path = (root / evidence_reference).resolve()
    if root not in evidence_path.parents or evidence_path == root:
        raise PolicyError("Evidence path is outside the approved read-only directory")
    if evidence_path.suffix.lower() not in {".pcap", ".pcapng"}:
        raise PolicyError("The first TShark operation accepts only PCAP or PCAPNG evidence")

    if operation is TSHARK_PACKET_SUMMARY:
        return operation, [
            operation.binary,
            "-n",
            "-r",
            str(evidence_path),
            "-T",
            "fields",
            "-E",
            "header=y",
            "-E",
            "separator=,",
            "-e",
            "frame.number",
            "-e",
            "frame.time_epoch",
            "-e",
            "ip.src",
            "-e",
            "ip.dst",
            "-e",
            "_ws.col.Protocol",
            "-e",
            "frame.len",
        ]

    raise PolicyError("Operation has no approved command builder")
