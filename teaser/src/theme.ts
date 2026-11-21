import { loadFont } from '@remotion/fonts';
import { staticFile } from 'remotion';

// Same faces as the app (bundled, no internet needed): Bricolage Grotesque for headings, Figtree for text.
export const DISPLAY = '"Bricolage Grotesque", Figtree, system-ui, sans-serif';
export const BODY = 'Figtree, system-ui, sans-serif';

loadFont({ family: 'Bricolage Grotesque', url: staticFile('fonts/bricolage-grotesque-latin-opsz-normal.woff2'), weight: '200 800' });
for (const weight of ['400', '600', '700', '800']) {
    loadFont({ family: 'Figtree', url: staticFile(`fonts/figtree-latin-${weight}-normal.woff2`), weight });
}

export const C = {
    paper: '#f6f6f3',
    ink: '#0f172a',
    sub: '#475569',
    amber: '#f59e0b',
    indigo: '#6366f1',
    violet: '#8b5cf6',
    emerald: '#10b981',
    rose: '#e11d48',
    marker: '#fde68a',
};

export type ExaminerKey = 'hrd' | 'dosen' | 'investor';

// The three examiners, exactly as on the app's cards (backend/config/exam.php).
export const EXAMINERS: Record<ExaminerKey, {
    name: string;
    title: string;
    mode: string;
    avatar: string;
    color: string;
    rgb: string;
    icon: IconName;
    traits: string[];
    line: string;
}> = {
    hrd: {
        name: 'VOIC-INTERVIEW', title: 'Interview dengan HRD', mode: 'Wawancara Kerja', avatar: 'img/hrd.webp',
        color: C.amber, rgb: '245 158 11', icon: 'briefcase', traits: ['Ceria', 'Ekspresif', 'Hangat'],
        line: 'Wah, seru banget! Ceritain dong.',
    },
    dosen: {
        name: 'VOIC-SIDANG', title: 'Sidang dengan Dosen Penguji', mode: 'Sidang Skripsi', avatar: 'img/dosen.webp',
        color: C.indigo, rgb: '99 102 241', icon: 'academic', traits: ['Berwibawa', 'Tegas', 'Adil'],
        line: 'Datanya. Datanya dulu yang saya tanya.',
    },
    investor: {
        name: 'VOIC-BISNIS', title: 'Pitching Bisnis dengan Investor', mode: 'Pitching Startup', avatar: 'img/investor.webp',
        color: C.violet, rgb: '139 92 246', icon: 'rocket', traits: ['Energik', 'Blak-blakan', 'Gercep'],
        line: 'Oke, oke. Tapi cuannya mana?',
    },
};

export type IconName = 'briefcase' | 'academic' | 'rocket' | 'mic' | 'chat' | 'bolt' | 'qr' | 'wifi-off' | 'gift' | 'user';

// Line icons (stroke = currentColor), the first three copied from the app.
export const ICONS: Record<IconName, string> = {
    briefcase: '<rect x="3" y="7" width="18" height="13" rx="2.5"/><path stroke-linecap="round" d="M8 7V5.5A1.5 1.5 0 019.5 4h5A1.5 1.5 0 0116 5.5V7M3 12.5h18"/>',
    academic: '<path stroke-linejoin="round" d="M12 4L2 9l10 5 10-5-10-5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 11.2V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.8M22 9v5"/>',
    rocket: '<path stroke-linecap="round" stroke-linejoin="round" d="M14 4.5c3-1.2 5.5-.5 5.5-.5s.7 2.5-.5 5.5c-1 2.6-3.6 5.4-6.7 7.2L9.3 13.7C11.1 10.6 11.4 5.5 14 4.5zM9.3 13.7L7 13l-2.5 1.5L7 9.8l3.7-.6M12.3 16.7L13 19l-1.5 2.5 4.7-2.5.6-3.7M6 18c-1 .5-2 2-2 2s1.5-1 2-2z"/><circle cx="15.5" cy="8.5" r="1.3"/>',
    mic: '<rect x="9" y="3" width="6" height="11" rx="3"/><path stroke-linecap="round" d="M5.5 11a6.5 6.5 0 0013 0M12 17.5V21M8.5 21h7"/>',
    chat: '<path stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v8a2.5 2.5 0 01-2.5 2.5H10l-4.5 4v-4h0A1.5 1.5 0 014 14.5z"/><path stroke-linecap="round" d="M8.5 8.5h7M8.5 11.5h4.5"/>',
    bolt: '<path stroke-linejoin="round" d="M13 2.5L4.5 13.5H12l-1 8 8.5-11H12l1-8z"/>',
    qr: '<rect x="3.5" y="3.5" width="6.5" height="6.5" rx="1"/><rect x="14" y="3.5" width="6.5" height="6.5" rx="1"/><rect x="3.5" y="14" width="6.5" height="6.5" rx="1"/><path stroke-linecap="round" d="M14 14h2.5v2.5M20.5 14v6.5H14M17.5 17.5v3"/>',
    'wifi-off': '<path stroke-linecap="round" d="M3 3l18 18M8.5 16.5a5 5 0 017 0M5 12.5a10 10 0 015.2-2.7M19 12.5a10 10 0 00-2.4-1.7M2 9a14.5 14.5 0 016-3.1M22 9a14.5 14.5 0 00-9.4-3.4"/><circle cx="12" cy="20" r="1" fill="currentColor"/>',
    gift: '<rect x="3.5" y="8" width="17" height="4.5" rx="1"/><path d="M5 12.5V20a1 1 0 001 1h12a1 1 0 001-1v-7.5M12 8v13"/><path stroke-linejoin="round" d="M12 8C10.5 4 6.5 4 7 6.5S12 8 12 8zM12 8c1.5-4 5.5-4 5-1.5S12 8 12 8z"/>',
    user: '<circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
};
