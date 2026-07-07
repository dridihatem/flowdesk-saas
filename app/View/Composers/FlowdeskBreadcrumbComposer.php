<?php

namespace App\View\Composers;

use App\Services\BreadcrumbService;
use Illuminate\View\View;

class FlowdeskBreadcrumbComposer
{
    public function __construct(private BreadcrumbService $breadcrumbs) {}

    public function compose(View $view): void
    {
        $data = $this->breadcrumbs->forRequest(request());
        $view->with('flowdeskBreadcrumbs', $data['items'])
            ->with('flowdeskBreadcrumbBack', $data['back']);
    }
}
