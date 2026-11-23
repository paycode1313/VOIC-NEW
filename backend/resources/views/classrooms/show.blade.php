@php
    $bandColor = fn (?float $band) => $band === null ? 'text-slate-400' : ($band >= 7 ? 'text-emerald-600' : ($band >= 5.5 ? 'text-amber-600' : 'text-rose-600'));
@endphp

<x-exam-layout :title="$classroom->name" :back="route('classrooms.index')" back-label="Kelas">
    <div class="mx-auto max-w-6xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">
        <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Kelas</p>
                <h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">{{ $classroom->name }}</h1>
                <p class="mt-2 max-w-2xl text-slate-600">Rapor setiap mahasiswa yang bergabung muncul di sini. Kamu hanya bisa melihat; rekaman suara dan penghapusan data tetap milik mahasiswa.</p>
            </div>

            {{-- Join code, link and QR to show on a slide or share in the class group --}}
            <div class="flex items-center gap-4 rounded-3xl bg-slate-900 p-4 pr-6 text-white" x-data="{ copied: false }">
                <div id="class-qr" data-url="{{ $classroom->joinUrl() }}" class="h-24 w-24 shrink-0 rounded-xl bg-white p-1.5 [&_svg]:h-full [&_svg]:w-full" aria-label="QR code untuk bergabung"></div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-300">Kode gabung</p>
                    <p class="font-mono text-3xl font-extrabold tracking-[0.2em]">{{ $classroom->join_code }}</p>
                    <button type="button" class="mt-1 text-sm font-semibold text-indigo-200 hover:text-white"
                            @click="navigator.clipboard?.writeText(@js($classroom->joinUrl())); copied = true; setTimeout(() => copied = false, 2000)"
                            x-text="copied ? 'Tautan disalin!' : 'Salin tautan gabung'">Salin tautan gabung</button>
                </div>
            </div>
        </div>

        <section class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="Ringkasan kelas">
            @foreach ([
                ['Mahasiswa', $overview['members'], null],
                ['Aktif 7 hari terakhir', $overview['active_week'], null],
                ['Rata-rata band terakhir', $overview['average'] !== null ? number_format($overview['average'], 1) : '–', null],
                ['Membaik di sesi terakhir', $overview['improving'], 'dari '.$members->filter(fn ($row) => $row['delta'] !== null)->count().' yang sudah 2+ sesi'],
            ] as [$label, $value, $hint])
                <div class="rounded-3xl border border-black/5 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                    <p class="font-display mt-1 text-3xl font-extrabold">{{ $value }}</p>
                    @if ($hint)<p class="text-xs text-slate-500">{{ $hint }}</p>@endif
                </div>
            @endforeach
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-black/5 bg-white">
            @if ($members->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-lg font-extrabold">Belum ada mahasiswa</p>
                    <p class="mt-1 text-sm text-slate-600">Bagikan kode <strong class="font-mono">{{ $classroom->join_code }}</strong> atau QR di atas. Mahasiswa perlu akun VOIC untuk bergabung.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Mahasiswa</th>
                                <th class="px-3 py-3 font-semibold">Sesi</th>
                                <th class="px-3 py-3 font-semibold">Band terakhir</th>
                                <th class="px-3 py-3 font-semibold">Perubahan</th>
                                <th class="px-3 py-3 font-semibold">Perlu dilatih</th>
                                <th class="px-3 py-3 font-semibold">Latihan terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($members as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('classrooms.member', [$classroom, $row['student']]) }}" class="font-bold hover:text-indigo-600">{{ $row['student']->name }}</a>
                                        <p class="text-xs text-slate-500">{{ $row['student']->email }}</p>
                                    </td>
                                    <td class="px-3 py-3 font-mono">{{ $row['sessions'] }}</td>
                                    <td class="px-3 py-3 font-display text-lg font-extrabold {{ $bandColor($row['latest']) }}">{{ $row['latest'] !== null ? number_format($row['latest'], 1) : '–' }}</td>
                                    <td class="px-3 py-3">
                                        @if ($row['delta'] === null)
                                            <span class="text-slate-400">–</span>
                                        @else
                                            <span @class(['rounded-full px-2.5 py-0.5 text-xs font-bold', 'bg-emerald-100 text-emerald-800' => $row['delta'] > 0, 'bg-amber-100 text-amber-800' => $row['delta'] < 0, 'bg-slate-100 text-slate-600' => $row['delta'] == 0])>
                                                {{ $row['delta'] > 0 ? '▲ +' : ($row['delta'] < 0 ? '▼ ' : '') }}{{ number_format($row['delta'], 1) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-slate-600">{{ $row['weakest'] ? ($labels[$row['weakest']] ?? $row['weakest']) : '–' }}</td>
                                    <td class="px-3 py-3 text-slate-600">
                                        @if ($row['last_at'])
                                            <a href="{{ $row['last_url'] }}" class="hover:text-indigo-600 hover:underline">{{ $row['last_at']->locale('id')->diffForHumans() }}</a>
                                        @else
                                            <span class="text-slate-400">Belum latihan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <form method="POST" action="{{ route('classrooms.destroy', $classroom) }}" class="mt-10 text-center" onsubmit="return confirm('Hapus kelas ini? Mahasiswa keluar dari kelas, rapor mereka tetap tersimpan di akun masing-masing.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">Hapus kelas</button>
        </form>
    </div>

    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script>
        (function () {
            var box = document.getElementById('class-qr');
            if (!box || typeof qrcode !== 'function') return;
            var qr = qrcode(0, 'M');
            qr.addData(box.dataset.url);
            qr.make();
            box.innerHTML = qr.createSvgTag(3, 1);
        })();
    </script>
</x-exam-layout>
