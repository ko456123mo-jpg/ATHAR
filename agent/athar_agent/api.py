"""Small outbound HTTPS client for the ATHAR CAINE Forensic Agent."""

from __future__ import annotations

import json
from dataclasses import dataclass
from typing import Any, Mapping
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen


class ApiError(RuntimeError):
    """Raised when ATHAR rejects an Agent API request or the API is unavailable."""


@dataclass(frozen=True)
class AtharApi:
    base_url: str
    token: str
    timeout_seconds: int = 30
    allow_insecure_localhost: bool = False

    def __post_init__(self) -> None:
        parsed = urlparse(self.base_url)
        local_hosts = {"127.0.0.1", "localhost", "::1"}
        if parsed.scheme != "https" and not (self.allow_insecure_localhost and parsed.hostname in local_hosts):
            raise ValueError("ATHAR_BASE_URL must use HTTPS; insecure HTTP is permitted only for explicit localhost development")
        if not self.token.strip():
            raise ValueError("ATHAR_AGENT_TOKEN is required")

    def heartbeat(self, hostname: str, capabilities: list[str]) -> Mapping[str, Any]:
        return self._post("/api/v1/agent/heartbeat", {"hostname": hostname, "capabilities": capabilities})

    def claim(self) -> Mapping[str, Any]:
        return self._post("/api/v1/agent/jobs/claim", {})

    def status(self, job_id: str, status: str, exit_code: int | None = None, error_message: str | None = None) -> Mapping[str, Any]:
        payload: dict[str, Any] = {"status": status}
        if exit_code is not None:
            payload["exit_code"] = exit_code
        if error_message:
            payload["error_message"] = error_message[:10_000]
        return self._post(f"/api/v1/agent/jobs/{job_id}/status", payload)

    def submit_result(self, job_id: str, raw_output: str, parsed_result: list[Mapping[str, Any]], tool_version: str, adapter_version: str, exit_code: int) -> Mapping[str, Any]:
        return self._post(f"/api/v1/agent/jobs/{job_id}/result", {
            "raw_output": raw_output,
            "parsed_result": parsed_result,
            "tool_version": tool_version,
            "adapter_version": adapter_version,
            "exit_code": exit_code,
        })

    def _post(self, path: str, payload: Mapping[str, Any]) -> Mapping[str, Any]:
        body = json.dumps(payload, separators=(",", ":")).encode("utf-8")
        request = Request(
            self.base_url.rstrip("/") + path,
            data=body,
            method="POST",
            headers={"Authorization": f"Bearer {self.token}", "Content-Type": "application/json", "Accept": "application/json"},
        )
        try:
            with urlopen(request, timeout=self.timeout_seconds) as response:  # noqa: S310 - URL policy is enforced above.
                return json.loads(response.read().decode("utf-8"))
        except HTTPError as error:
            detail = error.read().decode("utf-8", errors="replace")[:1000]
            raise ApiError(f"ATHAR API returned HTTP {error.code}: {detail}") from error
        except URLError as error:
            raise ApiError(f"ATHAR API request failed: {error.reason}") from error
