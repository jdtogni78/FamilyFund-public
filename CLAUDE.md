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

All commands run from `app1/family-fund-app/`:

```bash
# Development
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
docker-compose exec familyfund composer install
npm install && npm run dev

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

Tests organized in `tests/`:
- `Feature/` - Full HTTP request tests
- `APIs/` - API endpoint tests (28+ suites)
- `Repositories/` - Repository pattern tests (30+ suites)
- `GoldenData/` - Integration tests with versioned datasets
- `DataFactory.php` - Test data generation

## Docker Services

- **familyfund** - Laravel app (bitnami/laravel:11.5.0)
- **mariadb** - Database (famfun/1234, database: familyfund)
- **mailhog** - Email testing (UI: localhost:8025)

## Development Notes

- Share prices calculated from previous day's NAV
- Quarterly reports generated via queue jobs
- PDF reports use wkhtmltopdf (installed in container)
- Email testing via MailHog in development
