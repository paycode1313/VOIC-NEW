/**
 * Scroll effects for pages that ask for them (loaded by app.js only when the page has [data-split] or [data-reveal]):
 *  - Lenis: smooth scrolling (wheel + #anchor links), driven by GSAP's ticker so ScrollTrigger stays in sync.
 *  - GSAP + ScrollTrigger: headings marked [data-split] appear word by word when scrolled into view.
 *  - Motion: cards marked [data-reveal] slide in when they enter the screen, lift on hover and dip on press.
 *    [data-reveal="tilt"] cards also lean a few degrees towards the pointer.
 * Not used at all with prefers-reduced-motion (app.js does not load this file then).
 */
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { animate, hover, inView, press } from 'motion';

gsap.registerPlugin(ScrollTrigger);

// ---- Lenis smooth scroll ----
const lenis = new Lenis({
    autoRaf: false,
    lerp: 0.1,
    anchors: { offset: -80 }, // the sticky header is 56px tall, plus some breathing room
    allowNestedScroll: true,
});
lenis.on('scroll', ScrollTrigger.update);
gsap.ticker.add((time) => lenis.raf(time * 1000));
gsap.ticker.lagSmoothing(0);
window.voicLenis = lenis;

// ---- Headings: word by word ----
// Text is split into words; an inner element (a highlighter mark, gradient text) stays whole and moves as one word,
// because splitting a background-clip:text span would make its letters invisible.
function splitWords(heading) {
    const words = [];
    const wrap = (content) => {
        const outer = document.createElement('span');
        outer.className = 'voic-word';
        const inner = document.createElement('span');
        inner.className = 'voic-word-in';
        inner.append(content);
        outer.append(inner);
        words.push(inner);
        return outer;
    };
    for (const node of [...heading.childNodes]) {
        if (node.nodeType === Node.TEXT_NODE) {
            const parts = node.textContent.split(/(\s+)/);
            const fragment = document.createDocumentFragment();
            for (const part of parts) {
                if (!part) continue;
                fragment.append(/^\s+$/.test(part) ? document.createTextNode(' ') : wrap(document.createTextNode(part)));
            }
            node.replaceWith(fragment);
        } else if (node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'BR') {
            const holder = document.createComment('');
            node.replaceWith(holder);
            holder.replaceWith(wrap(node));
        }
    }
    return words;
}

document.querySelectorAll('[data-split]').forEach((heading) => {
    heading.setAttribute('aria-label', heading.textContent.replace(/\s+/g, ' ').trim());
    const words = splitWords(heading);
    heading.querySelectorAll('.voic-word').forEach((word) => word.setAttribute('aria-hidden', 'true'));
    gsap.set(heading, { visibility: 'visible' });
    gsap.from(words, {
        yPercent: 110,
        rotate: 4,
        opacity: 0,
        duration: 0.9,
        ease: 'expo.out',
        stagger: 0.07,
        delay: Number(heading.dataset.splitDelay || 0),
        onStart: () => heading.classList.add('is-in'),
        scrollTrigger: { trigger: heading, start: 'top 85%', once: true },
    });
});

// ---- Cards: entrance, hover, press (Motion) ----
const spring = { type: 'spring', stiffness: 320, damping: 24, mass: 0.8 };
const cards = [...document.querySelectorAll('[data-reveal]')];

cards.forEach((card) => {
    // Cards next to each other come in one after another.
    const siblings = [...card.parentElement.children].filter((el) => el.hasAttribute('data-reveal'));
    const order = siblings.indexOf(card);
    inView(card, () => {
        animate(card, { opacity: [0, 1], y: [48, 0], scale: [0.96, 1] }, { ...spring, delay: order * 0.1 });
    }, { amount: 0.2 });

    const tilt = card.dataset.reveal === 'tilt';
    hover(card, (element) => {
        animate(element, { y: -8, scale: 1.02 }, spring);
        if (!tilt) return () => animate(element, { y: 0, scale: 1 }, spring);
        const lean = (event) => {
            const box = element.getBoundingClientRect();
            animate(element, {
                rotateY: ((event.clientX - box.left) / box.width - 0.5) * 8,
                rotateX: (0.5 - (event.clientY - box.top) / box.height) * 8,
                transformPerspective: 900,
            }, { type: 'spring', stiffness: 200, damping: 20 });
        };
        element.addEventListener('pointermove', lean);
        return () => {
            element.removeEventListener('pointermove', lean);
            animate(element, { y: 0, scale: 1, rotateX: 0, rotateY: 0 }, spring);
        };
    });
    press(card, (element) => {
        animate(element, { scale: 0.97 }, { type: 'spring', stiffness: 500, damping: 30 });
        return () => animate(element, { scale: element.matches(':hover') ? 1.02 : 1 }, spring);
    });
});

// Images and fonts change the page height after load: let ScrollTrigger and Lenis measure again.
window.addEventListener('load', () => {
    lenis.resize();
    ScrollTrigger.refresh();
});
