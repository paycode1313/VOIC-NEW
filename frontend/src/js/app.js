import Alpine from 'alpinejs';
import examRoom from './exam-room';
import { speechText } from './pronunciation';

window.Alpine = Alpine;
// Chart.js (~200 KB) is only used by the practice report's radar chart: loaded there, not on every page.
window.loadChart = () => import('chart.js/auto').then((module) => (window.Chart = module.default));
window.voicSpeechText = speechText;

// Read a text aloud with the browser voice (report: hear how the stronger answer sounds).
// Same pronunciation rules and voice choice as the call room. Returns false when the browser has no voice.
window.voicSpeak = (text, language = 'id', onEnd = () => {}) => {
    const synth = window.speechSynthesis;
    if (!synth || !text) return false;
    synth.cancel();
    const lang = language === 'en' ? 'en-US' : 'id-ID';
    // [placeholders] are there to be filled in, not read out: a short pause instead.
    const utterance = new SpeechSynthesisUtterance(speechText(text.replace(/\s*\[[^\]]*\]\s*/g, ', '), language));
    utterance.lang = lang;
    utterance.rate = language === 'en' ? 0.98 : 1.02;
    const voices = synth.getVoices();
    const voice = voices.find((v) => v.lang === lang && /natural|google|online/i.test(v.name)) || voices.find((v) => v.lang.startsWith(lang.slice(0, 2)));
    if (voice) utterance.voice = voice;
    utterance.onend = onEnd;
    utterance.onerror = onEnd;
    synth.speak(utterance);
    return true;
};

Alpine.data('examRoom', examRoom);

// Form hints follow the browser's language ("Please fill out this field") even on an Indonesian page (the UI is
// Indonesian unless ?lang=en): say them in Indonesian. A field's own oninvalid message runs after this one and wins.
if (!/[?&]lang=en\b/.test(location.search)) {
    document.addEventListener('invalid', (event) => {
        const field = event.target;
        if (!field?.validity || field.validity.customError) return;
        const { validity } = field;
        const message = validity.valueMissing
            ? (field.type === 'checkbox' ? 'Centang kotak ini dulu ya.' : 'Kolom ini perlu diisi.')
            : validity.typeMismatch && field.type === 'email' ? 'Tulis alamat email yang benar, misalnya nama@email.com.'
            : validity.tooShort ? `Minimal ${field.minLength} karakter ya.`
            : validity.tooLong ? `Maksimal ${field.maxLength} karakter.`
            : validity.patternMismatch ? 'Formatnya belum sesuai.'
            : '';
        if (message) {
            field.setCustomValidity(message);
            field.dataset.voicHint = '1';
        }
    }, true);
    const clear = (event) => {
        if (event.target?.dataset?.voicHint) {
            event.target.setCustomValidity('');
            delete event.target.dataset.voicHint;
        }
    };
    document.addEventListener('input', clear, true);
    document.addEventListener('change', clear, true);
}

// Cards marked .voic-tilt lean a few degrees towards a mouse pointer (not on touch, not with reduced motion).
if (window.matchMedia?.('(hover: hover) and (prefers-reduced-motion: no-preference)').matches) {
    document.addEventListener('pointermove', (event) => {
        const card = event.target.closest?.('.voic-tilt');
        if (!card) return;
        const box = card.getBoundingClientRect();
        card.style.setProperty('--ry', `${((event.clientX - box.left) / box.width - 0.5) * 6}deg`);
        card.style.setProperty('--rx', `${(0.5 - (event.clientY - box.top) / box.height) * 6}deg`);
    });
    document.addEventListener('pointerout', (event) => {
        const card = event.target.closest?.('.voic-tilt');
        if (card && !card.contains(event.relatedTarget)) {
            card.style.removeProperty('--rx');
            card.style.removeProperty('--ry');
        }
    });
}

// Card spotlight: the soft glow on hovered cards follows the pointer (css: Motion kit).
if (window.matchMedia?.('(hover: hover)').matches) {
    document.addEventListener('pointermove', (event) => {
        const card = event.target.closest?.('[class*="rounded-3xl"], [class*="rounded-2xl"], .voic-card, .voic-tilt');
        if (!card) return;
        const box = card.getBoundingClientRect();
        card.style.setProperty('--mx', `${event.clientX - box.left}px`);
        card.style.setProperty('--my', `${event.clientY - box.top}px`);
    }, { passive: true });
}

// Images shimmer as a skeleton until they have loaded, then fade in.
function watchImage(img) {
    if (img.complete || img.dataset.noSkeleton !== undefined) return;
    img.classList.add('voic-img-loading');
    const done = () => {
        img.classList.remove('voic-img-loading');
        img.classList.add('voic-img-loaded');
    };
    img.addEventListener('load', done, { once: true });
    img.addEventListener('error', () => img.classList.remove('voic-img-loading'), { once: true });
}
document.querySelectorAll('img').forEach(watchImage);

// A thin bar along the top while the next page loads (links and forms to this site).
function showNavBar() {
    if (document.querySelector('.voic-navbar')) return;
    const bar = document.createElement('div');
    bar.className = 'voic-navbar';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);
}
document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download') || link.origin !== location.origin) return;
    if (link.pathname === location.pathname && link.search === location.search) return; // same page / #anchor
    showNavBar();
});
document.addEventListener('submit', (event) => {
    if (!event.defaultPrevented && (event.target.method || 'get').toLowerCase() !== 'dialog') showNavBar();
});
// Back/forward from the cache: remove a bar left over from leaving the page.
window.addEventListener('pageshow', () => document.querySelectorAll('.voic-navbar').forEach((bar) => bar.remove()));

// Landing-style pages: smooth scroll (Lenis), word-by-word headings (GSAP) and card motion (Motion).
// Loaded only where a page asks for it, and never with reduced motion (the layout's <head> sets html.voic-fx).
if (document.documentElement.classList.contains('voic-fx') && document.querySelector('[data-split], [data-reveal]')) {
    import('./scroll-fx').catch(() => document.documentElement.classList.remove('voic-fx'));
}

Alpine.start();
