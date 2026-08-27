# ATHAR Architecture Baseline

ATHAR is a Laravel 13 and PHP 8.3+ platform for managing digital-forensics cases, private evidence, analysis jobs, normalized results, investigator findings, reviewable reports, and traceability records.

## Trust boundaries

The Laravel application, MySQL database, Redis queue, and private evidence storage run on the host development environment. CAINE 14.0 runs in a separate VirtualBox virtual machine. A dedicated Forensic Agent operates inside CAINE and only makes outbound HTTPS requests to ATHAR; no public inbound service is opened on the CAINE virtual machine.

The initial proof of concept uses a benign PCAP file and an allowlisted TShark operation. The progression of a tool is `Cataloged`, `Configured`, `Installed`, `Executable`, then `End-to-End Verified`. A tool must not be displayed as supported until it has met the evidence requirement for its state.

## Job execution lifecycle

`CREATED → QUEUED → CLAIMED → RUNNING → PARSING → COMPLETED`

An allowed job can instead transition to `FAILED`, `TIMED_OUT`, or `CANCELLED`. The agent accepts only a `tool_id`, an `operation_key`, a controlled evidence reference, and parameters validated against the server-side allowlist. It must never accept or invoke a user-provided shell command.

## Evidence lifecycle

`Register → SHA-256 → Private Store → Verify → Read-only Analysis → Export / Archive`

Original evidence is private, access-controlled, and never overwritten by the analysis process. Each sensitive action produces an Audit Log record; each evidence custody event links to the previous event hash to make modification detectable.

## Repository layout

| Path | Purpose |
|---|---|
| `app/Domain/Forensics` | Laravel domain code for evidence, jobs, tools, results, and custody services. |
| `config/forensics.php` | Approved first-release operations and agent limits. |
| `database/migrations` | Laravel migrations for the ATHAR MySQL schema. |
| `docs/` | Architecture, local setup, API, and CAINE agent documentation. |
| `agent/` | A separate Python Forensic Agent package deployed inside CAINE 14.0. |
| `tests/Feature/Forensics` | Integration and security-flow tests. |

## First end-to-end path

`Case → Evidence → SHA-256 → Analysis Job → Queue → Agent Claim → TShark → Raw Result → Parsed JSON → Finding → Report`

The path is considered verified only when ATHAR retains the job ID, Agent execution metadata, raw output, parsed result, result hash, and an audit trail without changing the original evidence.
