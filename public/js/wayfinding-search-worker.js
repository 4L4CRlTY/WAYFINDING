'use strict';

let indexedEntries = [];
let activeVersion = null;

const phrasesToRemove = [
    'i want to go to', 'i wanna go to', 'i need to go to', 'take me to',
    'route me to', 'bring me to', 'navigate to', 'go to', 'where is',
    'find', 'search', 'please', 'room', 'office', 'asa ang',
    'asa dapit ang', 'adto ko sa', 'ganahan ko moadto sa', 'moadto ko sa',
    'dad-a ko sa', 'pangitaa ang', 'pangita ang', 'palihog', 'kwarto',
    'opisina'
];

function normalize(value) {
    let normalized = String(value || '').toLowerCase();
    phrasesToRemove.forEach(phrase => {
        normalized = normalized.replaceAll(phrase, ' ');
    });

    return normalized
        .replace(/[^a-z0-9\s]/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function scoreCandidate(candidate, normalized, queryWords) {
    if (!candidate) return -1;
    if (normalized === candidate) return 2000 + candidate.length;

    const escaped = candidate.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    if (new RegExp(`(^|\\s)${escaped}(\\s|$)`, 'u').test(normalized)) {
        return 1700 + candidate.length;
    }
    if (normalized.includes(candidate)) return 1500 + candidate.length;

    const candidateWords = candidate.split(' ').filter(Boolean);
    const commonWords = queryWords.filter(word => candidateWords.includes(word));
    return commonWords.length ? (commonWords.length * 120) + candidate.length : -1;
}

function rank(query) {
    const normalized = normalize(query);
    if (!normalized) return [];
    const queryWords = normalized.split(' ').filter(Boolean);
    const matches = [];

    indexedEntries.forEach((item, index) => {
        if (!item.hasResult) return;
        let score = scoreCandidate(item.normalizedKeyword, normalized, queryWords);
        if (score < 100) return;
        if (item.destinationType === 'room') score += 350;
        if (item.destinationType === 'building') score += 120;
        matches.push({ index, score, priority: item.priority });
    });

    matches.sort((left, right) =>
        right.score - left.score
        || right.priority - left.priority
        || left.index - right.index
    );
    return matches.map(({ index, score }) => ({ index, score }));
}

self.addEventListener('message', event => {
    const message = event.data || {};

    if (message.type === 'init') {
        activeVersion = message.version ?? null;
        indexedEntries = (Array.isArray(message.entries) ? message.entries : []).map(entry => ({
            normalizedKeyword: normalize(entry?.keyword),
            destinationType: String(entry?.destination_type || ''),
            priority: Number(entry?.priority || 0),
            hasResult: Boolean(entry?.result)
        }));
        self.postMessage({ type: 'ready', version: activeVersion });
        return;
    }

    if (message.type !== 'search') return;

    try {
        self.postMessage({
            type: 'result',
            requestId: message.requestId,
            version: activeVersion,
            matches: rank(message.query)
        });
    } catch (error) {
        self.postMessage({
            type: 'error',
            requestId: message.requestId,
            code: 'SEARCH_WORKER_FAILED',
            message: error instanceof Error ? error.message : 'Search worker failed.'
        });
    }
});
