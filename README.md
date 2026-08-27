# ATHAR

ATHAR is a secure, traceable platform for managing digital-forensics investigations. It is built with **Laravel 13**, **PHP 8.3+**, **MySQL**, and **Redis**, while forensic execution is delegated to a separate **Forensic Agent** inside **CAINE 14.0** on VirtualBox.

> ATHAR manages cases, evidence integrity, allowed analysis jobs, results, findings, review, and reports. It is not a replacement for forensic tools and does not automatically reach legal or investigative conclusions.

## First verified path

The first end-to-end integration is intentionally bounded to a benign PCAP sample and TShark:

```text
Case → Evidence → SHA-256 → Analysis Job → Queue → CAINE Agent
→ TShark → Raw Result → Parsed JSON → Finding → Reviewable Report
```

Only an allowlisted `tool_id` and `operation_key` can reach the Agent. ATHAR never sends a raw user-provided shell command, and original evidence is stored privately and accessed read-only during analysis.

## Repository structure

| Path | Responsibility |
|---|---|
| `app/Domain/Forensics/` | Laravel domain services for evidence, jobs, results, tools, and custody. |
| `config/forensics.php` | First-release Tool Registry policy and execution limits. |
| `database/migrations/` | MySQL schema migrations for ATHAR entities. |
| `docs/` | Architecture, API contract, CAINE Agent, and local setup documentation. |
| `agent/` | Separate Python package installed in CAINE 14.0. |
| `tests/Feature/Forensics/` | Laravel feature and security-flow tests. |

## Local setup

See [Local Development Setup](docs/LOCAL_SETUP.md) before running the application. The host system runs Laravel, MySQL, Redis, and the queue worker. CAINE stays isolated inside VirtualBox and communicates through outbound HTTPS polling.

## Agent safety policy

The starter Agent implements a strict `tshark.packet_summary` allowlist. It uses a fixed executable path and an argument array with `shell=False`; it rejects unknown operations, non-PCAP input, and evidence references that escape the approved read-only directory. Run its policy tests with:

```bash
cd agent
python3 -m unittest discover -v
```

## Development status

The repository contains the Laravel 13 foundation, approved architecture, first-release API contract, CAINE Agent starter, TShark execution policy, and policy tests. Models, migrations, UI, authentication/authorization, private evidence storage, job endpoints, parsers, and end-to-end tests are the next implementation phases.
