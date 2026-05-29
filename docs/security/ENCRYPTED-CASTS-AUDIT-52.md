## FamilyFund — encrypted-casts audit + stage APP_KEY decision (issue #52)

**Date:** 2026-05-28 · **Status:** EVIDENCE COLLECTED — one model, two
columns, plus one env-based `Crypt::decrypt`. Recommendation: **Option 2
(fresh stage APP_KEY + null/skip encrypted columns on restore)**. Decision
feeds #47 (DB restore tooling) and #48 (`.env.stage.sops`).

This audit supports #36 step 3 (stand-up of the stage environment that
restores from a prod dump). Restoring an `encrypted`-cast column under a
different `APP_KEY` than the one that wrote it makes the column
undecryptable, so the restore tooling needs an explicit policy.

---

## 1. Eloquent encrypted casts — full surface

Repo searched with `rg` (case-insensitive) for every variant Laravel
supports — `'encrypted'`, `'encrypted:array'`, `'encrypted:object'`,
`'encrypted:collection'`, `'encrypted:json'` — across
`app1/family-fund-app/app/`. Single hit:

| Model | File | Column | Cast | Origin |
|---|---|---|---|---|
| `App\Models\User` | `app/Models/User.php:71` | `two_factor_secret` | `encrypted` | 2026-01-11 (Fortify-style 2FA) |
| `App\Models\User` | `app/Models/User.php:72` | `two_factor_recovery_codes` | `encrypted:array` | 2026-01-11 |

Schema (`database/migrations/2026_01_11_211413_add_two_factor_to_users_table.php`):

```php
$table->text('two_factor_secret')->nullable()->after('password');
$table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
$table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
```

`two_factor_confirmed_at` is **not** encrypted — it's a plain `timestamp`,
so it survives an APP_KEY change unaided.

Nothing else: no other model uses `'encrypted'` casts, and there is no
business/PII column encrypted at the cast layer (addresses, phones,
deposit details, credit-line records, etc. all store plaintext at rest
and rely on row-level ACL for confidentiality).

---

## 2. Non-cast `APP_KEY`-dependent ciphertext

`rg "Crypt::|encryptString|decryptString"` across the whole app surfaces
one non-cast site:

| Site | File | Mechanism |
|---|---|---|
| Mail SMTP password | `app/Providers/AppServiceProvider.php:84-90` | `Crypt::decrypt(env('MAIL_PASSWORD_ENCRYPTED'))` at boot, sets `mail.mailers.smtp.password` |

This is an opt-in mechanism — if `MAIL_PASSWORD_ENCRYPTED` is empty
(the current dev state per `SECRET-ROTATION-PLAN.md` line 9) the code
falls through to plain `MAIL_PASSWORD`. The ciphertext lives in the
git-ignored `.env` (and its `.sops` twin), **not in the DB**, so it's
already handled by per-env env files — not by the prod→stage DB restore.
Mentioned here for completeness because rotating `APP_KEY` invalidates
that ciphertext too (the pre-flight in `bin/rotate-dev-secrets.sh:51-55`
already guards against this).

---

## 3. What the prod dump actually contains

Two pieces of existing tooling already tell us what the prod data
volume of encrypted material is in practice:

- **`database/prod_to_dev.sql:163-171`** already issues
  `UPDATE users SET two_factor_secret = NULL, two_factor_recovery_codes = NULL, two_factor_confirmed_at = NULL`
  on every prod→dev restore (guarded so it no-ops on pre-2026-01 schemas).
  We have **established precedent** for treating encrypted-2FA as
  per-environment, not per-key.
- **`app1/family-fund-app/bin/rotate-dev-secrets.sh:49-55`** counts 2FA
  rows + MAIL_PASSWORD_ENCRYPTED before rotating the dev APP_KEY and
  aborts if either is nonzero. Per `SECRET-ROTATION-PLAN.md` line 9
  ("No encrypted data to migrate (2FA rows = 0, MAIL_PASSWORD_ENCRYPTED
  empty)"), dev currently has zero 2FA-enrolled users. Prod row count
  is not yet measured here (read-only SSH gated on the operator) but
  is expected to be similarly small / zero — 2FA is a recently added
  feature, no rollout campaign yet.

So in **expected volume terms**: prod-side encrypted ciphertext is
order-of-magnitude 0–single-digit rows, and there is **no encrypted
business data** in the DB at all. The decision below trades against
that constraint, not a bulk-data migration.

---

## 4. The two options (ticket framing)

The ticket states the two options for restoring the prod DB into stage:

| # | What | APP_KEY | Encrypted columns |
|---|---|---|---|
| 1 | Stage reuses prod's `APP_KEY` | identical | decode unchanged |
| 2 | Stage uses a fresh `APP_KEY`, restore tooling re-encrypts (or nulls) the affected columns | different | cleaner secret boundary |

### Option 1 — reuse prod `APP_KEY` on stage

**Pros**
- Zero re-encryption work in the restore pipeline.
- Existing prod 2FA enrollments keep working in stage (if anyone ever
  exercises stage as a real user — they won't, see "Stage usage" below).
- One fewer secret to rotate independently.

**Cons (load-bearing)**
- **Blast-radius coupling.** Any compromise of the stage `.env`, stage
  host (`spirit`), or stage's `.env.stage.sops` recipient set leaks the
  prod `APP_KEY`. Stage will run on the same box as prod (#46 stands
  the stage stack up on spirit), under more permissive access policies
  for cutover testing — exactly the surface we're trying to keep out
  of the prod key blast-radius.
- **Rotation coupling.** Any future prod `APP_KEY` rotation (e.g. if
  a stage operator's laptop is suspect) forces a synchronised stage
  rotation, or stage starts orphaning newly-encrypted columns.
- **Negates the point of cutting stage out as a separate env.** The
  whole reason stage exists in the go-public plan is to derisk cutover
  with a copy of prod data under a *different* trust boundary than
  prod itself. Sharing the encryption key undoes that.
- **Public-track signal.** Documenting "stage reuses prod APP_KEY" in
  a public repo is itself bad opsec — even though the key value
  isn't disclosed, the topology hint is.

### Option 2 — fresh stage `APP_KEY`, null/re-encrypt on restore *(RECOMMENDED)*

**Pros**
- Strict separation of prod and stage cryptographic material — stage
  compromise does not transfer to prod encrypted data.
- Aligns with the existing per-env `.env.*.sops` model (we already
  have per-env `APP_KEY`s for dev; stage is the same shape).
- **Re-encryption cost is trivial here** because the entire encrypted
  surface is "2FA secrets + recovery codes" on a single table column
  pair, and we already null them on prod→dev. The restore tool just
  reuses the SQL from `prod_to_dev.sql:165-171` (idempotent +
  schema-guarded) before swapping in the stage `APP_KEY`.
- **`MAIL_PASSWORD_ENCRYPTED` is env-only**, so it's already
  per-environment and out of the DB-restore path entirely. Stage's
  `.env.stage.sops` carries its own value (or empty, since stage will
  route to a non-prod mail target per the cutover plan).

**Cons**
- Any stage user who had enrolled in 2FA (currently: zero) has to
  re-enroll after a fresh stage rebuild. Acceptable — stage is for
  the cutover team, not end users.
- The restore tooling needs the null-step. It's ~5 lines of guarded
  SQL we can lift verbatim from `prod_to_dev.sql`.

### Stage usage assumption (why the cons in Option 2 don't bite)

Stage is **not** customer-facing — its job is to (a) prove the prod
backup actually restores, (b) host the cutover dress-rehearsal, (c)
sanity-check schema/seed/migration drift before flipping DNS. End
users will not log in to stage; the cutover team will. Forcing the
cutover team to re-enroll 2FA after each prod→stage rebuild is, if
anything, **desirable** — it proves the 2FA flow works against the
restored data.

---

## 5. Recommendation

**Adopt Option 2.** The re-encryption cost the ticket worried about is
near-zero in this codebase because:

1. Only one model has encrypted casts (User 2FA).
2. We already have the null-on-restore SQL in `prod_to_dev.sql` —
   the prod→stage restore tooling can reuse it verbatim.
3. There is no encrypted business/PII data anywhere in the DB.
4. `MAIL_PASSWORD_ENCRYPTED` is env-side, not DB-side, and is already
   per-environment.

The cleaner trust boundary is worth far more than the cost of nulling
two columns on restore.

---

## 6. Forward links — what this decision drives

- **#47 (DB restore tooling: `prod_dump → stage`)** — must include a
  `NULL`-on-restore step for `users.two_factor_secret`,
  `users.two_factor_recovery_codes`, `users.two_factor_confirmed_at`.
  Lift the guarded `UPDATE` from `database/prod_to_dev.sql:163-171`
  so the same pattern covers prod→dev and prod→stage. The
  ticket's "(with optional re-encrypt)" parenthetical reduces to "no
  re-encrypt needed; null them" per this audit.
- **#48 (`.env.stage.sops` + `secrets.sh stage` profile)** — must
  generate a **fresh** stage `APP_KEY` (`php artisan key:generate`
  against the stage container's `.env.stage`), distinct from prod.
  Document that prod and stage `APP_KEY`s are independent and rotate
  independently.
- **`MAIL_PASSWORD_ENCRYPTED` in `.env.stage.sops`** — leave empty
  unless/until stage genuinely needs SMTP (cutover team mail traffic
  should route to a stage-only mailer per #46). If set, it must be
  ciphertext under the **stage** `APP_KEY`, not prod's.
- **Future change-watch.** If any future ticket adds an `'encrypted'`
  cast to a model with business data (e.g. SSN, bank routing,
  customer address), this audit becomes outdated and the
  re-encryption-vs-null tradeoff must be revisited per column —
  business data can't be nulled the way 2FA secrets can. Add an entry
  to `docs/ENGINEERING_DECISIONS.md` if/when that happens.

---

## 7. Reproducing this audit

```bash
# 1. Eloquent encrypted casts (every variant)
rg -n "'encrypted(:[a-z]+)?'" app1/family-fund-app/app/Models/

# 2. Non-cast APP_KEY-dependent ciphertext
rg -n "Crypt::|encryptString|decryptString" app1/family-fund-app/

# 3. Schema for the affected columns
rg -n "two_factor_secret|two_factor_recovery_codes" \
   app1/family-fund-app/database/migrations/

# 4. Existing null-on-restore pattern (precedent for #47)
sed -n '160,175p' database/prod_to_dev.sql

# 5. Dev's pre-rotation guard (mirror in any stage rotation runbook)
sed -n '40,60p' app1/family-fund-app/bin/rotate-dev-secrets.sh
```
