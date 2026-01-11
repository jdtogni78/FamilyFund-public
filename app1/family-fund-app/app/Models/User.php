<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'password' => 'required|string|min:8',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'person_id',
        'is_admin',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Check if user is an application admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin ?? false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function accounts()
    {
        return $this->hasMany(\App\Models\AccountExt::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class);
    }

    /**
     * Check if user is a system administrator (global access).
     */
    public function isSystemAdmin(): bool
    {
        // System admin role uses fund_id=0 to indicate global access
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);

        $isAdmin = $this->hasRole('system-admin');

        setPermissionsTeamId($originalTeamId);

        return $isAdmin;
    }

    /**
     * Check if user has a specific role within a fund.
     */
    public function hasRoleInFund(string $role, int $fundId): bool
    {
        if ($this->isSystemAdmin()) {
            return true;
        }

        // Set the team context temporarily
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId($fundId);

        $hasRole = $this->hasRole($role);

        // Restore original team context
        setPermissionsTeamId($originalTeamId);

        return $hasRole;
    }

    /**
     * Check if user can access a specific account.
     */
    public function canAccessAccount(AccountExt $account): bool
    {
        // System admins can access all accounts
        if ($this->isSystemAdmin()) {
            return true;
        }

        // Check if user owns the account directly
        if ($this->id === $account->user_id) {
            return true;
        }

        // Check if user has fund-level access
        $fundId = $account->fund_id;
        return $this->hasRoleInFund('fund-admin', $fundId)
            || $this->hasRoleInFund('financial-manager', $fundId);
    }

    /**
     * Check if two-factor authentication is enabled and confirmed.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null
            && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Get fund IDs the user has access to with their access level.
     *
     * @return array{full: array<int>, readonly: array<int>}
     */
    public function getAccessibleFundIds(): array
    {
        if ($this->isSystemAdmin()) {
            $allFundIds = FundExt::pluck('id')->toArray();
            return ['full' => $allFundIds, 'readonly' => []];
        }

        $fullAccess = [];
        $readonlyAccess = [];

        // Get all roles with fund_id
        $roles = $this->roles()->whereNotNull('fund_id')->get();

        foreach ($roles as $role) {
            $fundId = $role->fund_id;
            if (in_array($role->name, ['fund-admin', 'financial-manager'])) {
                $fullAccess[] = $fundId;
            } elseif ($role->name === 'beneficiary') {
                // Beneficiary has readonly access to their own data within the fund
                if (!in_array($fundId, $fullAccess)) {
                    $readonlyAccess[] = $fundId;
                }
            }
        }

        return [
            'full' => array_unique($fullAccess),
            'readonly' => array_unique($readonlyAccess),
        ];
    }

    /**
     * Get account IDs the user can directly access (as a beneficiary).
     */
    public function getOwnAccountIds(): array
    {
        return $this->accounts()->pluck('id')->toArray();
    }
}
