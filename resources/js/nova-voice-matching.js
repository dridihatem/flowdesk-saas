import { flowdeskNormalizeVoicePhrase } from './ai-voice';

const COMMAND_PREFIXES = [
    'go to', 'open', 'show', 'navigate to', 'take me to',
    'aller a', 'aller à', 'ouvrir', 'ouvre', 'affiche',
    'ajouter', 'ajoute', 'ajout', 'nouveau', 'nouvelle', 'nouvel',
    'creer', 'créer', 'montre', 'va a', 'va à',
    'page', 'list', 'liste', 'the', 'la', 'le', 'les', 'a', 'à',
];

function splitWords(text) {
    if (!text) {
        return [];
    }
    return text.split(/\s+/).filter(Boolean);
}

function stripCommandFillers(text) {
    let n = flowdeskNormalizeVoicePhrase(text);
    let changed = true;
    while (changed) {
        changed = false;
        for (const prefix of COMMAND_PREFIXES) {
            if (n.startsWith(`${prefix} `)) {
                n = n.slice(prefix.length + 1).trim();
                changed = true;
            }
            if (n.endsWith(` ${prefix}`)) {
                n = n.slice(0, -(prefix.length + 1)).trim();
                changed = true;
            }
        }
    }

    return n.replace(/\s+page$/u, '').replace(/\s+list$/u, '').trim();
}

export function stripWakeFromText(text, wakePhrases) {
    let normalized = flowdeskNormalizeVoicePhrase(text);
    for (const phrase of wakePhrases) {
        if (normalized.startsWith(phrase)) {
            return normalized.slice(phrase.length).trim();
        }
        if (normalized.includes(phrase)) {
            return normalized.replace(phrase, '').trim();
        }
    }

    return normalized;
}

function commandQueryVariantsFromNormalized(normalized, { includeStripped = true } = {}) {
    const base = flowdeskNormalizeVoicePhrase(normalized);
    if (!base) {
        return [];
    }

    const variants = [base];
    if (includeStripped) {
        const stripped = stripCommandFillers(base);
        if (stripped && stripped !== base) {
            variants.push(stripped);
        }
    }

    return variants;
}

export function commandQueryVariants(text, wakePhrases, { includeStripped = true } = {}) {
    return commandQueryVariantsFromNormalized(stripWakeFromText(text, wakePhrases), { includeStripped });
}

function wordsInOrder(queryWords, phraseWords) {
    let phraseIndex = 0;
    for (const word of queryWords) {
        while (phraseIndex < phraseWords.length && phraseWords[phraseIndex] !== word) {
            phraseIndex++;
        }
        if (phraseIndex >= phraseWords.length) {
            return false;
        }
        phraseIndex++;
    }

    return true;
}

function scorePhraseMatch(query, phrase, phraseWords) {
    if (!query || !phrase) {
        return 0;
    }
    if (query === phrase) {
        return 10000;
    }
    if (query.includes(phrase)) {
        return 5000 + phrase.length;
    }
    if (query.length >= 3 && phrase.startsWith(query) && phrase.length > query.length && phrase[query.length] === ' ') {
        return 3500 + query.length;
    }

    const qWords = splitWords(query);
    const pWords = phraseWords || splitWords(phrase);

    if (qWords.length >= 2 && pWords.length > qWords.length) {
        const tail = pWords.slice(-qWords.length);
        if (tail.join(' ') === qWords.join(' ')) {
            return 7500 + qWords.length * 50;
        }
    }

    if (qWords.length >= 2 && wordsInOrder(qWords, pWords)) {
        return 4500 + qWords.length * 100 - Math.abs(pWords.length - qWords.length) * 10;
    }

    if (qWords.length === 1 && pWords.length === 1) {
        const q = qWords[0];
        const p = pWords[0];
        if (q.endsWith('s') && q.slice(0, -1) === p) {
            return 8000;
        }
        if (p.endsWith('s') && p.slice(0, -1) === q) {
            return 8000;
        }

        return 0;
    }

    if (qWords.length === 1) {
        const w = qWords[0];
        if (!pWords.includes(w)) {
            const plural = w.endsWith('s') ? w.slice(0, -1) : `${w}s`;
            if (!pWords.includes(plural)) {
                return 0;
            }
        }

        return 800 - pWords.length;
    }

    if (pWords.length === 1 && qWords.includes(pWords[0])) {
        return 600;
    }

    return 0;
}

function enrichRow(phrase, cmd) {
    const words = splitWords(phrase);

    return {
        phrase,
        words,
        firstWord: words[0] || '',
        cmd,
    };
}

export function buildCommandIndex(commands) {
    const rows = [];
    const exact = new Map();

    for (const cmd of commands || []) {
        for (const phraseRaw of cmd.phrases || []) {
            const phrase = flowdeskNormalizeVoicePhrase(phraseRaw);
            if (!phrase) {
                continue;
            }
            const row = enrichRow(phrase, cmd);
            rows.push(row);
            if (!exact.has(phrase)) {
                exact.set(phrase, row);
            }
        }
    }

    rows.sort((a, b) => b.phrase.length - a.phrase.length);

    return { rows, exact };
}

export function buildWorkflowIndex(workflows) {
    const rows = [];
    const exact = new Map();

    for (const workflow of workflows || []) {
        for (const phraseRaw of workflow.phrases || []) {
            const phrase = flowdeskNormalizeVoicePhrase(phraseRaw);
            if (!phrase) {
                continue;
            }
            const row = enrichRow(phrase, workflow);
            rows.push(row);
            if (!exact.has(phrase)) {
                exact.set(phrase, row);
            }
        }
    }

    rows.sort((a, b) => b.phrase.length - a.phrase.length);

    return { rows, exact };
}

export function buildBriefingIndex(phrases) {
    const rows = (phrases || [])
        .map((phrase) => enrichRow(flowdeskNormalizeVoicePhrase(phrase), null))
        .filter((row) => row.phrase);

    rows.sort((a, b) => b.phrase.length - a.phrase.length);

    return { rows, exact: new Map(rows.map((row) => [row.phrase, row])) };
}

function candidateRows(query, index) {
    const exact = index.exact.get(query);
    if (exact) {
        return [exact];
    }

    const queryWords = splitWords(query);
    if (queryWords.length === 0) {
        return [];
    }

    const queryWordSet = new Set(queryWords);
    const candidates = [];

    for (const row of index.rows) {
        if (query.includes(row.phrase)) {
            candidates.push(row);
            continue;
        }
        if (row.words.length === 1) {
            const word = row.words[0];
            if (queryWordSet.has(word)) {
                candidates.push(row);
            }
            continue;
        }
        if (queryWordSet.has(row.firstWord) || row.phrase.startsWith(`${query} `) || (query.length >= 3 && row.phrase.startsWith(query))) {
            candidates.push(row);
        }
    }

    return candidates.length > 0 ? candidates : index.rows;
}

export function bestPhraseMatch(query, index) {
    if (!query || !index?.rows?.length) {
        return null;
    }

    const exact = index.exact.get(query);
    if (exact) {
        return { ...exact, score: 10000, query };
    }

    let best = null;
    let bestScore = 0;
    const rows = candidateRows(query, index);

    for (const row of rows) {
        const score = scorePhraseMatch(query, row.phrase, row.words);
        if (score > bestScore) {
            bestScore = score;
            best = row;
        } else if (score > 0 && score === bestScore && best) {
            const diffA = Math.abs(row.phrase.length - query.length);
            const diffB = Math.abs(best.phrase.length - query.length);
            if (diffA < diffB) {
                best = row;
            }
        }
        if (bestScore === 10000) {
            break;
        }
    }

    if (!best) {
        return null;
    }

    return { ...best, score: bestScore, query };
}

export function resolveBestPhraseMatch(text, wakePhrases, index, { fromNormalized = false, includeStripped = true } = {}) {
    const variants = fromNormalized
        ? commandQueryVariantsFromNormalized(text, { includeStripped })
        : commandQueryVariants(text, wakePhrases, { includeStripped });

    let best = null;
    for (const query of variants) {
        if (!query || query.length < 2) {
            continue;
        }

        const match = bestPhraseMatch(query, index);
        if (match && (!best || match.score > best.score)) {
            best = match;
        }
        if (best?.score === 10000) {
            break;
        }
    }

    return best;
}

export function isPartialBriefingMatch(normalized, briefingIndex) {
    if (!normalized) {
        return false;
    }

    for (const row of briefingIndex.rows) {
        const phrase = row.phrase;
        if (phrase === normalized) {
            continue;
        }
        if (phrase.startsWith(`${normalized} `) || (phrase.startsWith(normalized) && phrase.length > normalized.length)) {
            return true;
        }
    }

    return false;
}

export function isPartialCommandMatch(normalized, commandIndex) {
    if (!normalized) {
        return false;
    }

    let prefixMatches = 0;
    for (const row of commandIndex.rows) {
        const phrase = row.phrase;
        if (phrase === normalized) {
            continue;
        }
        if (phrase.startsWith(`${normalized} `) || (phrase.startsWith(normalized) && phrase.length > normalized.length)) {
            prefixMatches++;
            if (prefixMatches > 1) {
                return true;
            }
        }
    }

    return prefixMatches === 1;
}

export function shouldExecuteCommand(query, isFinal, briefingIndex, commandIndex) {
    const variants = commandQueryVariantsFromNormalized(query, { includeStripped: isFinal });
    if (variants.length === 0) {
        return false;
    }

    let bestBriefing = null;
    let bestNav = null;
    for (const normalized of variants) {
        if (!normalized || normalized.length < 2) {
            continue;
        }

        const briefing = bestPhraseMatch(normalized, briefingIndex);
        if (briefing && (!bestBriefing || briefing.score > bestBriefing.score)) {
            bestBriefing = briefing;
        }

        const nav = bestPhraseMatch(normalized, commandIndex);
        if (nav && (!bestNav || nav.score > bestNav.score)) {
            bestNav = nav;
        }
    }

    if (bestBriefing && bestBriefing.score >= 5000) {
        return true;
    }

    if (!isFinal) {
        for (const normalized of variants) {
            if (isPartialBriefingMatch(normalized, briefingIndex)) {
                return false;
            }
            if (isPartialCommandMatch(normalized, commandIndex)) {
                return false;
            }
        }

        if (!bestNav) {
            return false;
        }

        if (bestNav.score >= 10000) {
            return true;
        }

        const phraseWords = bestNav.words;
        const queryWords = splitWords(bestNav.query || variants[0] || '');

        if (phraseWords.length === 1 && queryWords.length <= 1 && bestNav.score >= 8000) {
            return true;
        }

        return false;
    }

    return true;
}

export function matchesDirectCommand(normalized, commandIndex) {
    const variants = commandQueryVariantsFromNormalized(normalized);
    for (const query of variants) {
        if (bestPhraseMatch(query, commandIndex)) {
            return true;
        }
    }

    return false;
}

export function resolveCommandIntent(text, wakePhrases, indices, { isFinal = true } = {}) {
    const variants = commandQueryVariants(text, wakePhrases, { includeStripped: isFinal });
    if (variants.length === 0) {
        return { variants, briefing: null, workflow: null, command: null };
    }

    let briefing = null;
    let workflow = null;
    let command = null;

    for (const query of variants) {
        if (!query || query.length < 2) {
            continue;
        }

        const briefingHit = bestPhraseMatch(query, indices.briefing);
        if (briefingHit && (!briefing || briefingHit.score > briefing.score)) {
            briefing = briefingHit;
        }

        const workflowHit = bestPhraseMatch(query, indices.workflow);
        if (workflowHit && (!workflow || workflowHit.score > workflow.score)) {
            workflow = workflowHit;
        }

        const commandHit = bestPhraseMatch(query, indices.command);
        if (commandHit && (!command || commandHit.score > command.score)) {
            command = commandHit;
        }

        if (briefing?.score === 10000 && workflow?.score === 10000 && command?.score === 10000) {
            break;
        }
    }

    return { variants, briefing, workflow, command };
}
