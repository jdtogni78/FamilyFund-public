#!/usr/bin/env bash
# Run the fast local security checks used by CI.
#
# By default this exits on the first failing check. Use --continue-on-error
# or SECURITY_SCAN_CONTINUE=1 to run every check and report all failures.

set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
REPO_ROOT="$(cd "$APP_DIR/../.." && pwd)"
LOCAL_TOOLS_BIN="$REPO_ROOT/.tools/bin"
LOCAL_TOOLS_HOME="$REPO_ROOT/.tools/home"
LOCAL_CERT_FILE="$REPO_ROOT/.tools/semgrep-venv/lib/python3.9/site-packages/certifi/cacert.pem"

if [[ -d "$LOCAL_TOOLS_BIN" ]]; then
  export PATH="$LOCAL_TOOLS_BIN:$PATH"
fi

CONTINUE_ON_ERROR="${SECURITY_SCAN_CONTINUE:-0}"
FAILURES=()

usage() {
  cat <<'USAGE'
Usage: bin/security-scan.sh [--continue-on-error]

Options:
  --continue-on-error  Run all checks and summarize failures at the end.
  -h, --help           Show this help.

Environment:
  SECURITY_SCAN_CONTINUE=1  Same as --continue-on-error.
  NPM_AUDIT_LEVEL=moderate  npm audit threshold. Defaults to moderate.
USAGE
}

for arg in "$@"; do
  case "$arg" in
    --continue-on-error|--all)
      CONTINUE_ON_ERROR=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "[security-scan] Unknown argument: $arg" >&2
      usage >&2
      exit 2
      ;;
  esac
done

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "[security-scan] Missing required command: $1" >&2
    echo "[security-scan] Install it locally or run the GitHub workflow." >&2
    exit 127
  fi
}

run_step() {
  echo "[security-scan] $*" >&2
  "$@"
}

run_check() {
  local name="$1"
  shift

  echo "[security-scan] === $name ===" >&2
  if "$@"; then
    echo "[security-scan] PASS: $name" >&2
    return 0
  fi

  local exit_code=$?
  echo "[security-scan] FAIL: $name exited $exit_code" >&2

  if [[ "$CONTINUE_ON_ERROR" == "1" ]]; then
    FAILURES+=("$name ($exit_code)")
    return 0
  fi

  exit "$exit_code"
}

detect_container() {
  if [[ -n "${FF_CONTAINER:-}" ]]; then
    echo "$FF_CONTAINER"
  elif docker ps --format '{{.Names}}' | grep -qx app1-familyfund-1; then
    echo app1-familyfund-1
  elif docker ps --format '{{.Names}}' | grep -qx ffacl-familyfund-1; then
    echo ffacl-familyfund-1
  elif docker ps --format '{{.Names}}' | grep -qx familyfund; then
    echo familyfund
  else
    return 1
  fi
}

run_composer_audit() {
  if command -v composer >/dev/null 2>&1; then
    run_step composer audit --locked
    return
  fi

  require_command docker
  local container
  if ! container="$(detect_container)"; then
    echo "[security-scan] Missing composer and no FamilyFund container is running." >&2
    echo "[security-scan] Start Docker or install composer locally." >&2
    exit 127
  fi

  run_step docker exec "$container" composer audit --locked
}

run_semgrep_scan() {
  mkdir -p "$LOCAL_TOOLS_HOME"

  local semgrep_env=(
    env
    "HOME=$LOCAL_TOOLS_HOME"
    "SEMGREP_SEND_METRICS=${SEMGREP_SEND_METRICS:-off}"
  )

  if [[ -f "$LOCAL_CERT_FILE" ]]; then
    semgrep_env+=("SSL_CERT_FILE=$LOCAL_CERT_FILE")
  fi

  run_step "${semgrep_env[@]}" semgrep scan --config p/laravel --config p/php --config p/owasp-top-ten "$APP_DIR"
}

cd "$APP_DIR"

require_command npm
require_command gitleaks
require_command semgrep
require_command trivy

run_check "Composer audit" run_composer_audit
run_check "npm audit" run_step npm audit --audit-level="${NPM_AUDIT_LEVEL:-moderate}"
run_check "Gitleaks" run_step gitleaks detect --source "$REPO_ROOT" --redact --config "$REPO_ROOT/.gitleaks.toml"
run_check "Semgrep" run_semgrep_scan
run_check "Trivy config" run_step trivy config --severity HIGH,CRITICAL --exit-code 1 "$REPO_ROOT"

if [[ "${#FAILURES[@]}" -gt 0 ]]; then
  echo "[security-scan] Failed checks:" >&2
  printf '[security-scan] - %s\n' "${FAILURES[@]}" >&2
  exit 1
fi

echo "[security-scan] All checks passed." >&2
