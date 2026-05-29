<?php
/**
 * Seeded positive cases for .semgrep/familyfund.yml. Each # ruleid: ...
 * marker tells semgrep that the line below MUST match that rule. Lines
 * without a marker MUST NOT match (false-positive guard). Edit when you
 * tighten / loosen rules so this file stays the authoritative spec.
 *
 * Run:   semgrep scan --config .semgrep/familyfund.yml .semgrep/familyfund.test.php
 *
 * This file is intentionally NOT real Laravel code; it's a fixture and is
 * NOT picked up by the live-tree scan in bin/security-scan.sh (which scans
 * app1/family-fund-app, not the repo root .semgrep dir).
 */

namespace SemgrepTests\FamilyFund;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// Rule 1: ff-dev-login-route-outside-env-guard
// ---------------------------------------------------------------------------

// ruleid: ff-dev-login-route-outside-env-guard
Route::get('/dev-login/{redirect?}', function () { return 'pwned'; });

// ruleid: ff-dev-login-route-outside-env-guard
Route::any('/dev-login/{redirect?}', function () { return 'pwned'; });

// True-negative: the real routes/web.php wraps the same Route::get in an
// app()->environment(...) guard — this MUST stay quiet.
if (app()->environment('local', 'dev', 'testing')) {
    // ok: guarded by environment check
    Route::get('/dev-login/{redirect?}', function () { return 'fine'; });
}

if (\Illuminate\Support\Facades\App::environment('local', 'dev')) {
    // ok: fully-qualified facade form of the same guard
    Route::get('/dev-login', function () { return 'fine'; });
}

// ---------------------------------------------------------------------------
// Rule 2: ff-dev-token-call-outside-allowed-context
// ---------------------------------------------------------------------------

function fake_controller_action(Request $request): string
{
    // ruleid: ff-dev-token-call-outside-allowed-context
    Artisan::call("security:dev-token", ["--as" => $request->input("role")]);

    // ruleid: ff-dev-token-call-outside-allowed-context
    \Illuminate\Support\Facades\Artisan::call("security:dev-token", []);

    return "x";
}

// True-negative: an unrelated artisan call must NOT match.
function ok_artisan_call(): void
{
    Artisan::call("queue:work", []);
}

// ---------------------------------------------------------------------------
// Rule 3: ff-raw-sql-interpolates-request-input
// ---------------------------------------------------------------------------

function raw_sql_examples(Request $request, $q): void
{
    // ruleid: ff-raw-sql-interpolates-request-input
    $q->whereRaw("account_id = " . $request->input("id"));

    // ruleid: ff-raw-sql-interpolates-request-input
    DB::raw("SELECT * FROM transactions WHERE id = " . $request->input("id"));

    // ruleid: ff-raw-sql-interpolates-request-input
    DB::statement("DELETE FROM accounts WHERE id = " . $request->query("id"));

    // ruleid: ff-raw-sql-interpolates-request-input
    $q->orderByRaw("created_at " . $request->input("dir"));

    // ruleid: ff-raw-sql-interpolates-request-input
    $q->whereRaw("user_id = " . $_GET["id"]);

    // True-negative: parameterized whereRaw — the shape the live code uses.
    $q->whereRaw("ABS(shares_due - ?) < ?", [$request->input("shares"), 0.01]);

    // True-negative: hardcoded literal, no request input.
    DB::raw("COUNT(*)");
}

// ---------------------------------------------------------------------------
// Rule 4: ff-unsafe-file-read-from-request
// ---------------------------------------------------------------------------

function file_read_examples(Request $request): void
{
    // ruleid: ff-unsafe-file-read-from-request
    $bytes = file_get_contents($request->input("path"));

    // ruleid: ff-unsafe-file-read-from-request
    $bytes = file_get_contents("/var/data/" . $request->input("name"));

    // ruleid: ff-unsafe-file-read-from-request
    $fh = fopen($request->input("path"), "r");

    // ruleid: ff-unsafe-file-read-from-request
    readfile($request->query("file"));

    // ruleid: ff-unsafe-file-read-from-request
    $contents = Storage::get($request->input("key"));

    // ruleid: ff-unsafe-file-read-from-request
    $contents = Storage::disk("local")->get($request->input("key"));

    // True-negative: hardcoded path.
    $bytes = file_get_contents("/app/config/app.php");
}
