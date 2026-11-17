@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-blue-500 dark:border-blue-400 text-start text-base font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-navy-900/60 focus:outline-none focus:text-blue-700 dark:focus:text-blue-300 focus:bg-blue-100 dark:focus:bg-navy-900 focus:border-blue-700 dark:focus:border-blue-400 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-navy-900/40 hover:border-slate-300 dark:hover:border-navy-700 focus:outline-none focus:text-slate-900 dark:focus:text-slate-100 focus:bg-slate-50 dark:focus:bg-navy-900 focus:border-slate-300 dark:focus:border-navy-600 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
