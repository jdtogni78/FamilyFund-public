# ED-0002 — SOPS + age for in-repo encrypted env secrets


- **Date:** 2026-05-24 · **Status:** Accepted (location amended by ED-0017) · **Builds on:** ED-0001
- **Context:** ED-0001 kept secrets out of git, but distribution still relied on a
  shared-passphrase GPG tarball on melnick (`familyfund_secrets.tar.gz.gpg`):
  not versioned, all-or-nothing, and a single shared passphrase. We wanted
  versioned, reviewable, recipient-based secrets that can live *in* the repo.
- **Decision:** Adopt **SOPS + age**. Encrypted twins (`<name>.sops`, e.g.
  `app1/family-fund-app/.env.dev.sops`, `.env.stage.sops`, `app1/.env.sops`) are
  **committed**; plaintext stays git-ignored. Recipients live in repo-root
  `.sops.yaml` (public age key only); the **private** key stays off-repo at
  `~/.config/sops/age/keys.txt` and is transported out-of-band. Manage pairs with
  `app1/family-fund-app/bin/secrets.sh` (`status|decrypt|encrypt|edit|rekey|recipient`).
  Use **dotenv** in/out mode so diffs are line-by-line and keys stay readable.
- **Consequences:** New-machine setup needs only the age key, then
  `bin/secrets.sh decrypt` — replaces the melnick `scp` (now a documented
  fallback). SOPS's dotenv store strips **blank lines** on round-trip (cosmetic);
  all `KEY=VALUE` assignments and comments are preserved byte-for-byte (verified).
  `.gitignore` re-includes `*.sops` after the broad `**/.env.*` ignore. Adding a
  teammate/machine = append their `age1…` key to `.sops.yaml` + `bin/secrets.sh rekey`.
  Committing encrypted secrets is safe because secrets were rotated post-leak
  (ED-0001) and only age-key holders can decrypt. **Amended by ED-0017:** even
  though encrypted-in-public is *cryptographically* safe, the encrypted twins and
  `.sops.yaml` now live in a private sibling repo rather than in the public app
  repo (defense-in-depth: no secret material — not even ciphertext — ships
  publicly).
- **Source:** `.sops.yaml`, `app1/family-fund-app/bin/secrets.sh`, `SETUP.md` §2.
