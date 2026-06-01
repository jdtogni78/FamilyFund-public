# Family Fund — Setup (Mac)

Laravel 11 + MariaDB + Vite, run via docker-compose. This doc gets a fresh Mac to a working `http://localhost:3000`.

Related: [dstrader](../../dstrader), [dstrader-aws](../../dstrader-aws), [fin-export](../../fin-export).

---

## 1. Mac base — once per machine

```bash
# Homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Claude
brew install --cask claude claude-code
claude /login

# GitHub CLI + auth
brew install gh
gh auth login

# Docker Desktop
brew install --cask docker
open -a Docker   # accept license, wait for whale icon

# MySQL client (for db load/dump from host)
brew install mysql-client
echo 'export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"' >> ~/.zshrc

# Node (for frontend build)
brew install node
```

## 2. Clone

```bash
mkdir -p ~/dev && cd ~/dev
gh repo clone jdtogni78/FamilyFund
cd FamilyFund
```

> All compose commands below run from `app1/` (NOT `app1/family-fund-app/`).

## 3. .env

Per CLAUDE.md, `.env` is a symlink to `.env.<ENV>`:

```bash
cd app1/family-fund-app
ln -sf .env.dev .env
```

If `.env.dev` doesn't exist yet, copy `.env.example` to `.env.dev` and fill in:

```
APP_URL=http://localhost:3000
DB_HOST=db
DB_PORT=3306
DB_DATABASE=familyfund_dev
DB_USERNAME=famfun_dev
DB_PASSWORD=1234
MAIL_HOST=mail
MAIL_PORT=1025
```

## 4. Bring up the stack

```bash
cd ~/dev/FamilyFund/app1
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Services come up:

| Service | Container | URL / port |
|---|---|---|
| Laravel app | `familyfund` | http://localhost:3000 |
| MariaDB | `db` | 127.0.0.1:3306 |
| MailHog | `mail` | http://localhost:8025 |
| QuickChart | `quickchart` | http://localhost:3400 |

## 5. First-run install (in container)

```bash
# PHP deps — composer install. Accept errors on first run per README.
docker compose exec familyfund composer install

# Database — fresh schema
docker compose exec familyfund php artisan migrate:fresh

# (Optional) seed dev data from a dump
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < database/dev/familyfund_dev_data_YYYYMMDD.sql
```

## 6. Frontend build (Tailwind + Vite)

```bash
cd ~/dev/FamilyFund/app1/family-fund-app
npm install
npm run build           # production assets
# or: npm run dev       # watch mode for active dev
```

> Rebuild after editing Blade templates with new Tailwind classes — Tailwind purges unused classes at build time.

## 7. Verify

```bash
open http://localhost:3000
# Test user (created by prod_to_dev.sql):
#   email: claude@test.local
#   password: claude-test-2024
```

## 8. Tests (must run in container — host can't reach DB)

```bash
docker compose exec familyfund php artisan test
docker compose exec familyfund php artisan test --filter=TransactionTest
```

## 9. Queue worker (emails / reports)

```bash
docker compose exec familyfund php artisan queue:work
# or detached:
docker exec -it familyfund php artisan queue:work
```

## 10. Loading prod data into dev (with anonymization)

```bash
cd ~/dev/FamilyFund/app1/family-fund-app
docker compose exec familyfund php artisan migrate:fresh
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < database/prod/familyfund_prod_data_YYYYMMDD.sql
# One-shot: anonymize + run pending migrations + seed permissions + seed QA users.
app1/family-fund-app/bin/prod-to-dev.sh
```

`bin/prod-to-dev.sh` wraps `database/prod_to_dev.sql` plus the two artisan
follow-ups a fresh prod dump otherwise misses: the credit-line
backfill migration and the spatie permissions/QA-users seeders. The SQL itself
resets passwords (`devpassword123`), anonymizes names/emails, and preserves
`admin@dev.familyfund.local` and `claude@test.local`.

## 11. Credentials checklist

- [ ] `.env.dev` filled in (DB pw `1234`, mail host `mail`, app URL `http://localhost:3000`)
- [ ] `.env.prod` (on spirit only; never copy from prod to local)
- [ ] Dev DB password: `famfun_dev` / `1234`
- [ ] Prod DB root password: `root` / `123456` (per README; rotate on first install)
- [ ] Admin login: the address in `ADMIN_EMAILS` (set your own password via `php artisan tinker`)
- [ ] Test user: `claude@test.local` / `claude-test-2024`

> **No secrets in this repo.** `.env*` files are gitignored. Generate fresh passwords on first install rather than reusing README defaults in production.
