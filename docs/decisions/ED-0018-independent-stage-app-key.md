# ED-0018 — Stage `APP_KEY` is independent of prod; encrypted columns are nulled on restore


- **Date:** 2026-05-28 · **Status:** Accepted · **Builds on:** ED-0017
- **Context:** The go-public plan (#36) restores a prod DB dump into a new
  `stage` environment on spirit (#46). Any column on a model with an
  `'encrypted'` cast was written under prod's `APP_KEY`; restoring it under a
  different key makes it undecryptable. The ticket (#52) framed two options:
  (1) stage reuses prod's `APP_KEY`, or (2) stage uses a fresh key and the
  restore tool re-encrypts or nulls the affected columns. The audit
  (`docs/security/ENCRYPTED-CASTS-AUDIT-52.md`) found the entire encrypted
  surface is `User.two_factor_secret` (`encrypted`) + `User.two_factor_recovery_codes`
  (`encrypted:array`) — no business/PII columns are encrypted at the cast
  layer. The non-cast `Crypt::decrypt(env('MAIL_PASSWORD_ENCRYPTED'))` site in
  `AppServiceProvider` is env-side, not DB-side, and is already per-environment.
- **Decision:** Adopt **Option 2 — fresh stage `APP_KEY`, null the encrypted
  2FA columns on restore.** Stage and prod hold independent `APP_KEY`s, rotated
  independently. The prod→stage restore tool (#47) lifts the guarded
  `UPDATE users SET two_factor_secret = NULL, two_factor_recovery_codes = NULL,
  two_factor_confirmed_at = NULL` already used by `database/prod_to_dev.sql`
  (lines 163–171) so the same idempotent pattern covers prod→dev and
  prod→stage. `.env.stage.sops` (#48) carries its own `APP_KEY` from
  `php artisan key:generate` on the stage container.
- **Consequences:**
  - Stage compromise does not transfer to prod encrypted data — strict
    key separation across environments.
  - Cutover team members who enable 2FA on stage must re-enroll after any
    prod→stage rebuild. Acceptable (and a useful smoke test).
  - If a future ticket adds an `'encrypted'` cast to a column carrying
    *business* data (SSN, bank routing, customer address, etc.), this
    decision must be revisited per column — business data can't be nulled
    on restore the way 2FA secrets can.
- **Source:** #52 (audit + decision); `docs/security/ENCRYPTED-CASTS-AUDIT-52.md`;
  `app/Models/User.php` casts; `database/prod_to_dev.sql:163-171`;
  drives #47 (restore tooling) and #48 (`.env.stage.sops`).
