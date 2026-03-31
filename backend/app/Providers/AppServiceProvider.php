<?php

namespace App\Providers;

use App\Repositories\Interfaces\MatchRepositoryInterface;
use App\Repositories\Interfaces\TeamRepositoryInterface;
use App\Repositories\MatchRepository;
use App\Repositories\TeamRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TeamRepositoryInterface::class, TeamRepository::class);
        $this->app->singleton(MatchRepositoryInterface::class, MatchRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
