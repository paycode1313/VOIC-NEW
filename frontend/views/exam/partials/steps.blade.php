{{--
    How it works, as three numbered stops on a dotted path.
    @param bool $isEn
    @param list<array{0: string, 1: string, 2: string}> $steps  heading, body, highlighter colour
    @param ?string $title
--}}
<section class="relative mt-20">
    @isset($title)
        <h2 class="mb-8 text-3xl font-extrabold sm:text-4xl">{{ $title }}</h2>
    @endisset
    <ol class="relative grid gap-5 sm:grid-cols-3">
        <span class="pointer-events-none absolute left-8 right-8 top-9 hidden border-t-2 border-dashed border-slate-300 sm:block" aria-hidden="true"></span>
        @foreach ($steps as [$heading, $body, $mark])
            <li class="voic-rise relative rounded-3xl border border-black/5 bg-white/80 p-5 shadow-sm backdrop-blur" style="--d: {{ 120 + $loop->index * 100 }}ms">
                <span class="voic-sticker font-display relative h-12 w-12 justify-center text-2xl font-extrabold text-slate-900" style="background: {{ $mark }}; transform: rotate({{ [-6, 4, -3][$loop->index % 3] }}deg)">{{ $loop->iteration }}</span>
                <h3 class="mt-4 text-lg font-extrabold">{{ $heading }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $body }}</p>
            </li>
        @endforeach
    </ol>
</section>
