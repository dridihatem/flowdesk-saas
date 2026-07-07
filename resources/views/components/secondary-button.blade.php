<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-lg border bg-flow-surface px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[var(--flow-primary)] focus:ring-offset-2 disabled:opacity-25 dark:focus:ring-offset-slate-900 flow-secondary-button']) }}>
    {{ $slot }}
</button>
