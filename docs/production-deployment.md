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
- Monitor `GET /up` for application health.
- Monitor `storage/logs/laravel.log` and `storage/logs/worker.log`.
- Schedule Laravel's scheduler once per minute if scheduled tasks are added:

```cron
* * * * * cd /var/www/wayfinding && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```
