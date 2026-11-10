<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-black/10 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-black/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-900 disabled:opacity-40 dark:border-white/15 dark:text-slate-200 dark:hover:bg-white/10']) }}>
    {{ $slot }}
</button>
