<?php

namespace App\Providers;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\TransactionExt;
use App\Policies\AccountPolicy;
use App\Policies\FundPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        AccountExt::class => AccountPolicy::class,
        TransactionExt::class => TransactionPolicy::class,
        FundExt::class => FundPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define a super-admin gate that grants all permissions
        Gate::before(function ($user, $ability) {
            if ($user->isSystemAdmin()) {
                return true;
            }
        });
    }
}
