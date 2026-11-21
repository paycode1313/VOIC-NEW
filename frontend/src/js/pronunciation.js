// Pelafalan untuk suara browser (cadangan jika server tidak mengirim audio).
// Kamus dan aturannya sama dengan backend/ai_service/pronunciation.py: ubah di sana dan di sini bersamaan.
import lexicon from '../../../backend/ai_service/pronunciation.json';

// "Rp 2,5 juta" -> "2,5 juta rupiah", also a range: "Rp 12-15 juta" -> "12-15 juta rupiah" (then "12 sampai 15").
const RUPIAH_UNIT = /\bRp\.?\s*(\d+(?:[.,]\d+)?(?:\s*-\s*\d+(?:[.,]\d+)?)?\s*(?:ribu|juta|miliar|triliun|thousand|million|billion|trillion))\b/gi;
const RUPIAH = /\bRp\.?\s*(\d{1,3}(?:[.,]\d{3})+|\d+)(?![.,]?\d)/g;
// Hyphens the model writes in place of "-" (non-breaking, figure dash).
const HYPHENS = /[‐‑‒]/g;
// "12-15" / "2025–2026" -> "12 sampai 15": read as a range, not "dua belas strip lima belas".
const RANGE = /(?<![\p{L}\p{N}_.,])(\d+(?:[.,]\d+)?)\s*[-–]\s*(\d+(?:[.,]\d+)?)(?![\p{L}\p{N}_-])/gu;

const escape = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const tables = Object.fromEntries(
    Object.entries(lexicon)
        .filter(([lang]) => !lang.startsWith('_'))
        .map(([lang, words]) => {
            const keys = Object.keys(words).sort((a, b) => b.length - a.length);
            const pattern = new RegExp(`(?<![\\p{L}\\p{N}_])(${keys.map(escape).join('|')})(?![\\p{L}\\p{N}_])`, 'giu');
            const table = new Map(Object.entries(words).map(([k, v]) => [k.toLowerCase(), [k, v]]));
            return [lang, { pattern, table }];
        }),
);

/** Teks yang diucapkan suara browser untuk bahasa ini ('id' atau 'en'); teks di layar tidak berubah. */
export function speechText(text, language = 'id') {
    if (!text) return text;
    const { pattern, table } = tables[language === 'en' ? 'en' : 'id'];
    return text
        .replace(HYPHENS, '-')
        .replace(/(\d)\s+%/g, '$1%')
        .replace(RUPIAH_UNIT, (_, amount) => `${amount} rupiah`)
        .replace(RUPIAH, (_, amount) => `${amount.replace(/[.,]/g, '')} rupiah`)
        .replace(RANGE, (_, from, to) => `${from}${language === 'en' ? ' to ' : ' sampai '}${to}`)
        .replace(pattern, (found) => {
            const [key, spoken] = table.get(found.toLowerCase());
            if (key === key.toUpperCase() && /\p{L}/u.test(key)) return found === key ? spoken : found;
            return found[0] === found[0].toUpperCase() && found[0] !== found[0].toLowerCase() ? spoken[0].toUpperCase() + spoken.slice(1) : spoken;
        });
}
