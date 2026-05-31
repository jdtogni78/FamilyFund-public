# Setting up FamilyFund on a new computer

## Personal setup specifics

This doc uses placeholders for two internal hosts and one ssh user:

- `<your-backup-host>` — wherever you keep the env-secrets bundle and DB dumps (e.g. a NAS).
- `<your-prod-host>` — your FamilyFund prod-deploy target.
- `<your-host-user>` — the ssh user on both of the above (single-identity setup).
- `<your-new-machine>` — the machine you're setting up (the receiver in step 0(b)).

If you're the **original maintainer**, see the private companion at
`familyfund-secrets/docs/SETUP-host-details.md` for the actual hostnames, ssh
user, bundle paths, and the dstrader-EC2 reference.

If you're **standing up your own FamilyFund instance**, substitute your own
equivalents (and the `REDACTED_*_HOST` placeholders) below — every step uses
universally-applicable mechanics (docker, sops, php artisan, npm) once the
host particulars are filled in.

## What's shared on the backup host

`<your-backup-host>` (e.g. a Synology NAS at `REDACTED_NAS_HOST`, ssh as
`<your-host-user>`) holds the files that are not in git but are needed for a
working install. All live in `~/familyfund_db_backups/`:

| File | Purpose | Size |
|---|---|---|
| `familyfund_dev_data_<DATE>.sql` | Dev DB data dump (load into MariaDB) | ~2.4 MB |
| `familyfund_ddl_<DATE>.sql`      | DDL-only dump (schema reference)    | ~55 KB  |
| `familyfund_secrets.tar.gz.gpg`  | Encrypted bundle of `.env*` + `mailhog-outgoing.json` (gpg symmetric, AES256, passphrase known to the user) | ~2 KB |

Pull commands (from the new machine, after step 1 below):

```bash
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_secrets.tar.gz.gpg /tmp/
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_dev_data_20260419.sql database/dev/
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_ddl_20260419.sql      database/
```

If your backup host is a Synology NAS it drops the SFTP subsystem, so use
`scp -O` (legacy protocol). `ssh` and `rsync` work fine.

## Prerequisites on the new machine

- git
- docker + docker compose v2
- node 18+ and npm
- [sops](https://github.com/getsops/sops) + [age](https://github.com/FiloSottile/age) (recommended: decrypt the in-repo encrypted env secrets)
- gpg (legacy fallback only — the legacy bundle on `<your-backup-host>` predates SOPS)
- ssh access to `<your-backup-host>` (`REDACTED_NAS_HOST`) as `<your-host-user>` — see step 0
- ssh access to `<your-prod-host>` (`REDACTED_PROD_HOST`) as `<your-host-user>` — needed for deploys, see step 0

## 0. Set up internal host access (backup host + prod host)

Two internal hosts are referenced throughout this setup:

| Host | IP | Purpose |
|---|---|---|
| `<your-backup-host>` | `REDACTED_NAS_HOST` | NAS / file host — holds env bundle + DB dumps (step 2/3) |
| `<your-prod-host>`   | `REDACTED_PROD_HOST` | prod deploy target for FamilyFund |

### a) Add to /etc/hosts

```bash
sudo tee -a /etc/hosts <<'EOF'
REDACTED_NAS_HOST	<your-backup-host>
REDACTED_PROD_HOST	<your-prod-host>
EOF
```

Verify:

```bash
ping -c 1 <your-backup-host> && ping -c 1 <your-prod-host>
```

### b) Get the SSH keys onto the new machine

This is a single-operator setup that reuses one identity everywhere
(`<your-backup-host>`, `<your-prod-host>`, GitHub, mariadb). The fastest
path is to copy the existing `~/.ssh` bundle from a machine that already
works, rather than generating fresh keys.

**Prereq:** sshd must be running on the *new* machine to receive the push
(macOS: System Settings → General → Sharing → Remote Login, or
`sudo systemsetup -setremotelogin on`), and the new machine must be in the
sender's `/etc/hosts` too.

From the **existing** working machine (`~/.ssh`), push the bundle to the
new machine (`<your-new-machine>`):

```bash
cd ~/.ssh
scp dstrader.pem id_* maria* mariapw authorized_keys config \
    <your-host-user>@<your-new-machine>:~/.ssh
```

That transfers:

| File | Used for |
|---|---|
| `id_rsa` / `id_rsa.pub` | `<your-backup-host>` + `<your-prod-host>` login |
| `id_github` / `id_github.pub` | `git clone`/push to your GitHub repos |
| `mariadb` / `mariadb.pub` / `mariapw` | DB access keys + password file |
| `dstrader.pem` | access key for a separate cloud (e.g. AWS/EC2) account, if any |
| `authorized_keys`, `config` | inbound auth + host aliases |

On the **new** machine, lock down permissions (scp does not preserve them
and ssh refuses world-readable keys):

```bash
chmod 700 ~/.ssh
chmod 600 ~/.ssh/id_* ~/.ssh/dstrader.pem ~/.ssh/mariadb ~/.ssh/mariapw ~/.ssh/authorized_keys ~/.ssh/config
chmod 644 ~/.ssh/*.pub
```

Confirm passwordless login works:

```bash
ssh <your-host-user>@<your-backup-host> 'hostname'
ssh <your-host-user>@<your-prod-host>   'hostname'
```

> Alternative (fresh key): `ssh-keygen -t ed25519` then
> `ssh-copy-id <your-host-user>@<your-backup-host>` /
> `ssh-copy-id <your-host-user>@<your-prod-host>`. Only do this if you
> can't reach an existing configured machine — a new key also means
> re-registering with GitHub and the DB hosts.

If `ssh` fails with `Host key verification failed` after editing `/etc/hosts`, the IP was already in `~/.ssh/known_hosts` with a different key. Either accept the new key on first connect, or remove the stale line: `ssh-keygen -R REDACTED_NAS_HOST` (and `-R <your-backup-host>`).

You also need the passphrase for the GPG-encrypted secrets bundle on the backup host (step 2) — that is *not* the SSH password; it was set when the bundle was created. Ask the project owner if you don't have it.

## 1. Clone the repo

```bash
mkdir -p ~/dev && cd ~/dev
git clone git@github.com:jdtogni78/FamilyFund.git
cd FamilyFund
```

### Install the git secret-scanning hooks (do this once per clone)

Pick **one** of these two install paths — both run **gitleaks** against the same
repo-root `.gitleaks.toml`, so an `.env`, dump, key, or PII can't be committed by
accident (the leak that started all of this). CI's "Secret Scan" job
(`.github/workflows/security-scan.yml`) is the hard gate regardless.

**Option A — pre-commit framework (recommended; version-pinned, standard):**

```bash
pip install pre-commit   # or: brew install pre-commit
pre-commit install       # wires .git/hooks/pre-commit -> the official gitleaks hook
pre-commit run --all-files   # optional: scan everything already tracked
```

The pinned config lives in [`.pre-commit-config.yaml`](.pre-commit-config.yaml)
(official `gitleaks` hook, fixed `rev`). The framework auto-installs the gitleaks
binary; bump the pin with `pre-commit autoupdate`.

**Option B — zero-dependency repo hooks (no Python needed):**

```bash
bin/install-git-hooks.sh   # points core.hooksPath -> .githooks
```

This wires `pre-commit` (scans staged changes) and `pre-push` (scans outgoing
commits) through gitleaks. The hooks use a local `gitleaks` binary if present
(`brew install gitleaks`), otherwise fall back to Docker; if neither is available
they warn and pass. One-off bypass: `SKIP_GITLEAKS=1 git commit …` / `… git push …`.

> Use **one** path, not both — `pre-commit install` and `core.hooksPath` both
> claim the `pre-commit` hook, and running both would scan twice. Option B also
> adds a `pre-push` history scan that Option A does not.

## 2. Decrypt the env secrets (SOPS + age — recommended)

The dev/stage/compose env secrets are **SOPS+age**-encrypted
([SOPS](https://github.com/getsops/sops) + [age](https://github.com/FiloSottile/age))
and live in a **separate PRIVATE repo, `familyfund-secrets`** — *not* in this
(public) repo, so no secret ships here even encrypted. That repo holds the
encrypted `*.sops` twins and the SOPS recipe (`.sops.yaml`), mirroring this
repo's relative paths (`app1/.env.sops`, `app1/family-fund-app/.env.dev.sops`,
`app1/family-fund-app/.env.stage.sops`). To decrypt on a new machine you need
**two** out-of-band things: a clone of `familyfund-secrets` and the **age
private key**. See [docs/ENGINEERING_DECISIONS.md](docs/ENGINEERING_DECISIONS.md)
(ED-0002, and the secrets-repo split ED) for the rationale.

```bash
# 1. Clone the PRIVATE secrets repo next to this one (the default location the
#    tooling looks for; override with $FF_SECRETS_DIR if you put it elsewhere):
git clone git@github.com:jdtogni78/familyfund-secrets.git ~/dev/familyfund-secrets

# 2. Put the age private key in place (transport it out-of-band, NEVER commit it
#    to EITHER repo — keep it in your password manager / a working machine):
mkdir -p ~/.config/sops/age
#   e.g. scp it from a working machine:
scp -O <your-backup-host>:~/familyfund_db_backups/age-keys.txt ~/.config/sops/age/keys.txt
chmod 600 ~/.config/sops/age/keys.txt

# 3. Materialize every plaintext env from its encrypted twin in the secrets repo
#    (recreates the .env -> .env.dev symlink automatically; writes files as 600):
app1/family-fund-app/bin/secrets.sh decrypt        # all of: dev, stage, compose
app1/family-fund-app/bin/secrets.sh status         # confirm (never prints values)
```

`bin/secrets.sh` resolves the secrets repo as `${FF_SECRETS_DIR:-~/dev/familyfund-secrets}`,
falling back to any in-repo `.sops` files if that clone is absent (graceful
degradation for transitional checkouts/CI). Subcommands: `status`,
`decrypt [name|all]`, `encrypt [name|all]`, `edit <name>` (edit in `$EDITOR`,
re-encrypts on save), `rekey` (re-wrap for new `.sops.yaml` recipients),
`recipient`. After changing a secret, run `bin/secrets.sh encrypt <name>` then
**commit the updated `.sops` file in the `familyfund-secrets` repo** (not here):

```bash
app1/family-fund-app/bin/secrets.sh encrypt dev
( cd ~/dev/familyfund-secrets && git add -A && git commit -m "rotate dev env" && git push )
```

> `mailhog-outgoing.json` is not a secret env — it still comes from the backup-host
> bundle (or is regenerated by Mailpit). Grab it from the fallback below if needed.

### Onboarding a machine that pre-dates the SOPS migration

If the clone was made before 2026-05-24 (ED-0002 / ED-0017) it may already have
plaintext `.env`, `.env.prod`, `.env.stage` left over from the original setup.
After `secrets.sh decrypt` succeeds, delete the stragglers so the SOPS-managed
versions are the only ones on disk:

```bash
# From repo root. Confirm mtimes look pre-rotation before deleting.
stat -f "%Sm  %N" app1/family-fund-app/.env app1/family-fund-app/.env.prod app1/family-fund-app/.env.stage
rm app1/family-fund-app/.env.prod app1/family-fund-app/.env.stage   # only if not used locally
# .env itself is now a symlink to .env.dev (created by secrets.sh decrypt); leave it.
```

Notebook / non-dev hosts that will never run the FF stack can skip the SOPS
install entirely and just delete the stale plaintexts — they're gitignored, so
the only risk is keeping a known-leaked dev password on disk.

### Fallback: legacy GPG bundle from the backup host

Predates SOPS; use only if you can't get the age key.

```bash
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_secrets.tar.gz.gpg /tmp/
gpg -d /tmp/familyfund_secrets.tar.gz.gpg | tar -xz -C /tmp/
# → /tmp now has .env, .env.dev, .env.prod, .env.stage, mailhog-outgoing.json
mv /tmp/.env /tmp/.env.dev /tmp/.env.prod /tmp/.env.stage app1/family-fund-app/
mv /tmp/mailhog-outgoing.json app1/
rm /tmp/familyfund_secrets.tar.gz.gpg
cd app1/family-fund-app && ln -sf .env.dev .env && cd ../..   # .env symlink convention
```

## 3. Pull the dev DB dump from the backup host

```bash
mkdir -p database/dev
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_dev_data_20260419.sql database/dev/
scp -O <your-backup-host>:~/familyfund_db_backups/familyfund_ddl_20260419.sql database/
```

## 4. Bring up Docker

```bash
cd app1
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker ps   # confirm familyfund + mariadb + mailhog + quickchart are Up
```

The container name will be `app1-familyfund-1` (or `familyfund` depending on your compose version). Use whatever shows in `docker ps` — substitute below as `<familyfund-container>` and `<mariadb-container>`.

## 5. Install PHP dependencies (inside the container)

```bash
docker exec <familyfund-container> composer install
```

If composer fails on platform reqs from the host, run it via a one-off composer container:

```bash
docker run --rm -v "$PWD/family-fund-app:/app" -w /app composer:2 \
  install --no-interaction --ignore-platform-req=ext-gd --ignore-platform-req=ext-pcntl
```

## 6. Install + build frontend (on the host, from `app1/family-fund-app/`)

```bash
cd family-fund-app
npm install --legacy-peer-deps
npm run build
cd ..
```

## 7. Load the DB dump

> **DB credentials depend on which stack is running.** A *fresh dev stack*
> (`docker-compose.dev.yml`, container `mariadb`) uses `famfun_dev` / `1234`.
> A machine reused from a dstrader **stage** image (container `db`) uses
> `root` / `123456` and `APP_ENV=stage`. Check with
> `docker exec <familyfund-container> php artisan tinker --execute 'echo app()->environment();'`
> and substitute `<dbuser>`/`<dbpass>` below accordingly.

`familyfund_dev_data_20260419.sql` is a **full schema+data dump** (56
`CREATE TABLE`s + data + a `migrations` snapshot through 2026-04-19). It
contains no `DROP DATABASE`, so load it into a clean database — don't load
it on top of an existing schema, and don't separately load the DDL dump
(the DDL file is schema-reference only):

```bash
# Wipe + recreate, then load the full dump
docker exec <mariadb-container> mariadb -u<dbuser> -p<dbpass> -e \
  "DROP DATABASE IF EXISTS familyfund_dev; CREATE DATABASE familyfund_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker exec -i <mariadb-container> mariadb -u<dbuser> -p<dbpass> familyfund_dev \
  < database/dev/familyfund_dev_data_20260419.sql
```

## 8. Apply post-April migrations + seed permissions

The dump's `migrations` snapshot ends at
`2026_04_19_120000_restore_dev_funds_fixture`. Migrations after that date
(credit lines, etc.) create tables that are **not** in the dump, so a
plain `migrate --force` applies them cleanly with no conflicts — there is
nothing to "mark as already done":

```bash
docker exec <familyfund-container> php artisan migrate --force

# Seed roles and permissions:
docker exec <familyfund-container> php artisan db:seed --class=RolesAndPermissionsSeeder --force

# claude@test.local ships in the dump (~id 170); (re)assign system-admin.
# firstOrCreate guards the case where a thinner dump lacks the user:
docker exec <familyfund-container> php artisan tinker --execute "
\$u = \App\Models\User::firstOrCreate(['email'=>'claude@test.local'], ['name'=>'Claude Test','password'=>bcrypt('claude-test-2024')]);
setPermissionsTeamId(0);
\$u->assignRole('system-admin');
echo \$u->id.' '.\$u->getRoleNames()->implode(',');
"
```

> **`/dev-login` only works when `APP_ENV` is `dev`/`local`.** On a reused
> **stage** container it reports `stage` and `/dev-login/...` returns 404 —
> log in normally at `/login` with `claude@test.local` /
> `claude-test-2024` instead. To get the dev-only stack (and mailhog +
> quickchart), bring it up with the dev compose file (step 4) rather than
> reusing the stage image.

## 9. Verify

```bash
# Run the test suite (should be 1791 passing). The default run already excludes
# the slow @group nightly tests via phpunit.xml (ED-0006); the incomplete/
# needs-data-refactor groups have no members, so don't bother excluding them.
docker exec <familyfund-container> php artisan test

# Or use the wrapper that handles vite-rebuild + container autodetect:
./family-fund-app/bin/test.sh

# Hit the app:
open http://localhost:3001
# or http://localhost:3401 if you used the ffacl parallel compose project
```

Auto-login for dev: `http://localhost:3001/dev-login/dashboard` (logs you in as `claude@test.local`).

## Running the suite isolated from a live dev DB

The suite has **no `RefreshDatabase` and no separate test DB connection** —
`phpunit.xml` only sets `APP_ENV=testing`, so `php artisan test` runs
against whatever DB the container's `.env` points at. Running it in the
dev container therefore writes test rows into `familyfund_dev`. To run it
without touching dev, stand up a parallel stack with its own DB:

```bash
cd app1

# Parallel compose project on port offset 1 (app 3001, db 3307, mail 8026,
# chart 3401). Offsets persist in app1/.dc-ports.
./launch_docker.sh test up -d
```

Point the test stack at its own DB and key. In the
`family-fund-app/` that the `familyfund-test` container mounts at `/app`,
create `.env.test` (copy of `.env.dev`) with `APP_ENV=testing` and
`DB_DATABASE=familyfund_test`, then symlink it:

```bash
cd family-fund-app && ln -sfn .env.test .env && cd ..
```

> **Reuse the dev `APP_KEY` in `.env.test`.** The test DB is cloned from
> dev (below); a fresh key makes cloned encrypted columns undecryptable.

Seed `familyfund_test` by cloning the running dev DB (the `db-*`
containers ship the `mariadb`/`mariadb-dump` clients, not `mysql`;
root/123456):

```bash
docker exec db-dev mariadb-dump -uroot -p123456 --single-transaction \
  --quick --no-tablespaces familyfund_dev \
  | docker exec -i db-test mariadb -uroot -p123456 familyfund_test
```

Re-run that clone whenever you want a clean baseline. (`docker` may not be
on a non-interactive shell's PATH — prefix `export PATH=/usr/local/bin:$PATH`.)

## Troubleshooting

- **Vite manifest not found**: `cd app1/family-fund-app && npm run build`
- **Tests fail with "Cant find asset CASH"**: the dev dump didn't load. Re-run step 7.
- **Tests get 403s in `setUp`**: the system-admin role wasn't assigned. Re-run the tinker block in step 8.
- **scp from `<your-backup-host>` fails with "subsystem request failed"**: use `scp -O` (legacy protocol) — Synology NAS hosts drop the SFTP subsystem.
- **Container name mismatch**: substitute whatever `docker ps` shows. The compose project name (default vs `-p ffacl`) determines the prefix.
- **`secrets.sh decrypt` says "skip (no encrypted)"**: the `familyfund-secrets`
  clone is missing (or in a non-default path). Clone it to `~/dev/familyfund-secrets`
  or set `FF_SECRETS_DIR`. `bin/secrets.sh status` prints the resolved `secrets dir`.

## File layout reference

The encrypted `*.sops` twins and the `.sops.yaml` recipe live in the SEPARATE
private `familyfund-secrets` repo (cloned next to this one), not in this tree.
The plaintext `.env*` below are git-ignored and materialized by `secrets.sh decrypt`.

```
~/dev/
├── familyfund-secrets/             # PRIVATE repo (clone separately; FF_SECRETS_DIR)
│   ├── .sops.yaml                  # SOPS recipe (public age recipient only)
│   ├── app1/.env.sops              # encrypted compose env twin
│   └── app1/family-fund-app/
│       ├── .env.dev.sops           # encrypted dev env twin
│       └── .env.stage.sops         # encrypted stage env twin
└── FamilyFund/                     # this (public) repo — ships NO secrets
    ├── app1/
    │   ├── docker-compose.yml          # base compose
    │   ├── docker-compose.dev.yml      # dev overrides (port 3001, dev DB volume)
    │   ├── docker-compose.ffacl.yml    # optional parallel project on ports 3401/3406/...
    │   ├── mailhog-outgoing.json       # restored from secrets bundle
    │   ├── .env                        # git-ignored; decrypted from familyfund-secrets
    │   └── family-fund-app/
    │       ├── .env                    # git-ignored; symlink to .env.dev (decrypted)
    │       ├── .env.dev                # git-ignored; decrypted from familyfund-secrets
    │       ├── .env.stage              # git-ignored; decrypted from familyfund-secrets
    │       └── bin/test.sh             # test wrapper
    └── database/
        ├── dev/familyfund_dev_data_20260419.sql   # restored from <your-backup-host>
        └── familyfund_ddl_20260419.sql            # restored from <your-backup-host>
```

## Updating the secrets bundle later

When env files change and you want to re-share:

```bash
cd app1/family-fund-app
tar -czf /tmp/familyfund_secrets.tar.gz .env .env.dev .env.prod .env.stage \
  -C ../ mailhog-outgoing.json
gpg --batch --symmetric --cipher-algo AES256 -o /tmp/familyfund_secrets.tar.gz.gpg /tmp/familyfund_secrets.tar.gz
scp -O /tmp/familyfund_secrets.tar.gz.gpg <your-backup-host>:~/familyfund_db_backups/
rm /tmp/familyfund_secrets.tar.gz /tmp/familyfund_secrets.tar.gz.gpg
```

## Updating the DB dump later

From a working dev environment:

```bash
cd app1/family-fund-app/generators
DATE=$(date +%Y%m%d)
./dump_data.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
./dump_ddl.sh  dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
scp -O ../../../database/dev/familyfund_dev_data_${DATE}.sql <your-backup-host>:~/familyfund_db_backups/
scp -O ../../../database/familyfund_ddl_${DATE}.sql           <your-backup-host>:~/familyfund_db_backups/
```
