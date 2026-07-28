import { performance } from 'node:perf_hooks';

const baseUrl = String(process.env.WAYFINDING_LOAD_BASE_URL || 'http://wayfinding.test')
    .replace(/\/+$/, '');
const requestCount = Math.max(1, Number(process.env.WAYFINDING_LOAD_REQUESTS || 200));
const concurrency = Math.max(
    1,
    Math.min(requestCount, Number(process.env.WAYFINDING_LOAD_CONCURRENCY || 100)),
);
const timeoutMs = Math.max(1000, Number(process.env.WAYFINDING_LOAD_TIMEOUT_MS || 15000));
const url = `${baseUrl}/data/campus-snapshot.json`;

const durations = [];
const errors = [];
let nextRequest = 0;
let transferredBytes = 0;

function percentile(values, percentage) {
    if (!values.length) return 0;
    const index = Math.min(
        values.length - 1,
        Math.ceil((percentage / 100) * values.length) - 1,
    );
    return values[index];
}

async function requestSnapshot(index) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    const startedAt = performance.now();

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Wayfinding-Capacity-Test': String(index + 1),
            },
            signal: controller.signal,
        });
        const body = await response.arrayBuffer();
        const duration = performance.now() - startedAt;

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const snapshot = JSON.parse(new TextDecoder().decode(body));
        if (snapshot?.schema_version !== 1 || !snapshot?.datasets?.['/api/paths']) {
            throw new Error('Invalid campus snapshot payload');
        }

        durations.push(duration);
        transferredBytes += body.byteLength;
    } catch (error) {
        errors.push({
            request: index + 1,
            message: error instanceof Error ? error.message : String(error),
        });
    } finally {
        clearTimeout(timeout);
    }
}

async function worker() {
    while (nextRequest < requestCount) {
        const index = nextRequest;
        nextRequest += 1;
        await requestSnapshot(index);
    }
}

console.log(
    `Testing ${url} with ${requestCount} requests and ${concurrency} concurrent clients...`,
);

const suiteStartedAt = performance.now();
await Promise.all(Array.from({ length: concurrency }, () => worker()));
const elapsedMs = performance.now() - suiteStartedAt;
durations.sort((a, b) => a - b);

const result = {
    requests: requestCount,
    concurrency,
    successful: durations.length,
    failed: errors.length,
    elapsed_ms: Number(elapsedMs.toFixed(1)),
    requests_per_second: Number((durations.length / (elapsedMs / 1000)).toFixed(1)),
    response_ms: {
        min: Number((durations[0] || 0).toFixed(1)),
        p50: Number(percentile(durations, 50).toFixed(1)),
        p95: Number(percentile(durations, 95).toFixed(1)),
        p99: Number(percentile(durations, 99).toFixed(1)),
        max: Number((durations.at(-1) || 0).toFixed(1)),
    },
    transferred_mib: Number((transferredBytes / 1024 / 1024).toFixed(2)),
};

console.log(JSON.stringify(result, null, 2));

if (errors.length) {
    console.error('First failures:', errors.slice(0, 5));
    process.exitCode = 1;
}
