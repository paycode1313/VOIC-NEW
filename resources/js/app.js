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

Alpine.start();
