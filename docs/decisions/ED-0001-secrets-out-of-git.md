# ED-0001 — Secrets stay out of git, rotate & scan (not a secrets server)


- **Date:** 2026-05-22 · **Status:** Accepted
- **Context:** A public `.env.dev` leak exposed dev/stage `APP_KEY` + DB password
  (prod creds live separately on spirit and were not exposed). We needed a
  secret-management posture proportional to a localhost/LAN docker setup.
- **Decision:** Keep all real secrets in **git-ignored** files (`.env`, `.env.dev`,
  `.env.stage`, `app1/.env`); the only tracked env file is `.env.example`
  (placeholders). De-hardcode DB creds from compose → `${FF_DB_*:-changeme}` read
  from git-ignored `app1/.env`. Add a repeatable **rotation runbook** (quarterly +
  on leak), `bin/rotate-dev-secrets.sh` (APP_KEY stage), and **secret scanning**
  (gitleaks locally + in CI). Chose this over standing up a secrets server (Vault),
  which was judged overkill for the footprint.
- **Consequences:** A fresh clone/worktree must recreate `app1/.env` or compose
  uses `changeme` and the DB fails. Encrypted DB columns (`two_factor_*`) are
  APP_KEY-bound, so APP_KEY rotation has a pre-flight guard. Prod creds remain
  hardcoded/weak in dstrader-aws (analyze-only; out of this repo's scope).
- **Source:** `docs/security/SECURITY-EXPOSURE.md` (in this repo); rotation
  plan + runbook live in the private `familyfund-secrets/docs/` repo
  (`SECRET-ROTATION-PLAN.md`, `SECRET-ROTATION-RUNBOOK.md`).
