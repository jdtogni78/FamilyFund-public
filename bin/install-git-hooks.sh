#!/usr/bin/env bash
# Point git at the repo's tracked hooks (.githooks/) so pre-commit + pre-push run
# the gitleaks secret scan. Run once per clone. Idempotent; safe to re-run.
#
# core.hooksPath is per-clone local config (not committed), shared across all
# linked worktrees of this repo, so a single run covers every worktree.
set -euo pipefail

root="$(git rev-parse --show-toplevel)"
chmod +x "$root/.githooks/pre-commit" "$root/.githooks/pre-push" 2>/dev/null || true
git -C "$root" config core.hooksPath .githooks

echo "✅ Installed git hooks: core.hooksPath -> .githooks"
echo "   pre-commit scans staged changes; pre-push scans outgoing commits."
echo "   Uses a local 'gitleaks' binary if present, else Docker (the CI"
echo "   'Secret Scan' job gates regardless)."
echo "   One-off bypass:  SKIP_GITLEAKS=1 git commit ...   |   git push ..."
