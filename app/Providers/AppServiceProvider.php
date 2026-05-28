<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // NIST SP 800-63B: prioritise length over complexity rules.
        // Complexity requirements (mixed case, symbols) are omitted — they
        // produce predictable patterns (Password1!) and reduce real entropy.
        // 12-char minimum applies everywhere so dev and prod behave identically.
        // Breach check (HIBP) is enabled only in production to avoid network
        // calls during local development and CI.
        Password::defaults(fn (): Password => Password::min(12)
            ->max(128)
            ->when(app()->isProduction(), fn ($rule) => $rule->uncompromised())
        );
    }
}
