@php
    $featureRows = $featureRows ?? [];
    $compact = $compact ?? false;
@endphp
@if (count($featureRows) > 0)
    <ul @class(['space-y-1.5', 'text-xs text-slate-600 dark:text-slate-400' => $compact, 'space-y-2 text-sm text-slate-600 dark:text-slate-400' => ! $compact])>
        @foreach ($featureRows as $row)
            <li class="flex items-start justify-between gap-3">
                <span @class([
                    'text-slate-700 dark:text-slate-300',
                    'font-medium' => ! $compact,
                    'text-slate-400 line-through dark:text-slate-500' => ! $row['enabled'],
                ])>{{ $row['label'] }}</span>
                <span @class([
                    'shrink-0 tabular-nums text-end',
                    'font-semibold text-emerald-700 dark:text-emerald-400' => $row['enabled'] && ! $compact,
                    'font-semibold text-emerald-600 dark:text-emerald-400' => $row['enabled'] && $compact,
                    'font-semibold text-rose-600 dark:text-rose-400' => ! $row['enabled'],
                ])>
                    {{ $row['status'] }}
                </span>
            </li>
        @endforeach
    </ul>
@endif
