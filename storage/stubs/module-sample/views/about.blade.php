<div class="flow-panel p-6 sm:p-8">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">À propos de ce module</h3>
    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
        Ceci est une page secondaire chargée depuis <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">views/about.blade.php</code>.
    </p>

    <h4 class="mt-6 text-sm font-semibold text-slate-900 dark:text-white">Structure du paquet</h4>
    <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"><code>quick-notes/
├── module.json
└── views/
    ├── index.blade.php   ← page principale (/modules/quick-notes)
    └── about.blade.php   ← cette page (/modules/quick-notes/about)</code></pre>

    <h4 class="mt-6 text-sm font-semibold text-slate-900 dark:text-white">Variables disponibles</h4>
    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-slate-600 dark:text-slate-400">
        <li><code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">$module</code> — le modèle InstalledModule (name, slug, version, manifest…)</li>
        <li>Composants Blade du CRM (<code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">flow-panel</code>, boutons, Alpine.js, Tailwind…)</li>
    </ul>

    <a
        href="{{ route('modules.show', ['slug' => $module->slug]) }}"
        class="mt-6 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
    >
        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
        Retour au module
    </a>
</div>
