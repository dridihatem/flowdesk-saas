@props(['metrics' => []])
<div class="mt-4">
    <x-flow.table>
        <thead class="bg-flow-surface-muted text-start text-xs font-medium uppercase tracking-wide text-flow-text-muted">
            <tr>
                <th class="px-4 py-3 text-start">{{ __('Metric') }}</th>
                <th class="px-4 py-3 text-start">{{ __('Value') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-flow-border text-flow-text">
            <tr>
                <td class="px-4 py-3 text-start">{{ __('Workspace') }}</td>
                <td class="px-4 py-3 font-mono text-xs text-start">{{ auth()->user()?->company?->subdomain ?? '—' }}</td>
            </tr>
            <tr>
                <td class="px-4 py-3 text-start">{{ __('Locale') }}</td>
                <td class="px-4 py-3 text-start">{{ app()->getLocale() }}</td>
            </tr>
            <tr>
                <td class="px-4 py-3 text-start">{{ __('Clients') }}</td>
                <td class="px-4 py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($metrics['clients_count'] ?? 0) }}</span></td>
            </tr>
            <tr>
                <td class="px-4 py-3 text-start">{{ __('Projects') }}</td>
                <td class="px-4 py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($metrics['projects_count'] ?? 0) }}</span></td>
            </tr>
        </tbody>
    </x-flow.table>
</div>
