# Production deployment

## Environment

Copy `.env.production.example` to `.env` on the production server and set:

- `APP_KEY` with `php artisan key:generate`
- the real HTTPS `APP_URL`
- production database credentials
- production mail credentials

Keep `APP_ENV=production`, `APP_DEBUG=false`, and
`APP_TIMEZONE=Asia/Manila`.

Do not commit the production `.env` file.

## Deploy

Run these commands from the application directory:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

The web-server user must be able to write to `storage` and
`bootstrap/cache`.

After changing `.env`, configuration, or routes:

```bash
php artisan optimize:clear
php artisan optimize
```

## Queue worker

Copy `deploy/wayfinding-worker.conf` into Supervisor's configuration
directory and update its application path and user when necessary.

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart wayfinding-worker:*
```

Deployments that change queued job code should run:

```bash
php artisan queue:restart
```

## Health and operations

- Configure the web-server document root to `public/`.
- Serve the application over HTTPS.
- Keep `/sw.js` revalidating (`Cache-Control: no-cache`) instead of applying
  the immutable cache rule used for hashed `/build/assets/*` files.
- Monitor `GET /up` for application health.
- Monitor `storage/logs/laravel.log` and `storage/logs/worker.log`.
- Schedule Laravel's scheduler once per minute if scheduled tasks are added:

```cron
* * * * * cd /var/www/wayfinding && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## PWA and offline navigation

The installed campus app requires a secure HTTPS origin. After deployment:

1. Open `/user/dashboard` on a phone while online.
2. Confirm the profile menu reports **Offline-ready**.
3. Install the app when **Install Campus App** becomes available.
4. Load at least one route, disable the network, and confirm that saved campus
   destinations and GPS guidance remain available in the open session.
5. Cold-open the app while offline and confirm the privacy-safe offline screen
   appears instead of cached account HTML.

The service worker caches only same-origin built assets and public campus API
responses. Do not add authenticated pages, profile photos, logout/CSRF
responses, destination-search queries, or third-party map tiles to its cache.

Example Nginx rules:

```nginx
location = /sw.js {
    add_header Cache-Control "no-cache";
    try_files $uri =404;
}

location /build/assets/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
    try_files $uri =404;
}
```
