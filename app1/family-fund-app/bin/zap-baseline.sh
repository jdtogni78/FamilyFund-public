#!/usr/bin/env bash
# Run an OWASP ZAP baseline scan against a local or preview FamilyFund URL.
#
# Usage:
#   bin/zap-baseline.sh
#   ZAP_TARGET=http://localhost:3001 bin/zap-baseline.sh
#   ZAP_TARGET=http://host.docker.internal:3001 bin/zap-baseline.sh

set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
REPORT_DIR="${ZAP_REPORT_DIR:-$APP_DIR/storage/security/zap}"
TARGET="${ZAP_TARGET:-http://host.docker.internal:3001}"
ZAP_IMAGE="${ZAP_IMAGE:-ghcr.io/zaproxy/zaproxy:stable}"

mkdir -p "$REPORT_DIR"

echo "[zap-baseline] Target: $TARGET" >&2
echo "[zap-baseline] Reports: $REPORT_DIR" >&2

docker run --rm \
  -v "$REPORT_DIR:/zap/wrk:rw" \
  "$ZAP_IMAGE" \
  zap-baseline.py \
  -t "$TARGET" \
  -r zap-baseline.html \
  -J zap-baseline.json \
  -w zap-baseline.md
