<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsCompleted
{
    /**
     * Routes that should be accessible even when 2FA is pending.
     */
    protected array $except = [
        'two-factor-challenge',
        'two-factor-challenge/*',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in - continue
        if (!$user) {
            return $next($request);
        }

        // Check if 2FA challenge is pending
        if (session()->has('login.id') && session('login.id') === $user->id) {
            // User has 2FA enabled and hasn't completed the challenge
            if ($user->hasTwoFactorEnabled() && !$this->shouldPassThrough($request)) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }

    /**
     * Determine if the request should pass through without 2FA check.
     */
    protected function shouldPassThrough(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
