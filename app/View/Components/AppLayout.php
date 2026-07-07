<?php

namespace App\View\Components;

use App\Services\CompanyThemeService;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @var array<string, mixed>
     */
    protected array $flowdeskTheme;

    protected string $flowdeskLayoutView;

    public function __construct()
    {
        $company = app()->bound('currentCompany') && app('currentCompany')
            ? app('currentCompany')
            : auth()->user()?->company;

        $service = app(CompanyThemeService::class);
        $user = auth()->user();
        $this->flowdeskTheme = $service->themeFor($company, $user);
        $this->flowdeskLayoutView = $service->layoutView($company, $user);
    }

    public function render(): View
    {
        return $this->view('layouts.app', [
            'flowdeskTheme' => $this->flowdeskTheme,
            'flowdeskLayoutView' => $this->flowdeskLayoutView,
        ]);
    }
}
