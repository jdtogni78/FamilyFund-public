<?php

/*
|--------------------------------------------------------------------------
| Family Fund application settings
|--------------------------------------------------------------------------
|
| Parameterized identities pulled out of source per the pre-publish PII
| cleanup (rotation plan §4; plan lives in the private familyfund-secrets
| repo). The committed defaults are synthetic placeholders; set the real
| operator address(es) only in the git-ignored .env, never in a tracked file.
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

    // Enforce system-admin-only writes (create/update/delete) on global
    // shared/reference API resources and the reference bulk-write endpoints
    // (#82). When false the write-authz tightening is a no-op — reads and the
    // auth:sanctum requirement are unaffected. Default ON; deploy a fresh prod
    // cutover with FF_ENFORCE_ADMIN_WRITES=false first, confirm the dstrader
    // service token can still push prices, then flip it on (see #82).
    'enforce_admin_writes' => filter_var(
        env('FF_ENFORCE_ADMIN_WRITES', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    // Email identity of the dstrader machine-to-machine service account (#82).
    // Created with the global system-admin role by DstraderServiceUserSeeder and
    // used by security:service-token to mint the `app` / `scheduler` API tokens.
    // Authenticates via Sanctum token, never an interactive password.
    'service_user_email' => env('DSTRADER_SERVICE_EMAIL', 'dstrader-service@familyfund.local'),

];
