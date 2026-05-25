#!/usr/bin/env bash
# Run the FamilyFund test suite with up-to-date Vite assets.
#
# Builds public/build/manifest.json if it's missing or older than any
# source file under resources/, then runs `php artisan test` inside the
# familyfund container. All extra args are passed to artisan test.
#
# Container name autodetects: $FF_CONTAINER, then ffacl-familyfund-1,
# then familyfund.
#
# Usage:
#   bin/test.sh
#   bin/test.sh --filter=AuthorizationTest
#   FF_CONTAINER=app1-familyfund-1 bin/test.sh

set -euo pipefail

cd "$(dirname "$0")/.."

MANIFEST="public/build/manifest.json"
SRC_DIR="resources"

needs_build() {
  [[ ! -f "$MANIFEST" ]] && return 0
  # Stale if any file under resources/ is newer than the manifest.
  if [[ -n "$(find "$SRC_DIR" -type f -newer "$MANIFEST" -print -quit 2>/dev/null)" ]]; then
    return 0
  fi
  return 1
}

if needs_build; then
  echo "[test.sh] Vite assets stale; running npm run build…" >&2
  if [[ ! -d node_modules ]]; then
    npm install >&2
  fi
  npm run build >&2
else
  echo "[test.sh] Vite assets up-to-date." >&2
fi

if [[ -n "${FF_CONTAINER:-}" ]]; then
  CONTAINER="$FF_CONTAINER"
elif docker ps --format '{{.Names}}' | grep -qx ffacl-familyfund-1; then
  CONTAINER=ffacl-familyfund-1
elif docker ps --format '{{.Names}}' | grep -qx familyfund; then
  CONTAINER=familyfund
else
  echo "[test.sh] No familyfund container running. Set FF_CONTAINER or start docker." >&2
  exit 2
fi

echo "[test.sh] Running tests in $CONTAINER" >&2
exec docker exec "$CONTAINER" php artisan test "$@"
