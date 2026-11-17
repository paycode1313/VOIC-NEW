@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200/90 bg-slate-50/70 px-4 py-3 text-[15px] font-medium text-slate-900 placeholder-slate-400 transition focus:border-brand-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/10 disabled:opacity-60']) }}>
