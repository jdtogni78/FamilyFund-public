# ED-0008 — Repository pattern + `*Ext` model variants


- **Date:** (pre-existing convention) · **Status:** Accepted
- **Context:** Consistent data access and a home for business logic distinct from
  Eloquent models.
- **Decision:** All data access goes through `*Repository` classes (extending
  `BaseRepository`); controllers inject repositories and validate via form
  requests. Extended business logic lives in `*Ext` model variants. Historical
  views use an `as_of` parameter throughout; APIs are versioned (`/api`, `/api/v1`).
- **Consequences:** New entities follow the generator-produced
  model/repository/controller/request/views shape. Business logic added to `*Ext`,
  not the base model (e.g. the transaction observer moved to `TransactionExt`).
- **Source:** CLAUDE.md (Architecture / Patterns).
