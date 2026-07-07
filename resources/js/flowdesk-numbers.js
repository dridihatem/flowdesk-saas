/**
 * Keep Western digits readable in RTL (Arabic) UI and TTS.
 */

const LRI = '\u2066';
const PDI = '\u2069';

/** Wrap number-like runs so they display left-to-right in Arabic UI. */
export function flowdeskIsolateRtlNumbers(text) {
    if (typeof document === 'undefined' || document.documentElement.getAttribute('dir') !== 'rtl') {
        return String(text ?? '');
    }

    return String(text ?? '').replace(
        /(\+?\d[\d\s.,\u00A0'°%]*(?:\s?(?:EUR|USD|TND|QAR|DT|€|\$))?)/gi,
        (match) => {
            if (!/\d/.test(match)) {
                return match;
            }

            return `${LRI}${match}${PDI}`;
        },
    );
}

/** Flatten locale-formatted amounts for clearer speech (2 380,00 → 2380.00). */
export function flowdeskNormalizeNumbersForSpeech(text) {
    let s = String(text ?? '');

    s = s.replace(/(\d{1,3}(?:[\s\u00A0]\d{3})+)(,\d{1,3})?/g, (_, ints, frac) => {
        const digits = ints.replace(/[\s\u00A0]/g, '');

        return frac ? `${digits}.${frac.slice(1)}` : digits;
    });

    s = s.replace(/(?<!\d\.)(\d+),(\d{1,3})(?!\d)/g, '$1.$2');

    return s;
}
