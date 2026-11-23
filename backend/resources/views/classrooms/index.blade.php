<x-exam-layout title="Kelas">
    <div class="mx-auto max-w-5xl px-4 pb-24 pt-8 sm:px-6 sm:pt-12">
        <h1 class="text-3xl font-extrabold sm:text-4xl">Kelas</h1>
        <p class="mt-2 max-w-2xl text-slate-600">Dosen, pembimbing, atau biro karier membuat kelas. Mahasiswa bergabung dengan kodenya, lalu pembuat kelas bisa memantau rapor latihan mereka.</p>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            {{-- Create a class (mentor) --}}
            <form method="POST" action="{{ route('classrooms.store') }}" class="rounded-3xl border border-black/5 bg-white p-6">
                @csrf
                <h2 class="text-lg font-extrabold">Buat kelas</h2>
                <p class="mt-1 text-sm text-slate-600">Untuk dosen dan mentor. Kamu dapat kode gabung untuk dibagikan.</p>
                <label for="name" class="mt-4 block text-xs font-bold uppercase tracking-wider text-slate-600">Nama kelas</label>
                <input id="name" name="name" type="text" maxlength="80" required value="{{ old('name') }}" placeholder="mis. Bimbingan Skripsi TI 2026"
                       class="mt-1.5 w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
                <button type="submit" class="mt-4 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Buat kelas</button>
            </form>

            {{-- Join a class (student) --}}
            <div class="rounded-3xl border border-black/5 bg-white p-6">
                <h2 class="text-lg font-extrabold">Gabung ke kelas</h2>
                <p class="mt-1 text-sm text-slate-600">Punya kode dari dosenmu? Masukkan di sini.</p>
                <a href="{{ route('classrooms.join') }}" class="mt-4 inline-flex rounded-full border border-slate-200 bg-slate-50 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100">Masukkan kode kelas</a>
            </div>
        </div>

        @if ($owned->isNotEmpty())
            <h2 class="mt-12 text-xl font-extrabold">Kelas yang kamu pantau</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($owned as $classroom)
                    <a href="{{ route('classrooms.show', $classroom) }}" class="rounded-2xl border border-black/5 bg-white p-5">
                        <p class="font-extrabold">{{ $classroom->name }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $classroom->members_count }} mahasiswa</p>
                        <p class="mt-3 inline-flex rounded-full bg-slate-900/[0.06] px-3 py-1 font-mono text-xs font-bold tracking-widest">{{ $classroom->join_code }}</p>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($joined->isNotEmpty())
            <h2 class="mt-12 text-xl font-extrabold">Kelas yang kamu ikuti</h2>
            <p class="mt-1 text-sm text-slate-600">Pembuat kelas ini bisa melihat rapor latihanmu (bukan rekaman suaramu).</p>
            <ul class="mt-4 divide-y divide-slate-100 rounded-3xl border border-black/5 bg-white">
                @foreach ($joined as $classroom)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="font-bold">{{ $classroom->name }}</p>
                            <p class="text-sm text-slate-500">oleh {{ $classroom->owner->name }}</p>
                        </div>
                        <form method="POST" action="{{ route('classrooms.leave', $classroom) }}" onsubmit="return confirm('Keluar dari kelas ini? Pembuatnya tidak bisa melihat rapormu lagi.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline">Keluar</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-exam-layout>
