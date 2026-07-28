# Smart Campus Navigation System

A GIS-enabled campus wayfinding application for Southern Leyte State
University – Tomas Oppus Campus. It provides outdoor and indoor navigation,
destination search, GPS-assisted starting points, hazard-aware routing, campus
events, and administrative tools for maintaining map data.

The application builds its routing graphs in the browser from the current
GeoJSON/API data. It does **not** use persisted `nodes` or `edges` database
tables.

## Main features

- Outdoor routing between entry points, buildings, and land-use destinations
- Floor-aware indoor routing to rooms and offices
- GPS, map-picked, and configured entry-point starting locations
- Text and voice destination search
- Blocked-path avoidance and active hazard penalties
- Admin management for maps, entrances, links, keywords, and campus events
- Role-based access for `admin`, `authorized_user`, and `user` accounts
- Inactive-account enforcement, POST logout, API throttling, and response
  caching

## Technology

- PHP 8.3 and Laravel 13
- MySQL/MariaDB or SQLite
- Blade, Vite 8, Tailwind CSS, and Alpine.js
- Leaflet and browser-side JavaScript routing
- PHPUnit, Node's built-in test runner, and Playwright browser tests

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel
- Composer 2
- Node.js 20.19+ or 22.12+
- npm
- MySQL/MariaDB for a shared installation, or SQLite for local development

## Local installation

### 1. Install dependencies

```bash
git clone <repository-url> wayfinding
cd wayfinding
composer install
npm install
```

### 2. Create the environment file

Linux/macOS:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Then generate the application key:

```bash
php artisan key:generate
```

Recommended local values:

```dotenv
APP_NAME="Smart Campus Navigation System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Manila
```

### 3. Configure the database

For SQLite, keep `DB_CONNECTION=sqlite` and create the database file if it does
not exist:

```powershell
New-Item database/database.sqlite -ItemType File
```

For MySQL or MariaDB, create an empty database and update `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wayfinding
DB_USERNAME=root
DB_PASSWORD=
```

Create the tables and development accounts:

```bash
php artisan migrate --seed
```

The current seeder creates these development-only accounts:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@gmail.com` | `111` |
| Authorized User | `authorized@gmail.com` | `111` |
| User | `user@gmail.com` | `111` |

Do not use these credentials in production. Change or replace all seeded
accounts immediately.

### 4. Prepare storage and frontend assets

```bash
php artisan storage:link
npm run build
```

### 5. Run the application

Run the web server, queue listener, log viewer, and Vite development server
together:

```bash
composer run dev
```

Alternatively, run the backend and frontend in separate terminals:

```bash
php artisan serve
npm run dev
```

Open `http://localhost:8000`.

## Common commands

```bash
# Run the PHP test suite
composer test

# Run JavaScript/module tests
npm test

# Run Chrome end-to-end navigation tests
npm run test:e2e

# Build production frontend assets
npm run build

# Enforce the production wayfinding bundle budgets
npm run test:performance

# Simulate 200 snapshot requests with 100 concurrent clients
npm run test:capacity

# Simulate complete guest/authenticated wayfinding journeys
npm run test:capacity:full

# Beginner-friendly local test: double-click this file in Explorer
run-local-capacity-test.cmd

# Run JavaScript, build, bundle-budget, and browser checks
npm run test:all

# Format PHP files
vendor/bin/pint

# Clear Laravel caches during local development
php artisan optimize:clear
```

On Windows without a Unix-compatible shell, run Pint with:

```powershell
php vendor/bin/pint
```

The browser suite expects the application at `http://wayfinding.test` and uses
the seeded `user@gmail.com` / `111` account. Override these values when needed:

```dotenv
E2E_BASE_URL=http://localhost:8000
E2E_USER_EMAIL=user@example.com
E2E_USER_PASSWORD=your-local-password
```

The user dashboard ships its component-based map sources as one minified,
versioned CSS entry and a small initial JavaScript core. Leaflet is included
locally in those production assets, so core map initialization does not depend
on a third-party CDN. Search/voice assistance, live GPS, and GPS diagnostics
are separate chunks fetched only when a signed-in user first opens the matching
feature. Guest sessions never request those account-only chunks. CR assistance
is also loaded on first use, while the route graph remains in the core so
ordinary outdoor and indoor routing behavior is unchanged.
`npm run test:performance` enforces separate size budgets for the initial core
and every lazy feature chunk.

The shared campus geometry is also published as
`public/data/campus-snapshot.json`. A dashboard first tries this single static
file instead of opening thirteen Laravel/database requests. Campus events are
included and filtered in the browser as their start/end times change. If the
file is missing, invalid, or unavailable, the unchanged `/api/*` endpoints are
used automatically. Exact, conversational, and fuzzy destination keyword
matches are resolved from the lazily loaded
`public/data/destination-keywords.json` using the same priority and
building/room context rules as the API.

## GPS field testing and calibration

GPS diagnostics are intentionally hidden from the normal user dashboard. In a
local debug environment, use a real phone over HTTPS and open
`/user/dashboard?gps_diagnostics=1` when validating GPS behavior:

1. Open the user dashboard and press **Use GPS**.
2. Expand the navigation status sheet.
3. Open **Location – Open testing tools**.
4. Press **Start Recording**, then walk a normal campus route.
5. Test an entrance-to-building route, a building-to-building route, and at
   least one indoor destination.
6. Press **Stop** and review the acceptance rate, 95th-percentile accuracy,
   session grade, and recommendation.
7. Press **Export CSV** if the measurements need to be compared or archived.

The diagnostics show accuracy, path offset, heading, speed, quality-lock
progress, and off-route confirmation. Recorded coordinates remain in the
browser's local storage and are not uploaded by the application. **Clear**
removes the saved session from that device.

Recommended field targets:

| Measurement | Target |
| --- | --- |
| Quality lock | Four stable readings at `20m` accuracy or better |
| Accepted readings | At least `80%` during an open-area walk |
| 95th-percentile accuracy | `20m` or better |
| Path snap offset | Within the safe `30m` cap |
| Off-route reroute | Only after three consecutive confirmations |
| Arrival | Within `10m` of the destination |

Repeat the same route at least twice and include areas beside buildings or tree
cover before changing any routing threshold. For home testing, enable the local
GPS simulator with `/user/dashboard?gps_simulator=1`; the same diagnostics and
recording flow is exercised by the automated browser suite. Both diagnostic
URLs require debug mode and remain absent from the normal teacher/user view.

## Offline and installable campus app

The user dashboard is an installable Progressive Web App (PWA). On a supported
browser, open the profile menu and choose **Install Campus App** when the button
appears. Installation and service-worker caching require HTTPS in production;
`localhost` is the browser-approved exception for local development.

After one successful online dashboard load:

- same-origin production app assets are kept for connection interruptions;
- public buildings, paths, entrances, hazards, indoor data, and campus events
  are refreshed from the network first and fall back to saved responses;
- an already-open route can continue using cached campus vectors and device
  GPS when the connection drops;
- the profile menu reports offline readiness and the connection banner reports
  when saved data is active;
- a non-blocking update prompt appears when a new service worker is ready.

For privacy and map-provider compliance, the service worker does **not** cache
authenticated dashboard HTML, profile data, logout requests, destination-search
queries, or third-party OpenStreetMap/CARTO tiles. A cold offline launch therefore
shows the branded offline screen; reconnect once to authenticate and begin a new
session. The campus vector overlay remains usable over its neutral background
during an active offline session.

To verify offline behavior locally, build production assets and serve the app
from `https://wayfinding.test` or from an HTTP `localhost` URL:

```bash
npm run build
php artisan serve
```

Load `/user/dashboard` online once, switch the browser network to **Offline**,
and press **Retry** in the connection banner. Previously loaded campus
destinations should remain available. Return online to refresh recent edits.

## GeoJSON data format

### Common requirements

Unless stated otherwise, an upload must be a non-empty GeoJSON
`FeatureCollection`:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {},
      "geometry": {
        "type": "Point",
        "coordinates": [124.9985, 10.2925]
      }
    }
  ]
}
```

GeoJSON coordinates must use `[longitude, latitude]`, not
`[latitude, longitude]`.

The server validates the following:

- Longitude must be from `-180` through `180`.
- Latitude must be from `-90` through `90`.
- A `LineString` must contain at least two positions.
- Every polygon ring must contain at least four positions.
- The first and last positions of every polygon ring must match.
- Every feature must use the geometry type allowed by its uploader.

Indoor-map boundary uploads are the exception: they may be a raw `Polygon` or
`MultiPolygon`, a single `Feature`, or a `FeatureCollection`.

### Geometry and property matrix

| Dataset | Allowed geometry | Recommended properties |
| --- | --- | --- |
| Buildings | `Polygon`, `MultiPolygon` | `name`, `color` |
| Land uses | `Polygon`, `MultiPolygon` | `name` |
| Outdoor paths | `LineString`, `MultiLineString` | `name`, `type`, `risk_level`, `difficulty_level`, `is_blocked`, `hazard_note` |
| Campus entry points | `Point` | `name` |
| Indoor-map boundary | `Polygon`, `MultiPolygon` | Boundary only; building and floor are selected in the admin form |
| Indoor rooms | `Polygon`, `MultiPolygon` | `name`, `room_code`, `type` |
| Indoor paths | `LineString`, `MultiLineString` | `name`, `path_type`, `is_blocked` |
| Indoor entrances/doors | `Point` | `name`, `ent_type`, `room_code` |

Supported outdoor path settings in the admin interface are:

- `walkway`
- `road`
- `stairs`
- `covered_stairs`

`risk_level` and `difficulty_level` use values from `1` to `3`.
`is_blocked` should be a JSON boolean (`true` or `false`).

### Outdoor path example

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "name": "Library Walkway",
        "type": "walkway",
        "risk_level": 1,
        "difficulty_level": 1,
        "is_blocked": false,
        "hazard_note": null
      },
      "geometry": {
        "type": "LineString",
        "coordinates": [
          [124.9981, 10.2921],
          [124.9984, 10.2923],
          [124.9987, 10.2925]
        ]
      }
    }
  ]
}
```

### Building example

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "name": "Engineering Building",
        "color": "#2b82cc"
      },
      "geometry": {
        "type": "Polygon",
        "coordinates": [
          [
            [124.9980, 10.2920],
            [124.9983, 10.2920],
            [124.9983, 10.2923],
            [124.9980, 10.2923],
            [124.9980, 10.2920]
          ]
        ]
      }
    }
  ]
}
```

### Indoor room and door relationship

Use the same `room_code` on a room polygon and its door point:

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "name": "Computer Laboratory",
        "room_code": "201",
        "type": "laboratory"
      },
      "geometry": {
        "type": "Polygon",
        "coordinates": [
          [
            [124.9980, 10.2920],
            [124.9981, 10.2920],
            [124.9981, 10.2921],
            [124.9980, 10.2921],
            [124.9980, 10.2920]
          ]
        ]
      }
    }
  ]
}
```

The corresponding indoor-entrance feature should use a `Point` geometry and
properties similar to:

```json
{
  "name": "Door 201",
  "ent_type": "door",
  "room_code": "201"
}
```

## Graph and routing rules

### General

- The browser derives graphs from data returned by the API.
- Edges are bidirectional.
- The route is selected with Dijkstra's lowest-total-cost algorithm.
- A route is unavailable when the start or destination cannot be connected to
  the generated graph.

### Outdoor graph

1. `LineString` and `MultiLineString` paths are flattened into individual
   segments.
2. Blocked paths are excluded. A path is treated as blocked when
   `is_blocked`, `blocked`, or a `status` value of `blocked` is present.
3. Crossing segments are automatically split at their intersection.
4. Nearby endpoints and path points are snapped within approximately
   `1.25 m`. Keep genuinely separate paths farther apart to prevent accidental
   connections.
5. Normal walkways and roads use their geographic distance as their base cost.
6. Stairs use `distance × 1.25 + 2`; exact `covered_stairs` use
   `distance × 1.15 + 1`.
7. Active hazard points only affect routing when `affects_routing` is enabled.
   Their current cost multipliers are:

   | Severity | Multiplier |
   | --- | --- |
   | None | `1.0` |
   | 1 | `1.7` |
   | 2 | `4.0` |
   | 3 | `200.0` |

8. `risk_level`, `difficulty_level`, and `hazard_note` are retained as path
   metadata. Current route weighting uses active hazard points rather than the
   stored risk/difficulty values.
9. Building-polygon overlap currently adds no routing penalty. Valid path
   lines may therefore pass through a mapped building footprint when that is
   how the campus data is digitized.

### Indoor graph

1. A graph is built per building from active indoor maps, paths, rooms,
   entrances, and stair links.
2. Blocked indoor paths are excluded.
3. Hallways use geographic distance. Indoor paths whose `path_type` contains
   `stairs` use `distance × 1.25 + 2`.
4. An entrance connects to the nearest path node on the same floor.
5. Rooms connect only through usable door entrances; the router does not draw
   a direct shortcut through a wall.
6. Door selection is attempted in this order:
   - exact room/door `room_code` match;
   - a door inside the room or within `1.8 m` of its boundary;
   - the nearest same-floor door within `8 m` of the room boundary.
7. If no usable door is found, routing to that room is intentionally disabled
   until its door data is corrected.
8. Room nodes may only be used as destinations. A route cannot pass through
   one room as a shortcut to another.
9. Travel between floors requires an explicit link between two indoor
   entrances whose type is `stairs`. Each inter-floor stair link has a cost of
   `6`.
10. A complete room route joins an outdoor building entrance to an indoor
    entrance. Explicit building-entrance links are recommended. Entrance
    selection considers route cost, destination-floor proximity, and
    main/primary entrance preference.

### Preparing reliable map data

- Split intended junctions in QGIS or place them within the snap tolerance.
- Do not draw disconnected lines that merely look connected at the current
  zoom level.
- Keep parallel but unrelated paths more than `1.25 m` apart.
- Mark closures with `is_blocked: true`.
- Place indoor door points on or near the correct room boundary.
- Give rooms and their doors matching, unique `room_code` values.
- Create stair entrance points on every connected floor, then link them in the
  admin interface.
- Create explicit outdoor-to-indoor entrance links for every routable
  building.

## Public API behavior

Public map endpoints are under `/api`, including buildings, paths, entry
points, land uses, indoor datasets, entrance links, search, hazards, and
campus events.

- Map data is limited to `120` requests per minute per device/account, with a
  `12,000` request per-network safety ceiling, and cached for `600` seconds.
- Campus events are cached for `30` seconds.
- Destination search is limited to `30` requests per minute per
  device/account, with a `3,000` request per-network safety ceiling.
- Successful admin data changes invalidate the current map-response cache and
  atomically regenerate the public campus snapshot. A regeneration failure is
  logged and users safely fall back to the existing APIs.

## Production deployment

Use `.env.production.example` as the production template. Set the real
application URL, key, database, mail, and secure infrastructure credentials.
Keep these values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Manila
```

Install and optimize:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan wayfinding:snapshot
php artisan optimize
```

Important production rules:

- Point the web-server document root at `public/`.
- Serve the application through HTTPS. GPS, PWA installation, and offline
  service-worker support are intentionally unavailable on insecure production
  origins.
- Serve `/sw.js` without a long-lived immutable cache. The registration uses
  update checks so users can receive new navigation releases safely.
- Allow the web-server user to write to `storage/` and `bootstrap/cache/`.
- Ensure `public/data/` is writable by PHP so authorized map changes can
  refresh the snapshot. If shared hosting does not permit that, run
  `php artisan wayfinding:snapshot` before uploading and include the generated
  `public/data/campus-snapshot.json` and
  `public/data/destination-keywords.json` files.
- Do not run the development user seeder in production.
- Restart workers with `php artisan queue:restart` after deployments that
  change queued code.
- Monitor `GET /up`, `storage/logs/laravel.log`, and the queue-worker log.

After deployment, run the static `npm run test:capacity` smoke test and the
mixed-journey `npm run test:capacity:full` test from another computer. Remote
tests require explicit ownership confirmation and should progress through
small stages before 500 or 1,000 virtual users. Require zero unexpected
failures and compare p95 response time between releases. These are capacity
tests, not guarantees against a hosting provider's changing bandwidth or
fair-use limits.

The repository includes:

- [`docs/production-deployment.md`](docs/production-deployment.md) for the full
  deployment checklist
- [`docs/capacity-testing.md`](docs/capacity-testing.md) for safe local,
  authenticated, and staged remote load testing
- [`.env.production.example`](.env.production.example) for environment values
- [`deploy/wayfinding-worker.conf`](deploy/wayfinding-worker.conf) for a
  Supervisor-managed queue worker

## Project structure

```text
app/Http/Controllers/       Admin and public API controllers
app/Http/Middleware/        Account, role, cache, and API middleware
app/Rules/                  Shared GeoJSON validation
app/Support/                Wayfinding response-cache support
database/migrations/        Application database schema
public/js/wayfinding/       Browser map, routing, GPS, search, events, and UI modules
public/css/wayfinding/      Component-based map interface styles
resources/views/            Blade pages and layouts
routes/api.php              Public wayfinding API
routes/web.php              Authentication and role-protected web routes
tests/                      PHP and JavaScript regression tests
```

## Security notes

- Only active accounts (`status = 1`) can sign in or continue using an
  authenticated session.
- Role-protected pages return HTTP `403` when the account has the wrong role.
- Logout actions use POST requests with CSRF protection.
- Never commit `.env`, production credentials, uploaded secrets, or private
  keys.
- Keep PHP, Composer, npm packages, the web server, and the database patched.
