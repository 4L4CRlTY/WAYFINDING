# Wayfinding capacity testing

## Easiest local test

For a beginner-friendly local check:

1. Open Laragon and click **Start All**.
2. Open the project folder.
3. Double-click `run-local-capacity-test.cmd`.
4. Wait for the summary and look for `"failed": 0`.

The one-click runner safely targets `http://wayfinding.test`, defaults to 10
virtual users with concurrency 5, and pauses so the result can be
screenshotted. It always uses the seeded local test account
`user@gmail.com` / `111`, ignores custom credential files, and never targets
Hostinger.

Pass the total users and concurrency as arguments instead of editing the file:

```powershell
# Safe baseline
.\run-local-capacity-test.cmd 10 5

# Heavy local checks
.\run-local-capacity-test.cmd 500 50
.\run-local-capacity-test.cmd 1000 50
```

Keep local concurrency at 50 or below. The local `.env` uses the request-local
`array` cache for stress testing because Windows Apache can deny simultaneous
locks on Laravel filesystem cache files. The production template remains on
the persistent `file` cache for Hostinger's Linux environment. Database
sessions remain enabled in both environments.

The full capacity test simulates an HTTP journey for many virtual users:

1. Open the guest dashboard.
2. Download the public campus snapshot.
3. Load current campus events.
4. Search for a destination.
5. Open a shared destination link when a token is configured.
6. Log in and open the authenticated user dashboard when test credentials are configured.

It measures each step separately and reports HTTP statuses, failures,
transferred bytes, and p50/p95/p99 response times.

The test does not execute Leaflet or GPS in a browser. Continue using the
Playwright suite and physical-phone testing for rendering smoothness.

## Local baseline

Start Laragon, then run from PowerShell:

```powershell
cd C:\laragon\www\wayfinding

$env:WAYFINDING_LOAD_BASE_URL='http://wayfinding.test'
$env:WAYFINDING_LOAD_USERS='25'
$env:WAYFINDING_LOAD_CONCURRENCY='10'
npm run test:capacity:full
```

## Authenticated dashboard scenario

Never use an administrator account or a real user's password. Create dedicated,
active accounts with the normal `user` role.

Copy `load-test-users.example.json` to `load-test-users.json`, replace the
example credentials, and keep that file local. It is excluded by `.gitignore`.

```powershell
$env:WAYFINDING_LOAD_USERS_FILE='load-test-users.json'
npm run test:capacity:full
```

For a small private check, one test account can be supplied without a file:

```powershell
$env:WAYFINDING_LOAD_USER_EMAIL='loadtest@example.com'
$env:WAYFINDING_LOAD_USER_PASSWORD='test-account-password'
npm run test:capacity:full
```

Credentials are assigned round-robin. Multiple dedicated accounts are more
realistic than reusing one account for every concurrent session.

## Shared destination link scenario

Provide the token portion after `/go/`:

```powershell
$env:WAYFINDING_LOAD_DESTINATION_TOKEN='paste-active-token-here'
npm run test:capacity:full
```

When omitted, this optional scenario is reported as disabled.

## Authorized remote test

Remote load testing is blocked unless permission is explicitly confirmed.
Only test a domain you own or are authorized to test.

```powershell
$env:WAYFINDING_LOAD_BASE_URL='https://your-domain.example'
$env:WAYFINDING_LOAD_CONFIRM='I_HAVE_PERMISSION'
$env:WAYFINDING_LOAD_USERS_FILE='load-test-users.json'
$env:WAYFINDING_LOAD_SEARCH_QUERY='information technology'
```

Use a quiet testing window and increase traffic gradually:

```powershell
# Baseline
$env:WAYFINDING_LOAD_USERS='10'
$env:WAYFINDING_LOAD_CONCURRENCY='10'
npm run test:capacity:full

# Stage 2
$env:WAYFINDING_LOAD_USERS='100'
$env:WAYFINDING_LOAD_CONCURRENCY='25'
npm run test:capacity:full

# Stage 3
$env:WAYFINDING_LOAD_USERS='250'
$env:WAYFINDING_LOAD_CONCURRENCY='50'
npm run test:capacity:full

# Run 500 and 1,000 only after every earlier stage has zero unexpected failures.
$env:WAYFINDING_LOAD_USERS='500'
$env:WAYFINDING_LOAD_CONCURRENCY='100'
npm run test:capacity:full
```

Monitor CPU, RAM, I/O, PHP workers, database connections, and 429/500/503
responses in hPanel while the test is running. A failure in one scenario does
not stop the remaining scenarios, so the final report shows which endpoint
became the bottleneck.

## Shared campus Wi-Fi throttling

Public wayfinding requests receive a long-lived, HttpOnly
`wayfinding_client_id` cookie. Rate limits use that device identity (or the
authenticated account ID) instead of counting every device on the same campus
Wi-Fi IP as one user.

The current limits are:

- Map APIs: 120 requests per device per minute, with a 12,000-request
  per-network safety ceiling.
- Destination search: 30 requests per device per minute, with a 3,000-request
  per-network safety ceiling.
- Guest and shared-link pages: 60 requests per device per minute, with a
  3,000-request per-network safety ceiling.

The load tester keeps one cookie jar per virtual user, so a same-machine test
accurately exercises many devices sharing one source IP. HTTP 429 indicates a
rate-limit failure; HTTP 500 indicates a separate application, database, or
server-worker failure that must be checked in `storage/logs/laravel.log`.

## Configuration

| Environment variable | Default | Purpose |
| --- | --- | --- |
| `WAYFINDING_LOAD_BASE_URL` | `http://wayfinding.test` | Target origin |
| `WAYFINDING_LOAD_USERS` | `25` | Total virtual-user journeys |
| `WAYFINDING_LOAD_CONCURRENCY` | `10` | Journeys running at the same time |
| `WAYFINDING_LOAD_TIMEOUT_MS` | `15000` | Per-request timeout |
| `WAYFINDING_LOAD_THINK_MS` | `100` | Small delay plus jitter between actions |
| `WAYFINDING_LOAD_SEARCH_QUERY` | `information technology` | Search phrase |
| `WAYFINDING_LOAD_USERS_FILE` | empty | Private JSON credential file |
| `WAYFINDING_LOAD_DESTINATION_TOKEN` | empty | Active `/go/{token}` token |
| `WAYFINDING_LOAD_CONFIRM` | empty | Must equal `I_HAVE_PERMISSION` remotely |

For an initial pass, require zero network errors and zero HTTP 429, 500, or 503
responses. Compare p95 latency and hPanel resource usage after every code or
hosting change.
