# Branch Status: claude/merge-main-i8Bhi

**Last Updated**: 2026-05-14  
**Purpose**: ACL implementation + Credit Lines feature integration

## Branch Contents

This branch contains:

1. **Merged from main**: Latest main branch changes
2. **Merged PR #3**: Credit Lines borrowing feature (49/50 use cases)
3. **ACL Implementation**: Full authorization system for all controllers

## What Has Been Done

### ACL System (Complete)

- **Policies Created**:
  - `AccountPolicy` - Account access control
  - `FundPolicy` - Fund access control
  - `TransactionPolicy` - Transaction access control
  - `AccountCreditLinePolicy` - Credit line access control

- **Controllers with Authorization**:
  - `AccountController` / `AccountControllerExt`
  - `TransactionController` / `TransactionControllerExt`
  - `FundController` / `FundControllerExt`
  - `AccountCreditLineControllerExt`
  - `CreditLineMatchResolutionController`
  - `UserRoleController`

- **Repositories with Query Scoping**:
  - `AccountRepository`
  - `TransactionRepository`
  - `FundRepository`
  - `AccountCreditLineRepository`

- **Supporting Infrastructure**:
  - `AuthorizationService` with scope methods
  - `RolesAndPermissionsSeeder` with all permissions
  - `SetFundPermissions` middleware
  - `EnsureTwoFactorIsCompleted` middleware

### Credit Lines Feature (From PR #3)

- Draw shares against account balances
- Repay on amortized schedules
- Readjust terms, reverse transactions
- Multi-generation trajectory visualization
- Payment simulator (dual modes)
- ~155 unit/feature tests + 17 Dusk browser tests

## What Needs To Be Done

### 1. Run Tests (Requires Docker)

```bash
cd app1
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker exec familyfund composer install
docker exec familyfund php artisan migrate

# Run all tests
docker exec familyfund php artisan test

# Run specific test groups
docker exec familyfund php artisan test --filter=Authorization
docker exec familyfund php artisan test --filter=TwoFactor
docker exec familyfund php artisan test --filter=UserRole
docker exec familyfund php artisan test --filter=CreditLine
```

### 2. Apply Database Migrations & Seeders

```bash
# Run migrations for new tables (permissions, login_activities, credit_lines, etc.)
docker exec familyfund php artisan migrate

# Seed roles and permissions
docker exec familyfund php artisan db:seed --class=RolesAndPermissionsSeeder

# Optionally assign initial roles
docker exec familyfund php artisan db:seed --class=InitialRolesAssignmentSeeder
```

### 3. Fix Any Test Failures

If tests fail, common issues to check:
- Missing authorization checks in new/modified controllers
- Policy methods not matching controller authorize() calls
- Repository not using `withAuthorization()` for queries
- Missing permissions in seeder for new functionality

### 4. Manual Testing

Test the following flows in browser (http://localhost:3001):

**ACL Testing**:
- Login as system admin - should see everything
- Login as fund admin - should only see their fund's data
- Login as financial manager - should be able to create/process but not delete
- Login as beneficiary - should only see own accounts/transactions

**Credit Lines Testing**:
- Create a credit line for an account
- Make a repayment
- Readjust terms
- Cancel a credit line
- Use the payment simulator

### 5. Create PR or Merge to Main

Once tests pass:
```bash
# Option A: Create PR
gh pr create --base main --head claude/merge-main-i8Bhi --title "ACL + Credit Lines Integration"

# Option B: Merge directly (if you have permissions)
git checkout main
git merge claude/merge-main-i8Bhi
git push origin main
```

## Role Permission Matrix

| Role | Accounts | Transactions | Funds | Credit Lines |
|------|----------|--------------|-------|--------------|
| **System Admin** | All | All | All | All |
| **Fund Admin** | CRUD (fund) | CRUD (fund) | View/Update | All (fund) |
| **Financial Manager** | View/Update | Create/Process | View | View/Create/Process |
| **Beneficiary** | View own | View own | View | View own |

## Key Files Reference

| File | Purpose |
|------|---------|
| `ACL_IMPLEMENTATION.md` | Full ACL documentation |
| `app/Policies/*.php` | Authorization policies |
| `app/Services/AuthorizationService.php` | Query scoping logic |
| `database/seeders/RolesAndPermissionsSeeder.php` | Permission definitions |
| `docs/credit_lines/` | Credit Lines feature docs |

## Environment Requirements

- PHP 8.2+
- Docker & Docker Compose
- MariaDB (runs in container)
- Node.js (for frontend assets)

## Quick Start Commands

```bash
# Start environment
cd app1
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Install dependencies
docker exec familyfund composer install
cd family-fund-app && npm install && npm run build && cd ..

# Run migrations
docker exec familyfund php artisan migrate

# Seed permissions
docker exec familyfund php artisan db:seed --class=RolesAndPermissionsSeeder

# Run tests
docker exec familyfund php artisan test

# Access app
open http://localhost:3001
```

## Test User (Dev Only)

Auto-login route available in dev environment:
```
GET http://localhost:3001/dev-login/dashboard
```

Uses test user: `claude@test.local` / `claude-test-2024`
