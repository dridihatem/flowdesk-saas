<?php

namespace App\Providers;

use App\AI\Nova\Providers\AIProvider;
use App\AI\Nova\Providers\PlatformAIProvider;
use App\AI\Nova\Tools\Assignment\RecommendAssignmentTool;
use App\AI\Nova\Tools\Client\CreateClientTool;
use App\AI\Nova\Tools\Client\GetClientTool;
use App\AI\Nova\Tools\Client\SearchClientsTool;
use App\AI\Nova\Tools\Client\UpdateClientTool;
use App\AI\Nova\Tools\Document\AnalyzeDocumentTool;
use App\AI\Nova\Tools\Document\GetDocumentTool;
use App\AI\Nova\Tools\Document\SearchDocumentsTool;
use App\AI\Nova\Tools\Invoice\CreateInvoiceTool;
use App\AI\Nova\Tools\Invoice\GetInvoiceTool;
use App\AI\Nova\Tools\Invoice\SearchInvoicesTool;
use App\AI\Nova\Tools\Invoice\SendInvoiceTool;
use App\AI\Nova\Tools\Invoice\UpdateInvoiceTool;
use App\AI\Nova\Tools\Meeting\CreateMeetingTool;
use App\AI\Nova\Tools\Meeting\GetMeetingTool;
use App\AI\Nova\Tools\Meeting\SearchMeetingsTool;
use App\AI\Nova\Tools\Meeting\UpdateMeetingTool;
use App\AI\Nova\Tools\Project\CreateProjectTool;
use App\AI\Nova\Tools\Project\DeleteProjectTool;
use App\AI\Nova\Tools\Project\GetProjectTool;
use App\AI\Nova\Tools\Project\SearchProjectsTool;
use App\AI\Nova\Tools\Project\UpdateProjectTool;
use App\AI\Nova\Tools\Quote\CreateQuoteTool;
use App\AI\Nova\Tools\Quote\GetQuoteTool;
use App\AI\Nova\Tools\Quote\SearchQuotesTool;
use App\AI\Nova\Tools\Quote\SendQuoteTool;
use App\AI\Nova\Tools\Quote\UpdateQuoteTool;
use App\AI\Nova\Tools\Report\ClientsReportTool;
use App\AI\Nova\Tools\Report\InvoicesReportTool;
use App\AI\Nova\Tools\Report\ProjectsReportTool;
use App\AI\Nova\Tools\Report\RevenueReportTool;
use App\AI\Nova\Tools\Report\WorkloadReportTool;
use App\AI\Nova\Tools\Task\AssignTaskTool;
use App\AI\Nova\Tools\Task\CreateTaskTool;
use App\AI\Nova\Tools\Task\DeleteTaskTool;
use App\AI\Nova\Tools\Task\GetTaskTool;
use App\AI\Nova\Tools\Task\SearchTasksTool;
use App\AI\Nova\Tools\Task\UpdateTaskTool;
use App\AI\Nova\Tools\ToolRegistry;
use Illuminate\Support\ServiceProvider;

class NovaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);
        $this->app->bind(AIProvider::class, PlatformAIProvider::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(ToolRegistry::class);

        $registry->register($this->app->make(SearchClientsTool::class), 'workspace.manage_clients');
        $registry->register($this->app->make(GetClientTool::class), 'workspace.manage_clients');
        $registry->register($this->app->make(CreateClientTool::class), 'workspace.manage_clients');
        $registry->register($this->app->make(UpdateClientTool::class), 'workspace.manage_clients');

        $registry->register($this->app->make(SearchProjectsTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(GetProjectTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(CreateProjectTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(UpdateProjectTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(DeleteProjectTool::class), 'workspace.manage_projects');

        $registry->register($this->app->make(SearchTasksTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(GetTaskTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(CreateTaskTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(UpdateTaskTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(AssignTaskTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(DeleteTaskTool::class), 'workspace.manage_projects');

        $registry->register($this->app->make(SearchInvoicesTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(GetInvoiceTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(CreateInvoiceTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(UpdateInvoiceTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(SendInvoiceTool::class), 'workspace.manage_invoices');

        $registry->register($this->app->make(SearchQuotesTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(GetQuoteTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(CreateQuoteTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(UpdateQuoteTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(SendQuoteTool::class), 'workspace.manage_invoices');

        $registry->register($this->app->make(SearchMeetingsTool::class));
        $registry->register($this->app->make(GetMeetingTool::class));
        $registry->register($this->app->make(CreateMeetingTool::class));
        $registry->register($this->app->make(UpdateMeetingTool::class));

        $registry->register($this->app->make(SearchDocumentsTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(GetDocumentTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(AnalyzeDocumentTool::class), 'workspace.manage_projects');

        $registry->register($this->app->make(RevenueReportTool::class), 'workspace.view_analytics');
        $registry->register($this->app->make(InvoicesReportTool::class), 'workspace.manage_invoices');
        $registry->register($this->app->make(ProjectsReportTool::class), 'workspace.manage_projects');
        $registry->register($this->app->make(WorkloadReportTool::class), 'workspace.manage_team');
        $registry->register($this->app->make(ClientsReportTool::class), 'workspace.manage_clients');

        $registry->register($this->app->make(RecommendAssignmentTool::class), 'workspace.manage_projects');
    }
}
