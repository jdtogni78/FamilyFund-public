#!/usr/bin/env bash
# Restore a fresh dev DB from a prod dump:
#   1. Anonymize (database/prod_to_dev.sql): rotate ALL user passwords to a known
#      dev hash (no real prod hash survives) and scrub ALL PII — names, emails,
#      birthdays, addresses, phones, government IDs, free-text notes, sessions.
#   2. Run pending migrations (e.g. credit-line allocation backfill —
#      QA-2026-05-19 #2).
#   3. Seed permissions + QA test users (QA-2026-05-19 #5 / #14).
#
# Usage (from any directory; the FF container must be running):
#   app1/family-fund-app/bin/prod-to-dev.sh
#
# Overrides (env vars):
#   FF_CONTAINER  family-fund container name (default: familyfund-dev)
#   DB_HOST       host that owns the DB (default: 127.0.0.1)
#   DB_PORT       (default: 3306)
#   DB_USER       (default: famfun_dev)
#   DB_PASS       (default: 1234)
#   DB_NAME       (default: familyfund_dev)
#   SQL_FILE      anonymization SQL (default: <repo>/database/prod_to_dev.sql)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Default: repo-level database/prod_to_dev.sql, three levels up from bin/
# (bin/ -> family-fund-app/ -> app1/ -> repo root/database).
REPO_DB_DIR="$(cd "$SCRIPT_DIR/../../../database" && pwd)"
SQL_FILE="${SQL_FILE:-$REPO_DB_DIR/prod_to_dev.sql}"

FF_CONTAINER="${FF_CONTAINER:-familyfund-dev}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-famfun_dev}"
DB_PASS="${DB_PASS:-1234}"
DB_NAME="${DB_NAME:-familyfund_dev}"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "[prod_to_dev] expected $SQL_FILE to exist." >&2
  exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
  echo "[prod_to_dev] mysql client not found on PATH. Install it or pipe the SQL through the container." >&2
  exit 2
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$FF_CONTAINER"; then
  echo "[prod_to_dev] container '$FF_CONTAINER' is not running. Set FF_CONTAINER or start the stack." >&2
  exit 3
fi

echo "[prod_to_dev] 1/3 anonymizing $DB_NAME via $SQL_FILE…"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "-p$DB_PASS" "$DB_NAME" < "$SQL_FILE"

echo "[prod_to_dev] 2/3 running pending migrations in $FF_CONTAINER…"
docker exec "$FF_CONTAINER" php artisan migrate --force

echo "[prod_to_dev] 3/3 seeding permissions + QA users…"
docker exec "$FF_CONTAINER" php artisan db:seed --class=RolesAndPermissionsSeeder --force
docker exec "$FF_CONTAINER" php artisan db:seed --class=QaTestUsersSeeder --force

echo "[prod_to_dev] done. Test users (password=password):"
echo "  qa-fund-admin@test.local         (fund-admin)"
echo "  qa-financial-manager@test.local  (financial-manager)"
echo "  qa-beneficiary@test.local        (beneficiary)"
echo "  claude@test.local                (fund-admin)"
