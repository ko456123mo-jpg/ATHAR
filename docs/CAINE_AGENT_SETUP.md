# CAINE 14.0 Forensic Agent Starter

The `agent/` directory is a separate Python package. Copy it to CAINE 14.0 only after the host-side ATHAR API exists and the network path is intentionally configured.

## Required environment variables

```dotenv
ATHAR_BASE_URL=https://athar-host.example
ATHAR_AGENT_TOKEN=set-on-caine-only
ATHAR_AGENT_NAME=caine-vbox-01
ATHAR_REQUEST_TIMEOUT_SECONDS=20
ATHAR_EXECUTION_TIMEOUT_SECONDS=300
ATHAR_MAX_OUTPUT_BYTES=10485760
ATHAR_WORKSPACE=/var/lib/athar-agent/work
ATHAR_EVIDENCE_ROOT=/mnt/athar-private
```

The token is unique per agent, stored only in CAINE, and transmitted through the `Authorization: Bearer` header. ATHAR stores only a token hash. Rotate or revoke a token immediately when the CAINE snapshot is copied, a token may have leaked, or the virtual machine is decommissioned.

## First approved operation

The first supported action is `tshark.packet_summary`, which invokes the fixed binary `/usr/bin/tshark` using an argument array rather than a shell command string. The evidence path must be an approved read-only local path delivered by ATHAR through the controlled evidence-access flow.

## Running v0.1

The v0.1 Agent performs one explicit outbound heartbeat, claim, execution, and result-submission cycle, then exits. This avoids introducing an unmanaged persistent service during the proof-of-concept stage:

```bash
cd /opt/athar-agent
ATHAR_BASE_URL=https://athar-host.example \
ATHAR_AGENT_TOKEN='token-issued-by-athar' \
ATHAR_EVIDENCE_ROOT=/mnt/athar-private \
python3 -m athar_agent.client --once
```

HTTPS is mandatory for a host-to-VirtualBox connection. The `--allow-insecure-localhost` option exists only for explicit same-host development at `127.0.0.1`; do not use it between the host and CAINE. Mount the host `storage/app/private` directory read-only at `/mnt/athar-private`; the returned relative reference begins with `evidence/` and must resolve to a controlled `working/` copy, never the immutable original. Follow `docs/CAINE_POC_CHECKLIST.md` before promoting a tool verification state.

## Safety conditions

The agent must reject unknown tool IDs, unknown operation keys, unrecognized parameter names, paths outside its read-only evidence location, excessive output, and expired jobs. It must run tools with `shell=False`, a temporary workspace, a bounded timeout, and least privilege. It must send status, tool version, adapter version, timestamps, raw-output reference, result hash, and error details back to ATHAR.

No evidence is automatically executed. A PCAP is used only as an input file to TShark for the first proof of concept.
