# Local Development Setup

This repository is designed to run on the host machine, while CAINE 14.0 remains in a separate VirtualBox virtual machine.

## Host requirements

Install PHP 8.3 or newer, Composer, MySQL 8 or compatible MariaDB, Redis, Node.js 22 or newer, and a current browser. Do not store real forensic evidence, production tokens, or a CAINE Agent token in Git.

## Laravel configuration

Copy `.env.example` to `.env`, create a MySQL database named `athar`, and use a local Redis instance for queue work. The required local values are shown below; replace placeholder values only in `.env`, never in committed files.

```dotenv
APP_NAME=ATHAR
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=athar
DB_USERNAME=athar_user
DB_PASSWORD=change-this-locally

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database
REDIS_CLIENT=phpredis

ATHAR_EVIDENCE_DISK=private
ATHAR_AGENT_POLL_PATH=/api/v1/agent/jobs/claim
ATHAR_AGENT_RESULT_LIMIT_BYTES=10485760
```

Install project dependencies with `composer install` and `npm install`, generate the application key using `php artisan key:generate`, run `php artisan migrate`, then use `php artisan queue:work redis` in a second terminal. The authenticated application must run separately from the queue worker.

## VirtualBox network

Use a Host-only Adapter or Internal Network for the first integration. CAINE needs a route to the host ATHAR endpoint but ATHAR must not expose a public inbound port on CAINE. Use a clean CAINE snapshot before adding or testing the Agent, and use benign fixtures only during development.
