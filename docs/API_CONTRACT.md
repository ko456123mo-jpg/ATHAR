# ATHAR API Contract — MVP

This document defines the Laravel REST surface for ATHAR’s first operational release. User-facing routes require an authenticated user session. CAINE Agent routes accept only a per-agent bearer token and never accept raw shell commands.

| Method | Route | Authorization | Purpose |
|---|---|---|---|
| `GET` | `/api/v1/cases` | Authenticated case viewer | List only cases accessible to the user. |
| `POST` | `/api/v1/cases` | Administrator or investigator | Create a case and assign its creator as the case lead. |
| `GET` | `/api/v1/cases/{case}` | Case viewer | Return the permitted case view and linked investigation metadata. |
| `POST` | `/api/v1/cases/{case}/close` | Case editor | Close a case with an audit event. |
| `POST` | `/api/v1/cases/{case}/evidence` | Case editor | Register or upload private evidence, calculate SHA-256, and append custody/audit events. |
| `POST` | `/api/v1/evidence/{evidence}/verify` | Evidence viewer | Recalculate and compare the evidence SHA-256. |
| `POST` | `/api/v1/evidence/{evidence}/working-copy` | Evidence downloader | Create the analysis working copy without modifying the original evidence. |
| `GET` | `/api/v1/evidence/{evidence}/download` | Evidence downloader | Download the original only through a logged and authorized request. |
| `GET` | `/api/v1/evidence/{evidence}/custody` | Evidence viewer | Return the hash-linked chain-of-custody events. |
| `POST` | `/api/v1/cases/{case}/analysis-jobs` | Case editor | Queue an allowlisted job only when a stored working copy exists; the original evidence key is never sent to the Agent. |
| `GET` | `/api/v1/analysis-jobs/{job}` | Case viewer | Return the current job state and permitted provenance metadata. |
| `POST` | `/api/v1/analysis-jobs/{job}/cancel` | Case editor | Cancel a job through the state-transition policy. |
| `POST` | `/api/v1/analysis-jobs/{job}/retry` | Case editor | Requeue an eligible failed or timed-out job before its retry limit is reached. |
| `GET` | `/api/v1/tool-categories` | Authenticated user | List registered tool categories. |
| `GET` | `/api/v1/tools` | Authenticated user | List catalogued tools, operations, versions, and verification state. |
| `POST` | `/api/v1/tool-categories` | Administrator | Add a tool category. |
| `POST` | `/api/v1/tools` | Administrator | Add a tool registry entry. |
| `POST` | `/api/v1/tools/{tool}/operations` | Administrator | Add a controlled operation, permitted evidence types, and execution limits. |
| `POST` | `/api/v1/tools/{tool}/verification` | Administrator | Add sequential verification evidence; catalogued does not imply installed, executable, or end-to-end verified. |
| `POST` | `/api/v1/cases/{case}/findings` | Case editor | Create a draft finding and record provenance for a linked evidence item when present. |
| `POST` | `/api/v1/findings/{finding}/review` | Administrator or reviewer | Record a reviewed, confirmed, or rejected decision. |
| `POST` | `/api/v1/cases/{case}/reports` | Case editor | Generate a private Markdown report revision from reviewed/confirmed findings. |
| `POST` | `/api/v1/reports/{report}/approve` | Administrator or reviewer | Approve a report and append custody/audit events. |
| `GET` | `/api/v1/reports/{report}` | Case viewer | Read permitted report metadata. |
| `GET` | `/api/v1/agents` | Administrator | List CAINE Agent inventory and job counts; token hashes are never serialized. |
| `POST` | `/api/v1/agents` | Administrator | Register an offline Agent and return a one-time bootstrap token in this response only. |
| `POST` | `/api/v1/agents/{agent}/rotate-token` | Administrator | Rotate a non-retired Agent token and return the replacement once. |
| `POST` | `/api/v1/agents/{agent}/retire` | Administrator | Retire an Agent; a retired token cannot authenticate. |
| `POST` | `/api/v1/agent/heartbeat` | Agent bearer token | Record a CAINE Agent heartbeat. |
| `POST` | `/api/v1/agent/jobs/claim` | Agent bearer token | Atomically claim one queued, enabled, working-copy-backed job. |
| `POST` | `/api/v1/agent/jobs/{job}/status` | Assigned Agent token | Submit an allowlisted job-state transition. |
| `POST` | `/api/v1/agent/jobs/{job}/result` | Assigned Agent token | Submit bounded raw output, structured parsed data, and tool/adapter provenance. |

The Blade workspace uses thin protected action routes that delegate to these existing controllers and domain services; it does not duplicate case, evidence, custody, audit, job, finding, or report logic in the view layer.

Expected HTTP semantics are `401` for missing or invalid authentication, `403` for missing authorization, `404` for inaccessible entities, `409` for invalid concurrent state changes, `422` for invalid input or disallowed execution preconditions, and `429` where a rate limit applies.
