# Controllers Review

Audit of `app/Http/Controllers/` in `app1/family-fund-app/`. Goal: identify auto-generated controllers that are no longer needed, and reorganize controllers that aren't in a versioned subdirectory.

## Layout before this work

```
app/Http/Controllers/
├── *.php                     # ~31 root-level controllers (InfyOm-generated CRUD + a few extras)
├── API/*.php                 # ~25 auto-generated REST API controllers
├── APIv1/*.php               # ~13 extended API controllers (mostly *APIControllerExt)
├── WebV1/*.php               # ~25 extended web controllers (mostly *ControllerExt) + standalones
├── Auth/*.php                # Laravel auth controllers
├── Traits/*.php              # 22 trait files (all in use)
├── AppBaseController.php     # Common base (sendResponse / sendError / sendSuccess)
└── Controller.php            # Laravel base
```

## Findings

### Category A — base controllers used only as inheritance parents

These bases have no direct route references, no usages outside their `*Ext` subclass, and no tests that instantiate them directly. They are InfyOm scaffolding (constructor + standard CRUD). The Ext children currently use `class XExt extends X` and call `parent::__construct($repo)`. To remove the base, the inherited CRUD methods are inlined into the Ext, the Ext is reparented to `AppBaseController`, and the base is deleted.

**Root `Controllers/` (17 files):**
- `AccountBalanceController`
- `AccountController`
- `AccountGoalController`
- `AccountMatchingRuleController`
- `AccountReportController`
- `AssetPriceController`
- `CashDepositController`
- `DepositRequestController`
- `FundController`
- `FundReportController`
- `GoalController`
- `MatchingRuleController`
- `PortfolioAssetController`
- `ScheduledJobController`
- `TradePortfolioController`
- `TradePortfolioItemController`
- `TransactionController`

**`Controllers/API/` (6 files):**
- `AccountReportAPIController`
- `AssetPriceAPIController`
- `FundReportAPIController`
- `ScheduledJobAPIController`
- `TradePortfolioAPIController`
- `TransactionAPIController`

### Category B — base controllers still actively used (keep)

These have a `Route::resource(...)` declaration pointing directly at them. The base is the live controller for standard CRUD; the `*Ext` sibling (where present) is only used for custom endpoints registered separately.

**Web (root `Controllers/`, kept but moved to `Controllers/Web/` for namespacing):**
- `AddressController`
- `AssetChangeLogController`
- `AssetController`
- `ChangeLogController`
- `IdDocumentController`
- `PersonController`
- `PhoneController`
- `PortfolioController`
- `ScheduleController`
- `TradeBandReportController`
- `TransactionMatchingController`
- `UserController`

**API (`Controllers/API/`, kept in place):**
`AccountAPIController`, `AccountBalanceAPIController`, `AccountMatchingRuleAPIController`, `AddressAPIController`, `AssetAPIController`, `AssetChangeLogAPIController`, `ChangeLogAPIController`, `FundAPIController`, `IdDocumentAPIController`, `MatchingRuleAPIController`, `PersonAPIController`, `PhoneAPIController`, `PortfolioAPIController`, `PortfolioAssetAPIController`, `PortfolioBalanceAPIController`, `ScheduleAPIController`, `TradePortfolioItemAPIController`, `TransactionMatchingAPIController`, `UserAPIController`

### Category C — organizational / placement issues

1. `Controllers/APIv1/ExchangeHolidayAPIController.php` — lives in `APIv1/` but isn't an `Ext`. It's a custom standalone API controller. Moved to `Controllers/API/` for consistency.
2. `Controllers/WebV1/AccountCreditLineControllerExt.php` — has the `Ext` suffix but `extends AppBaseController` (no parent it's extending). Renamed to `AccountCreditLineController` and all 25 route references updated.
3. `Controllers/WebV1/ExchangeHolidayController.php` — extended Laravel's `Controller` while all other WebV1 standalones extend `AppBaseController`. Reparented to `AppBaseController`.
4. 12 active web controllers lived at root with no version directory — moved to a new `Controllers/Web/` namespace.

## Layout after this work

```
app/Http/Controllers/
├── Web/                      # NEW: standalone web CRUD controllers (12 files, formerly root)
├── WebV1/                    # Extended web controllers (independent now, no base dependency)
├── API/                      # Auto-generated REST API + ExchangeHolidayAPIController (moved here)
├── APIv1/                    # Extended API controllers (independent now, no base dependency)
├── Auth/
├── Traits/
├── AppBaseController.php
├── Controller.php
└── HomeController.php
```

The root `Controllers/` directory holds only infrastructure (`Controller.php`, `AppBaseController.php`, `HomeController.php`) and the four subdirectories. Every other web/API controller is in a versioned subdirectory.

## Migration approach

For each base → Ext pair in Category A:
1. Copy methods from base into Ext that the Ext doesn't already override.
2. Copy the repository property declaration into Ext.
3. Rewrite the Ext constructor to assign the repository directly (instead of `parent::__construct(...)`).
4. Change `extends BaseController` → `extends AppBaseController`.
5. Update `use` statements (remove the import of the base; add imports the inlined methods need).
6. Delete the base file.

For Category B web moves: namespace change from `App\Http\Controllers` → `App\Http\Controllers\Web`; update `routes/web.php` accordingly.
