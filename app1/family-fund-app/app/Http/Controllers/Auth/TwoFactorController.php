<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Show the 2FA challenge form.
     */
    public function showChallenge()
    {
        if (!Session::has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the 2FA code.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = Session::get('two_factor_user_id');
        $remember = Session::get('two_factor_remember', false);

        if (!$userId) {
            return redirect()->route('login')
                ->with('status', 'Your session has expired. Please login again.');
        }

        $user = User::find($userId);
        if (!$user) {
            Session::forget(['two_factor_user_id', 'two_factor_remember']);
            return redirect()->route('login');
        }

        $code = $request->input('code');
        $secret = $user->two_factor_secret;

        // Check if it's a recovery code
        if ($this->isRecoveryCode($user, $code)) {
            $this->consumeRecoveryCode($user, $code);
            $this->completeLogin($user, $remember);
            return redirect()->intended(route('dashboard'));
        }

        // Verify TOTP code
        if (!$this->google2fa->verifyKey($secret, $code)) {
            LoginActivity::recordTwoFactorFailed($user);

            return back()->withErrors([
                'code' => 'The provided two-factor authentication code was invalid.',
            ]);
        }

        $this->completeLogin($user, $remember);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Complete the login process after 2FA verification.
     */
    protected function completeLogin(User $user, bool $remember): void
    {
        Session::forget(['two_factor_user_id', 'two_factor_remember']);
        Session::regenerate();

        Auth::login($user, $remember);

        LoginActivity::recordSuccess($user);
    }

    /**
     * Check if the code is a valid recovery code.
     */
    protected function isRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        return in_array($code, $recoveryCodes);
    }

    /**
     * Consume (remove) a used recovery code.
     */
    protected function consumeRecoveryCode(User $user, string $code): void
    {
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $recoveryCodes = array_values(array_filter($recoveryCodes, fn($c) => $c !== $code));

        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();
    }

    /**
     * Show the 2FA setup page.
     */
    public function showSetup()
    {
        $user = Auth::user();

        // Generate new secret if not set
        if (!$user->two_factor_secret) {
            $secret = $this->google2fa->generateSecretKey();
            $user->two_factor_secret = $secret;
            $user->save();
        }

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret
        );

        return view('auth.two-factor-setup', [
            'secret' => $user->two_factor_secret,
            'qrCodeUrl' => $qrCodeUrl,
            'isEnabled' => $user->hasTwoFactorEnabled(),
        ]);
    }

    /**
     * Enable 2FA after verification.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $code = $request->input('code');

        if (!$this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            return back()->withErrors([
                'code' => 'The provided verification code was invalid. Please try again.',
            ]);
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return redirect()->route('two-factor.recovery-codes')
            ->with('status', 'Two-factor authentication has been enabled.');
    }

    /**
     * Show recovery codes.
     */
    public function showRecoveryCodes()
    {
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup');
        }

        return view('auth.two-factor-recovery-codes', [
            'recoveryCodes' => $user->two_factor_recovery_codes,
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes()
    {
        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup');
        }

        $user->two_factor_recovery_codes = $this->generateRecoveryCodes();
        $user->save();

        return redirect()->route('two-factor.recovery-codes')
            ->with('status', 'Recovery codes have been regenerated.');
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();

        $user->two_factor_secret = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->route('profile')
            ->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * Generate a set of recovery codes.
     */
    protected function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }
}
