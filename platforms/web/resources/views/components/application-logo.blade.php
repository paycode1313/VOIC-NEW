@props([
    'variant' => 'horizontal',
])

@php
    $variant = in_array($variant, ['stacked', 'vertical']) ? 'stacked' : ($variant === 'mark' ? 'mark' : 'horizontal');
    // Unique ID suffix to prevent gradient ID collisions if multiple instances are rendered on a page
    $uid = substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
    $emblemPath = 'M 86.0 46.5 A 4.5 4.5 0 0 0 81.5 42.0 L 81.5 33.0 A 5.0 5.0 0 0 0 71.5 33.0 L 71.5 42.0 A 4.5 4.5 0 0 0 67.0 46.5 L 59.75 46.5 A 4.5 4.5 0 0 0 55.25 42.0 L 55.25 25.75 A 5.25 5.25 0 0 0 44.75 25.75 L 44.75 42.0 A 4.5 4.5 0 0 0 40.25 46.5 L 33.0 46.5 A 4.5 4.5 0 0 0 28.5 42.0 L 28.5 37.5 A 5.0 5.0 0 0 0 18.5 37.5 L 18.5 62.5 A 5.0 5.0 0 0 0 28.5 62.5 L 28.5 58.0 A 4.5 4.5 0 0 0 33.0 53.5 L 40.25 53.5 A 4.5 4.5 0 0 0 44.75 58.0 L 44.75 74.25 A 5.25 5.25 0 0 0 55.25 74.25 L 55.25 58.0 A 4.5 4.5 0 0 0 59.75 53.5 L 67.0 53.5 A 4.5 4.5 0 0 0 71.5 58.0 L 71.5 67.0 A 5.0 5.0 0 0 0 81.5 67.0 L 81.5 58.0 A 4.5 4.5 0 0 0 86.0 53.5 Q 92.0 53.5 97.4 57.5 A 48.0 48.0 0 1 1 97.4 42.5 Q 92.0 46.5 86.0 46.5 Z';
@endphp

@if($variant === 'horizontal')
    <svg {{ $attributes->merge(['class' => 'h-8 w-auto text-slate-900 dark:text-white select-none shrink-0']) }}
         viewBox="0 0 355 100"
         fill="none"
         xmlns="http://www.w3.org/2000/svg"
         role="img"
         aria-label="VOIC AI">
        <defs>
            <linearGradient id="voicGradH_{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#256fe6"/>
                <stop offset="100%" stop-color="#0e3260"/>
            </linearGradient>
        </defs>
        <!-- Waveform Emblem -->
        <path d="{{ $emblemPath }}" fill="url(#voicGradH_{{ $uid }})"/>
        <!-- VOIC Geometric Wordmark -->
        <g stroke="currentColor" stroke-width="11.5" stroke-linecap="round" stroke-linejoin="round" fill="none">
            <!-- V -->
            <path d="M 124,26.75 L 144,73.25 L 164,26.75"/>
            <!-- O -->
            <circle cx="214" cy="50" r="23.25"/>
            <!-- I -->
            <line x1="268" y1="26.75" x2="268" y2="73.25"/>
            <!-- C -->
            <path d="M 341.8,35.05 A 23.25,23.25 0 1 0 341.8,64.95"/>
        </g>
    </svg>
@elseif($variant === 'stacked')
    <svg {{ $attributes->merge(['class' => 'h-24 w-auto text-slate-900 dark:text-white select-none shrink-0']) }}
         viewBox="0 0 200 152"
         fill="none"
         xmlns="http://www.w3.org/2000/svg"
         role="img"
         aria-label="VOIC AI">
        <defs>
            <linearGradient id="voicGradS_{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#256fe6"/>
                <stop offset="100%" stop-color="#0e3260"/>
            </linearGradient>
        </defs>
        <!-- Centered Waveform Emblem -->
        <g transform="translate(50, 4)">
            <path d="{{ $emblemPath }}" fill="url(#voicGradS_{{ $uid }})"/>
        </g>
        <!-- Centered VOIC Wordmark -->
        <g stroke="currentColor" stroke-width="5.0" stroke-linecap="round" stroke-linejoin="round" fill="none">
            <!-- V -->
            <path d="M 64.5,116.25 L 72.5,135.75 L 80.5,116.25"/>
            <!-- O -->
            <circle cx="100" cy="126" r="9.75"/>
            <!-- I -->
            <line x1="121.5" y1="116.25" x2="121.5" y2="135.75"/>
            <!-- C -->
            <path d="M 143.5,119.75 A 9.75,9.75 0 1 0 143.5,132.25"/>
        </g>
    </svg>
@elseif($variant === 'mark')
    <svg {{ $attributes->merge(['class' => 'h-8 w-8 select-none shrink-0']) }}
         viewBox="0 0 100 100"
         fill="none"
         xmlns="http://www.w3.org/2000/svg"
         role="img"
         aria-label="VOIC Emblem">
        <defs>
            <linearGradient id="voicGradM_{{ $uid }}" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#256fe6"/>
                <stop offset="100%" stop-color="#0e3260"/>
            </linearGradient>
        </defs>
        <path d="{{ $emblemPath }}" fill="url(#voicGradM_{{ $uid }})"/>
    </svg>
@endif
