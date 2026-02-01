# ACL Implementation Review

This document describes the Access Control List (ACL) implementation for FamilyFund, including the roles, permissions, policies, and authorization checks across the application.

## Overview

The ACL system uses [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) with fund-scoped roles. This means users can have different roles for different funds (e.g., Fund Admin for Fund A, but only Beneficiary for Fund B).

## Role Hierarchy

| Role | Scope | Description |
|------|-------|-------------|
| **System Admin** | Global (fund_id = 0) | Full bypass of all authorization - can do everything |
| **Fund Admin** | Per-fund | Full CRUD within their fund(s), can manage users |
| **Financial Manager** | Per-fund | Create/process transactions, view all fund data (no delete) |
| **Beneficiary** | Per-fund | View own account and transactions only |

## Permissions

### Account Permissions
| Permission | Fund Admin | Financial Manager | Beneficiary |
|------------|:----------:|:-----------------:|:-----------:|
| `accounts.view` | ✅ | ✅ | ❌ |
| `accounts.view-own` | ✅ | ✅ | ✅ |
| `accounts.create` | ✅ | ❌ | ❌ |
| `accounts.update` | ✅ | ✅ | ❌ |
| `accounts.delete` | ✅ | ❌ | ❌ |

### Transaction Permissions
| Permission | Fund Admin | Financial Manager | Beneficiary |
|------------|:----------:|:-----------------:|:-----------:|
| `transactions.view` | ✅ | ✅ | ❌ |
| `transactions.view-own` | ✅ | ✅ | ✅ |
| `transactions.create` | ✅ | ✅ | ❌ |
| `transactions.process` | ✅ | ✅ | ❌ |
| `transactions.delete` | ✅ | ❌ | ❌ |

### Fund Permissions
| Permission | Fund Admin | Financial Manager | Beneficiary |
|------------|:----------:|:-----------------:|:-----------:|
| `funds.view` | ✅ | ✅ | ✅ |
| `funds.update` | ✅ | ❌ | ❌ |

### Other Permissions
| Permission | Fund Admin | Financial Manager | Beneficiary |
|------------|:----------:|:-----------------:|:-----------:|
| `portfolios.view` | ✅ | ✅ | ❌ |
| `portfolios.update` | ✅ | ❌ | ❌ |
| `reports.view` | ✅ | ✅ | ✅ |
| `reports.generate` | ✅ | ✅ | ❌ |
| `users.view` | ✅ | ❌ | ❌ |
| `users.assign-roles` | ✅ | ❌ | ❌ |

## Access Control by Page/Resource

### Accounts
| Action | System Admin | Fund Admin | Financial Manager | Beneficiary |
|--------|:------------:|:----------:|:-----------------:|:-----------:|
| List All | ✅ | ✅ (fund scoped) | ✅ (fund scoped) | ❌ |
| View Own | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ |
| Update | ✅ | ✅ | ✅ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ |

### Transactions
| Action | System Admin | Fund Admin | Financial Manager | Beneficiary |
|--------|:------------:|:----------:|:-----------------:|:-----------:|
| List All | ✅ | ✅ (fund scoped) | ✅ (fund scoped) | ❌ |
| View Own | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ✅ | ❌ |
| Process | ✅ | ✅ | ✅ | ❌ |
| Delete | ✅ | ✅ | ❌ | ❌ |

### Funds
| Action | System Admin | Fund Admin | Financial Manager | Beneficiary |
|--------|:------------:|:----------:|:-----------------:|:-----------:|
| List | ✅ | ✅ (own funds) | ✅ (own funds) | ✅ (own funds) |
| View | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ❌ | ❌ | ❌ |
| Update | ✅ | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ |

### User Role Management (`/admin/user-roles/*`)
| Action | System Admin | Fund Admin | Financial Manager | Beneficiary |
|--------|:------------:|:----------:|:-----------------:|:-----------:|
| View/Assign | ✅ | ❌ | ❌ | ❌ |

## Implementation Architecture

### 1. Policies

Located in `app/Policies/`:

- **AccountPolicy.php** - Controls access to accounts
- **FundPolicy.php** - Controls access to funds
- **TransactionPolicy.php** - Controls access to transactions

All policies implement a `before()` method that returns `true` for system admins, bypassing all other checks:

```php
public function before(User $user, string $ability): bool|null
{
    if ($user->isSystemAdmin()) {
        return true;
    }
    return null;
}
```

### 2. Authorization Service

Located at `app/Services/AuthorizationService.php`:

Provides query-level filtering to ensure users only see data they're permitted to access:

- `scopeAccountsQuery()` - Filters accounts by user access
- `scopeTransactionsQuery()` - Filters transactions by user access
- `scopeFundsQuery()` - Filters funds by user access
- `getAccessibleFundIds()` - Returns `{full: [], readonly: []}` arrays

### 3. Repository Traits

Located at `app/Repositories/Traits/AuthorizesQueries.php`:

Provides the `withAuthorization()` method for repositories:

```php
// Usage in controllers:
$this->accountRepository->withAuthorization()->all();
$this->transactionRepository->withAuthorization()->find($id);
$this->fundRepository->withAuthorization()->allQuery();
```

Repositories using this trait:
- `AccountRepository`
- `TransactionRepository`
- `FundRepository`

### 4. Middleware

Located in `app/Http/Middleware/`:

**SetFundPermissions.php**
- Extracts fund context from requests
- Sets fund ID for Spatie Permission's team feature
- Extraction order: route `{fund}` → `{fund_id}` → request input → `{account}` model

**EnsureTwoFactorIsCompleted.php**
- Enforces 2FA completion before accessing protected routes
- Bypasses: `two-factor-challenge`, `logout`

### 5. User Model Methods

Located in `app/Models/User.php`:

```php
$user->isSystemAdmin()           // Check if system admin
$user->hasRoleInFund($role, $fundId)  // Check fund-scoped role
$user->getAccessibleFundIds()    // Get {full: [], readonly: []}
$user->getOwnAccountIds()        // Get user's own account IDs
$user->canAccessAccount($account) // Check account access
```

## Controller Authorization

### Pattern for CRUD Operations

```php
// List (viewAny on class)
public function index()
{
    $this->authorize('viewAny', ModelExt::class);
    $items = $this->repository->withAuthorization()->all();
    // ...
}

// View (view on instance)
public function show($id)
{
    $item = $this->repository->withAuthorization()->find($id);
    if (empty($item)) { /* 404 */ }
    $this->authorize('view', $item);
    // ...
}

// Create (create on class)
public function create()
{
    $this->authorize('create', ModelExt::class);
    // ...
}

// Update (update on instance)
public function update($id)
{
    $item = $this->repository->withAuthorization()->find($id);
    if (empty($item)) { /* 404 */ }
    $this->authorize('update', $item);
    // ...
}

// Delete (delete on instance)
public function destroy($id)
{
    $item = $this->repository->withAuthorization()->find($id);
    if (empty($item)) { /* 404 */ }
    $this->authorize('delete', $item);
    // ...
}
```

### Controllers with Authorization

| Controller | Authorization Status |
|------------|---------------------|
| `AccountController` | ✅ Full authorization |
| `AccountControllerExt` | ✅ Full authorization |
| `TransactionController` | ✅ Full authorization |
| `TransactionControllerExt` | ✅ Full authorization |
| `FundController` | ✅ Full authorization |
| `FundControllerExt` | ✅ Full authorization |
| `UserRoleController` | ✅ System admin check |

## Route Protection

### Middleware Groups

All main routes are wrapped in the `auth` middleware group in `routes/web.php`:

```php
Route::middleware('auth')->group(function () {
    Route::resource('accounts', AccountControllerExt::class);
    Route::resource('funds', FundControllerExt::class);
    Route::resource('transactions', TransactionControllerExt::class);
    // ... other resources
});
```

### Admin-Only Routes

```php
// System admin only (checked in controller)
Route::get('/admin/user-roles', [UserRoleController::class, 'index']);
Route::get('/admin/user-roles/{user}', [UserRoleController::class, 'show']);
Route::post('/admin/user-roles/{user}/assign', [UserRoleController::class, 'assign']);
Route::post('/admin/user-roles/{user}/revoke', [UserRoleController::class, 'revoke']);
```

## Menu Visibility

The admin menu section is hidden for non-system-admins in `resources/views/layouts/menu.blade.php`:

```blade
@if(auth()->user() && method_exists(auth()->user(), 'isSystemAdmin') && auth()->user()->isSystemAdmin())
<li class="nav-item nav-dropdown">
    <!-- Admin menu items -->
</li>
@endif
```

## Two-Factor Authentication

### Setup Flow
1. User visits `/two-factor/setup`
2. QR code displayed for TOTP app
3. User enters code to verify
4. Recovery codes displayed

### Challenge Flow
1. User logs in with password
2. If 2FA enabled, redirected to `/two-factor-challenge`
3. User enters TOTP code or recovery code
4. Session marked as 2FA complete

### Views
- `auth/two-factor-setup.blade.php` - Setup/manage 2FA
- `auth/two-factor-challenge.blade.php` - Enter 2FA code
- `auth/two-factor-recovery-codes.blade.php` - View recovery codes

## Database Schema

### Permission Tables (Spatie)

```sql
-- roles table
id, name, guard_name, created_at, updated_at

-- permissions table
id, name, guard_name, created_at, updated_at

-- model_has_roles (with team/fund support)
role_id, model_type, model_id, team_id (fund_id)

-- model_has_permissions
permission_id, model_type, model_id

-- role_has_permissions
permission_id, role_id
```

### Login Activity Table

```sql
CREATE TABLE login_activities (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    browser VARCHAR(255),
    device VARCHAR(255),
    status ENUM('success', 'failed', 'two_factor_pending', 'two_factor_failed'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### User 2FA Columns

```sql
ALTER TABLE users ADD COLUMN two_factor_secret TEXT;
ALTER TABLE users ADD COLUMN two_factor_recovery_codes TEXT;
ALTER TABLE users ADD COLUMN two_factor_confirmed_at TIMESTAMP;
```

## Seeders

### RolesAndPermissionsSeeder

Creates all roles and permissions:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### InitialRolesAssignmentSeeder

Assigns initial roles to existing users based on business rules.

## Testing

Test files in `tests/Feature/`:

- `AuthorizationTest.php` - Policy and authorization tests (51 tests)
- `TwoFactorAuthTest.php` - 2FA flow tests
- `UserRoleManagementTest.php` - Role assignment tests

Run tests:
```bash
docker exec familyfund php artisan test --filter=Authorization
docker exec familyfund php artisan test --filter=TwoFactor
docker exec familyfund php artisan test --filter=UserRole
```

## Security Considerations

1. **System Admin Bypass** - System admins bypass all policy checks via `before()` method
2. **Query Scoping** - Always use `withAuthorization()` on repositories to prevent data leakage
3. **Fund Context** - Middleware extracts fund context for proper permission checking
4. **2FA Enforcement** - Middleware ensures 2FA is completed before accessing protected routes
5. **Login Tracking** - All login attempts are logged with IP, user agent, and status

## Changelog

### 2026-02-01 - Authorization Fixes

Added missing authorization checks to:
- `AccountControllerExt` - `showAsOf()`, `showPdfAsOf()`
- `TransactionController` - All CRUD methods
- `TransactionControllerExt` - All methods (index, create, preview, store, previewPending, processPending, processAllPending, edit, clone, resendEmail, bulkCreate, bulkPreview, bulkStore)
- `FundController` - All CRUD methods including `createWithSetup`, `storeWithSetup`
- `FundControllerExt` - All methods (showAsOf, showPDFAsOf, tradeBandsAsOf, showTradeBandsPDFAsOf, portfolios, overview, overviewData, editFourPctGoal, updateFourPctGoal)
- `FundRepository` - Added `AuthorizesQueries` trait
