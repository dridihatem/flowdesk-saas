<?php

namespace App\AI\Nova\Support;

final class ActionRisk
{
    public const LOW = 'LOW';

    public const MEDIUM = 'MEDIUM';

    public const HIGH = 'HIGH';

    /**
     * Default risk map for Nova tools. HIGH always requires confirmation.
     *
     * @var array<string, string>
     */
    public const TOOL_RISKS = [
        'clients.search' => self::LOW,
        'clients.get' => self::LOW,
        'clients.create' => self::MEDIUM,
        'clients.update' => self::MEDIUM,
        'projects.search' => self::LOW,
        'projects.get' => self::LOW,
        'projects.create' => self::MEDIUM,
        'projects.update' => self::MEDIUM,
        'projects.delete' => self::HIGH,
        'tasks.search' => self::LOW,
        'tasks.get' => self::LOW,
        'tasks.create' => self::LOW,
        'tasks.update' => self::MEDIUM,
        'tasks.assign' => self::MEDIUM,
        'tasks.delete' => self::HIGH,
        'invoices.search' => self::LOW,
        'invoices.get' => self::LOW,
        'invoices.create' => self::MEDIUM,
        'invoices.update' => self::MEDIUM,
        'invoices.send' => self::HIGH,
        'quotes.search' => self::LOW,
        'quotes.get' => self::LOW,
        'quotes.create' => self::MEDIUM,
        'quotes.update' => self::MEDIUM,
        'quotes.send' => self::HIGH,
        'meetings.search' => self::LOW,
        'meetings.get' => self::LOW,
        'meetings.create' => self::MEDIUM,
        'meetings.update' => self::MEDIUM,
        'documents.search' => self::LOW,
        'documents.get' => self::LOW,
        'documents.analyze' => self::LOW,
        'reports.revenue' => self::LOW,
        'reports.invoices' => self::LOW,
        'reports.projects' => self::LOW,
        'reports.workload' => self::LOW,
        'reports.clients' => self::LOW,
        'assignments.recommend' => self::LOW,
        'projects.plan_from_document' => self::MEDIUM,
    ];

    public static function forTool(string $toolName): string
    {
        return self::TOOL_RISKS[$toolName] ?? self::MEDIUM;
    }

    public static function requiresConfirmation(string $risk, bool $companyAllowsAutoSend = false, string $toolName = ''): bool
    {
        if ($risk === self::HIGH) {
            if ($companyAllowsAutoSend && in_array($toolName, ['invoices.send', 'quotes.send'], true)) {
                return false;
            }

            return true;
        }

        return false;
    }
}
