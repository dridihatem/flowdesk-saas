<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-flow border border-flow-border bg-flow-surface shadow-sm']) }}>
    <table class="flow-data-table min-w-full table-fixed divide-y divide-flow-border text-sm text-start">
        {{ $slot }}
    </table>
</div>
