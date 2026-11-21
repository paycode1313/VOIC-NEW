import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import examRoom from './exam-room';
import { speechText } from './pronunciation';

window.Alpine = Alpine;
window.Chart = Chart;
window.voicSpeechText = speechText;

Alpine.data('examRoom', examRoom);

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

Alpine.start();
