<?php

namespace App\Providers;

use App\Models\Project;
use App\Observers\ProjectObserver;
use App\View\Composers\FlowdeskBreadcrumbComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('currentCompany', fn (): ?\App\Models\Company => null);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Project::observe(ProjectObserver::class);

        View::composer(
            [
                'themes.default.layouts.sidebar',
                'themes.default.layouts.topbar',
                'themes.default.layouts.minimal',
                'components.admin-layout',
            ],
            FlowdeskBreadcrumbComposer::class
        );

    }
}
