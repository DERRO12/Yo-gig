<?php

namespace App\Providers;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('site', $this->app->make(SiteSettings::class));
        });

        Auth::viaRequest('api-token', fn (Request $request): ?User => $this->userFromApiToken($request));
    }

    /**
     * Resolve the mobile app's bearer token to its user and remember the token for logout.
     */
    protected function userFromApiToken(Request $request): ?User
    {
        $token = ApiToken::findByPlainText($request->bearerToken());

        if (! $token) {
            return null;
        }

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        $request->attributes->set('api_token', $token);

        return $token->user;
    }
}
