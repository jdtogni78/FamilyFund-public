# Runbook — dstrader ⇄ FamilyFund service-token rotation

> Scope: the Sanctum API tokens dstrader uses to call FamilyFund's `/api/*`
> surface. Background: [ED-0016](../ENGINEERING_DECISIONS.md), issue #82.

## Overview

FamilyFund's API is `auth:sanctum`-locked, and reference-data writes are
system-admin-only (ED-0016). dstrader authenticates as a single **system-admin
service user** (`config('familyfund.service_user_email')`, default
`dstrader-service@familyfund.local`, created by `DstraderServiceUserSeeder`)
using **two named, expiring Sanctum tokens**:

| Token name        | Consumer                                   | Env var                  | Calls |
|-------------------|--------------------------------------------|--------------------------|-------|
| `dstrader-app`    | dstrader Java app (`HTTPUtils`, `HolidayAPIClient`) | `FF_API_TOKEN`           | `asset_prices_bulk_update`, `portfolio_assets_bulk_update`, `trade_portfolios`, `asset_prices/gaps`, `exchange_holidays` reads |
| `dstrader-scheduler` | bash report scheduler (`familyfund_bashlib.sh`) | `FF_SCHEDULER_API_TOKEN` | `schedule_jobs`, `fund_reports`, `account_matching_rules`, `matching_rules` reads |

Tokens are stored encrypted (SOPS+age; see ED-0002 / #80 / dstrader-aws #8) and
injected as the env vars above. **Never commit a plaintext token** — the
`familyfund.properties` files hold URLs only.

Default TTL is **90 days**. Rotation cadence is **60 days** (30-day safety
margin).

## Minting / rotating (the command)

All on the FamilyFund side (`php artisan`), prod-allowed:

```bash
# Mint/rotate a token (prints ONLY the plaintext token on stdout):
php artisan security:service-token --name=app       --rotate --ttl=90
php artisan security:service-token --name=scheduler  --rotate --ttl=90

# After confirming the consumer works on the new token, revoke the old one(s):
php artisan security:service-token --name=app --revoke-previous

# Housekeeping / monitoring:
php artisan security:service-token --prune-expired
php artisan security:service-token --check-expiry --warn-days=14   # exit!=0 if near expiry
```

`--rotate` keeps the prior token valid (overlap) until `--revoke-previous`, so
rotation is **zero-downtime**.

## Automated rotation (the cron)

The actual rotation runs from **dstrader-docker** (it must distribute the new
token to dstrader), via `rotate_ff_tokens.sh`. For each token name it:

1. `NEW=$(ff_artisan security:service-token --name=<name> --rotate --ttl=90)`
2. writes `NEW` into the SOPS secret + runtime env (`FF_API_TOKEN` /
   `FF_SCHEDULER_API_TOKEN`)
3. reloads the consumer (restart the dstrader Java container for `app`; the bash
   scheduler picks up the new env on its next run)
4. **verifies** with a smoke call (`ff_get asset_prices/gaps` → 2xx); aborts and
   keeps the old token if it fails
5. on success, `ff_artisan security:service-token --name=<name> --revoke-previous`
6. logs to `dstrader/prod/logs/ff_token_rotation.log`

Cron entries:

```cron
# dstrader-docker host crontab — rotate every 60 days (inside the 90d TTL)
0 3 1 */2 *  /path/to/rotate_ff_tokens.sh >> /path/dstrader/prod/logs/ff_token_rotation.log 2>&1
```

```cron
# FamilyFund container — drives the Laravel scheduler (expiry warning + prune).
# Confirm this is installed; the credit-line jobs already depend on it.
* * * * *  php /app/artisan schedule:run >> /dev/null 2>&1
```

The Laravel scheduler (defined in `routes/console.php` — this app uses the
Laravel 11 minimal bootstrap, so `app/Console/Kernel.php::schedule()` is **not**
wired; `php artisan schedule:list` is the source of truth) runs `--check-expiry`
daily (emails `config('familyfund.alert_email')` + logs a WARNING if a token is
within 14 days of expiry) and `--prune-expired` weekly. This is the safety net if
the external rotation cron fails — a token can't silently lapse and kill price
feeds.

## Emergency rotation (suspected leak)

1. Mint a replacement immediately:
   `TOKEN=$(php artisan security:service-token --name=app --rotate --ttl=90)`
2. Distribute: update the SOPS secret + runtime env with `$TOKEN`.
3. Reload the consumer (restart dstrader Java container / next scheduler run).
4. **Verify** (below).
5. Revoke the compromised token: `php artisan security:service-token --name=app --revoke-previous`.
6. If the whole account is suspect, revoke *all* its tokens in tinker:
   `User::where('email', config('familyfund.service_user_email'))->first()->tokens()->delete();`
   then re-mint both `app` and `scheduler`.

## Verification

```bash
# As the new app token, a read must succeed (2xx):
curl -s -o /dev/null -w '%{http_code}\n' \
  -H "Authorization: Bearer $FF_API_TOKEN" \
  -H "Accept: application/json" \
  "$FF_BASE/api/asset_prices/gaps"            # expect 200

# A reference write must succeed for the (system-admin) service token (2xx):
#   POST $FF_BASE/api/asset_prices_bulk_update  with a valid price payload
# A non-admin token must get 403 on that same write (proves the gate).
```

## Rollback

The prior token is valid until `--revoke-previous`. To roll back, restore the
previous token from SOPS history into the env and restart the consumer; do not
revoke until the rollback is confirmed.

## Dependencies / TODO

- Automated **distribution** (the re-encrypt step) depends on SOPS+age being live
  for dstrader-docker (#80 / dstrader-aws #8). Until then `rotate_ff_tokens.sh`
  writes a git-ignored runtime env file and the re-encrypt step is manual.
- `rotate_ff_tokens.sh` itself is added under #82's dstrader-docker work.
