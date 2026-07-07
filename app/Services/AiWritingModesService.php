<?php

namespace App\Services;

use App\Models\Company;

class AiWritingModesService
{
    public function __construct(
        private AiCreditUsageService $credits,
        private CompanyGrowthAiService $growth,
    ) {}

    /**
     * @return list<array{key: string, label: string, modes: list<array{mode: string, title: string, description: string, icon: string, credit_cost: int, placeholder: string, default_context: string, uses_workspace_data: bool}>}>
     */
    public function groupsFor(Company $company): array
    {
        $task = AiCreditUsageService::TASK_ASSISTANT;

        $mode = fn (string $key, string $titleKey, string $descKey, string $placeholderKey, string $icon, bool $usesWorkspace = false, array $capabilities = []) => [
            'mode' => $key,
            'title' => __($titleKey),
            'description' => __($descKey),
            'icon' => $icon,
            'credit_cost' => $this->credits->creditsForTask($task, $key),
            'placeholder' => __($placeholderKey),
            'default_context' => $usesWorkspace ? $this->growth->buildContext($company, $key) : '',
            'uses_workspace_data' => $usesWorkspace,
            'capabilities' => $capabilities,
        ];

        return [
            [
                'key' => 'sales',
                'label' => __('ai_writing_group_sales'),
                'modes' => [
                    $mode('proposal', 'ai_writing_mode_proposal_title', 'ai_writing_mode_proposal_desc', 'ai_writing_mode_proposal_placeholder', 'fa-file-lines', false, ['client_picker', 'create_quote', 'quote_lines', 'speak']),
                    $mode('pricing', 'ai_writing_mode_pricing_title', 'ai_writing_mode_pricing_desc', 'ai_writing_mode_pricing_placeholder', 'fa-tags'),
                    $mode('form', 'ai_writing_mode_form_title', 'ai_writing_mode_form_desc', 'ai_writing_mode_form_placeholder', 'fa-clipboard-list'),
                ],
            ],
            [
                'key' => 'communication',
                'label' => __('ai_writing_group_communication'),
                'modes' => [
                    $mode('client_email', 'ai_writing_mode_client_email_title', 'ai_writing_mode_client_email_desc', 'ai_writing_mode_client_email_placeholder', 'fa-envelope'),
                    $mode('task_followup', 'ai_writing_mode_task_followup_title', 'ai_writing_mode_task_followup_desc', 'ai_writing_mode_task_followup_placeholder', 'fa-list-check'),
                    $mode('ticket', 'ai_writing_mode_ticket_title', 'ai_writing_mode_ticket_desc', 'ai_writing_mode_ticket_placeholder', 'fa-life-ring'),
                ],
            ],
            [
                'key' => 'content',
                'label' => __('ai_writing_group_content'),
                'modes' => array_values(array_filter([
                    $mode('summary', 'ai_writing_mode_summary_title', 'ai_writing_mode_summary_desc', 'ai_writing_mode_summary_placeholder', 'fa-align-left'),
                    $mode('project_description', 'ai_writing_mode_project_description_title', 'ai_writing_mode_project_description_desc', 'ai_writing_mode_project_description_placeholder', 'fa-diagram-project'),
                    $mode('seo', 'ai_writing_mode_seo_title', 'ai_writing_mode_seo_desc', 'ai_writing_mode_seo_placeholder', 'fa-magnifying-glass-chart'),
                    config('flowdesk.landing_page_writing_mode_enabled')
                        ? $mode('landing_page', 'ai_writing_mode_landing_page_title', 'ai_writing_mode_landing_page_desc', 'ai_writing_mode_landing_page_placeholder', 'fa-browser')
                        : null,
                ])),
            ],
            [
                'key' => 'growth',
                'label' => __('ai_writing_group_growth'),
                'modes' => [
                    $mode('growth_projects', 'Projects growth advisor', 'AI decisions on pipeline health, stalled projects, and delivery priorities.', 'ai_writing_mode_growth_extra_placeholder', 'fa-diagram-project', true),
                    $mode('growth_invoices', 'Invoices growth advisor', 'AI suggestions for cash collection, reminders, and revenue recovery.', 'ai_writing_mode_growth_extra_placeholder', 'fa-file-invoice-dollar', true),
                    $mode('growth_clients', 'Clients growth advisor', 'AI recommendations to retain, upsell, and re-engage clients.', 'ai_writing_mode_growth_extra_placeholder', 'fa-users', true),
                ],
            ],
        ];
    }

    /**
     * Flat list for Alpine (all modes with group label).
     *
     * @return list<array<string, mixed>>
     */
    public function flatModesFor(Company $company): array
    {
        $flat = [];
        foreach ($this->groupsFor($company) as $group) {
            foreach ($group['modes'] as $mode) {
                $flat[] = array_merge($mode, ['group' => $group['label']]);
            }
        }

        return $flat;
    }
}
