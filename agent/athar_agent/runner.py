"""Bounded, no-shell execution of an already allowlisted forensic operation."""

from __future__ import annotations

import subprocess
from dataclasses import dataclass
from pathlib import Path
from typing import Mapping

from .policy import Operation, build_command


@dataclass(frozen=True)
class ExecutionResult:
    command: list[str]
    exit_code: int
    stdout: str
    stderr: str
    timed_out: bool


def run_allowlisted_job(job: Mapping[str, object], evidence_root: Path) -> tuple[Operation, ExecutionResult]:
    """Run a single policy-approved job without a shell interpreter."""

    operation, command = build_command(job, evidence_root)
    try:
        completed = subprocess.run(
            command,
            shell=False,
            check=False,
            capture_output=True,
            text=True,
            timeout=operation.timeout_seconds,
        )
        stdout = completed.stdout[: operation.max_output_bytes]
        stderr = completed.stderr[: operation.max_output_bytes]
        return operation, ExecutionResult(command, completed.returncode, stdout, stderr, False)
    except subprocess.TimeoutExpired as error:
        stdout = (error.stdout or "")[: operation.max_output_bytes]
        stderr = (error.stderr or "")[: operation.max_output_bytes]
        return operation, ExecutionResult(command, -1, stdout, stderr, True)
