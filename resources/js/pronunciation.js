// Pelafalan untuk suara browser (cadangan jika server tidak mengirim audio).
// Kamus dan aturannya sama dengan ai_service/pronunciation.py: ubah di sana dan di sini bersamaan.
import lexicon from '../../ai_service/pronunciation.json';

const RUPIAH_UNIT = /\bRp\.?\s*(\d+(?:[.,]\d+)?\s*(?:ribu|juta|miliar|triliun|thousand|million|billion|trillion))\b/gi;
const RUPIAH = /\bRp\.?\s*(\d{1,3}(?:[.,]\d{3})+|\d+)(?![.,]?\d)/g;

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
        .replace(RUPIAH_UNIT, (_, amount) => `${amount} rupiah`)
        .replace(RUPIAH, (_, amount) => `${amount.replace(/[.,]/g, '')} rupiah`)
        .replace(pattern, (found) => {
            const [key, spoken] = table.get(found.toLowerCase());
            if (key === key.toUpperCase() && /\p{L}/u.test(key)) return found === key ? spoken : found;
            return found[0] === found[0].toUpperCase() && found[0] !== found[0].toLowerCase() ? spoken[0].toUpperCase() + spoken.slice(1) : spoken;
        });
}
