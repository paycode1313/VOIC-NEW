{{-- Auth pages: a single centered card on the site shell. --}}
<x-exam-layout :title="$title ?? null">
    <div class="mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-md flex-col justify-center px-4 py-10">
        {{ $slot }}
    </div>
</x-exam-layout>
