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
  GITLEAKS_SCAN_HISTORY=1   Scan full git history (pre-publish) instead of just
                            the working tree. Default scans the working tree.
  TRIVY_REFRESH_CHECKS=1    Force re-download of the Trivy checks bundle.
  SEMGREP_MAX_ATTEMPTS=3    Retries for transient Semgrep config downloads.
  COMPOSER_IMAGE=composer:2 Image used for composer audit when composer is absent.
  PHPSTAN_MEMORY_LIMIT=1G   PHP memory_limit for the phpstan run.
  PHPSTAN_IMAGE=php:8.4-cli Image used for phpstan when no local PHP is present.
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

  # Capture the real exit code directly. Do NOT wrap "$@" in `if`: when the
  # check fails, an `if "$@"; then ...; fi` with no else returns 0, so a later
  # `$?` would read 0 and mask the failure (the --continue-on-error bug).
  local exit_code=0
  "$@" || exit_code=$?

  if [[ "$exit_code" -eq 0 ]]; then
    echo "[security-scan] PASS: $name" >&2
    return 0
  fi

  echo "[security-scan] FAIL: $name exited $exit_code" >&2

  if [[ "$CONTINUE_ON_ERROR" == "1" ]]; then
    FAILURES+=("$name ($exit_code)")
    return 0
  fi

  exit "$exit_code"
}

run_composer_audit() {
  # Always audit THIS repo's composer.lock. Prefer a local composer; otherwise
  # use the official composer image mounting our app dir. We deliberately do NOT
  # `docker exec` into a running FamilyFund container: in a multi-worktree setup
  # it can mount a different checkout and audit the wrong composer.lock.
  if command -v composer >/dev/null 2>&1; then
    run_step composer audit --locked
    return
  fi

  require_command docker
  echo "[security-scan] No local composer; auditing via ${COMPOSER_IMAGE:-composer:2} image." >&2
  run_step docker run --rm -v "$APP_DIR":/app -w /app "${COMPOSER_IMAGE:-composer:2}" audit --locked
}

run_phpstan() {
  # Larastan boots the Laravel app to resolve types, so phpstan needs PHP plus
  # the installed vendor/. Prefer a local PHP; otherwise run in a stock php
  # image with the app dir mounted. We deliberately do NOT `docker exec` a
  # running FamilyFund container: in a multi-worktree setup it can analyze a
  # different checkout (same rationale as run_composer_audit).
  if [[ ! -x vendor/bin/phpstan ]]; then
    echo "[security-scan] phpstan missing — run 'composer install' in $APP_DIR." >&2
    return 127
  fi

  local mem="${PHPSTAN_MEMORY_LIMIT:-1G}"

  if command -v php >/dev/null 2>&1; then
    run_step php -d memory_limit="$mem" vendor/bin/phpstan analyse --no-progress
    return
  fi

  require_command docker
  echo "[security-scan] No local PHP; running phpstan via ${PHPSTAN_IMAGE:-php:8.4-cli}." >&2

  # Force a single process in the container: the stock php image has no pcntl
  # (parallel workers crash) and small Docker VMs OOM with N workers. CI runs
  # phpstan under setup-php where parallel is fine. The override is mounted from
  # a host temp file so we never write into the repo tree.
  local override; override="$(mktemp)"
  cat >"$override" <<'NEON'
includes:
    - /app/phpstan.neon
parameters:
    parallel:
        maximumNumberOfProcesses: 1
NEON
  local rc=0
  run_step docker run --rm \
    -v "$APP_DIR":/app -v "$override":/tmp/phpstan-1proc.neon:ro -w /app \
    "${PHPSTAN_IMAGE:-php:8.4-cli}" \
    php -d memory_limit="$mem" vendor/bin/phpstan analyse \
      -c /tmp/phpstan-1proc.neon --no-progress || rc=$?
  rm -f "$override"
  return "$rc"
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

  # Note: p/laravel was dropped — the registry returns an intermittent HTTP 404
  # for it, which aborted the whole scan (exit 7) before any rules ran. p/php +
  # p/owasp-top-ten + p/security-audit cover the same ground reliably.
  # FamilyFund-specific rules live at repo-root .semgrep/familyfund.yml
  # (issue #17 / ED-0019). They run alongside the registry packs; the local
  # file never 404s, so it goes outside the retry loop's download-error path.
  local configs=(--config p/php --config p/owasp-top-ten --config p/security-audit)
  if [[ -f "$REPO_ROOT/.semgrep/familyfund.yml" ]]; then
    configs+=(--config "$REPO_ROOT/.semgrep/familyfund.yml")
  fi

  # The registry download can still flake transiently; retry only on download/
  # config errors (never on real findings) so a hiccup doesn't fail the gate.
  local attempts="${SEMGREP_MAX_ATTEMPTS:-3}" n=1 rc log
  log="$(mktemp)"
  while :; do
    rc=0
    echo "[security-scan] ${semgrep_env[*]} semgrep scan ${configs[*]} $APP_DIR" >&2
    "${semgrep_env[@]}" semgrep scan "${configs[@]}" "$APP_DIR" >"$log" 2>&1 || rc=$?
    cat "$log" >&2
    if [[ "$rc" -ne 0 && "$rc" -ne 1 ]] \
       && grep -qiE "Failed to download configuration|HTTP 404|invalid configuration file" "$log" \
       && [[ "$n" -lt "$attempts" ]]; then
      echo "[security-scan] Semgrep config download failed (transient); retry $((n + 1))/$attempts..." >&2
      n=$((n + 1)); sleep "$((n * 3))"; continue
    fi
    break
  done
  rm -f "$log"
  return "$rc"
}

run_trivy_config() {
  local cache_dir="${TRIVY_CACHE_DIR:-$HOME/Library/Caches/trivy}"
  [[ "$(uname -s)" == "Darwin" ]] || cache_dir="${TRIVY_CACHE_DIR:-$HOME/.cache/trivy}"

  # The checks-bundle download is what makes Trivy stall. Fetch it once (bounded
  # by Trivy's own --timeout), then always run with --skip-check-update so
  # repeat runs never hit the registry. Set TRIVY_REFRESH_CHECKS=1 to refresh.
  if [[ ! -d "$cache_dir/policy" || "${TRIVY_REFRESH_CHECKS:-0}" == "1" ]]; then
    echo "[security-scan] Caching Trivy checks bundle (one-time)..." >&2
    trivy config --cache-dir "$cache_dir" --timeout 3m --severity HIGH,CRITICAL \
      "$REPO_ROOT" >/dev/null 2>&1 || true
  fi

  # Skip vendored configs (laravel/sail Dockerfiles, node_modules) — we don't
  # own those; only our own Dockerfiles/compose should gate the build.
  # Pass --ignorefile explicitly: trivy reads .trivyignore from the cwd (here
  # $APP_DIR), not the scan target, so the repo-root file is otherwise skipped.
  local ignorefile=()
  [[ -f "$REPO_ROOT/.trivyignore" ]] && ignorefile=(--ignorefile "$REPO_ROOT/.trivyignore")
  run_step trivy config --cache-dir "$cache_dir" --skip-check-update \
    "${ignorefile[@]}" \
    --skip-dirs '**/vendor/**' --skip-dirs '**/node_modules/**' \
    --severity HIGH,CRITICAL --exit-code 1 "$REPO_ROOT"
}

run_npm_audit() {
  # Delegate to the shared gate so the local run and CI agree exactly. It runs
  # `npm audit --json` and fails only on advisories >= NPM_AUDIT_LEVEL whose
  # GHSA id is not in the repo-root .npm-audit-ignore list.
  NPM_AUDIT_IGNORE="$REPO_ROOT/.npm-audit-ignore" \
  NPM_AUDIT_LEVEL="${NPM_AUDIT_LEVEL:-moderate}" \
    run_step node "$APP_DIR/bin/npm-audit-gate.cjs"
}

run_gitleaks() {
  # Routine gate: scan the working tree (current committed state), which is what
  # "don't commit a secret" actually means. Deep git-history scanning + purge of
  # already-removed historical secrets is a separate pre-publish concern (see the
  # secret-rotation ticket); opt into it with GITLEAKS_SCAN_HISTORY=1.
  local mode=(--no-git)
  [[ "${GITLEAKS_SCAN_HISTORY:-0}" == "1" ]] && mode=()
  run_step gitleaks detect --source "$REPO_ROOT" "${mode[@]}" \
    --redact --config "$REPO_ROOT/.gitleaks.toml"
}

cd "$APP_DIR"

require_command npm
require_command gitleaks
require_command semgrep
require_command trivy

run_check "Composer audit" run_composer_audit
run_check "PHPStan (static analysis)" run_phpstan
run_check "npm audit" run_npm_audit
run_check "Gitleaks" run_gitleaks
run_check "Semgrep" run_semgrep_scan
run_check "Trivy config" run_trivy_config

if [[ "${#FAILURES[@]}" -gt 0 ]]; then
  echo "[security-scan] Failed checks:" >&2
  printf '[security-scan] - %s\n' "${FAILURES[@]}" >&2
  exit 1
fi

echo "[security-scan] All checks passed." >&2
