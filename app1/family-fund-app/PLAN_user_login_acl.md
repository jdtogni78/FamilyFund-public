# User Login & ACL Implementation Plan

## Overview

Enable secure user login with fund-specific access control, 2FA, and role-based permissions.

**Current State:**
- Authentication: Laravel Breeze + Livewire/Volt (complete)
- Authorization: NONE - all authenticated users can access all data
- Roles: None - no packages, tables, or policies

**Target State:**
- Fund-specific roles: System Admin, Fund Admin, Financial Manager, Beneficiary
- ACL enforcement: Users see only their own data (beneficiaries) or fund data (admins/managers)
- TOTP 2FA with recovery codes
- Enhanced profile with password strength, session management, activity log
- Admin UI for role management

---

## Phase 1: Database & Package Setup

### 1.1 Install Packages
```bash
composer require spatie/laravel-permission
composer require pragmarx/google2fa-laravel
composer require bacon/bacon-qr-code
composer require jenssegers/agent
```

### 1.2 Configure spatie/laravel-permission with Teams

Create `config/permission.php`:
```php
'teams' => true,
'team_foreign_key' => 'fund_id',  // Use fund_id as team scope
```

### 1.3 Migrations to Create

| Migration | Purpose |
|-----------|---------|
| `add_two_factor_to_users_table` | Add `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` |
| `create_login_activities_table` | Track login attempts with IP, device, status |
| spatie migrations | `roles`, `permissions`, `model_has_roles` (with fund_id) |

### 1.4 Seed Roles

**Permissions:** `accounts.view`, `accounts.view-own`, `accounts.create`, `accounts.update`, `transactions.view`, `transactions.view-own`, `transactions.create`, `transactions.process`, `funds.view`, `funds.update`, `portfolios.view`, `portfolios.update`, `reports.view`, `reports.generate`, `system.admin`

**Roles per fund:**
- `system-admin` (fund_id=NULL): All permissions globally
- `fund-admin`: Full fund access
- `financial-manager`: Transactions, deposits, matching rules
- `beneficiary`: View own account only

---

## Phase 2: Model & Service Layer

### 2.1 Update User Model

**File:** `app/Models/User.php`

Add:
- `HasRoles` trait from spatie
- 2FA fields to `$fillable` and `$hidden`
- Encrypted casts for `two_factor_secret`, `two_factor_recovery_codes`
- Helper methods: `isSystemAdmin()`, `hasRoleInFund($role, $fundId)`, `canAccessAccount($account)`, `hasTwoFactorEnabled()`

### 2.2 Create AuthorizationService

**File:** `app/Services/AuthorizationService.php`

Methods:
- `scopeAccountsQuery(Builder $query)`: Filter accounts by user access
- `scopeTransactionsQuery(Builder $query)`: Filter transactions by user access
- `getAccessibleFundIds()`: Returns `['full' => [...], 'readonly' => [...]]`

### 2.3 Create Models

| Model | File | Purpose |
|-------|------|---------|
| `LoginActivity` | `app/Models/LoginActivity.php` | Login audit log |

---

## Phase 3: Policy Layer

### 3.1 Create Policies

| Policy | Model | Key Logic |
|--------|-------|-----------|
| `AccountPolicy` | `AccountExt` | System admin bypasses; fund admin/manager see all in fund; beneficiary sees own only |
| `TransactionPolicy` | `TransactionExt` | Access via account ownership |
| `FundPolicy` | `FundExt` | View if has any role in fund; update if fund-admin |
| `PortfolioPolicy` | `Portfolio` | Follows fund access |
| `CashDepositPolicy` | `CashDeposit` | Follows account access |

### 3.2 Register in AuthServiceProvider

**File:** `app/Providers/AuthServiceProvider.php` (create new)

```php
protected $policies = [
    AccountExt::class => AccountPolicy::class,
    TransactionExt::class => TransactionPolicy::class,
    FundExt::class => FundPolicy::class,
];
```

---

## Phase 4: Repository Layer

### 4.1 Create AuthorizesQueries Trait

**File:** `app/Repositories/Traits/AuthorizesQueries.php`

- `withAuthorization()`: Enable filtering
- `applyAuthorizationScope(Builder $query)`: Override in child repos

### 4.2 Update Repositories

- `AccountRepository`: Filter by user access
- `TransactionRepository`: Filter by user access

---

## Phase 5: Middleware Layer

### 5.1 Create Middleware

| Middleware | Purpose |
|------------|---------|
| `SetFundPermissions` | Determine fund_id from route/request, call `setPermissionsTeamId()` |
| `EnsureTwoFactorIsCompleted` | Redirect to 2FA challenge if pending |

### 5.2 Register in bootstrap/app.php

```php
$middleware->web(append: [
    SetFundPermissions::class,
    EnsureTwoFactorIsCompleted::class,
]);
$middleware->alias([
    'role' => RoleMiddleware::class,
    'permission' => PermissionMiddleware::class,
]);
```

---

## Phase 6: Controller Authorization

### 6.1 Update Controllers Pattern

Add to each controller method:
```php
$this->authorize('viewAny', AccountExt::class);  // index
$this->authorize('view', $account);               // show
$this->authorize('create', AccountExt::class);   // create/store
$this->authorize('update', $account);            // edit/update
$this->authorize('delete', $account);            // destroy
```

Use authorized repository:
```php
$accounts = $this->accountRepository->withAuthorization()->all();
```

### 6.2 Update Form Requests

Change `authorize()` from `return true` to actual permission checks.

### 6.3 Controllers to Update

- `AccountController`, `AccountControllerExt`
- `TransactionController`, `TransactionControllerExt`
- `FundController`, `FundControllerExt`
- `PortfolioController`
- `CashDepositController`
- All API controllers

---

## Phase 7: 2FA Implementation

### 7.1 Login Flow Modification

**File:** `app/Livewire/Forms/LoginForm.php`

In `authenticate()`:
1. Validate credentials
2. If user has 2FA enabled:
   - Store `login.id` in session
   - Logout temporarily
   - Redirect to `/two-factor-challenge`
3. On successful 2FA, complete login

### 7.2 Create 2FA Components

| Component | File | Purpose |
|-----------|------|---------|
| `TwoFactorAuthenticationForm` | `resources/views/livewire/profile/two-factor-authentication-form.blade.php` | Enable/disable 2FA, show QR, recovery codes |
| `TwoFactorChallenge` | `resources/views/livewire/pages/auth/two-factor-challenge.blade.php` | Code entry during login |

### 7.3 2FA Features

- Generate secret with `Google2FA::generateSecretKey()`
- QR code with `BaconQrCode`
- Confirm with valid code before enabling
- 8 single-use recovery codes (encrypted)
- Rate limit: 5 attempts/minute

---

## Phase 8: Profile Enhancements

### 8.1 Update Profile Page

**File:** `resources/views/profile.blade.php`

Add sections:
1. Two-Factor Authentication (new)
2. Active Sessions (new)
3. Login Activity (new)

### 8.2 Password Improvements

**File:** `app/Providers/AppServiceProvider.php`

```php
Password::defaults(fn () => Password::min(8)->mixedCase()->numbers()->symbols());
```

Create `resources/views/components/password-strength-indicator.blade.php` with JS strength meter.

### 8.3 Session Management Component

**File:** `resources/views/livewire/profile/session-manager.blade.php`

- List sessions from `sessions` table
- Parse user agent with `jenssegers/agent`
- Revoke individual or all other sessions

### 8.4 Login Activity Component

**File:** `resources/views/livewire/profile/login-activity.blade.php`

- Display last 20 login attempts
- Show status, IP, browser, device

---

## Phase 9: Admin Role Management UI

### 9.1 Routes

Add to `routes/web.php`:
```php
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
    Route::get('user-roles/{user}', [UserRoleController::class, 'show'])->name('user-roles.show');
    Route::post('user-roles/{user}/assign', [UserRoleController::class, 'assign'])->name('user-roles.assign');
    Route::delete('user-roles/{user}/revoke', [UserRoleController::class, 'revoke'])->name('user-roles.revoke');
});
```

### 9.2 Controller

**File:** `app/Http/Controllers/WebV1/UserRoleController.php`

- `index()`: List users with roles, filters by fund/role
- `show($user)`: User detail with role management
- `assign($user)`: Add role to user for fund
- `revoke($user)`: Remove role (prevent last admin removal)

### 9.3 Views

| View | Purpose |
|------|---------|
| `admin/user-roles/index.blade.php` | DataTable of users with roles, filters |
| `admin/user-roles/show.blade.php` | Single user role management, assign form, history |

### 9.4 Role Change Audit

Create `RoleChangeLog` model to track all role changes with:
- who changed, what changed, when

---

## Phase 10: Migration Script

### 10.1 Initial Role Assignment

**File:** Migration `seed_initial_roles.php`

1. Make `admin@dev.familyfund.local` a System Admin
2. For all other users with accounts: assign `beneficiary` role for their fund(s)
3. Test user `claude@test.local` gets beneficiary on first fund

### 10.2 Update prod_to_dev.sql

Add SQL to sync roles when loading prod data to dev.

---

## View Layer Updates

### Blade Authorization Directives

```blade
@can('create', App\Models\AccountExt::class)
    <a href="{{ route('accounts.create') }}">Create Account</a>
@endcan

@can('update', $account)
    <a href="{{ route('accounts.edit', $account) }}">Edit</a>
@endcan
```

---

## Critical Files to Create/Modify

### New Files
- `config/permission.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Services/AuthorizationService.php`
- `app/Policies/AccountPolicy.php`, `TransactionPolicy.php`, `FundPolicy.php`
- `app/Repositories/Traits/AuthorizesQueries.php`
- `app/Http/Middleware/SetFundPermissions.php`
- `app/Http/Middleware/EnsureTwoFactorIsCompleted.php`
- `app/Http/Controllers/WebV1/UserRoleController.php`
- `app/Models/LoginActivity.php`
- `app/Exceptions/TwoFactorAuthenticationRequired.php`
- `resources/views/livewire/profile/two-factor-authentication-form.blade.php`
- `resources/views/livewire/profile/session-manager.blade.php`
- `resources/views/livewire/profile/login-activity.blade.php`
- `resources/views/livewire/pages/auth/two-factor-challenge.blade.php`
- `resources/views/admin/user-roles/index.blade.php`
- `resources/views/admin/user-roles/show.blade.php`

### Files to Modify
- `app/Models/User.php` - Add HasRoles, 2FA fields
- `app/Livewire/Forms/LoginForm.php` - Add 2FA check
- `bootstrap/app.php` - Register middleware
- `routes/web.php` - Add admin routes
- `resources/views/profile.blade.php` - Add new sections
- All controllers - Add authorization calls
- All repositories - Add AuthorizesQueries trait

---

## Verification Plan

### 1. Role Assignment
- [ ] admin@dev.familyfund.local is system admin
- [ ] Other users are beneficiaries for their funds
- [ ] Admin UI shows all users and roles

### 2. ACL Enforcement
- [ ] Beneficiary sees only their own accounts
- [ ] Fund admin sees all accounts in their fund
- [ ] System admin sees everything
- [ ] API endpoints respect same ACL

### 3. 2FA Flow
- [ ] Enable 2FA from profile shows QR
- [ ] Must enter valid code to confirm
- [ ] Login redirects to challenge when 2FA enabled
- [ ] Recovery codes work

### 4. Profile Features
- [ ] Password strength indicator works
- [ ] Sessions list shows current and other sessions
- [ ] Can revoke other sessions
- [ ] Login activity shows recent logins

### 5. Manual Testing
```bash
# As beneficiary
curl -L http://localhost:3000/dev-login/accounts/8
# Should only see account 8

# As system admin
# Should see all accounts
```

---

## Implementation Order

1. **Phase 1-2**: Database, packages, models (foundation)
2. **Phase 3-4**: Policies, repository integration (ACL logic)
3. **Phase 5-6**: Middleware, controller updates (enforcement)
4. **Phase 9-10**: Admin UI, migrations (role management)
5. **Phase 7-8**: 2FA, profile enhancements (security features)

Each phase can be tested independently before proceeding.
