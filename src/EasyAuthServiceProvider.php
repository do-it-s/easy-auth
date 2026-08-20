<?php

namespace DoITs\EasyAuth;

use DoITs\EasyAuth\Actions\GenerateRegistrationOptions;
use DoITs\EasyAuth\Actions\VerifyPasskey;
use DoITs\EasyAuth\Contracts\EasyAuthUser;
use DoITs\EasyAuth\Http\Middleware\EnsureProfileIsComplete;
use DoITs\EasyAuth\Http\Responses\PasskeyLoginResponse as EasyAuthPasskeyLoginResponse;
use DoITs\EasyAuth\Listeners\AuditLogSubscriber;
use DoITs\EasyAuth\Models\Invitation;
use DoITs\EasyAuth\Models\Tenant;
use DoITs\EasyAuth\Policies\InvitationPolicy;
use DoITs\EasyAuth\Policies\TenantPolicy;
use DoITs\EasyAuth\Policies\UserPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions as BaseGenerateRegistrationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey as BaseVerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
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

        // Wraps the vendor action to write a passkey.verification_failed
        // audit log entry before rethrowing. See VerifyPasskey's docblock
        // for why a reportable() exception hook can't reach this instead.
        $this->app->bind(BaseVerifyPasskey::class, VerifyPasskey::class);

        // Decorates the vendor login response so a PWA re-sync (see
        // config('easy-auth.pwa_resync_path')) can hand its restored
        // device_uuid back to the client. extend() rather than a direct
        // bind() override of the contract, since PasskeysServiceProvider
        // binds it as a singleton too and extend() decorates whatever's
        // there regardless of provider registration order.
        $this->app->extend(
            PasskeyLoginResponseContract::class,
            fn (PasskeyLoginResponseContract $response) => new EasyAuthPasskeyLoginResponse($response)
        );

        // Registers the audit log's channel unless the host app already
        // defined one of this name itself, e.g. to point it somewhere other
        // than local disk. Config, not a published stub, since this way a
        // host app that never touches logging.php still gets a working
        // channel out of the box.
        $channel = config('easy-auth.audit_log_channel');

        if (! config()->has("logging.channels.{$channel}")) {
            config(["logging.channels.{$channel}" => [
                'driver' => 'daily',
                'path' => storage_path("logs/{$channel}.log"),
                'level' => 'info',
                'days' => config('easy-auth.audit_log_retention_days'),
            ]]);
        }
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

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/easy-auth'),
        ], 'easy-auth-lang');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'easy-auth');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'easy-auth');

        // loadRoutesFrom() alone does not inherit the host application's
        // "web" middleware group (session, CSRF, cookies, shared errors),
        // so it must be applied explicitly here. Skipped entirely when a
        // host app calls EasyAuth::ignoreRoutes(), since it means the app
        // is declaring its own routes against this package's controllers.
        if (EasyAuth::$registersRoutes) {
            Route::middleware('web')->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

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

            $deviceUuid = $user->device?->uuid;

            if ($deviceUuid === $request->header('X-Device-Uuid')) {
                return true;
            }

            // A home-screen PWA's localStorage is isolated from Safari's,
            // so a freshly-opened PWA sends no X-Device-Uuid header at all
            // rather than a mismatched one — this branch is for that
            // specific case, not a general relaxation of the check above.
            // Trusted only once per session (pull() consumes the flag),
            // and only because the session already proved, by reaching
            // config('easy-auth.pwa_resync_path')'s controller, that this
            // browsing context knows that unguessable URL. Leaves the
            // mismatch branch (SignInController::store()'s forced logout +
            // account-deletion email) and normal /login attempts
            // completely untouched: neither of those ever sets this flag.
            if ($deviceUuid && ! $request->header('X-Device-Uuid') && $request->session()->pull('pwa_resync_allowed')) {
                $request->session()->put('pwa_resync_device_uuid', $deviceUuid);

                return true;
            }

            return false;
        });

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
        Gate::policy(config('auth.providers.users.model'), UserPolicy::class);

        Event::subscribe(AuditLogSubscriber::class);
    }
}
