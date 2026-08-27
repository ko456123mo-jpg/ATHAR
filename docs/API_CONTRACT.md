# Initial API Contract

The endpoint names below are the Laravel REST contract for the first ATHAR release. Each request requires authentication; the Agent endpoints require a per-agent bearer token instead of a user session.

| Method | Route | Purpose |
|---|---|---|
| `POST` | `/api/v1/cases` | Create a case. |
| `GET` | `/api/v1/cases` | List cases available to the authenticated user. |
| `POST` | `/api/v1/cases/{case}/evidence` | Register or upload private evidence. |
| `POST` | `/api/v1/evidence/{evidence}/verify` | Recalculate and compare SHA-256. |
| `GET` | `/api/v1/evidence/{evidence}/custody` | Return the evidence chain of custody. |
| `POST` | `/api/v1/cases/{case}/analysis-jobs` | Queue an allowlisted analysis job. |
| `GET` | `/api/v1/analysis-jobs/{job}` | Read the current job status and permitted metadata. |
| `POST` | `/api/v1/analysis-jobs/{job}/cancel` | Request cancellation under the job transition policy. |
| `POST` | `/api/v1/agent/heartbeat` | Record a CAINE Agent heartbeat. |
| `POST` | `/api/v1/agent/jobs/claim` | Atomically claim one eligible job. |
| `POST` | `/api/v1/agent/jobs/{job}/status` | Submit a permitted job state transition. |
| `POST` | `/api/v1/agent/jobs/{job}/result` | Submit raw-result metadata and parsed result data. |

The server must return `401` for missing or invalid authentication, `403` for missing permission, `404` for inaccessible entities, `409` for invalid concurrent state changes, `422` for invalid input, and `429` when a rate limit applies.
