# ATHAR × CAINE 14.0: Real-Environment POC Checklist

> **Scope boundary.** This checklist proves only the first allowlisted operation, `tshark.packet_summary`, using a benign PCAP or PCAPNG. Passing it does not verify any other CAINE tool, does not authorize raw shell commands, and must not be used with real evidentiary material during development.

## 1. Host preparation

Prepare an ATHAR host with PHP 8.3+, MySQL 8-compatible storage, Redis, and a TLS-enabled web server. Create a non-privileged database account whose permissions are limited to the ATHAR database. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `DB_CONNECTION=mysql`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `FILESYSTEM_DISK=private`, and `ATHAR_EVIDENCE_DISK=private` in the host-only `.env` file. Generate a unique `APP_KEY`; no token, password, database dump, or evidence file belongs in Git.

Run the application migration and catalogue seed, then start a Redis queue worker and Laravel scheduler under the host’s service manager. The scheduler invokes `athar:expire-analysis-jobs` once per minute and is safe to repeat because only overdue `claimed` or `running` jobs transition to `timed_out`.

```bash
php artisan migrate --seed --force
php artisan queue:work redis --tries=1 --timeout=360
php artisan schedule:work
```

## 2. Isolated VirtualBox network and TLS

Use a **Host-only Adapter** or an **Internal Network** that permits CAINE to reach the ATHAR host’s TLS endpoint but exposes no inbound service on CAINE. Use a certificate trusted by CAINE for the exact host name or host-only IP used in `ATHAR_BASE_URL`. Never use `--allow-insecure-localhost` across the VM boundary, and do not publish CAINE ports to the public network.

Record the selected host-only address, certificate subject/SAN, firewall rule, and CAINE snapshot identifier in the case-independent deployment record. Do not treat reachability, a binary path, or a heartbeat as proof of end-to-end tool verification.

## 3. Working-copy handoff

The host creates the immutable original under `storage/app/private/evidence/<case>/<evidence>/original.*` and a controlled analysis copy under `storage/app/private/evidence/<case>/<evidence>/working/<ulid>/input.*`. Configure a VirtualBox shared folder whose **host path is exactly** `storage/app/private`, mark it read-only in VirtualBox, then mount it read-only inside CAINE at `/mnt/athar-private`.

```bash
sudo mkdir -p /mnt/athar-private
sudo mount -t vboxsf -o ro athar_private /mnt/athar-private
findmnt -no TARGET,OPTIONS /mnt/athar-private
```

Set `ATHAR_EVIDENCE_ROOT=/mnt/athar-private`. The relative `evidence_reference` returned by ATHAR begins with `evidence/…`, so it resolves to the working-copy path under that mount. The Agent policy rejects traversal outside this root. Confirm the selected input resides in a `working/` path; do not make the immutable original writable or executable.

## 4. Agent onboarding and one-shot run

Register the Agent as an administrator through the protected ATHAR Agent API or issue one bootstrap token with `php artisan athar:agent-token caine-vbox-01 --hostname=<caine-hostname>`. Transfer the one-time token through an approved secure channel, save it only in the CAINE local environment file with restrictive permissions, and never paste it into documentation, terminal history intended for sharing, or Git.

```dotenv
ATHAR_BASE_URL=https://<athar-host-only-address-or-name>
ATHAR_AGENT_TOKEN=<one-time-bootstrap-token>
ATHAR_AGENT_NAME=caine-vbox-01
ATHAR_REQUEST_TIMEOUT_SECONDS=20
ATHAR_EXECUTION_TIMEOUT_SECONDS=300
ATHAR_MAX_OUTPUT_BYTES=10485760
ATHAR_EVIDENCE_ROOT=/mnt/athar-private
```

From the copied Agent package in CAINE, discover the observed binary version, then run a single explicit polling cycle. The Agent sends an outbound heartbeat, claims at most one eligible working-copy-backed job, performs only the allowlisted TShark argument array, parses the result, and submits bounded output.

```bash
/usr/bin/tshark --version
cd /opt/athar-agent
python3 -m athar_agent.client --once
```

## 5. Evidence to retain for the POC

| Control | Evidence to capture | Promotion condition |
|---|---|---|
| Network and TLS | Host-only network details, TLS validation result, and CAINE snapshot ID | The Agent reaches ATHAR via HTTPS without an insecure bypass. |
| Agent identity | Agent record ID and heartbeat time; never the token | The authenticated Agent is online and its token hash alone is retained by ATHAR. |
| Tool presence | `/usr/bin/tshark --version` output and observed path | The tool may advance from `cataloged` to the next applicable state only through the protected sequential verification route. |
| Working-copy safety | Relative reference, mounted path/options, original SHA-256, and working-copy SHA-256 | The claimed job references a working-copy path and does not expose the original key. |
| Execution provenance | Job public ID, status transitions, bounded raw output key, parsed-result hash, tool version, adapter version, custody events, and audit events | A benign PCAP job reaches `completed` through the Agent’s actual HTTPS cycle. |

Promote TShark to `end_to_end_verified` only after all POC artifacts have been reviewed. A failed POC must remain traceable as `failed` or `timed_out`; it is not a reason to loosen the allowlist, path policy, TLS requirement, or result-size limit.
