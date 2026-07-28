import { readFileSync } from 'node:fs';
import { performance } from 'node:perf_hooks';
import { resolve } from 'node:path';

const REMOTE_CONFIRMATION = 'I_HAVE_PERMISSION';

function positiveNumber(value, fallback, minimum = 1) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? Math.max(minimum, parsed) : fallback;
}

function percentile(values, percentage) {
    if (!values.length) return 0;

    const index = Math.min(
        values.length - 1,
        Math.ceil((percentage / 100) * values.length) - 1,
    );

    return values[index];
}

function isLocalTarget(url) {
    return ['localhost', '127.0.0.1', '::1']
        .includes(url.hostname)
        || url.hostname.endsWith('.test');
}

function decodeHtml(value) {
    return String(value || '')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#039;', "'")
        .replaceAll('&lt;', '<')
        .replaceAll('&gt;', '>');
}

function csrfTokenFrom(html) {
    const input = html.match(
        /<input[^>]+name=["']_token["'][^>]+value=["']([^"']+)["'][^>]*>/i,
    ) || html.match(
        /<input[^>]+value=["']([^"']+)["'][^>]+name=["']_token["'][^>]*>/i,
    );

    return decodeHtml(input?.[1] || '');
}

class CookieJar {
    constructor() {
        this.cookies = new Map();
    }

    absorb(headers) {
        const values = typeof headers.getSetCookie === 'function'
            ? headers.getSetCookie()
            : [headers.get('set-cookie')].filter(Boolean);

        for (const value of values) {
            const pair = String(value).split(';', 1)[0];
            const separator = pair.indexOf('=');
            if (separator < 1) continue;

            this.cookies.set(
                pair.slice(0, separator).trim(),
                pair.slice(separator + 1).trim(),
            );
        }
    }

    header() {
        return [...this.cookies.entries()]
            .map(([name, value]) => `${name}=${value}`)
            .join('; ');
    }
}

function loadCredentials() {
    const credentials = [];
    const usersFile = String(process.env.WAYFINDING_LOAD_USERS_FILE || '').trim();
    const singleEmail = String(process.env.WAYFINDING_LOAD_USER_EMAIL || '').trim();
    const singlePassword = String(process.env.WAYFINDING_LOAD_USER_PASSWORD || '');

    if (usersFile) {
        const document = JSON.parse(readFileSync(resolve(usersFile), 'utf8'));
        if (!Array.isArray(document)) {
            throw new Error('WAYFINDING_LOAD_USERS_FILE must contain a JSON array.');
        }

        for (const entry of document) {
            const email = String(entry?.email || '').trim();
            const password = String(entry?.password || '');
            if (email && password) credentials.push({ email, password });
        }
    }

    if (singleEmail && singlePassword) {
        credentials.push({ email: singleEmail, password: singlePassword });
    }

    return credentials;
}

const target = new URL(
    String(process.env.WAYFINDING_LOAD_BASE_URL || 'http://wayfinding.test')
        .replace(/\/+$/, ''),
);
const virtualUsers = Math.floor(
    positiveNumber(process.env.WAYFINDING_LOAD_USERS, 25),
);
const concurrency = Math.floor(Math.min(
    virtualUsers,
    positiveNumber(process.env.WAYFINDING_LOAD_CONCURRENCY, 10),
));
const timeoutMs = positiveNumber(process.env.WAYFINDING_LOAD_TIMEOUT_MS, 15_000, 1_000);
const thinkTimeMs = positiveNumber(process.env.WAYFINDING_LOAD_THINK_MS, 100, 0);
const searchQuery = String(
    process.env.WAYFINDING_LOAD_SEARCH_QUERY || 'information technology',
).trim();
const destinationToken = String(
    process.env.WAYFINDING_LOAD_DESTINATION_TOKEN || '',
).trim();
const dryRun = process.env.WAYFINDING_LOAD_DRY_RUN === '1';
const credentials = loadCredentials();

if (!isLocalTarget(target)) {
    if (process.env.WAYFINDING_LOAD_CONFIRM !== REMOTE_CONFIRMATION) {
        throw new Error(
            `Remote load testing is blocked. Set WAYFINDING_LOAD_CONFIRM=${REMOTE_CONFIRMATION} `
            +'only when you own or have permission to test the target.',
        );
    }

    if (
        target.protocol !== 'https:'
        && process.env.WAYFINDING_LOAD_ALLOW_INSECURE_REMOTE !== '1'
    ) {
        throw new Error(
            'Remote authenticated testing requires HTTPS. '
            +'Set WAYFINDING_LOAD_ALLOW_INSECURE_REMOTE=1 only for an authorized staging server.',
        );
    }
}

const configSummary = {
    target: target.origin,
    virtual_users: virtualUsers,
    concurrency,
    timeout_ms: timeoutMs,
    think_time_ms: thinkTimeMs,
    public_scenarios: [
        'guest_dashboard',
        'campus_snapshot',
        'campus_events',
        'destination_search',
        ...(destinationToken ? ['destination_link'] : []),
    ],
    authenticated_dashboard: credentials.length > 0,
    credential_count: credentials.length,
};

if (dryRun) {
    console.log(JSON.stringify(configSummary, null, 2));
    process.exit(0);
}

const metrics = new Map();
const failures = [];
let completedUsers = 0;
let nextUser = 0;

function metricFor(name) {
    if (!metrics.has(name)) {
        metrics.set(name, {
            durations: [],
            bytes: 0,
            requests: 0,
            failures: 0,
            statuses: {},
        });
    }

    return metrics.get(name);
}

function recordFailure(name, virtualUser, message) {
    metricFor(name).failures += 1;
    if (failures.length < 20) {
        failures.push({
            scenario: name,
            virtual_user: virtualUser + 1,
            message,
        });
    }
}

async function pause() {
    if (thinkTimeMs <= 0) return;

    const jitter = Math.floor(Math.random() * Math.max(1, thinkTimeMs * 0.35));
    await new Promise(resolve => setTimeout(resolve, thinkTimeMs + jitter));
}

async function requestStep(
    virtualUser,
    name,
    path,
    {
        method = 'GET',
        headers = {},
        body,
        jar,
        redirect = 'follow',
        acceptedStatuses = null,
        validate = null,
    } = {},
) {
    const metric = metricFor(name);
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    const startedAt = performance.now();
    let response;

    try {
        const cookie = jar?.header();
        response = await fetch(new URL(path, target), {
            method,
            headers: {
                Accept: 'text/html,application/json;q=0.9,*/*;q=0.8',
                'User-Agent': `WayfindingCapacityTest/1.0 VU-${virtualUser + 1}`,
                'X-Wayfinding-Capacity-Test': String(virtualUser + 1),
                ...(cookie ? { Cookie: cookie } : {}),
                ...headers,
            },
            body,
            redirect,
            signal: controller.signal,
        });
        jar?.absorb(response.headers);

        const buffer = await response.arrayBuffer();
        const text = new TextDecoder().decode(buffer);
        const duration = performance.now() - startedAt;
        const accepted = acceptedStatuses
            ? acceptedStatuses.includes(response.status)
            : response.ok;

        metric.requests += 1;
        metric.bytes += buffer.byteLength;
        metric.durations.push(duration);
        metric.statuses[response.status] = (metric.statuses[response.status] || 0) + 1;

        if (!accepted) {
            throw new Error(`HTTP ${response.status}`);
        }

        if (validate) {
            await validate({ response, text, buffer });
        }

        return { response, text };
    } catch (error) {
        if (!response) {
            metric.requests += 1;
        }

        const message = error?.name === 'AbortError'
            ? `Timed out after ${timeoutMs}ms`
            : (error instanceof Error ? error.message : String(error));
        recordFailure(name, virtualUser, message);
        throw error;
    } finally {
        clearTimeout(timeout);
    }
}

async function publicJourney(virtualUser, jar) {
    await requestStep(virtualUser, 'guest_dashboard', '/guest', {
        jar,
        validate: ({ text }) => {
            if (!text.includes('WAYFINDING_GUEST_MODE')) {
                throw new Error('Guest dashboard marker was not found.');
            }
        },
    }).catch(() => null);
    await pause();

    await requestStep(
        virtualUser,
        'campus_snapshot',
        '/data/campus-snapshot.json',
        {
            jar,
            validate: ({ text }) => {
                const snapshot = JSON.parse(text);
                if (
                    Number(snapshot?.schema_version) !== 1
                    || !snapshot?.datasets?.['/api/paths']
                ) {
                    throw new Error('Campus snapshot payload is invalid.');
                }
            },
        },
    ).catch(() => null);
    await pause();

    await requestStep(virtualUser, 'campus_events', '/api/campus-events', {
        jar,
        validate: ({ text }) => {
            const events = JSON.parse(text);
            if (!Array.isArray(events)) {
                throw new Error('Campus events payload is not an array.');
            }
        },
    }).catch(() => null);
    await pause();

    await requestStep(
        virtualUser,
        'destination_search',
        `/api/search-destination?q=${encodeURIComponent(searchQuery)}`,
        {
            jar,
            validate: ({ text }) => {
                const result = JSON.parse(text);
                if (!result || typeof result !== 'object') {
                    throw new Error('Destination search payload is invalid.');
                }
            },
        },
    ).catch(() => null);

    if (destinationToken) {
        await pause();
        await requestStep(
            virtualUser,
            'destination_link',
            `/go/${encodeURIComponent(destinationToken)}`,
            {
                jar,
                validate: ({ text }) => {
                    if (!text.includes('WAYFINDING_SHARED_DESTINATION')) {
                        throw new Error('Shared destination marker was not found.');
                    }
                },
            },
        ).catch(() => null);
    }
}

async function authenticatedJourney(virtualUser, credential, jar) {
    if (!credential) return;

    const loginPage = await requestStep(
        virtualUser,
        'auth_login_page',
        '/login',
        { jar },
    ).catch(() => null);

    if (!loginPage) return;

    const csrfToken = csrfTokenFrom(loginPage.text);
    if (!csrfToken) {
        recordFailure('auth_login_page', virtualUser, 'CSRF token was not found.');
        return;
    }

    await pause();

    const form = new URLSearchParams({
        _token: csrfToken,
        email: credential.email,
        password: credential.password,
    });
    const login = await requestStep(
        virtualUser,
        'auth_login_submit',
        '/login',
        {
            method: 'POST',
            jar,
            redirect: 'manual',
            acceptedStatuses: [302, 303],
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Origin: target.origin,
                Referer: new URL('/login', target).href,
            },
            body: form,
            validate: ({ response }) => {
                const location = response.headers.get('location') || '';
                if (!location.includes('/user/dashboard')) {
                    throw new Error(`Login redirected to an unexpected location: ${location || '(none)'}`);
                }
            },
        },
    ).catch(() => null);

    if (!login) return;
    await pause();

    await requestStep(
        virtualUser,
        'authenticated_dashboard',
        '/user/dashboard',
        {
            jar,
            validate: ({ text }) => {
                if (!text.includes('id="map"')) {
                    throw new Error('Authenticated dashboard map was not found.');
                }
            },
        },
    ).catch(() => null);
}

async function runVirtualUser(virtualUser) {
    const jar = new CookieJar();
    await publicJourney(virtualUser, jar);

    if (credentials.length) {
        await pause();
        await authenticatedJourney(
            virtualUser,
            credentials[virtualUser % credentials.length],
            jar,
        );
    }

    completedUsers += 1;
}

async function worker() {
    while (nextUser < virtualUsers) {
        const virtualUser = nextUser;
        nextUser += 1;
        await runVirtualUser(virtualUser);
    }
}

console.log('Wayfinding mixed-journey capacity test');
console.log(JSON.stringify(configSummary, null, 2));

if (!credentials.length) {
    console.warn(
        'Authenticated dashboard scenario skipped. '
        +'Provide WAYFINDING_LOAD_USERS_FILE or WAYFINDING_LOAD_USER_EMAIL/PASSWORD.',
    );
} else if (credentials.length < concurrency) {
    console.warn(
        `${credentials.length} credential(s) will be reused across ${concurrency} concurrent workers. `
        +'Use multiple dedicated load-test accounts for the most realistic session test.',
    );
}

const suiteStartedAt = performance.now();
await Promise.all(Array.from({ length: concurrency }, () => worker()));
const elapsedMs = performance.now() - suiteStartedAt;

const scenarioResults = {};
let totalRequests = 0;
let totalFailures = 0;
let totalBytes = 0;

for (const [name, metric] of metrics.entries()) {
    metric.durations.sort((a, b) => a - b);
    totalRequests += metric.requests;
    totalFailures += metric.failures;
    totalBytes += metric.bytes;

    scenarioResults[name] = {
        requests: metric.requests,
        failed: metric.failures,
        statuses: metric.statuses,
        response_ms: {
            min: Number((metric.durations[0] || 0).toFixed(1)),
            p50: Number(percentile(metric.durations, 50).toFixed(1)),
            p95: Number(percentile(metric.durations, 95).toFixed(1)),
            p99: Number(percentile(metric.durations, 99).toFixed(1)),
            max: Number((metric.durations.at(-1) || 0).toFixed(1)),
        },
        transferred_mib: Number((metric.bytes / 1024 / 1024).toFixed(2)),
    };
}

const result = {
    virtual_users: virtualUsers,
    concurrency,
    completed_users: completedUsers,
    requests: totalRequests,
    failed: totalFailures,
    failure_rate: totalRequests
        ? Number((totalFailures / totalRequests).toFixed(4))
        : 0,
    elapsed_ms: Number(elapsedMs.toFixed(1)),
    requests_per_second: Number((totalRequests / (elapsedMs / 1000)).toFixed(1)),
    transferred_mib: Number((totalBytes / 1024 / 1024).toFixed(2)),
    scenarios: scenarioResults,
};

console.log(JSON.stringify(result, null, 2));

if (failures.length) {
    console.error('First failures:', failures);
    process.exitCode = 1;
}
