#!/usr/bin/env bash
#
# rotate-dev-secrets.sh — Stage 1 of the FamilyFund secret-rotation automation:
# rotate the dev APP_KEY at runtime (NO DB downtime) + invalidate sessions/tokens,
# with a pre-flight safety gate. See the SECRET-ROTATION-RUNBOOK.md in the
# private familyfund-secrets/docs/ repo.
#
# Scope: APP_KEY only. It does NOT rotate the DB password (that step has downtime
# and is not yet automated). It aborts if any 'encrypted' DB data exists, because
# rotating APP_KEY would orphan it (handle per the runbook before rotating).
#
# This script rotates the value in the running container's git-ignored .env only;
# it does NOT touch any encrypted twin. Since the .sops twins now live in the
# private familyfund-secrets repo (resolved via $FF_SECRETS_DIR — see secrets.sh),
# after rotating re-encrypt + commit there:
#     bin/secrets.sh encrypt dev   # writes <FF_SECRETS_DIR>/.../.env.dev.sops
#     ( cd "${FF_SECRETS_DIR:-~/dev/familyfund-secrets}" && git add -A && git commit )
#
# Usage:
#   bin/rotate-dev-secrets.sh [-y]
#     -y / --yes / FF_ROTATE_YES=1   skip the confirmation prompt
#   Overridable env:
#     FF_APP_CONTAINER (app1-familyfund-1)  FF_DB_CONTAINER (app1-mariadb-1)
#     FF_APP_URL (http://localhost:3001)    FF_ROTATION_BACKUP (~/familyfund-secret-rotation-backup/<date>)
#
set -euo pipefail

APP_CONTAINER="${FF_APP_CONTAINER:-app1-familyfund-1}"
DB_CONTAINER="${FF_DB_CONTAINER:-app1-mariadb-1}"
APP_URL="${FF_APP_URL:-http://localhost:3001}"
BACKUP_DIR="${FF_ROTATION_BACKUP:-$HOME/familyfund-secret-rotation-backup/$(date +%F)}"
DC="$(command -v docker || echo /usr/local/bin/docker)"
YES="${FF_ROTATE_YES:-0}"
[ "${1:-}" = "-y" ] || [ "${1:-}" = "--yes" ] && YES=1

log()  { printf '\n=== %s ===\n' "$*"; }
note() { printf '  %s\n' "$*"; }
die()  { printf 'ABORT: %s\n' "$*" >&2; exit 1; }
keysha() { printf '%s' "$1" | shasum | cut -c1-12; }

# ── pre-flight ───────────────────────────────────────────────────────────────
log "pre-flight"
[ -x "$DC" ] || die "docker not found (set PATH or FF_* overrides)"
"$DC" inspect "$APP_CONTAINER" >/dev/null 2>&1 || die "$APP_CONTAINER not running"
"$DC" inspect "$DB_CONTAINER"  >/dev/null 2>&1 || die "$DB_CONTAINER not running"
note "app=$APP_CONTAINER db=$DB_CONTAINER url=$APP_URL"

# encrypted-data guard (query via the app's own DB connection — correct creds)
twofa="$("$DC" exec "$APP_CONTAINER" php artisan tinker --execute='echo \App\Models\User::whereNotNull("two_factor_secret")->count();' 2>/dev/null | tr -dc '0-9')"
twofa="${twofa:-?}"
mailenc="$("$DC" exec "$APP_CONTAINER" sh -lc 'grep -cE "^MAIL_PASSWORD_ENCRYPTED=.+" .env 2>/dev/null || true' | tr -dc '0-9')"
mailenc="${mailenc:-0}"
note "users with 2FA: $twofa | MAIL_PASSWORD_ENCRYPTED set: $mailenc"
[ "$twofa" = "0" ] || die "2FA rows present — rotating APP_KEY orphans them. Null them or re-encrypt first (see runbook)."
[ "$mailenc" = "0" ] || die "MAIL_PASSWORD_ENCRYPTED set — decrypt-then-re-encrypt with the new key first (see runbook)."

# informational: who's on the shared dev DB (will be logged out; DB not broken by APP_KEY)
if [ -x "$HOME/.familyfund-pool/pool.sh" ]; then
  leased="$(PATH=/usr/local/bin:$PATH "$HOME/.familyfund-pool/pool.sh" list 2>/dev/null | awk '$4=="leased"{print $1}' | tr '\n' ' ')"
  [ -n "$leased" ] && note "NOTE: leased preview pools ($leased) share this dev DB — their web users get logged out (DB stays up)."
fi

env="$APP_CONTAINER":; # silence shellcheck about unused style
old_key="$("$DC" exec "$APP_CONTAINER" sh -lc 'grep -E "^APP_KEY=" .env | cut -d= -f2-' | tr -d '\r')"
[ -n "$old_key" ] || die "could not read current APP_KEY from $APP_CONTAINER:.env"
note "current APP_KEY sha: $(keysha "$old_key")"

if [ "$YES" != "1" ]; then
  printf '\nProceed with APP_KEY rotation (logs out all dev/pool web sessions)? [y/N] '
  read -r ans; case "$ans" in y|Y) ;; *) die "cancelled by user";; esac
fi

# ── rotate ───────────────────────────────────────────────────────────────────
log "backup old APP_KEY off-repo (perms 600)"
mkdir -p "$BACKUP_DIR"
( umask 077; printf 'APP_KEY (pre-rotation %s) = %s\n' "$(date -u +%FT%TZ)" "$old_key" \
    >> "$BACKUP_DIR/old-app-key.SECRET.txt" )
chmod 600 "$BACKUP_DIR/old-app-key.SECRET.txt"
note "appended to $BACKUP_DIR/old-app-key.SECRET.txt"

log "regenerate APP_KEY (writes via .env -> git-ignored .env.dev)"
"$DC" exec "$APP_CONTAINER" php artisan key:generate --force
new_key="$("$DC" exec "$APP_CONTAINER" sh -lc 'grep -E "^APP_KEY=" .env | cut -d= -f2-' | tr -d '\r')"
[ "$(keysha "$new_key")" != "$(keysha "$old_key")" ] || die "APP_KEY did not change"
note "new APP_KEY sha: $(keysha "$new_key")"

log "invalidate sessions + password-reset tokens"
"$DC" exec "$APP_CONTAINER" php artisan tinker --execute='
  foreach (["sessions","password_resets"] as $t) {
    try { \DB::statement("TRUNCATE TABLE $t"); echo "truncated $t\n"; }
    catch (\Throwable $e) { echo "skip $t: ".$e->getMessage()."\n"; }
  }' 2>/dev/null || true

log "clear caches + restart app"
"$DC" exec "$APP_CONTAINER" php artisan optimize:clear >/dev/null 2>&1 || true
"$DC" restart "$APP_CONTAINER" >/dev/null

# ── verify ───────────────────────────────────────────────────────────────────
log "verify"
code=000
for _ in $(seq 1 20); do
  code="$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/login" 2>/dev/null || echo 000)"
  [ "$code" = "200" ] && break; sleep 2
done
live_sha="$("$DC" exec "$APP_CONTAINER" php artisan tinker --execute='echo substr(sha1(config("app.key")),0,12);' 2>/dev/null | tr -dc '0-9a-f')"
note "$APP_URL/login -> $code"
note "running app key sha: $live_sha (want $(keysha "$new_key"))"
[ "$code" = "200" ] || die "app not serving after rotation"
[ "$live_sha" = "$(keysha "$new_key")" ] || die "running key != new key (config cache?)"
log "APP_KEY rotation complete ✓"
