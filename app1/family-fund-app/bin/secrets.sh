#!/usr/bin/env bash
#
# secrets.sh — manage SOPS+age-encrypted FamilyFund env secrets.
#
# Plaintext env files are git-ignored; their encrypted twins (`<name>.sops`,
# dotenv format) ARE committed. Recipients live in the repo-root `.sops.yaml`;
# the age PRIVATE key lives off-repo at ~/.config/sops/age/keys.txt (or
# $SOPS_AGE_KEY_FILE) and is never committed. See docs/security/ + SETUP.md.
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

DOTENV="--input-type dotenv --output-type dotenv"
AGE_KEY="${SOPS_AGE_KEY_FILE:-$HOME/.config/sops/age/keys.txt}"
export SOPS_AGE_KEY_FILE="$AGE_KEY"

die()  { printf 'secrets: %s\n' "$*" >&2; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }

have sops || die "sops not found on PATH (install to ~/.local/bin or .tools/bin)"

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

recipient() { grep -oE 'age1[0-9a-z]+' .sops.yaml || die "no age recipient in .sops.yaml"; }

encrypt_one() {
  local p="$1" e="$1.sops"
  [ -f "$p" ] || { printf '  skip (no plaintext): %s\n' "$p" >&2; return 0; }
  cp "$p" "$e"
  # shellcheck disable=SC2086
  sops --encrypt --in-place $DOTENV "$e"
  printf '  encrypted: %s -> %s\n' "$p" "$e" >&2
}

decrypt_one() {
  local p="$1" e="$1.sops"
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
    local p e ps es; p="$(path_for "$n")"; e="$p.sops"
    ps="-"; es="-"; [ -f "$p" ] && ps="present"; [ -f "$e" ] && es="committed"
    printf '%-9s %-38s %-10s %-10s\n' "$n" "$p" "$ps" "$es"
  done
  printf '\nage key file : %s %s\n' "$AGE_KEY" "$([ -f "$AGE_KEY" ] && echo '(present)' || echo '(MISSING -> decrypt fails)')"
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
    e="$(resolve "$1").sops"; [ -f "$e" ] || die "no encrypted file: $e"
    # shellcheck disable=SC2086
    exec sops $DOTENV "$e" ;;
  rekey)
    for n in $ALL_NAMES; do
      e="$(path_for "$n").sops"; [ -f "$e" ] || continue
      sops updatekeys -y "$e"; printf '  rekeyed: %s\n' "$e" >&2
    done ;;
  -h|--help) sed -n '2,20p' "$0" ;;
  *) die "unknown command: $sub (try: status | decrypt | encrypt | edit | rekey | recipient)" ;;
esac
