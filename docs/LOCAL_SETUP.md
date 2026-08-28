# ATHAR Host Setup

ATHAR runs on the host machine; CAINE 14.0 remains isolated in a separate VirtualBox virtual machine. Install PHP 8.3+, Composer, MySQL 8-compatible storage, Redis, Node.js 22+, and a current browser. Do not commit real forensic evidence, database dumps, production tokens, or CAINE Agent tokens.

Copy `.env.example` to `.env`, create an `athar` MySQL database and a least-privilege `athar_user`, then set the local credentials only in `.env`. The committed template now defaults to MySQL, Redis queue/cache, and the dedicated private disk. Generate the application key, install dependencies, migrate the schema, and seed the controlled tool catalogue.

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
```

Run the authenticated web service, Redis queue worker, and Laravel scheduler as separate host-managed processes. In production, use your operating-system service manager rather than a terminal left open manually. The scheduler operates `athar:expire-analysis-jobs`; it transitions only overdue claimed/running work and writes custody/audit records.

```bash
php artisan serve
php artisan queue:work redis --tries=1 --timeout=360
php artisan schedule:work
```

For local HTTP development, `SESSION_SECURE_COOKIE=false` is appropriate. For the host-to-CAINE TLS endpoint, set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and use a TLS certificate trusted by CAINE. Use a Host-only Adapter or Internal Network: CAINE must be able to reach ATHAR, but ATHAR must not expose an inbound service on CAINE. The full one-shot CAINE proof-of-concept procedure is in [CAINE_POC_CHECKLIST.md](CAINE_POC_CHECKLIST.md).
