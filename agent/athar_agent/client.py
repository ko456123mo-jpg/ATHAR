"""Command-line entry point for a single safe outbound ATHAR Agent polling cycle."""

from __future__ import annotations

import argparse
import os
import socket
import subprocess
import sys
from pathlib import Path
from typing import Any, Mapping

from .api import ApiError, AtharApi
from .parser import parse_packet_summary
from .policy import PolicyError
from .runner import run_allowlisted_job

ADAPTER_VERSION = "0.1.0"
CAPABILITIES = ["tshark.packet_summary"]


def tshark_version() -> str:
    completed = subprocess.run(["/usr/bin/tshark", "--version"], shell=False, check=False, capture_output=True, text=True, timeout=15)
    return (completed.stdout.splitlines() or ["unknown"])[0][:128]


def execute_once(api: AtharApi, evidence_root: Path, hostname: str) -> int:
    api.heartbeat(hostname, CAPABILITIES)
    claim = api.claim()
    if claim.get("job") is None and "job_id" not in claim:
        return 0
    job: Mapping[str, Any] = claim
    job_id = str(job["job_id"])
    try:
        api.status(job_id, "running")
        operation, execution = run_allowlisted_job(job, evidence_root)
        if execution.timed_out:
            api.status(job_id, "timed_out", execution.exit_code, execution.stderr or "Allowlisted operation exceeded its time limit")
            return 1
        if execution.exit_code != 0:
            api.status(job_id, "failed", execution.exit_code, execution.stderr or "Allowlisted operation returned a non-zero exit code")
            return 1
        parsed = parse_packet_summary(execution.stdout)
        api.submit_result(job_id, execution.stdout, parsed, tshark_version(), ADAPTER_VERSION, execution.exit_code)
        return 0
    except (PolicyError, ValueError, ApiError, OSError, subprocess.SubprocessError) as error:
        try:
            api.status(job_id, "failed", error_message=str(error))
        except ApiError:
            pass
        return 1


def main() -> int:
    parser = argparse.ArgumentParser(description="ATHAR CAINE Forensic Agent — one outbound polling cycle")
    parser.add_argument("--once", action="store_true", help="Run one heartbeat/claim/execute/report cycle (required for v0.1).")
    parser.add_argument("--allow-insecure-localhost", action="store_true", help="Permit HTTP only for localhost development; never use on a VM or network.")
    args = parser.parse_args()
    if not args.once:
        parser.error("Only --once is supported in v0.1 to avoid an unmanaged persistent process")
    api = AtharApi(
        base_url=os.environ.get("ATHAR_BASE_URL", ""),
        token=os.environ.get("ATHAR_AGENT_TOKEN", ""),
        timeout_seconds=int(os.environ.get("ATHAR_REQUEST_TIMEOUT_SECONDS", os.environ.get("ATHAR_API_TIMEOUT_SECONDS", "30"))),
        allow_insecure_localhost=args.allow_insecure_localhost,
    )
    evidence_root = Path(os.environ.get("ATHAR_EVIDENCE_ROOT", "/var/lib/athar/evidence"))
    return execute_once(api, evidence_root, os.environ.get("ATHAR_AGENT_NAME", socket.gethostname()))


if __name__ == "__main__":
    sys.exit(main())
