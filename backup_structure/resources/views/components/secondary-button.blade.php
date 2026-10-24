<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white dark:bg-navy-900 border border-slate-300 dark:border-navy-700 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-300 uppercase tracking-wider shadow-xs hover:bg-slate-50 dark:hover:bg-navy-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-navy-900 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
