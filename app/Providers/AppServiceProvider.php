<?php

namespace App\Providers;

use App\Models\TrainingSession;
use App\Policies\EvaluationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Register EvaluationPolicy for TrainingSession model
        Gate::policy(TrainingSession::class, EvaluationPolicy::class);
    }
}
