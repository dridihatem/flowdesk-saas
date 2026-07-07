<div class="space-y-6">
    <div class="flow-panel p-6 sm:p-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $module->name }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $module->description }}</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                v{{ $module->version }}
            </span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-indigo-200/80 bg-indigo-50/60 p-4 dark:border-indigo-500/30 dark:bg-indigo-950/30">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Slug</p>
                <p class="mt-1 font-mono text-sm text-slate-900 dark:text-white">{{ $module->slug }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-4 dark:border-emerald-500/30 dark:bg-emerald-950/30">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Auteur</p>
                <p class="mt-1 text-sm text-slate-900 dark:text-white">{{ $module->author ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-4 dark:border-amber-500/30 dark:bg-amber-950/30">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">{{ __('Installed') }}</p>
                <p class="mt-1 text-sm text-slate-900 dark:text-white">{{ $module->installed_at?->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="flow-panel p-6 sm:p-8" x-data="{
        notes: JSON.parse(localStorage.getItem('flowdesk_module_quick_notes') || '[]'),
        draft: '',
        save() { localStorage.setItem('flowdesk_module_quick_notes', JSON.stringify(this.notes)); },
        add() {
            const t = this.draft.trim();
            if (!t) { return; }
            this.notes.unshift({ id: Date.now(), text: t, at: new Date().toLocaleString() });
            this.draft = '';
            this.save();
        },
        remove(id) {
            this.notes = this.notes.filter(n => n.id !== id);
            this.save();
        }
    }">
        <h4 class="text-base font-semibold text-slate-900 dark:text-white">Notes rapides</h4>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Démo : les notes sont stockées dans votre navigateur (localStorage). Ajoutez une migration dans database/migrations/ pour une vraie table.</p>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <input
                type="text"
                x-model="draft"
                @keydown.enter.prevent="add()"
                placeholder="Écrire une note puis Entrée…"
                class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            />
            <button
                type="button"
                @click="add()"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                Ajouter
            </button>
        </div>

        <ul class="mt-5 space-y-2">
            <li x-show="notes.length === 0" x-cloak class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                Aucune note pour le moment.
            </li>
            <template x-for="n in notes" :key="n.id">
                <li class="flex items-start justify-between gap-3 rounded-lg border border-slate-200/80 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-800 dark:text-slate-200" x-text="n.text"></p>
                        <p class="mt-0.5 text-[11px] text-slate-400" x-text="n.at"></p>
                    </div>
                    <button
                        type="button"
                        @click="remove(n.id)"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40"
                    >
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </button>
                </li>
            </template>
        </ul>
    </div>

    <div class="flow-panel p-6 sm:p-8">
        <h4 class="text-base font-semibold text-slate-900 dark:text-white">Pages supplémentaires</h4>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            Chaque fichier Blade dans <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">views/</code> devient une page :
            <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">views/about.blade.php</code> →
            <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs dark:bg-slate-800">/modules/{{ $module->slug }}/about</code>
        </p>
        <a
            href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'about']) }}"
            class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
        >
            Voir la page « about »
            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
        </a>
    </div>
</div>
