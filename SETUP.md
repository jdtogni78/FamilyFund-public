# Setting up FamilyFund on a new computer

## What's shared on melnick

Melnick (the Synology NAS at `REDACTED_NAS_HOST`, ssh as `jdtogni`) holds the
files that are not in git but are needed for a working install. All live in
`~/familyfund_db_backups/`:

| File | Purpose | Size |
|---|---|---|
| `familyfund_dev_data_<DATE>.sql` | Dev DB data dump (load into MariaDB) | ~2.4 MB |
| `familyfund_ddl_<DATE>.sql`      | DDL-only dump (schema reference)    | ~55 KB  |
| `familyfund_secrets.tar.gz.gpg`  | Encrypted bundle of `.env*` + `mailhog-outgoing.json` (gpg symmetric, AES256, passphrase known to the user) | ~2 KB |

Pull commands (from the new machine, after step 1 below):

```bash
scp -O melnick:~/familyfund_db_backups/familyfund_secrets.tar.gz.gpg /tmp/
scp -O melnick:~/familyfund_db_backups/familyfund_dev_data_20260419.sql database/dev/
scp -O melnick:~/familyfund_db_backups/familyfund_ddl_20260419.sql      database/
```

Synology drops the SFTP subsystem, so use `scp -O` (legacy protocol). `ssh`
and `rsync` work fine.

## Prerequisites on the new machine

- git
- docker + docker compose v2
- node 18+ and npm
- gpg (for decrypting secrets)
- ssh access to melnick (REDACTED_NAS_HOST) as jdtogni — see step 0
- ssh access to spirit (REDACTED_PROD_HOST) as jdtogni — needed for deploys, see step 0

## 0. Set up internal host access (melnick + spirit)

Two internal hosts are referenced throughout this setup:

| Host | IP | Purpose |
|---|---|---|
| `melnick` | REDACTED_NAS_HOST | Synology NAS — holds env bundle + DB dumps (step 2/3) |
| `spirit`  | REDACTED_PROD_HOST | dstrader server — prod deploy target for FamilyFund |

### a) Add to /etc/hosts

```bash
sudo tee -a /etc/hosts <<'EOF'
REDACTED_NAS_HOST	melnick
REDACTED_PROD_HOST	spirit
EOF
```

Verify:

```bash
ping -c 1 melnick && ping -c 1 spirit
```

### b) Get the SSH keys onto the new machine

This is a single-operator setup that reuses one identity everywhere
(melnick, spirit, GitHub, mariadb). The fastest path is to copy the
existing `~/.ssh` bundle from a machine that already works, rather than
generating fresh keys.

**Prereq:** sshd must be running on the *new* machine to receive the push
(macOS: System Settings → General → Sharing → Remote Login, or
`sudo systemsetup -setremotelogin on`), and the new machine must be in the
sender's `/etc/hosts` too.

From the **existing** working machine (`~/.ssh`), push the bundle to the
new machine (here called `macmini`):

```bash
cd ~/.ssh
scp dstrader.pem id_* maria* mariapw authorized_keys config \
    jdtogni@macmini:~/.ssh
```

That transfers:

| File | Used for |
|---|---|
| `id_rsa` / `id_rsa.pub` | melnick + spirit login |
| `id_github` / `id_github.pub` | `git clone`/push to jdtogni78 repos |
| `mariadb` / `mariadb.pub` / `mariapw` | DB access keys + password file |
| `dstrader.pem` | dstrader-aws / EC2 access |
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
ssh jdtogni@melnick 'hostname'
ssh jdtogni@spirit  'hostname'
```

> Alternative (fresh key): `ssh-keygen -t ed25519` then
> `ssh-copy-id jdtogni@melnick` / `ssh-copy-id jdtogni@spirit`. Only do
> this if you can't reach an existing configured machine — a new key also
> means re-registering with GitHub and the DB hosts.

If `ssh` fails with `Host key verification failed` after editing `/etc/hosts`, the IP was already in `~/.ssh/known_hosts` with a different key. Either accept the new key on first connect, or remove the stale line: `ssh-keygen -R REDACTED_NAS_HOST` (and `-R melnick`).

You also need the melnick passphrase for the GPG-encrypted secrets bundle (step 2) — that is *not* the SSH password; it was set when the bundle was created. Ask the project owner if you don't have it.

## 1. Clone the repo

```bash
mkdir -p ~/dev && cd ~/dev
git clone git@github.com:jdtogni78/FamilyFund.git
cd FamilyFund
```

## 2. Pull the secrets bundle from melnick

```bash
scp -O melnick:~/familyfund_db_backups/familyfund_secrets.tar.gz.gpg /tmp/
gpg -d /tmp/familyfund_secrets.tar.gz.gpg | tar -xz -C /tmp/
# → /tmp now has .env, .env.dev, .env.prod, .env.stage, mailhog-outgoing.json
```

You'll be prompted for the passphrase you set when the bundle was created.

Place them where they belong:

```bash
mv /tmp/.env /tmp/.env.dev /tmp/.env.prod /tmp/.env.stage app1/family-fund-app/
mv /tmp/mailhog-outgoing.json app1/
rm /tmp/familyfund_secrets.tar.gz.gpg
```

(Optional, per project convention) make `.env` a symlink to whichever environment you'll work in:

```bash
cd app1/family-fund-app && ln -sf .env.dev .env && cd ../..
```

## 3. Pull the dev DB dump from melnick

```bash
mkdir -p database/dev
scp -O melnick:~/familyfund_db_backups/familyfund_dev_data_20260419.sql database/dev/
scp -O melnick:~/familyfund_db_backups/familyfund_ddl_20260419.sql database/
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
# Run the test suite (should be 1791 passing):
docker exec <familyfund-container> php artisan test --exclude-group=incomplete,needs-data-refactor

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
- **scp from melnick fails with "subsystem request failed"**: use `scp -O` (legacy protocol) — Synology drops the SFTP subsystem.
- **Container name mismatch**: substitute whatever `docker ps` shows. The compose project name (default vs `-p ffacl`) determines the prefix.

## File layout reference

```
FamilyFund/
├── app1/
│   ├── docker-compose.yml          # base compose
│   ├── docker-compose.dev.yml      # dev overrides (port 3001, dev DB volume)
│   ├── docker-compose.ffacl.yml    # optional parallel project on ports 3401/3406/...
│   ├── mailhog-outgoing.json       # restored from secrets bundle
│   └── family-fund-app/
│       ├── .env                    # restored from secrets bundle (symlink to .env.dev)
│       ├── .env.dev                # restored from secrets bundle
│       ├── .env.prod               # restored from secrets bundle
│       ├── .env.stage              # restored from secrets bundle
│       └── bin/test.sh             # test wrapper
└── database/
    ├── dev/familyfund_dev_data_20260419.sql   # restored from melnick
    └── familyfund_ddl_20260419.sql            # restored from melnick
```

## Updating the secrets bundle later

When env files change and you want to re-share:

```bash
cd app1/family-fund-app
tar -czf /tmp/familyfund_secrets.tar.gz .env .env.dev .env.prod .env.stage \
  -C ../ mailhog-outgoing.json
gpg --batch --symmetric --cipher-algo AES256 -o /tmp/familyfund_secrets.tar.gz.gpg /tmp/familyfund_secrets.tar.gz
scp -O /tmp/familyfund_secrets.tar.gz.gpg melnick:~/familyfund_db_backups/
rm /tmp/familyfund_secrets.tar.gz /tmp/familyfund_secrets.tar.gz.gpg
```

## Updating the DB dump later

From a working dev environment:

```bash
cd app1/family-fund-app/generators
DATE=$(date +%Y%m%d)
./dump_data.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
./dump_ddl.sh  dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
scp -O ../../../database/dev/familyfund_dev_data_${DATE}.sql melnick:~/familyfund_db_backups/
scp -O ../../../database/familyfund_ddl_${DATE}.sql           melnick:~/familyfund_db_backups/
```
