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
- ssh access to melnick (REDACTED_NAS_HOST) as jdtogni

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

```bash
docker exec -i <mariadb-container> mariadb -u famfun_dev -p1234 familyfund_dev \
  < ../database/dev/familyfund_dev_data_20260419.sql
```

## 8. Reconcile migrations + seed permissions

The dump includes a snapshot of the `migrations` table from April 2026. Newer migrations (credit lines, ACL, etc.) need to be marked or applied:

```bash
# Try migrating — it will error on existing tables it doesn't know are already there.
docker exec <familyfund-container> php artisan migrate --force

# If it fails on "table already exists", mark those as done manually:
docker exec <mariadb-container> mariadb -u famfun_dev -p1234 familyfund_dev -e "
INSERT INTO migrations (migration, batch) VALUES
('2026_05_13_000001_create_account_credit_lines_table', 99),
('2026_05_13_000002_create_credit_line_payments_table', 99),
('2026_05_13_000003_create_credit_line_adjustments_table', 99),
('2026_05_13_000004_create_transaction_reversals_table', 99),
('2026_05_13_000006_add_notification_settings_to_account_credit_lines', 99),
('2026_05_13_000007_create_account_credit_line_balances_table', 99);"

# Then re-run migrate to apply the genuinely-new ones:
docker exec <familyfund-container> php artisan migrate --force

# Seed roles and permissions:
docker exec <familyfund-container> php artisan db:seed --class=RolesAndPermissionsSeeder --force

# Assign claude@test.local the system-admin role (for /dev-login):
docker exec <familyfund-container> php artisan tinker --execute "
\$u = \App\Models\User::where('email','claude@test.local')->first();
setPermissionsTeamId(0);
\$u->assignRole('system-admin');
"
```

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
