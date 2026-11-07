@php $class = $class ?? 'h-6 w-6'; @endphp
@switch($name)
    @case('briefcase')
        <svg class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2.5"/><path stroke-linecap="round" d="M8 7V5.5A1.5 1.5 0 019.5 4h5A1.5 1.5 0 0116 5.5V7M3 12.5h18"/></svg>
        @break
    @case('academic')
        <svg class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M12 4L2 9l10 5 10-5-10-5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 11.2V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.8M22 9v5"/></svg>
        @break
    @case('rocket')
        <svg class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4.5c3-1.2 5.5-.5 5.5-.5s.7 2.5-.5 5.5c-1 2.6-3.6 5.4-6.7 7.2L9.3 13.7C11.1 10.6 11.4 5.5 14 4.5zM9.3 13.7L7 13l-2.5 1.5L7 9.8l3.7-.6M12.3 16.7L13 19l-1.5 2.5 4.7-2.5.6-3.7M6 18c-1 .5-2 2-2 2s1.5-1 2-2z"/><circle cx="15.5" cy="8.5" r="1.3"/></svg>
        @break
    @default
        <svg class="{{ $class }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
@endswitch
