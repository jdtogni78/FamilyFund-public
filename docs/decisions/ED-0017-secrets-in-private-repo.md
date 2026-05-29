# ED-0017 — Encrypted secrets move to a separate private repo


- **Date:** 2026-05-25 · **Status:** Accepted · **Builds on / amends:** ED-0002
- **Context:** ED-0002 committed the SOPS+age-**encrypted** env twins (`*.sops`)
  and the SOPS recipe (`.sops.yaml`) directly into this repo, on the reasoning
  that ciphertext is safe to publish (only age-key holders can decrypt). As the
  repo is prepared to go **public** (#16), we want a stricter posture:
  **no secret material at all in the public repo — not even encrypted blobs.**
  Encrypted-in-public is cryptographically fine but is an unnecessary attack
  surface (offline brute-force of a future-broken cipher, recipient-set
  disclosure, "why are there secrets in a public repo" optics) and the encrypted
  blobs already in git history are a separate cleanup (#36).
- **Decision:** Move the encrypted twins + `.sops.yaml` OUT into a **new PRIVATE
  repo `jdtogni78/familyfund-secrets`**, cloned next to this one. The tooling
  stays here but is repointed: `bin/secrets.sh` resolves the secrets dir as
  `${FF_SECRETS_DIR:-~/dev/familyfund-secrets}` and reads/writes the `.sops`
  twins + `.sops.yaml` there, mirroring this repo's relative paths
  (`app1/.env.sops`, `app1/family-fund-app/.env.{dev,stage}.sops`). If the secrets
  clone is absent it **falls back to any in-repo `.sops` files** so transitional
  checkouts and CI keep working. Plaintext envs are still git-ignored and still
  materialized into their canonical in-app paths (the app reads them there). The
  age **private** key remains off-repo and is committed to **neither** repo.
- **Consequences:**
  - New-machine setup needs **two** out-of-band artifacts now: a clone of
    `familyfund-secrets` *and* the age private key (SETUP.md §2).
  - After rotating/editing a secret, re-encrypt with `bin/secrets.sh encrypt`
    then commit in the **secrets repo**, not here (`rotate-dev-secrets.sh` notes
    this).
  - This does **not** rewrite git history; the already-committed encrypted blobs
    are handled separately by the history-purge work (#36).
- **Source:** #16 (secrets-move publish-blocker); `app1/family-fund-app/bin/secrets.sh`,
  `SETUP.md` §2; repo `jdtogni78/familyfund-secrets` (private).
