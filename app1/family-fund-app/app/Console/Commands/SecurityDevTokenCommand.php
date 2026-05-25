<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Dev-only: mint a Sanctum personal-access token for a QA user so an
 * authenticated DAST / OWASP ZAP scan (bin/zap-authenticated.sh, #13/#49) can
 * exercise the `/api/*` surface as a real (typically low-privilege) user.
 *
 * Prints ONLY the plain-text token on stdout so callers can capture it. Refuses
 * to run in production. Uses the same role-alias map as the /dev-login route.
 */
class SecurityDevTokenCommand extends Command
{
    protected $signature = 'security:dev-token {--as=beneficiary : Role alias (admin|system-admin|fund-admin|financial-manager|beneficiary) or an email}';

    protected $description = 'Dev-only: mint a Sanctum API token for a QA user (for authenticated DAST/ZAP scans)';

    /**
     * Role alias → ordered list of candidate emails (first that resolves wins).
     * The admin aliases resolve to the configured ADMIN_EMAILS
     * (config/familyfund.php; default is the non-PII dev admin that
     * prod_to_dev.sql renames the admin to). Set ADMIN_EMAILS in .env to add the
     * real address as a fallback for an un-scrubbed prod dump / test baseline.
     */
    private function aliases(): array
    {
        $admin = config('familyfund.admin_emails');

        return [
            'admin' => $admin,
            'system-admin' => $admin,
            'fund-admin' => ['qa-fund-admin@test.local'],
            'financial-manager' => ['qa-financial-manager@test.local'],
            'beneficiary' => ['qa-beneficiary@test.local'],
        ];
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('security:dev-token is disabled in production.');

            return self::FAILURE;
        }

        $as = (string) $this->option('as');
        $candidates = $this->aliases()[$as] ?? [$as];

        $user = User::whereIn('email', $candidates)->first();
        if (! $user) {
            $this->error('No user for ' . $as . ' (tried: ' . implode(', ', $candidates) . '). Seed QaTestUsersSeeder first.');

            return self::FAILURE;
        }

        // Print only the token so a scan script can capture it directly.
        $this->line($user->createToken('dast-zap-' . now()->timestamp)->plainTextToken);

        return self::SUCCESS;
    }
}
