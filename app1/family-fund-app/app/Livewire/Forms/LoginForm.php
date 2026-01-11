<?php

namespace App\Livewire\Forms;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Whether 2FA challenge is required after authentication.
     */
    public bool $requiresTwoFactor = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            // Record failed login attempt
            LoginActivity::recordFailed(null);

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Check if user has 2FA enabled
        $user = Auth::user();
        if ($user && $user->hasTwoFactorEnabled()) {
            // Store user ID for 2FA verification
            Session::put('two_factor_user_id', $user->id);
            Session::put('two_factor_remember', $this->remember);

            // Log out the user until 2FA is verified
            Auth::logout();

            // Record login attempt as 2FA pending
            LoginActivity::recordTwoFactorPending($user);

            // Set flag for redirect
            $this->requiresTwoFactor = true;

            return;
        }

        // Record successful login
        LoginActivity::recordSuccess($user);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
