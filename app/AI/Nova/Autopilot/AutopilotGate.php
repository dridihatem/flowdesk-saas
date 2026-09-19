<?php

namespace App\AI\Nova\Autopilot;

use App\AI\Nova\Agent\NovaContext;
use RuntimeException;

/**
 * Autopilot is scaffolded and gated. Unsafe automatic end-to-end execution
 * is never enabled unless mode is explicitly "autopilot" AND the feature flag
 * is on — currently always blocked for destructive multi-step flows.
 */
class AutopilotGate
{
    public const MODES = ['manual', 'suggest', 'confirm', 'autopilot'];

    public function mode(NovaContext $context): string
    {
        $mode = strtolower($context->autopilotMode);
        if (! in_array($mode, self::MODES, true)) {
            return 'manual';
        }

        return $mode;
    }

    public function assertCanAutoExecute(NovaContext $context, string $action): void
    {
        $mode = $this->mode($context);
        if ($mode === 'manual' || $mode === 'suggest' || $mode === 'confirm') {
            throw new RuntimeException("Autopilot blocked ({$mode}): '{$action}' requires user confirmation.");
        }

        // Even in autopilot mode, keep a hard kill-switch until Phase 30 is stable.
        if (! (bool) config('flowdesk.nova_autopilot_enabled', false)) {
            throw new RuntimeException('Autopilot mode is scaffolded but not enabled on this platform.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function status(NovaContext $context): array
    {
        return [
            'mode' => $this->mode($context),
            'enabled' => (bool) config('flowdesk.nova_autopilot_enabled', false),
            'safe_to_run' => false,
            'message' => 'Autopilot is gated. Use Manual / Suggest / Confirm until foundation phases are stable.',
        ];
    }
}
