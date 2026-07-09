<?php

namespace DoITs\EasyAuth;

use DoITs\EasyAuth\Actions\GenerateRegistrationOptions;
use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Http\Middleware\EnsureProfileIsComplete;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Policies\InvitationPolicy;
use DoITs\EasyAuth\Policies\TenantPolicy;
use DoITs\EasyAuth\Policies\UserPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions as BaseGenerateRegistrationOptions;
use Laravel\Passkeys\Passkeys;

class EasyAuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/easy-auth.php', 'easy-auth');

        // Overrides the vendor action's authenticatorSelection() to honor
        // config('easy-auth.force_platform_authenticator'). See that class's
        // docblock for why this is opt-in.
        $this->app->bind(BaseGenerateRegistrationOptions::class, GenerateRegistrationOptions::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/easy-auth.php' => config_path('easy-auth.php'),
        ], 'easy-auth-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/easy-auth'),
        ], 'easy-auth-views');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'easy-auth');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'easy-auth');

        // loadRoutesFrom() alone does not inherit the host application's
        // "web" middleware group (session, CSRF, cookies, shared errors),
        // so it must be applied explicitly here.
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->app['router']->aliasMiddleware('profile.complete', EnsureProfileIsComplete::class);

        // The host application owns its own User model (see Contracts\EasyAuthUser
        // and Concerns\IsEasyAuthUser), so {user} route parameters must be resolved
        // against the configured auth model explicitly rather than relying on
        // implicit, type-hint-based route model binding.
        Route::bind('user', function (string $value): EasyAuthUser {
            $userModel = config('auth.providers.users.model');

            return $userModel::findOrFail($value);
        });

        Passkeys::authorizeLoginUsing(function (Request $request, EasyAuthUser $user): bool {
            // laravel/passkeys' own PasskeyLoginController reads "remember"
            // straight off the request after this hook runs, but this
            // package's JS client never sends that field. This hook is the
            // only point the package can reach before that read happens, so
            // it doubles as the place to apply config('easy-auth.remember_me')
            // as a default — only when the field is absent, so a caller that
            // explicitly sent remember=false is still respected.
            if (! $request->has('remember') && config('easy-auth.remember_me')) {
                $request->merge(['remember' => true]);
            }

            return $user->device?->uuid === $request->header('X-Device-Uuid');
        });

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(config('auth.providers.users.model'), UserPolicy::class);
    }
}
