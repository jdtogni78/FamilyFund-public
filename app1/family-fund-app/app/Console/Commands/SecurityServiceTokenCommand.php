<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Manage the dstrader service account's Sanctum API tokens (#82).
 *
 * Unlike the dev-only security:dev-token, this is prod-allowed: it is the
 * supported way to mint/rotate the long-lived machine tokens dstrader uses to
 * push prices/positions and trigger reports against the admin-gated `/api/*`
 * surface. Two named tokens are expected — `app` (Java trading app) and
 * `scheduler` (bash report scheduler) — rotated independently.
 *
 * Modes (mutually exclusive; first match wins):
 *   --prune-expired   delete the service user's expired token rows, exit 0
 *   --check-expiry    warn (log + email) if any active token expires within
 *                     --warn-days; exit non-zero if so (for a cron safety net)
 *   (default)         mint/rotate: create a new `dstrader-<name>` token with an
 *                     expiry and print ONLY its plain text on stdout
 *
 * The mint path prints nothing but the token to stdout so a rotation script can
 * capture it with `TOKEN=$(php artisan security:service-token --name=app
 * --rotate)`. All diagnostics go to stderr.
 */
class SecurityServiceTokenCommand extends Command
{
    protected $signature = 'security:service-token
        {--name= : Token name base (app|scheduler) — required to mint/rotate}
        {--rotate : Mint a new token (the default action when --name is given)}
        {--ttl=90 : Days until the new token expires (0 = never)}
        {--revoke-previous : After minting, delete older tokens of the same name}
        {--prune-expired : Delete the service user expired tokens, then exit}
        {--check-expiry : Warn if any active token expires within --warn-days; exit non-zero}
        {--warn-days=14 : Day threshold used by --check-expiry}';

    protected $description = "Manage the dstrader service account's Sanctum API tokens (#82): mint/rotate, prune, expiry-check.";

    public function handle(): int
    {
        $email = config('familyfund.service_user_email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->stderr("Service user not found ({$email}). Run DstraderServiceUserSeeder first.");
            return self::FAILURE;
        }

        if ($this->option('prune-expired')) {
            return $this->pruneExpired($user);
        }

        if ($this->option('check-expiry')) {
            return $this->checkExpiry($user);
        }

        return $this->rotate($user);
    }

    private function rotate(User $user): int
    {
        $name = (string) $this->option('name');
        if ($name === '') {
            $this->stderr('Specify --name=app|scheduler to mint/rotate a token.');
            return self::FAILURE;
        }

        $tokenName = 'dstrader-' . $name;
        $ttl = (int) $this->option('ttl');
        $expiresAt = $ttl > 0 ? now()->addDays($ttl) : null;

        // Sanctum ^4: createToken($name, $abilities, $expiresAt). Identity is
        // system-admin (authz is by role, not token abilities), so grant '*'.
        $new = $user->createToken($tokenName, ['*'], $expiresAt);

        if ($this->option('revoke-previous')) {
            $deleted = $user->tokens()
                ->where('name', $tokenName)
                ->where('id', '!=', $new->accessToken->getKey())
                ->delete();
            $this->stderr("Revoked {$deleted} previous '{$tokenName}' token(s).");
        }

        $when = $expiresAt ? $expiresAt->toDateString() : 'never';
        $this->stderr("Minted '{$tokenName}' (expires {$when}).");

        // ONLY the plaintext token on stdout, so a script can capture it.
        $this->line($new->plainTextToken);

        return self::SUCCESS;
    }

    private function pruneExpired(User $user): int
    {
        $count = $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $this->stderr("Pruned {$count} expired service token(s).");
        return self::SUCCESS;
    }

    private function checkExpiry(User $user): int
    {
        $warnDays = (int) $this->option('warn-days');
        $threshold = now()->addDays($warnDays);

        $soon = $user->tokens()
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', $threshold)
            ->get();

        if ($soon->isEmpty()) {
            $this->stderr("OK: no service tokens expire within {$warnDays} days.");
            return self::SUCCESS;
        }

        $lines = $soon->map(fn ($t) => "  - {$t->name} expires {$t->expires_at->toDateString()}")->all();
        $body = "dstrader service API token(s) expire within {$warnDays} days — rotate before they lapse "
            . "(price feeds break on expiry). See docs/runbooks/ff-service-token-rotation.md.\n\n"
            . implode("\n", $lines);

        Log::warning('[security:service-token] ' . str_replace("\n", ' ', $body));
        $this->stderr($body);

        // Best-effort admin email; never let a mail failure mask the non-zero exit.
        try {
            $to = config('familyfund.alert_email');
            if ($to) {
                Mail::raw($body, function ($m) use ($to) {
                    $m->to($to)->subject('[FamilyFund] dstrader service token expiring soon');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('[security:service-token] expiry alert email failed: ' . $e->getMessage());
        }

        return self::FAILURE;
    }

    /** Write a diagnostic line to stderr, keeping stdout reserved for the token. */
    private function stderr(string $message): void
    {
        if (defined('STDERR')) {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            error_log($message);
        }
    }
}
