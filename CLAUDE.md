# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Family Fund is a Laravel 11 financial fund management system for tracking fund shares, portfolios, beneficiary accounts, and transactions. Uses repository pattern with extensive test coverage.

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade + Livewire 3 + Volt + Bootstrap 5 + Tailwind CSS
- **Database**: MariaDB
- **Build**: Vite
- **PDF**: wkhtmltopdf via knplabs/knp-snappy

## Commands

All commands run from `app1/` (NOT `app1/family-fund-app/`):

```bash
# Development (run from app1/)
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
docker exec familyfund composer install
npm install && npm run dev   # run from app1/family-fund-app/

# Testing
php artisan test                              # All tests
php artisan test --testsuite=Feature          # Feature tests
php artisan test --testsuite=APIs             # API endpoint tests
php artisan test --testsuite=Repositories     # Repository tests
php artisan test --testsuite=GoldenData       # Golden dataset tests
php artisan test --filter=TransactionTest     # Single test file

# Database
php artisan migrate
php artisan migrate:fresh
php artisan db:seed
php artisan tinker

# Queue (for email/reports)
php artisan queue:listen
```

## Architecture

### Directory Structure (app1/family-fund-app/)

- `app/Models/` - Eloquent models (many have `*Ext` variants with business logic)
- `app/Repositories/` - Repository pattern for data access (extends BaseRepository)
- `app/Http/Controllers/` - Standard CRUD controllers
- `app/Http/Controllers/API/` - REST API endpoints
- `app/Http/Controllers/WebV1/` - Extended controllers (trade portfolios, rebalancing, reports)
- `app/Http/Requests/` - Form validation
- `app/Livewire/` - Volt single-file components
- `resources/views/` - Blade templates organized by entity

### Key Domain Models

- **Fund** - Investment fund with NAV and share pricing
- **Account/AccountExt** - Beneficiary accounts tracking fund shares
- **Portfolio/TradePortfolio** - Asset collections with allocations
- **Asset** - Stocks, crypto, real estate with price history
- **Transaction** - Deposits, withdrawals, rebalancing trades
- **Goal/AccountGoal** - Beneficiary goal tracking

### Patterns

- Repository pattern: all data access via `*Repository` classes
- Controllers inject repositories and use form request validation
- `*Ext` model variants contain extended business logic
- Historical views via `as_of` parameter throughout
- API versioning: `/api/`, `/api/v1/`

## Testing

**Current Status (2026-01-10):** 288 passing (all pass with exclusions)

Tests organized in `tests/`:
- `Feature/` - Full HTTP request tests
- `APIs/` - API endpoint tests (28+ suites)
- `Repositories/` - Repository pattern tests (30+ suites)
- `GoldenData/` - Integration tests with versioned datasets
- `DataFactory.php` - Test data generation

```bash
# Run tests in Docker
docker exec familyfund php artisan test
docker exec familyfund php artisan test --exclude-group=incomplete,needs-data-refactor  # Skip problematic tests
docker exec familyfund php artisan test --filter=TransactionTest    # Single test

# Coverage report
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 | grep -E "^  (Lines|Methods|Classes):"
```

See `test_plan.md` for detailed test fix progress and remaining issues.

## Docker Services

**Note:** Uses custom docker-compose at `app1/docker-compose.yml`, NOT Laravel Sail (`family-fund-app/docker-compose.yml`).

Run from `app1/`:
```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Services:
- **familyfund** - Laravel app (custom Dockerfile with wkhtmltopdf)
- **mariadb** (container: `db`) - Database (famfun_dev/1234, database: familyfund_dev)
- **mailhog** (container: `mail`) - Email testing (UI: http://localhost:8025)
- **quickchart** - Chart image generation (http://localhost:3400)

Environment: `.env` uses `MAIL_HOST=mail` to match container name.

## Database Backup

Run from `app1/family-fund-app/generators/`. Scripts need connection params since DB runs in Docker.

```bash
# Dev backup (connects to container on localhost:3306)
./dump_data.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234

# Dev DDL backup
./dump_ddl.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
```

Backups saved to:
- DDL: `database/familyfund_ddl_YYYYMMDD.sql`
- Data: `database/dev/familyfund_dev_data_YYYYMMDD.sql`

Load prod to dev + anonymize (see README.md for full docs):
```bash
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < database/prod/familyfund_prod_data_*.sql
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < database/prod_to_dev.sql
```

## Code Generators

Boilerplate code generators using InfyOm Laravel Generator in `app1/family-fund-app/generators/`:

- **`models_from_table.sh`** - Generate model, controller, repository, views from existing DB table
  ```bash
  php artisan infyom:scaffold ModelName --fromTable --tableName table_name
  ```
- **`api_from_json.sh`** - Generate API endpoints from JSON schema
  ```bash
  php artisan infyom:api ModelName --fieldsFile resources/api_schemas/ModelName.json
  ```
- **`models_from_json.sh`** - Generate models from JSON schema
- **`dump_ddl.sh`** / **`dump_data.sh`** - Database export utilities
- **`form_to_blade_converter.py`** - Convert forms to Blade templates

The generators create: Model, Repository, Controller, Request classes, Views (index, create, edit, show, fields, table).

## Development Notes

- Share prices calculated from previous day's NAV
- Quarterly reports generated via queue jobs
- PDF reports use wkhtmltopdf (installed in container)
- Email testing via MailHog in development

## Agent Identification

Register in `.claude-agents` file per global instructions. See `~/.claude/CLAUDE.md` for details.

**Agent Name Map:**
| Session ID | Name | Purpose |
|------------|------|---------|
| (your-session-id) | claude1 | (brief description) |

## Claude Testing

A dedicated test user exists for automated testing (created by `prod_to_dev.sql`):
- **Email**: claude@test.local
- **Password**: claude-test-2024

Dev-only auto-login route (local environment only):
```
GET /dev-login/{redirect?}
```
Example: `curl -L http://localhost:3000/dev-login/accounts/8` to auto-login and access account 8.
