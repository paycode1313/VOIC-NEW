@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 dark:border-navy-700 bg-white dark:bg-navy-950/70 text-slate-900 dark:text-slate-100 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-blue-500 dark:focus:ring-blue-500 rounded-xl shadow-sm']) }}>
