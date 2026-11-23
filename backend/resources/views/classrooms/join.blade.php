<x-exam-layout title="Gabung ke kelas" :back="route('classrooms.index')" back-label="Kelas">
    <div class="mx-auto max-w-lg px-4 pb-24 pt-8 sm:pt-16">
        <form method="POST" action="{{ route('classrooms.join.store') }}" class="rounded-3xl border border-black/5 bg-white p-6 sm:p-8">
            @csrf
            <h1 class="text-2xl font-extrabold">Gabung ke kelas</h1>

            @if ($classroom)
                <p class="mt-2 text-slate-600"><strong class="text-slate-900">{{ $classroom->name }}</strong> oleh {{ $classroom->owner->name }}</p>
            @elseif ($code !== '')
                <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">Kode <strong>{{ $code }}</strong> tidak ditemukan. Cek lagi ke dosenmu.</p>
            @else
                <p class="mt-2 text-slate-600">Masukkan kode enam karakter dari dosen atau mentormu.</p>
            @endif

            @if ($alreadyMember)
                <p class="mt-5 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Kamu sudah bergabung ke kelas ini.</p>
                <a href="{{ route('exam.index') }}" class="mt-5 inline-flex rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Mulai latihan</a>
            @else
                <label for="code" class="mt-5 block text-xs font-bold uppercase tracking-wider text-slate-600">Kode kelas</label>
                <input id="code" name="code" type="text" maxlength="20" required autocomplete="off" autocapitalize="characters"
                       value="{{ old('code', $code) }}" placeholder="ABC123"
                       class="mt-1.5 w-full rounded-xl border-slate-200 font-mono text-lg uppercase tracking-[0.3em] focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('code')" class="mt-1.5" />

                <label class="mt-5 flex gap-3 rounded-2xl bg-slate-900/[0.04] p-4 text-sm text-slate-700">
                    <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('consent'))>
                    <span>Saya setuju pembuat kelas bisa melihat <strong>rapor latihan</strong> saya: band score, transkrip, dan masukan. Rekaman suara tetap pribadi. Saya bisa keluar dari kelas kapan saja.</span>
                </label>
                <x-input-error :messages="$errors->get('consent')" class="mt-1.5" />

                <button type="submit" class="mt-6 w-full rounded-full bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">Gabung</button>
            @endif
        </form>
    </div>
</x-exam-layout>
