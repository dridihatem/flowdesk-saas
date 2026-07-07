<button {{ $attributes->merge(['type' => 'submit', 'class' => 'flow-primary-button inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-transparent bg-flow-primary font-semibold text-xs uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-flow-primary-hover focus:outline-none focus:ring-2 focus:ring-[var(--flow-primary)] focus:ring-offset-2 active:opacity-90 dark:focus:ring-offset-slate-900']) }}>
    {{ $slot }}
</button>
