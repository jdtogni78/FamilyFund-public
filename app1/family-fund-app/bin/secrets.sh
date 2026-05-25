#!/usr/bin/env bash
#
# secrets.sh — manage SOPS+age-encrypted FamilyFund env secrets.
#
# The encrypted twins (`<name>.sops`, dotenv format) and the SOPS recipe
# (`.sops.yaml`) live in a SEPARATE PRIVATE repo, cloned next to this one, so the
# (soon-public) app repo ships NO secrets at all — not even encrypted. That repo
# is resolved as $FF_SECRETS_DIR (default ~/dev/familyfund-secrets); if it is not
# present, the script falls back to any in-repo `.sops` files so it still degrades
# gracefully. Plaintext env files are git-ignored and are always materialized
# into their canonical in-app paths (the app reads them there). The age PRIVATE
# key lives off-repo at ~/.config/sops/age/keys.txt (or $SOPS_AGE_KEY_FILE) and is
# never committed to EITHER repo. See docs/security/ + SETUP.md.
#
# Usage (run from anywhere; the script cd's to the repo root):
#   bin/secrets.sh status              # show each pair's state (NEVER prints values)
#   bin/secrets.sh decrypt [name|all]  # materialize plaintext from .sops (default: all)
#   bin/secrets.sh encrypt [name|all]  # (re)encrypt plaintext into .sops  (default: all present)
#   bin/secrets.sh edit <name>         # open the encrypted file in $EDITOR (re-encrypts on save)
#   bin/secrets.sh rekey               # re-wrap every .sops for current .sops.yaml recipients
#   bin/secrets.sh recipient           # print the configured age recipient(s)
#
# "name" is a manifest key (dev | stage | compose) or a literal path.
set -euo pipefail

APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
REPO_ROOT="$(cd "$APP_DIR/../.." && pwd)"
# sops/age may live in the repo-local toolchain or the user's ~/.local/bin.
[ -d "$REPO_ROOT/.tools/bin" ] && export PATH="$REPO_ROOT/.tools/bin:$PATH"
[ -d "$HOME/.local/bin" ]      && export PATH="$HOME/.local/bin:$PATH"

cd "$REPO_ROOT"

# Secrets repo: the .sops twins + .sops.yaml live OUTSIDE this repo, in a private
# sibling clone, so nothing secret (even encrypted) ships in the public app repo.
# Resolve $FF_SECRETS_DIR (default ~/dev/familyfund-secrets); fall back to the
# in-repo paths if that clone is absent, so legacy/transitional checkouts and
# CI still work. SECRETS_DIR ends up either pointing at the private clone or at
# REPO_ROOT (the legacy in-repo layout).
DEFAULT_SECRETS_DIR="$HOME/dev/familyfund-secrets"
SECRETS_DIR="${FF_SECRETS_DIR:-$DEFAULT_SECRETS_DIR}"
if [ -d "$SECRETS_DIR" ]; then
  SECRETS_SRC="external"
else
  SECRETS_DIR="$REPO_ROOT"   # graceful fallback to the old in-repo layout
  SECRETS_SRC="in-repo"
fi

DOTENV="--input-type dotenv --output-type dotenv"
AGE_KEY="${SOPS_AGE_KEY_FILE:-$HOME/.config/sops/age/keys.txt}"
export SOPS_AGE_KEY_FILE="$AGE_KEY"

# SOPS reads creation rules from the .sops.yaml in CWD or an ancestor; when the
# recipe lives in the secrets repo, point SOPS at it explicitly for encrypt/rekey.
SOPS_CONFIG="$SECRETS_DIR/.sops.yaml"
[ -f "$SOPS_CONFIG" ] || SOPS_CONFIG="$REPO_ROOT/.sops.yaml"

die()  { printf 'secrets: %s\n' "$*" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }

have sops || die "sops not found on PATH (install to ~/.local/bin or .tools/bin)"

# Encrypted-twin path for a plaintext path: <SECRETS_DIR>/<plaintext>.sops, with a
# fallback to the in-repo twin if it still exists there (transition period).
sops_for() {
  local p="$1" ext_twin="$SECRETS_DIR/$1.sops" in_repo_twin="$REPO_ROOT/$1.sops"
  if [ -f "$ext_twin" ]; then printf '%s\n' "$ext_twin"
  elif [ -f "$in_repo_twin" ]; then printf '%s\n' "$in_repo_twin"
  else printf '%s\n' "$ext_twin"   # canonical target for a not-yet-created twin
  fi
}

# Manifest: name -> plaintext path (relative to repo root). The encrypted twin
# is always "<plaintext>.sops". Kept as a case map (no bash-4 assoc arrays, so
# it runs on macOS's stock bash 3.2 too).
ALL_NAMES="dev stage compose"
path_for() {
  case "$1" in
    dev)     echo "app1/family-fund-app/.env.dev" ;;
    stage)   echo "app1/family-fund-app/.env.stage" ;;
    compose) echo "app1/.env" ;;
    *) return 1 ;;
  esac
}

# name | path | <path>.sops  ->  plaintext path
resolve() {
  if path_for "$1" >/dev/null 2>&1; then path_for "$1"; return; fi
  case "$1" in
    *.sops) printf '%s\n' "${1%.sops}" ;;
    *)      printf '%s\n' "$1" ;;
  esac
}

recipient() { grep -oE 'age1[0-9a-z]+' "$SOPS_CONFIG" || die "no age recipient in $SOPS_CONFIG"; }

encrypt_one() {
  local p="$1" e; e="$(sops_for "$1")"
  [ -f "$p" ] || { printf '  skip (no plaintext): %s\n' "$p" >&2; return 0; }
  mkdir -p "$(dirname "$e")"
  cp "$p" "$e"
  # shellcheck disable=SC2086
  sops --config "$SOPS_CONFIG" --encrypt --in-place $DOTENV "$e"
  printf '  encrypted: %s -> %s\n' "$p" "$e" >&2
}

decrypt_one() {
  local p="$1" e; e="$(sops_for "$1")"
  [ -f "$e" ] || { printf '  skip (no encrypted): %s\n' "$e" >&2; return 0; }
  [ -f "$AGE_KEY" ] || die "age key not found at $AGE_KEY (set SOPS_AGE_KEY_FILE)"
  # shellcheck disable=SC2086
  sops --decrypt $DOTENV "$e" > "$p"
  chmod 600 "$p"
  printf '  decrypted: %s -> %s\n' "$e" "$p" >&2
  # The app reads .env (a symlink to .env.dev by project convention); recreate it.
  if [ "$p" = "app1/family-fund-app/.env.dev" ] && [ ! -e "app1/family-fund-app/.env" ]; then
    ln -s .env.dev app1/family-fund-app/.env
    printf '  linked: app1/family-fund-app/.env -> .env.dev\n' >&2
  fi
}

cmd_status() {
  printf '%-9s %-38s %-10s %-10s\n' NAME PLAINTEXT-PATH PLAINTEXT ENCRYPTED
  for n in $ALL_NAMES; do
    local p e ps es; p="$(path_for "$n")"; e="$(sops_for "$p")"
    ps="-"; es="-"; [ -f "$p" ] && ps="present"; [ -f "$e" ] && es="present"
    printf '%-9s %-38s %-10s %-10s\n' "$n" "$p" "$ps" "$es"
  done
  printf '\nsecrets dir  : %s (%s)\n' "$SECRETS_DIR" "$SECRETS_SRC"
  printf 'sops recipe  : %s %s\n' "$SOPS_CONFIG" "$([ -f "$SOPS_CONFIG" ] && echo '(present)' || echo '(MISSING)')"
  printf 'age key file : %s %s\n' "$AGE_KEY" "$([ -f "$AGE_KEY" ] && echo '(present)' || echo '(MISSING -> decrypt fails)')"
  printf 'recipient(s) : %s\n' "$(recipient | tr '\n' ' ')"
}

sub="${1:-status}"; [ "$#" -gt 0 ] && shift || true
case "$sub" in
  status)    cmd_status ;;
  recipient) recipient ;;
  encrypt)
    t="${1:-all}"
    if [ "$t" = all ]; then for n in $ALL_NAMES; do encrypt_one "$(path_for "$n")"; done
    else encrypt_one "$(resolve "$t")"; fi ;;
  decrypt)
    t="${1:-all}"
    if [ "$t" = all ]; then for n in $ALL_NAMES; do decrypt_one "$(path_for "$n")"; done
    else decrypt_one "$(resolve "$t")"; fi ;;
  edit)
    [ -n "${1:-}" ] || die "usage: secrets.sh edit <name>"
    e="$(sops_for "$(resolve "$1")")"; [ -f "$e" ] || die "no encrypted file: $e"
    # shellcheck disable=SC2086
    exec sops --config "$SOPS_CONFIG" $DOTENV "$e" ;;
  rekey)
    for n in $ALL_NAMES; do
      e="$(sops_for "$(path_for "$n")")"; [ -f "$e" ] || continue
      sops --config "$SOPS_CONFIG" updatekeys -y "$e"; printf '  rekeyed: %s\n' "$e" >&2
    done ;;
  -h|--help) sed -n '2,20p' "$0" ;;
  *) die "unknown command: $sub (try: status | decrypt | encrypt | edit | rekey | recipient)" ;;
esac
