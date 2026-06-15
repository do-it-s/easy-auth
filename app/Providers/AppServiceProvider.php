<?php

namespace App\Providers;

use App\Models\Invitation;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\InvitationPolicy;
use App\Policies\TenantPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passkeys::authorizeLoginUsing(function (Request $request, User $user): bool {
            return $user->device?->uuid === $request->header('X-Device-Uuid');
        });

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
