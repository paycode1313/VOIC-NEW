@if (empty($leaders))
    <p class="mt-4 text-sm text-slate-500">Belum ada sesi selesai hari ini.</p>
@else
    <ol class="mt-3 divide-y divide-black/5 dark:divide-white/10">
        @foreach ($leaders as $rank => $leader)
            <li class="flex items-center gap-4 py-2.5">
                <span class="w-6 text-center text-sm font-extrabold {{ $rank < 3 ? '' : 'text-slate-400' }}">{{ $rank + 1 }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ $leader['name'] }}</p>
                    <p class="text-xs text-slate-500">{{ $leader['mode'] }} · {{ $leader['time'] }}</p>
                </div>
                <span class="rounded-full bg-slate-900 px-3 py-1 text-sm font-extrabold text-white dark:bg-white dark:text-slate-900">{{ number_format($leader['band'], 1) }}</span>
            </li>
        @endforeach
    </ol>
@endif
