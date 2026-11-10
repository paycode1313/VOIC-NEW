@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-black/10 bg-[#f6f6f3] px-4 py-2.5 text-[15px] text-slate-900 focus:border-slate-900 focus:ring-slate-900 disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:text-slate-100 dark:focus:border-white dark:focus:ring-white']) }}>
