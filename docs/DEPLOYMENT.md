# Deployment and Rollback

The production GitHub Actions workflow runs CI and deploys every successful push to `main`. The server-side [`deploy/production.sh`](../deploy/production.sh) script creates immutable releases under `/var/www/pace/releases`, atomically updates `/var/www/pace/current`, retains five releases, and restores the previous application symlink when its health check fails.

## Platform prerequisites

- PHP 8.3 or later with the extensions required by Composer and the selected database driver
- MySQL 8+ or PostgreSQL 15+ for production; SQLite is suitable for local evaluation
- Node.js and npm for building frontend assets
- A process manager for the queue worker
- Cron access for Laravel's scheduler
- TLS certificate and an HTTPS reverse proxy
- Private, durable storage for database backups and catalogue/export files
- SMTP or another configured Laravel mail transport

## Production environment

Create the production `.env` from `.env.example` and keep it outside version control. At minimum, review:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pace.example.org
APP_FORCE_HTTPS=true
APP_TRUSTED_PROXIES=127.0.0.1
LOG_CHANNEL=stack
LOG_STACK=daily,stderr
LOG_LEVEL=warning
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
CACHE_STORE=database
SESSION_DRIVER=database
MAIL_MAILER=smtp
BACKUP_DISK=local
BACKUP_PATH=backups
BACKUP_RETENTION_DAYS=30
```

The backup disk must be private and copied off the application server. Replace the proxy, database, mail, and storage values with hosting-specific settings. Never deploy the development `APP_KEY` or seed credentials.

## First deployment

Run these from the release directory:

```bash
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan admin:create admin@school.example --name="System Administrator"
php artisan system:check
```

Import the client-approved PACE workbook in the administration catalogue screen. Then run `php artisan catalogue:reconcile` and `php artisan system:validate-data`.

The automated first deployment seeds only roles, permissions, and the editable FICA school profile. It deliberately does not create development users, import a catalogue, or add opening stock. Create the first administrator with `admin:create`, then import and approve production data through the application.

## Required processes

Run one scheduler entry under the deployment user:

```cron
* * * * * cd /var/www/pace/current && php artisan schedule:run >> /dev/null 2>&1
```

Run the queue under Supervisor or systemd and restart it after every deployment:

```ini
[program:pace-worker]
command=php /var/www/pace/current/artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600
directory=/var/www/pace/current
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/pace-worker.log
```

Confirm `/up` returns HTTP 200, `/ready` returns HTTP 200, the scheduler and queue-worker heartbeats are current, and no failed jobs are shown on the administrator System status page.

## Subsequent deployment

1. Put the application into maintenance mode with a secret: `php artisan down --refresh=15 --secret="generated-secret"`.
2. Create and externally copy a pre-deployment backup: `php artisan backup:database`.
3. Install locked dependencies and build assets.
4. Run `php artisan migrate --force` and `php artisan optimize`.
5. Restart workers with `php artisan queue:restart`.
6. Run `php artisan system:check` and `php artisan system:validate-data`.
7. Return the application with `php artisan up`, then verify `/up`, `/ready`, login, and one role-specific workflow.

## Rollback

Prefer a forward fix when a deployed migration has written production data. For an application-only rollback, switch the `current` release symlink to the previous release, run `php artisan optimize`, restart workers, and verify health endpoints.

For an incompatible schema change, keep maintenance mode enabled, preserve the failed database as a safety backup, restore the pre-deployment backup with `php artisan backup:restore <backup-path> --force`, switch to the matching application release, restart workers, validate the system, and then run `php artisan up`. Do not use `migrate:rollback` blindly on production data.
