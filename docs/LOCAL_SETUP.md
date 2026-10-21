# ATHAR Host Setup

ATHAR runs on the host machine; CAINE 14.0 remains isolated in a separate VirtualBox virtual machine. Install PHP 8.3+, Composer, MySQL 8-compatible storage, Redis, Node.js 22+, and a current browser. Do not commit real forensic evidence, database dumps, production tokens, or CAINE Agent tokens.

## Quick local start (SQLite)

The committed `.env.example` is optimised for a fast first run: `DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=database`, and the dedicated `private` evidence disk (outside the public web root).

```bash
composer install
npm ci
npm run build                      # required — pages and 10 feature tests fail without Vite assets
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

> **`npm ci && npm run build` is not optional.** Without `public/build/manifest.json`, every Blade page that uses `@vite` throws `Vite manifest not found`, including the workspace UI.

## Production profile (MySQL 8 + Redis)

Create an `athar` MySQL database and a least-privilege `athar_user`, then set the credentials **only in `.env`**:

```dotenv
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=athar
DB_USERNAME=athar_user
DB_PASSWORD=...
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
SESSION_SECURE_COOKIE=true
```

`predis/predis` is a declared Composer dependency, so `QUEUE_CONNECTION=redis` works without the native `phpredis` extension.

## Running the services

Run the authenticated web service, the Redis queue worker, and the Laravel scheduler as separate host-managed processes. In production, use your operating-system service manager rather than a terminal left open manually. The scheduler operates `athar:expire-analysis-jobs`; it transitions only overdue claimed/running work and writes custody/audit records.

```bash
php artisan serve
php artisan queue:work redis --tries=1 --timeout=360
php artisan schedule:work
```

For local HTTP development, `SESSION_SECURE_COOKIE=false` is appropriate. For the host-to-CAINE TLS endpoint, set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and use a TLS certificate trusted by CAINE. Use a Host-only Adapter or Internal Network: CAINE must be able to reach ATHAR, but ATHAR must not expose an inbound service on CAINE. The full one-shot CAINE proof-of-concept procedure is in [CAINE_POC_CHECKLIST.md](CAINE_POC_CHECKLIST.md).

## Verifying the installation

```bash
php artisan test                               # 52 passed (235 assertions)
cd agent && python3 -m unittest discover -v    # 8 passed
composer audit                                 # expect: no known advisories
```
