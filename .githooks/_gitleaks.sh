#!/usr/bin/env bash
# Shared gitleaks runner for FamilyFund git hooks.
# Sourced by .githooks/pre-commit and .githooks/pre-push — not executed directly.
#
# Why we extract content instead of using `gitleaks protect`/`detect --log-opts`:
# those modes need a real .git directory, which breaks for (a) linked git
# worktrees (where .git is a file) and (b) the Docker fallback (which would have
# to mount the common git dir). Instead we extract the staged / outgoing file
# CONTENT into a temp tree that mirrors repo-relative paths — so the path-based
# allowlists in .gitleaks.toml still match — then scan it with the stable
# `detect --no-git` mode. Works identically from any worktree, with or without a
# local gitleaks binary.

GL_REPO_ROOT="$(git rev-parse --show-toplevel)"
GL_CONFIG="$GL_REPO_ROOT/.gitleaks.toml"
GL_IMAGE="${GITLEAKS_IMAGE:-ghcr.io/gitleaks/gitleaks:latest}"

gl_warn() { printf '%s\n' "$*" >&2; }

# gl_scan_dir <dir>
# Scan a directory tree with gitleaks. Returns gitleaks' exit code
# (0 = clean, non-zero = leaks found OR a scan error). If no scanner is
# available it warns and returns 0 — fail-open, because the CI "Secret Scan"
# job is the hard gate; the local hook is an early-warning convenience.
gl_scan_dir() {
  local dir="$1"
  local bin="${GITLEAKS_BIN:-}"
  if [ -z "$bin" ] && command -v gitleaks >/dev/null 2>&1; then bin="gitleaks"; fi

  # Note: arrays are expanded with the ${a[@]+"${a[@]}"} idiom so an *empty*
  # array doesn't trip `set -u` on bash 3.2 (macOS system bash).
  if [ -n "$bin" ]; then
    local cfg=()
    [ -f "$GL_CONFIG" ] && cfg=(--config "$GL_CONFIG")
    "$bin" detect --no-git --source "$dir" --redact --no-banner "${cfg[@]+"${cfg[@]}"}"
    return $?
  fi

  if command -v docker >/dev/null 2>&1; then
    local mounts=(-v "$dir:/scan:ro") cfg=()
    if [ -f "$GL_CONFIG" ]; then
      mounts+=(-v "$GL_CONFIG:/cfg.toml:ro")
      cfg=(--config /cfg.toml)
    fi
    docker run --rm "${mounts[@]}" "$GL_IMAGE" \
      detect --no-git --source /scan --redact --no-banner "${cfg[@]+"${cfg[@]}"}"
    return $?
  fi

  gl_warn "⚠️  gitleaks not found (no binary, no Docker) — local secret scan SKIPPED."
  gl_warn "    Install:  brew install gitleaks   (or set GITLEAKS_BIN=/path/to/gitleaks)"
  gl_warn "    The CI 'Secret Scan' job still gates this on push/PR."
  return 0
}
