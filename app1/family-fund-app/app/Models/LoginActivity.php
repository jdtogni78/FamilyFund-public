<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'platform',
        'device',
        'status',
        'location',
        'login_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_TWO_FACTOR_PENDING = 'two_factor_pending';
    public const STATUS_TWO_FACTOR_FAILED = 'two_factor_failed';

    /**
     * Get the user that owns this login activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a login attempt.
     */
    public static function record(
        ?User $user,
        string $status,
        ?Request $request = null
    ): self {
        $request = $request ?? request();
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        return self::create([
            'user_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
            'platform' => $agent->platform(),
            'device' => $agent->device() ?: ($agent->isDesktop() ? 'Desktop' : 'Unknown'),
            'status' => $status,
            'login_at' => now(),
        ]);
    }

    /**
     * Record a successful login.
     */
    public static function recordSuccess(User $user, ?Request $request = null): self
    {
        return self::record($user, self::STATUS_SUCCESS, $request);
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailed(?User $user = null, ?Request $request = null): self
    {
        return self::record($user, self::STATUS_FAILED, $request);
    }

    /**
     * Record a 2FA pending login.
     */
    public static function recordTwoFactorPending(User $user, ?Request $request = null): self
    {
        return self::record($user, self::STATUS_TWO_FACTOR_PENDING, $request);
    }

    /**
     * Record a failed 2FA attempt.
     */
    public static function recordTwoFactorFailed(User $user, ?Request $request = null): self
    {
        return self::record($user, self::STATUS_TWO_FACTOR_FAILED, $request);
    }

    /**
     * Get a human-readable description of the device.
     */
    public function getDeviceDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->browser) {
            $parts[] = $this->browser;
            if ($this->browser_version) {
                $parts[count($parts) - 1] .= ' ' . $this->browser_version;
            }
        }

        if ($this->platform) {
            $parts[] = 'on ' . $this->platform;
        }

        if ($this->device && $this->device !== 'Desktop') {
            $parts[] = '(' . $this->device . ')';
        }

        return implode(' ', $parts) ?: 'Unknown device';
    }

    /**
     * Check if this was a successful login.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Scope to get recent activities for a user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get recent activities.
     */
    public function scopeRecent($query, int $limit = 20)
    {
        return $query->orderBy('login_at', 'desc')->limit($limit);
    }
}
