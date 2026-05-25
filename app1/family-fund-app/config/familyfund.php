<?php

/*
|--------------------------------------------------------------------------
| Family Fund application settings
|--------------------------------------------------------------------------
|
| Parameterized identities pulled out of source per
| docs/security/SECRET-ROTATION-PLAN.md §4 (pre-publish PII cleanup). The
| committed defaults are synthetic placeholders; set the real operator
| address(es) only in the git-ignored .env, never in a tracked file.
|
*/

// Comma-separated allow-list of email addresses treated as application admins,
// in addition to users holding the System Admin role. Consumed by the dev-login
// route, the isAdmin() checks in OperationsController / FundTrait, the admin-only
// Blade toggles, the role seeder, and the bootstrap migrations.
//
// Default is the non-PII dev admin that prod_to_dev.sql renames the admin to
// (admin@dev.familyfund.local, see #79). Set ADMIN_EMAILS in the git-ignored
// .env to the real operator address(es) — e.g. for a raw prod dump before
// prod_to_dev.sql has run: ADMIN_EMAILS="admin@dev.familyfund.local,you@example.com".
$adminEmails = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('ADMIN_EMAILS', 'admin@dev.familyfund.local'))
)));

return [

    'admin_emails' => $adminEmails,

    // Recipient for operational alert emails (e.g. scheduled-job failure alerts).
    // Reuses MAIL_ADMIN_ADDRESS (shared with the other admin mailers) and falls
    // back to the first configured admin email.
    'alert_email' => env('MAIL_ADMIN_ADDRESS') ?: ($adminEmails[0] ?? 'admin@dev.familyfund.local'),

];
