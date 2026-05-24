# Handoff — FamilyFund secret rotation (paste into a NEW thread)

> Start the new thread in `/Users/claudio1/dev/FamilyFund` (rotation runs there).
> Scope is **secret rotation only** — not websites, not the history purge, not publishing.

---

Resume the **FamilyFund dev/stage secret rotation only**. Read these first and follow the plan:
- `/Users/claudio1/dev/job-search/SECRET-ROTATION-PLAN.md` (the plan — follow §1)
- `/Users/claudio1/dev/job-search/SECURITY-EXPOSURE.md` (background)
- Memory: `familyfund-dstrader-exposure`

**What leaked:** one `APP_KEY` + a 6-char `DB_PASSWORD` (in FamilyFund's tracked `.env.dev`, repo now private), reused by the local git-ignored `.env` and `.env.stage` → **dev + stage compromised**. Real-world risk is LOW (`familyfund_dev` is a localhost/LAN docker DB, not internet-facing).

**Scope guardrails:**
- Rotate **dev + stage only**. **PROD on `spirit` (REDACTED_PROD_HOST) = ANALYZE-ONLY — do NOT change prod.**
- Real users live in prod, so the bulk user-reset (everyone except `jdtogni`) is the user's to run on prod — **don't execute it**.
- dstrader is already purged/clean; the **FamilyFund history purge is a separate, deferred task — not part of this thread.**

**Blockers to handle before rotating (this is why it's paused):**
1. **Encrypted columns** — `User::two_factor_secret` (`encrypted`), `two_factor_recovery_codes` (`encrypted:array`), and `MAIL_PASSWORD_ENCRYPTED` (`Crypt::decrypt`). Rotating `APP_KEY` makes these unreadable → either decrypt-with-old then re-encrypt-with-new, or null `two_factor_*` for dev users (forces 2FA re-enroll) + re-encrypt the mail password.
2. **`familyfund_dev` is live to 3 envs** — `app1-familyfund-1`, `familyfund-pool0`, `familyfund-pool1`. Quiesce them and release pool/test leases (`lease-env` / `test-env` skills) before rotating the DB root password.

**Procedure (plan §1):**
1. Quiesce the pool + `app1` familyfund containers; release leases.
2. Handle encrypted data (decrypt/re-encrypt, or null 2FA + re-encrypt mail pw).
3. `APP_KEY`: `docker exec app1-familyfund-1 php artisan key:generate` (PHP is only in the container; writes the git-ignored `.env`). Repeat for stage. **New secrets ONLY in git-ignored `.env`/`.env.stage` — NEVER in tracked `.env.dev`.**
4. DB root pw: in `app1-mariadb-1`, `ALTER USER 'root'@'%' IDENTIFIED BY '<new>'; FLUSH PRIVILEGES;` → update `DB_PASSWORD` in git-ignored `.env`/`.env.stage` → restart pools with new env.
5. Invalidate (dev): `TRUNCATE password_resets;` `TRUNCATE sessions;`.
6. Verify `app1` + pools come back up; 2FA + mail work.

**Also lock in prevention (already staged in working trees):** commit the `.gitignore` hardening in FamilyFund + dstrader-docker. A pre-commit secret guard is already installed in `.git/hooks/pre-commit` of FamilyFund, dstrader, dstrader-docker.

**Open prod question (analyze-only):** confirm prod `APP_KEY` ≠ leaked dev key — after `ssh-add`, run:
`ssh jdtogni@REDACTED_PROD_HOST "grep '^APP_KEY=' ~/dev/FamilyFund/app1/family-fund-app/.env"`
and compare to `app1/family-fund-app/.env.dev`. If equal, **plan** a prod rotation window (don't execute).

**When done:** tick the checkboxes in `SECRET-ROTATION-PLAN.md`.
