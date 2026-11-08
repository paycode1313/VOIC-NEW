{{-- Signed-in pages (dashboard, history, profile) share the site shell. --}}
<x-exam-layout :title="$title ?? null">
    @isset($header)
        <div class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}

    <x-slot:after>
        <x-onboarding-wizard />
    </x-slot:after>
</x-exam-layout>
