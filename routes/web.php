<?php

use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\CompanyNoticeController as AdminCompanyNoticeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DeveloperDocumentationController;
use App\Http\Controllers\Admin\EmailMarketingTemplateModelController as AdminEmailMarketingTemplateModelController;
use App\Http\Controllers\Admin\InvoicePdfTemplateLibraryController as AdminInvoicePdfTemplateLibraryController;
use App\Http\Controllers\Admin\MarketplaceModuleController as AdminMarketplaceModuleController;
use App\Http\Controllers\Admin\MarketplaceOrderController as AdminMarketplaceOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentGatewaySettingsController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\PlatformAppearanceController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ThemeLibraryController as AdminThemeLibraryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientAccountRequestReviewController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientFollowUpController;
use App\Http\Controllers\ClientQuickStoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailMarketing\AudienceController as EmailMarketingAudienceController;
use App\Http\Controllers\EmailMarketing\CampaignController as EmailMarketingCampaignController;
use App\Http\Controllers\EmailMarketing\DashboardController as EmailMarketingDashboardController;
use App\Http\Controllers\EmailMarketing\EmailMarketingCampaignContentAiController;
use App\Http\Controllers\EmailMarketing\EmailMarketingPreviewEmailController;
use App\Http\Controllers\EmailMarketing\EmailMarketingTemplateAiController;
use App\Http\Controllers\EmailMarketing\EmailOpenTrackingController;
use App\Http\Controllers\EmailMarketing\SequenceController as EmailMarketingSequenceController;
use App\Http\Controllers\EmailMarketing\TemplateController as EmailMarketingTemplateController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormFieldController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\Hr\DashboardController as HrDashboardController;
use App\Http\Controllers\Hr\DepartmentController as HrDepartmentController;
use App\Http\Controllers\Hr\EmployeeController as HrEmployeeController;
use App\Http\Controllers\Hr\LeaveController as HrLeaveController;
use App\Http\Controllers\Hr\PayrollController as HrPayrollController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InvoiceAiController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceFlouciController;
use App\Http\Controllers\InvoicePaymentIntentController;
use App\Http\Controllers\InvoicePayPalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MarketingHubController;
use App\Http\Controllers\MarketplaceCartController;
use App\Http\Controllers\MarketplaceCheckoutController;
use App\Http\Controllers\ModuleActionController;
use App\Http\Controllers\ModulePageController;
use App\Http\Controllers\NegotiationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartnerRegistrationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\Portal\CalendarController as PortalCalendarController;
use App\Http\Controllers\Portal\ClientAccountRequestController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\InvoicePaymentController as PortalInvoicePaymentController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\ProposalController as PortalProposalController;
use App\Http\Controllers\Portal\QuoteRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectAiController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileVaultController;
use App\Http\Controllers\ProjectInstallmentController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProposalAiController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Provider\PartnershipController as ProviderPartnershipController;
use App\Http\Controllers\Provider\ProjectController as ProviderProjectController;
use App\Http\Controllers\Provider\ProposalController as ProviderProposalController;
use App\Http\Controllers\Provider\RemittanceRequestController as ProviderRemittanceRequestController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderPartnershipCompanyController;
use App\Http\Controllers\ProviderRemittanceInboxController;
use App\Http\Controllers\ProviderRemittanceReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\BillingTaxSettingsController;
use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\CalendarSchedulingController;
use App\Http\Controllers\Settings\DashboardLayoutController;
use App\Http\Controllers\Settings\GoogleCalendarController;
use App\Http\Controllers\Settings\InvoiceDocumentController;
use App\Http\Controllers\Settings\MarketingIntegrationsController;
use App\Http\Controllers\Settings\ModulesSettingsController;
use App\Http\Controllers\Settings\NavigationSettingsController;
use App\Http\Controllers\Settings\PaymentGatewaySettingsController as WorkspacePaymentGatewaySettingsController;
use App\Http\Controllers\Settings\ProviderCommissionSettingsController;
use App\Http\Controllers\Settings\ProviderRecruitmentSettingsController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SmtpController;
use App\Http\Controllers\Settings\TeamController;
use App\Http\Controllers\Settings\TwoFactorController;
use App\Http\Controllers\Settings\UiPresetController;
use App\Http\Controllers\Settings\WidgetEmbedController;
use App\Http\Controllers\Settings\WorkspaceAiAgentController;
use App\Http\Controllers\Settings\WorkspaceApiConnectController;
use App\Http\Controllers\Settings\WorkspaceContactController;
use App\Http\Controllers\Settings\WorkspaceCurrencyController;
use App\Http\Controllers\Settings\WorkspaceLocaleController;
use App\Http\Controllers\Settings\WorkspaceHubController;
use App\Http\Controllers\SharedFileController;
use App\Http\Controllers\StripeBillingPortalController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\Webhooks\FlouciWebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe/webhook', StripeWebhookController::class)->name('webhooks.stripe');
Route::post('/webhooks/flouci', FlouciWebhookController::class)->name('webhooks.flouci');

Route::post('/locale', [LocaleController::class, 'update'])
    ->name('locale.update');

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/features', [MarketingController::class, 'features'])->name('marketing.features');
Route::permanentRedirect('/framework', '/about');
Route::get('/about', [MarketingController::class, 'about'])->name('marketing.about');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/modules', [MarketingController::class, 'modules'])->name('marketing.modules');
Route::get('/modules/{slug}/{page?}', [ModulePageController::class, 'show'])
    ->where('page', '[a-zA-Z0-9_\-\/]+')
    ->name('modules.show');
Route::get('/cart', [MarketplaceCartController::class, 'index'])->name('marketing.cart');
Route::post('/cart/add/{marketplaceModule}', [MarketplaceCartController::class, 'add'])->name('marketing.cart.add');
Route::post('/cart/remove', [MarketplaceCartController::class, 'remove'])->name('marketing.cart.remove');
Route::post('/cart/currency', [MarketplaceCartController::class, 'updateCurrency'])->name('marketing.cart.currency');
Route::get('/checkout', [MarketplaceCheckoutController::class, 'show'])->name('marketing.checkout');
Route::post('/checkout', [MarketplaceCheckoutController::class, 'store'])->name('marketing.checkout.store');
Route::get('/checkout/success', [MarketplaceCheckoutController::class, 'success'])->name('marketing.checkout.success');
Route::get('/checkout/cancel/{order}', [MarketplaceCheckoutController::class, 'cancel'])->name('marketing.checkout.cancel');
Route::get('/checkout/pending/{order}', [MarketplaceCheckoutController::class, 'pending'])->name('marketing.checkout.pending');
Route::get('/orders/{order}/modules/{module}/download', [MarketplaceCheckoutController::class, 'download'])
    ->name('marketing.order.download')
    ->middleware('signed');
Route::get('/contact', [MarketingController::class, 'contact'])->name('marketing.contact');
Route::post('/contact', [MarketingController::class, 'contactStore'])->name('marketing.contact.store');
Route::permanentRedirect('/cgv', '/terms');
Route::permanentRedirect('/mentions-legales', '/legal');
Route::get('/terms', [MarketingController::class, 'terms'])->name('marketing.terms');
Route::get('/privacy', [MarketingController::class, 'privacy'])->name('marketing.privacy');
Route::get('/cookies', [MarketingController::class, 'cookies'])->name('marketing.cookies');
Route::get('/legal', [MarketingController::class, 'legal'])->name('marketing.legal');

Route::get('/e/o/{token}', [EmailOpenTrackingController::class, 'pixel'])
    ->name('email-marketing.tracking.open')
    ->middleware('throttle:120,1');

// Public vault file sharing (with or without password).
Route::get('/share/f/{token}', [SharedFileController::class, 'show'])
    ->name('share.file.show')
    ->middleware('throttle:60,1');
Route::post('/share/f/{token}', [SharedFileController::class, 'download'])
    ->name('share.file.download')
    ->middleware('throttle:20,1');

Route::middleware('guest')->group(function () {
    Route::get('/partner/{slug}', [PartnerRegistrationController::class, 'show'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->name('partner.register.show');
    Route::post('/partner/{slug}', [PartnerRegistrationController::class, 'store'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
        ->middleware('throttle:10,1')
        ->name('partner.register.store');
});

Route::middleware(['auth', 'verified', 'role:platform_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('themes', [AdminThemeLibraryController::class, 'index'])->name('themes.index');
    Route::post('themes', [AdminThemeLibraryController::class, 'store'])->name('themes.store');
    Route::delete('themes/{key}', [AdminThemeLibraryController::class, 'destroy'])->name('themes.destroy');
    Route::get('invoice-pdf-templates', [AdminInvoicePdfTemplateLibraryController::class, 'index'])->name('invoice-pdf-templates.index');
    Route::post('invoice-pdf-templates', [AdminInvoicePdfTemplateLibraryController::class, 'store'])->name('invoice-pdf-templates.store');
    Route::delete('invoice-pdf-templates/{key}', [AdminInvoicePdfTemplateLibraryController::class, 'destroy'])
        ->where('key', '[a-z0-9_]+')
        ->name('invoice-pdf-templates.destroy');
    Route::resource('email-template-models', AdminEmailMarketingTemplateModelController::class)
        ->parameters(['email-template-models' => 'emailMarketingTemplateModel'])
        ->except(['show']);
    Route::get('platform-appearance', [PlatformAppearanceController::class, 'edit'])->name('platform-appearance.edit');
    Route::put('platform-appearance', [PlatformAppearanceController::class, 'update'])->name('platform-appearance.update');
    Route::resource('companies', AdminCompanyController::class)->only(['index', 'show', 'create', 'store', 'destroy']);
    Route::put('companies/{company}/status', [AdminCompanyController::class, 'updateStatus'])->name('companies.status');
    Route::put('companies/{company}/plan', [AdminCompanyController::class, 'updatePlan'])->name('companies.plan');
    Route::post('companies/{company}/notice', [AdminCompanyNoticeController::class, 'store'])->name('companies.notice');
    Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update']);
    Route::resource('plans', AdminPlanController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::put('plans/{plan}/limits', [AdminPlanController::class, 'updateLimits'])->name('plans.limits.update');
    Route::resource('marketplace-modules', AdminMarketplaceModuleController::class)
        ->parameters(['marketplace-modules' => 'marketplaceModule'])
        ->except(['show']);
    Route::get('marketplace-orders', [AdminMarketplaceOrderController::class, 'index'])->name('marketplace-orders.index');
    Route::get('marketplace-orders/{marketplaceOrder}', [AdminMarketplaceOrderController::class, 'show'])->name('marketplace-orders.show');
    Route::put('marketplace-orders/{marketplaceOrder}/status', [AdminMarketplaceOrderController::class, 'updateStatus'])->name('marketplace-orders.status');
    Route::get('payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('payment-gateways', [PaymentGatewaySettingsController::class, 'edit'])->name('payment-gateways.edit');
    Route::put('payment-gateways', [PaymentGatewaySettingsController::class, 'update'])->name('payment-gateways.update');
    Route::get('settings', [PlatformSettingsController::class, 'edit'])->name('platform-settings.edit');
    Route::put('settings', [PlatformSettingsController::class, 'update'])->name('platform-settings.update');
    Route::get('settings/export-sql', [PlatformSettingsController::class, 'exportSql'])->name('platform-settings.export-sql');
    Route::get('developer-docs', [DeveloperDocumentationController::class, 'index'])->name('developer-docs.index');
    Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [AdminProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'tenant.match'])->group(function () {
    // Shared tenant features (safe for workspace staff, clients, and providers)
    Route::post('/clients/quick-store', ClientQuickStoreController::class)->name('clients.quick-store');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/widget/bootstrap', [ChatController::class, 'widgetBootstrap'])->name('chat.widget.bootstrap');
    Route::get('/chat/clients/{client}/open', [ChatController::class, 'openClient'])->name('chat.open.client');
    Route::get('/chat/providers/{provider}/open', [ChatController::class, 'openProvider'])->name('chat.open.provider');
    Route::get('/chat/{thread}/messages/full', [ChatController::class, 'messagesFull'])->name('chat.messages.full');
    Route::get('/chat/{thread}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{thread}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::get('/chat/{thread}/messages/poll', [ChatController::class, 'messagesJson'])->name('chat.messages.poll');

    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('tickets.status');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'tenant.match', 'workspace.staff'])->group(function () {
    // Company workspace root
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::middleware(['plan.feature:calendar'])->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/preview', [CalendarController::class, 'preview'])->name('calendar.preview');
        Route::post('/calendar/events', [CalendarEventController::class, 'store'])->name('calendar.events.store');
        Route::delete('/calendar/events/{event}', [CalendarEventController::class, 'destroy'])->name('calendar.events.destroy');
        Route::post('/calendar/sync/google', [CalendarEventController::class, 'syncGoogle'])->name('calendar.sync.google');
    });

    Route::post('/profile/embed-token', [ProfileController::class, 'regenerateEmbedToken'])
        ->middleware('plan.feature:widgets')
        ->name('profile.embed-token.regenerate');

    Route::middleware(['plan.feature:reports'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::middleware(['plan.feature:ai_credits'])->group(function () {
            Route::post('/reports/ai-counsel', [ReportController::class, 'aiCounsel'])->name('reports.ai-counsel');
            Route::post('/reports/ai-counsel/pdf', [ReportController::class, 'exportCounselPdf'])->name('reports.ai-counsel.pdf');
        });
    });

    Route::middleware(['plan.feature:analytics'])->group(function () {
        Route::get('/analytics', AnalyticsController::class)->name('analytics.index');
    });

    Route::middleware(['plan.feature:marketing_hub'])->group(function () {
        Route::get('/marketing', [MarketingHubController::class, 'index'])->name('marketing.hub');
        Route::patch('/marketing', [MarketingHubController::class, 'update'])->name('marketing.hub.update');
    });

    Route::middleware(['plan.feature:email_marketing'])->prefix('email-marketing')->name('email-marketing.')->group(function () {
        Route::get('/', EmailMarketingDashboardController::class)->name('index');
        Route::post('/campaigns/preview-email', EmailMarketingPreviewEmailController::class)->name('campaigns.preview-email');
        Route::post('/campaigns/{campaign}/send', [EmailMarketingCampaignController::class, 'send'])->name('campaigns.send');
        Route::post('/campaigns/{campaign}/sample', [EmailMarketingCampaignController::class, 'sendSample'])->name('campaigns.sample');
        Route::resource('campaigns', EmailMarketingCampaignController::class)->only([
            'index', 'create', 'store', 'show', 'edit', 'update', 'destroy',
        ]);
        Route::middleware(['plan.feature:ai_credits'])->group(function () {
            Route::post('/templates/ai', EmailMarketingTemplateAiController::class)->name('templates.ai');
            Route::post('/campaigns/content/ai', EmailMarketingCampaignContentAiController::class)->name('campaigns.content-ai');
        });
        Route::post('/templates/from-model/{slug}', [EmailMarketingTemplateController::class, 'storeFromModel'])
            ->where('slug', '[a-z0-9_]+')
            ->name('templates.from-model');
        Route::resource('templates', EmailMarketingTemplateController::class)->only([
            'index', 'create', 'store', 'edit', 'update', 'destroy',
        ]);
        Route::resource('audiences', EmailMarketingAudienceController::class)->only([
            'index', 'create', 'store', 'edit', 'update', 'destroy',
        ]);
        Route::get('/sequences', [EmailMarketingSequenceController::class, 'index'])->name('sequences.index');
    });

    Route::middleware(['plan.feature:hr'])->prefix('hr')->name('hr.')->group(function () {
        Route::get('/', HrDashboardController::class)->name('index');
        Route::get('/departments', [HrDepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [HrDepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [HrDepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [HrDepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::post('/employees/sync-team', [HrEmployeeController::class, 'syncTeam'])->name('employees.sync-team');
        Route::resource('employees', HrEmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::get('/leave', [HrLeaveController::class, 'index'])->name('leave.index');
        Route::post('/leave', [HrLeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/{leaveRequest}/approve', [HrLeaveController::class, 'approve'])->name('leave.approve');
        Route::post('/leave/{leaveRequest}/reject', [HrLeaveController::class, 'reject'])->name('leave.reject');
        Route::get('/payroll', [HrPayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll', [HrPayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payrollRun}', [HrPayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{payrollRun}/generate', [HrPayrollController::class, 'generate'])->name('payroll.generate');
        Route::post('/payroll/{payrollRun}/finalize', [HrPayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::post('/payroll/{payrollRun}/mark-paid', [HrPayrollController::class, 'markPaid'])->name('payroll.mark-paid');
    });

    Route::get('/billing', BillingController::class)->name('billing.index');
    Route::post('/billing/stripe-portal', StripeBillingPortalController::class)->name('billing.stripe-portal');

    Route::middleware(['plan.feature:ai_credits'])->group(function () {
        Route::get('/assistant', [AiAssistantController::class, 'index'])->name('assistant.index');
        Route::get('/assistant/summary', [AiAssistantController::class, 'summary'])->name('assistant.summary');
        Route::post('/assistant/chat', [AiAssistantController::class, 'chat'])->name('assistant.chat');
        Route::post('/assistant/speak', [AiAssistantController::class, 'speak'])->name('assistant.speak');
        Route::post('/assistant/briefing', [AiAssistantController::class, 'briefing'])->name('assistant.briefing');
        Route::post('/assistant/suggest', [AiAssistantController::class, 'suggest'])->name('assistant.suggest');
        Route::post('/assistant/voice-workflow', [AiAssistantController::class, 'voiceWorkflow'])->name('assistant.voice-workflow');
        Route::post('/assistant/proposal-prefill', [AiAssistantController::class, 'proposalPrefill'])->name('assistant.proposal-prefill');
        Route::post('/assistant/proposal-client-context', [AiAssistantController::class, 'proposalClientContext'])->name('assistant.proposal-client-context');
    });

    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/account-requests', [ClientAccountRequestReviewController::class, 'index'])->name('clients.account-requests.index');
    Route::post('/clients/account-requests/{clientAccountRequest}/approve', [ClientAccountRequestReviewController::class, 'approve'])->name('clients.account-requests.approve');
    Route::post('/clients/account-requests/{clientAccountRequest}/reject', [ClientAccountRequestReviewController::class, 'reject'])->name('clients.account-requests.reject');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::post('/clients/{client}/meetings', [ClientFollowUpController::class, 'storeMeeting'])->name('clients.meetings.store');
    Route::post('/clients/{client}/reminders', [ClientFollowUpController::class, 'storeReminder'])->name('clients.reminders.store');
    Route::post('/clients/{client}/notes', [ClientFollowUpController::class, 'storeNote'])->name('clients.notes.store');
    Route::post('/clients/{client}/tasks', [ClientFollowUpController::class, 'storeTask'])->name('clients.tasks.store');
    Route::post('/clients/{client}/feedbacks', [ClientFollowUpController::class, 'storeFeedback'])->name('clients.feedbacks.store');
    Route::post('/clients/{client}/meetings/{event}/summary/generate', [ClientFollowUpController::class, 'generateMeetingSummary'])->name('clients.meetings.summary.generate');
    Route::patch('/clients/{client}/meetings/{event}/summary', [ClientFollowUpController::class, 'updateMeetingSummary'])->name('clients.meetings.summary');
    Route::post('/clients/{client}/meetings/{event}/invite', [ClientFollowUpController::class, 'sendMeetingInvite'])->name('clients.meetings.invite');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    Route::resource('inquiries', InquiryController::class)->except(['edit']);
    Route::middleware(['plan.feature:projects'])->group(function () {
        Route::post('inquiries/{inquiry}/convert-project', [InquiryController::class, 'convertToProject'])->name('inquiries.convert-project');
    });

    Route::middleware(['plan.feature:projects'])->group(function () {
        Route::middleware(['plan.feature:ai_credits'])->group(function () {
            Route::post('projects/ai/example-workspace', [ProjectAiController::class, 'createExampleWorkspace'])->name('projects.ai.example-workspace');
        });
        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/files', [ProjectController::class, 'storeFile'])->name('projects.files.store');
        Route::delete('projects/{project}/files/{file}', [ProjectController::class, 'destroyFile'])->name('projects.files.destroy');
        Route::get('projects/{project}/vault/{file}/download', [ProjectFileVaultController::class, 'download'])->name('projects.vault.download');
        Route::post('projects/{project}/vault/{file}/shares', [ProjectFileVaultController::class, 'storeShare'])->name('projects.vault.shares.store');
        Route::delete('projects/{project}/vault/{file}/shares/{share}', [ProjectFileVaultController::class, 'destroyShare'])->name('projects.vault.shares.destroy');
        Route::middleware(['plan.feature:ai_credits'])->group(function () {
            Route::post('projects/{project}/ai/generate-workflow', [ProjectAiController::class, 'generateWorkflow'])->name('projects.ai.generate-workflow');
        });
        Route::patch('projects/{project}/team', [ProjectController::class, 'updateTeam'])->name('projects.team');
        Route::post('projects/{project}/installments', [ProjectInstallmentController::class, 'store'])->name('projects.installments.store');
        Route::patch('projects/{project}/installments/{installment}', [ProjectInstallmentController::class, 'update'])->name('projects.installments.update');
        Route::delete('projects/{project}/installments/{installment}', [ProjectInstallmentController::class, 'destroy'])->name('projects.installments.destroy');
        Route::get('projects/{project}/tasks/kanban', [ProjectTaskController::class, 'kanban'])->name('projects.tasks.kanban');
        Route::get('projects/{project}/tasks/gantt', [ProjectTaskController::class, 'gantt'])->name('projects.tasks.gantt');
        Route::post('projects/{project}/tasks/reorder', [ProjectTaskController::class, 'reorder'])->name('projects.tasks.reorder');
        Route::post('projects/{project}/tasks', [ProjectTaskController::class, 'store'])->name('projects.tasks.store');
        Route::patch('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'update'])->name('projects.tasks.update');
        Route::post('projects/{project}/tasks/{task}/tracking/start', [ProjectTaskController::class, 'trackingStart'])->name('projects.tasks.tracking.start');
        Route::post('projects/{project}/tasks/{task}/tracking/pause', [ProjectTaskController::class, 'trackingPause'])->name('projects.tasks.tracking.pause');
        Route::delete('projects/{project}/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('projects.tasks.destroy');
        Route::post('projects/{project}/tasks/{task}/files', [ProjectTaskController::class, 'storeFile'])->name('projects.tasks.files.store');
        Route::post('projects/{project}/tasks/{task}/comments', [ProjectTaskController::class, 'storeComment'])->name('projects.tasks.comments.store');
        Route::delete('projects/{project}/tasks/{task}/files/{file}', [ProjectTaskController::class, 'destroyFile'])->name('projects.tasks.files.destroy');
    });

    Route::post('invoices/ai-line-items', [InvoiceAiController::class, 'suggestDraft'])->name('invoices.ai-line-items.draft');
    Route::post('invoices/ai-line-items/scan', [InvoiceAiController::class, 'scanDraft'])->name('invoices.ai-line-items.scan.draft');
    Route::post('invoices/{invoice}/ai-line-items', [InvoiceAiController::class, 'suggestLineItems'])->name('invoices.ai-line-items');
    Route::post('invoices/{invoice}/ai-line-items/scan', [InvoiceAiController::class, 'scanLineItems'])->name('invoices.ai-line-items.scan');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/preview-panel', [InvoiceController::class, 'previewPanel'])->name('invoices.preview-panel');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
    Route::patch('invoices/{invoice}/payments/{payment}', [PaymentController::class, 'update'])->name('invoices.payments.update');
    Route::post('invoices/{invoice}/payment-intent', InvoicePaymentIntentController::class)->name('invoices.payment-intent');
    Route::post('invoices/{invoice}/paypal-order', InvoicePayPalController::class)->name('invoices.paypal-order');
    Route::post('invoices/{invoice}/flouci-payment', InvoiceFlouciController::class)->name('invoices.flouci-payment');
    Route::resource('invoices', InvoiceController::class);

    Route::post('/proposals/ai-line-items', [ProposalAiController::class, 'suggestDraft'])->name('proposals.ai-line-items.draft');
    Route::post('/proposals/ai-line-items/scan', [ProposalAiController::class, 'scanDraft'])->name('proposals.ai-line-items.scan.draft');
    Route::post('/proposals/{proposal}/ai-line-items', [ProposalAiController::class, 'suggestLineItems'])->name('proposals.ai-line-items');
    Route::post('/proposals/{proposal}/ai-line-items/scan', [ProposalAiController::class, 'scanLineItems'])->name('proposals.ai-line-items.scan');
    Route::get('/proposals/{proposal}/pdf', [ProposalController::class, 'pdf'])->name('proposals.pdf');
    Route::post('/proposals/{proposal}/send', [ProposalController::class, 'send'])->name('proposals.send');
    Route::post('/proposals/{proposal}/accept', [ProposalController::class, 'accept'])->name('proposals.accept');
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
    Route::put('/proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
    Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
    Route::post('/proposals/{proposal}/invoice', [InvoiceController::class, 'fromProposal'])->name('proposals.invoice');
    Route::post('/proposals/{proposal}/negotiations', [NegotiationController::class, 'store'])->name('proposals.negotiations.store');
    Route::post('/negotiations/{negotiation}/accept', [NegotiationController::class, 'accept'])->name('negotiations.accept');
    Route::post('/negotiations/{negotiation}/reject', [NegotiationController::class, 'reject'])->name('negotiations.reject');

    Route::middleware(['plan.feature:providers'])->group(function () {
        Route::get('providers/remittance-requests', [ProviderRemittanceInboxController::class, 'index'])->name('providers.remittance-requests.index');
        Route::resource('providers', ProviderController::class)->except(['show']);
        Route::post('providers/{provider}/remittance-requests/{remittanceRequest}/approve', [ProviderRemittanceReviewController::class, 'approve'])->name('providers.remittance-requests.approve');
        Route::post('providers/{provider}/remittance-requests/{remittanceRequest}/reject', [ProviderRemittanceReviewController::class, 'reject'])->name('providers.remittance-requests.reject');
        Route::middleware(['role:company_admin'])->group(function () {
            Route::get('providers/{provider}/partnership', [ProviderPartnershipCompanyController::class, 'show'])->name('providers.partnership.show');
            Route::get('providers/{provider}/partnership/contract', [ProviderPartnershipCompanyController::class, 'contract'])->name('providers.partnership.contract');
            Route::get('providers/{provider}/partnership/signature', [ProviderPartnershipCompanyController::class, 'signature'])->name('providers.partnership.signature');
            Route::post('providers/{provider}/partnership/sign', [ProviderPartnershipCompanyController::class, 'sign'])->name('providers.partnership.sign');
        });
    });

    Route::middleware(['plan.feature:forms'])->group(function () {
        Route::post('forms/{form}/fields/reorder', [FormFieldController::class, 'reorder'])->name('forms.fields.reorder');
        Route::patch('forms/{form}/fields/{field}', [FormFieldController::class, 'update'])->name('forms.fields.update');
        Route::post('forms/{form}/fields', [FormFieldController::class, 'store'])->name('forms.fields.store');
        Route::delete('forms/{form}/fields/{field}', [FormFieldController::class, 'destroy'])->name('forms.fields.destroy');
        Route::post('forms/{form}/bump-version', [FormController::class, 'bumpVersion'])->name('forms.bump-version');
        Route::get('forms/{form}/submissions', [FormSubmissionController::class, 'index'])->name('forms.submissions.index');
        Route::resource('forms', FormController::class)->except(['show']);
    });
    Route::middleware(['plan.feature:projects'])->group(function () {
        Route::post('form-submissions/{submission}/convert-project', [FormSubmissionController::class, 'convertToProject'])->name('form-submissions.convert-project');
    });

    Route::middleware(['plan.feature:modules'])->group(function () {
        Route::post('/modules/{slug}/actions', [ModuleActionController::class, 'handle'])->name('modules.actions');
        Route::get('/settings/modules', [ModulesSettingsController::class, 'index'])->name('settings.modules');
        Route::post('/settings/modules/install', [ModulesSettingsController::class, 'install'])->name('settings.modules.install');
        Route::post('/settings/modules/purchased/{marketplaceModule}/install', [ModulesSettingsController::class, 'installPurchased'])->name('settings.modules.purchased.install');
        Route::get('/settings/modules/purchased/{marketplaceModule}/download', [ModulesSettingsController::class, 'downloadPurchased'])->name('settings.modules.purchased.download');
        Route::delete('/settings/modules/purchased/{marketplaceOrderItem}', [ModulesSettingsController::class, 'destroyPurchased'])->name('settings.modules.purchased.destroy');
        Route::patch('/settings/modules/{module}/toggle', [ModulesSettingsController::class, 'toggle'])->name('settings.modules.toggle');
        Route::delete('/settings/modules/{module}', [ModulesSettingsController::class, 'destroy'])->name('settings.modules.destroy');

    });

    Route::get('/settings', WorkspaceHubController::class)->name('settings.workspace');

    Route::get('/settings/appearance', [AppearanceController::class, 'edit'])->name('settings.appearance');
    Route::put('/settings/appearance', [AppearanceController::class, 'update'])->name('settings.appearance.update');

    Route::get('/settings/branding', [BrandingController::class, 'edit'])->name('settings.branding');
    Route::put('/settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');

    Route::get('/settings/workspace-currency', [WorkspaceCurrencyController::class, 'edit'])->name('settings.workspace-currency');
    Route::put('/settings/workspace-currency', [WorkspaceCurrencyController::class, 'update'])->name('settings.workspace-currency.update');

    Route::get('/settings/workspace-locale', [WorkspaceLocaleController::class, 'edit'])->name('settings.workspace-locale');
    Route::put('/settings/workspace-locale', [WorkspaceLocaleController::class, 'update'])->name('settings.workspace-locale.update');

    Route::get('/settings/workspace-contact', [WorkspaceContactController::class, 'edit'])->name('settings.workspace-contact');
    Route::put('/settings/workspace-contact', [WorkspaceContactController::class, 'update'])->name('settings.workspace-contact.update');

    Route::middleware(['plan.feature:providers'])->group(function () {
        Route::get('/settings/provider-commissions', [ProviderCommissionSettingsController::class, 'edit'])->name('settings.provider-commissions');
        Route::put('/settings/provider-commissions', [ProviderCommissionSettingsController::class, 'update'])->name('settings.provider-commissions.update');
        Route::middleware(['role:company_admin'])->group(function () {
            Route::get('/settings/provider-recruitment', [ProviderRecruitmentSettingsController::class, 'edit'])->name('settings.provider-recruitment');
            Route::get('/settings/provider-recruitment/sample-terms/{locale}', [ProviderRecruitmentSettingsController::class, 'sampleTerms'])->name('settings.provider-recruitment.sample-terms');
            Route::put('/settings/provider-recruitment', [ProviderRecruitmentSettingsController::class, 'update'])->name('settings.provider-recruitment.update');
        });
    });

    Route::middleware(['plan.feature:widgets'])->group(function () {
        Route::get('/settings/widget-embed', [WidgetEmbedController::class, 'show'])->name('settings.widget-embed');
        Route::post('/settings/widget-embed/regenerate-token', [WidgetEmbedController::class, 'regenerateToken'])->name('settings.widget-embed.regenerate-token');
    });

    Route::get('/settings/api-connect', [WorkspaceApiConnectController::class, 'show'])->name('settings.api-connect');
    Route::post('/settings/api-connect/regenerate-token', [WorkspaceApiConnectController::class, 'regenerateToken'])->name('settings.api-connect.regenerate-token');

    Route::middleware(['plan.feature:workspace_ai_agent'])->group(function () {
        Route::get('/settings/ai-agent', [WorkspaceAiAgentController::class, 'edit'])->name('settings.ai-agent');
        Route::put('/settings/ai-agent', [WorkspaceAiAgentController::class, 'update'])->name('settings.ai-agent.update');
    });

    Route::get('/settings/smtp', [SmtpController::class, 'edit'])->name('settings.smtp');
    Route::put('/settings/smtp', [SmtpController::class, 'update'])->name('settings.smtp.update');

    Route::middleware(['plan.feature:email_marketing'])->group(function () {
        Route::get('/settings/marketing-integrations', [MarketingIntegrationsController::class, 'edit'])->name('settings.marketing-integrations');
        Route::put('/settings/marketing-integrations', [MarketingIntegrationsController::class, 'update'])->name('settings.marketing-integrations.update');
        Route::post('/settings/marketing-integrations/mailchimp-test', [MarketingIntegrationsController::class, 'testMailchimp'])
            ->name('settings.marketing-integrations.mailchimp-test');
        Route::post('/settings/marketing-integrations/sms-test', [MarketingIntegrationsController::class, 'testTwilio'])
            ->middleware('throttle:5,1')
            ->name('settings.marketing-integrations.sms-test');
    });

    Route::middleware(['plan.feature:calendar'])->group(function () {
        Route::get('/settings/calendar-scheduling', [CalendarSchedulingController::class, 'edit'])->name('settings.calendar-scheduling');
        Route::put('/settings/calendar-scheduling', [CalendarSchedulingController::class, 'update'])->name('settings.calendar-scheduling.update');
    });

    Route::middleware(['plan.feature:projects'])->group(function () {
        Route::get('/settings/google-calendar', [GoogleCalendarController::class, 'edit'])->name('settings.google-calendar');
        Route::get('/settings/google-calendar/redirect', [GoogleCalendarController::class, 'redirect'])->name('settings.google-calendar.redirect');
        Route::get('/settings/google-calendar/callback', [GoogleCalendarController::class, 'callback'])->name('settings.google-calendar.callback');
        Route::post('/settings/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect'])->name('settings.google-calendar.disconnect');
        Route::post('/settings/google-calendar/zoom', [GoogleCalendarController::class, 'updateZoom'])->name('settings.google-calendar.zoom');
    });

    Route::get('/settings/invoice-documents', [InvoiceDocumentController::class, 'edit'])->name('settings.invoice-documents');
    Route::put('/settings/invoice-documents', [InvoiceDocumentController::class, 'update'])->name('settings.invoice-documents.update');

    Route::get('/settings/billing-tax', [BillingTaxSettingsController::class, 'edit'])->name('settings.billing-tax');
    Route::put('/settings/billing-tax', [BillingTaxSettingsController::class, 'update'])->name('settings.billing-tax.update');

    Route::get('/settings/payment-gateways', [WorkspacePaymentGatewaySettingsController::class, 'edit'])->name('settings.payment-gateways');
    Route::put('/settings/payment-gateways', [WorkspacePaymentGatewaySettingsController::class, 'update'])->name('settings.payment-gateways.update');

    Route::get('/payments/{payment}/receipt', PaymentReceiptController::class)->name('payments.receipt');

    Route::get('/settings/security', [SecurityController::class, 'edit'])->name('settings.security');
    Route::put('/settings/security', [SecurityController::class, 'update'])->name('settings.security.update');

    Route::get('/settings/team', [TeamController::class, 'index'])->name('settings.team');
    Route::post('/settings/team', [TeamController::class, 'store'])->name('settings.team.store');
    Route::put('/settings/team/{user}', [TeamController::class, 'update'])->name('settings.team.update');
    Route::delete('/settings/team/{user}', [TeamController::class, 'destroy'])->name('settings.team.destroy');

    Route::get('/settings/two-factor', [TwoFactorController::class, 'show'])->name('settings.two-factor');
    Route::post('/settings/two-factor/prepare', [TwoFactorController::class, 'prepare'])->name('settings.two-factor.prepare');
    Route::post('/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('settings.two-factor.confirm');
    Route::delete('/settings/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.two-factor.destroy');

    Route::get('/settings/dashboard', [DashboardLayoutController::class, 'edit'])->name('settings.dashboard');
    Route::put('/settings/dashboard', [DashboardLayoutController::class, 'update'])->name('settings.dashboard.update');
    Route::get('/settings/navigation', [NavigationSettingsController::class, 'edit'])->name('settings.navigation');
    Route::put('/settings/navigation', [NavigationSettingsController::class, 'update'])->name('settings.navigation.update');
    Route::post('/settings/ui-presets', [UiPresetController::class, 'store'])->name('settings.ui-presets.store');
    Route::put('/settings/ui-presets/{preset}/activate', [UiPresetController::class, 'activate'])->name('settings.ui-presets.activate');
    Route::delete('/settings/ui-presets/{preset}', [UiPresetController::class, 'destroy'])->name('settings.ui-presets.destroy');
});

Route::middleware(['auth', 'verified', 'tenant.match', 'role:client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('calendar', [PortalCalendarController::class, 'index'])->middleware('plan.feature:calendar')->name('calendar');
        Route::get('calendar/preview', [PortalCalendarController::class, 'preview'])->middleware('plan.feature:calendar')->name('calendar.preview');
        Route::get('projects', [PortalProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/kanban', [PortalProjectController::class, 'kanban'])->name('projects.kanban');
        Route::get('projects/{project}/gantt', [PortalProjectController::class, 'gantt'])->name('projects.gantt');
        Route::post('projects/{project}/tasks/{task}/comments', [PortalProjectController::class, 'storeTaskComment'])->name('projects.tasks.comments.store');
        Route::post('projects/{project}/confirm-price', [PortalProjectController::class, 'confirmPrice'])->name('projects.confirm-price');
        Route::get('proposals', [PortalProposalController::class, 'index'])->name('proposals.index');
        Route::get('proposals/{proposal}', [PortalProposalController::class, 'show'])->name('proposals.show');
        Route::get('proposals/{proposal}/pdf', [PortalProposalController::class, 'pdf'])->name('proposals.pdf');
        Route::post('proposals/{proposal}/accept', [PortalProposalController::class, 'accept'])->name('proposals.accept');
        Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [PortalInvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/payment-intent', [PortalInvoicePaymentController::class, 'stripeIntent'])->name('invoices.payment-intent');
        Route::post('invoices/{invoice}/paypal-order', [PortalInvoicePaymentController::class, 'paypalOrder'])->name('invoices.paypal-order');
        Route::post('invoices/{invoice}/flouci-payment', [PortalInvoicePaymentController::class, 'flouciPayment'])->name('invoices.flouci-payment');
        Route::get('invoices/{invoice}/paypal/return', [PortalInvoicePaymentController::class, 'paypalReturn'])->name('invoices.paypal.return');
        Route::post('invoices/{invoice}/bank-transfer', [PortalInvoicePaymentController::class, 'bankTransfer'])->name('invoices.bank-transfer');
        Route::get('payments/{payment}/receipt', PaymentReceiptController::class)->name('payments.receipt');
        Route::get('payments', [PortalPaymentController::class, 'index'])->name('payments.index');
        Route::get('quote-requests', [QuoteRequestController::class, 'index'])->name('quote-requests.index');
        Route::get('quote-requests/create', [QuoteRequestController::class, 'create'])->name('quote-requests.create');
        Route::post('quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');
        Route::get('quote-requests/{inquiry}', [QuoteRequestController::class, 'show'])->name('quote-requests.show');
        Route::get('invite-colleague', [ClientAccountRequestController::class, 'create'])->name('client-account-requests.create');
        Route::post('invite-colleague', [ClientAccountRequestController::class, 'store'])->name('client-account-requests.store');
        Route::post('invite-colleague/{clientAccountRequest}/add-to-chat', [ClientAccountRequestController::class, 'addToChat'])->name('client-account-requests.add-to-chat');
    });

Route::middleware(['auth', 'verified', 'tenant.match', 'role:business_provider'])
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {
        Route::get('partnership', [ProviderPartnershipController::class, 'show'])->name('partnership.show');
        Route::get('partnership/contract', [ProviderPartnershipController::class, 'contract'])->name('partnership.contract');
        Route::post('partnership/sign', [ProviderPartnershipController::class, 'sign'])->name('partnership.sign');

        Route::get('/', ProviderDashboardController::class)->name('dashboard');
        Route::get('remittance-requests', [ProviderRemittanceRequestController::class, 'index'])->name('remittance-requests.index');
        Route::post('remittance-requests', [ProviderRemittanceRequestController::class, 'store'])->name('remittance-requests.store');

        Route::middleware('provider.partnership.active')->group(function () {
            Route::resource('projects', ProviderProjectController::class);
            Route::get('projects/{project}/proposals/create', [ProviderProposalController::class, 'create'])->name('projects.proposals.create');
            Route::post('projects/{project}/proposals', [ProviderProposalController::class, 'store'])->name('projects.proposals.store');
        });
    });

require __DIR__.'/auth.php';
