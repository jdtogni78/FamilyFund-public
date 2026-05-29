# ED-0004 — Object-level API authorization (IDOR remediation)


- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** API controllers allowed object access by ID without verifying the
  caller owned/could-see the object (IDOR).
- **Decision:** Introduce an `AuthorizesApiAccess` trait and apply object-level
  scoping across all API controllers; gate PII and system-admin operations; fix
  Sanctum `expires_at`; add authenticated ZAP tooling.
- **Consequences:** All API object access now runs an ownership/visibility check.
  Authenticated ZAP scans run in the scheduled CI security job.
- **Source:** #13 / #49 (merge `767600cd`).
