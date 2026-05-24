#!/usr/bin/env bash
# Authenticated OWASP ZAP baseline scan (#13 / #49 item A).
#
# Scans a running FamilyFund instance AS AN AUTHENTICATED USER by injecting a
# Sanctum bearer token into the Authorization header of every request, so the
# `/api/*` surface (auth:sanctum) is exercised under a real identity. Run it as a
# low-privilege role (default: beneficiary) to confirm the object-level / tenant
# scoping holds — the previously-reported anonymous reads and beneficiary IDOR
# should no longer surface.
#
# Usage:
#   # mint the token automatically from the app container:
#   FF_CONTAINER=familyfund-test1 ZAP_TARGET=http://host.docker.internal:3011 \
#     ZAP_AUTH_ROLE=beneficiary bin/zap-authenticated.sh
#
#   # or supply a token directly:
#   ZAP_AUTH_TOKEN=xxx ZAP_TARGET=http://host.docker.internal:3001 bin/zap-authenticated.sh
#
# Env:
#   ZAP_TARGET       target base URL as seen from inside the ZAP container
#   ZAP_AUTH_TOKEN   Sanctum plaintext token (skips minting)
#   FF_CONTAINER     app container to mint a token in (php artisan security:dev-token)
#   ZAP_AUTH_ROLE    role alias to mint for (default: beneficiary)
#   ZAP_REPORT_DIR   report output dir (default: storage/security/zap)
#   ZAP_IMAGE        ZAP image (default: ghcr.io/zaproxy/zaproxy:stable)

set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
REPORT_DIR="${ZAP_REPORT_DIR:-$APP_DIR/storage/security/zap}"
TARGET="${ZAP_TARGET:-http://host.docker.internal:3001}"
ZAP_IMAGE="${ZAP_IMAGE:-ghcr.io/zaproxy/zaproxy:stable}"
ROLE="${ZAP_AUTH_ROLE:-beneficiary}"

TOKEN="${ZAP_AUTH_TOKEN:-}"
if [ -z "$TOKEN" ]; then
  if [ -n "${FF_CONTAINER:-}" ]; then
    echo "[zap-auth] Minting token for role '$ROLE' in $FF_CONTAINER" >&2
    TOKEN="$(docker exec "$FF_CONTAINER" php artisan security:dev-token --as="$ROLE" | tail -1 | tr -d '\r')"
  fi
fi
if [ -z "$TOKEN" ]; then
  echo "[zap-auth] No bearer token. Set ZAP_AUTH_TOKEN, or FF_CONTAINER to mint one." >&2
  exit 2
fi

mkdir -p "$REPORT_DIR"
echo "[zap-auth] Target:  $TARGET (role=$ROLE)" >&2
echo "[zap-auth] Reports: $REPORT_DIR" >&2

# ZAP "replacer" add-on: add an Authorization: Bearer <token> header to every
# outgoing request so authenticated (sanctum) endpoints are reached. The -z
# value is forwarded VERBATIM to ZAP, so each pair needs its own `-config`, and
# the replacement value (which contains a space) must be inner-quoted.
ZAP_REPLACER="-config replacer.full_list(0).description=auth -config replacer.full_list(0).enabled=true -config replacer.full_list(0).matchtype=REQ_HEADER -config replacer.full_list(0).matchstr=Authorization -config replacer.full_list(0).regex=false -config \"replacer.full_list(0).replacement=Bearer ${TOKEN}\""

# On Linux CI the ZAP container reaches the app via the host network
# (host.docker.internal is Docker-Desktop-only); set ZAP_DOCKER_NET=host there.
NET_ARGS=()
[ -n "${ZAP_DOCKER_NET:-}" ] && NET_ARGS=(--network "$ZAP_DOCKER_NET")

docker run --rm \
  ${NET_ARGS[@]+"${NET_ARGS[@]}"} \
  -v "$REPORT_DIR:/zap/wrk:rw" \
  "$ZAP_IMAGE" \
  zap-baseline.py \
  -t "$TARGET" \
  -r zap-authenticated.html \
  -J zap-authenticated.json \
  -w zap-authenticated.md \
  -z "$ZAP_REPLACER"
