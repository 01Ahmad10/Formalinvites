# FormalEvites

Stage 1 of a formal-event invitation management system. Laravel contains the business rules and PostgreSQL stores the data; Vue, Inertia, TypeScript, and Tailwind provide the browser interface.

## Local setup

1. Copy `.env.example` to `.env` if it does not already exist, then set the PostgreSQL values below. Never commit `.env`.
2. Create database `formal_evites` in PostgreSQL 17.
3. Install dependencies with `composer install` and `npm.cmd install`.
4. Run `php artisan key:generate`, `php artisan migrate --seed`, and `npm.cmd run build`.

The local database configuration is `DB_CONNECTION=pgsql`, host `127.0.0.1`, port `5432`, database `formal_evites`, and user `postgres`. Use your own local password in `DB_PASSWORD`.

## Run the application

Run `php artisan serve` in one terminal and `npm.cmd run dev` in another. Visit the URL shown by Laravel (normally `http://127.0.0.1:8000`).

## Test data (local only)

All seeded accounts use password `password`:

- Admin: `admin@formalevites.test`
- Support: `support@formalevites.test`
- Customer: `maya@formalevites.test`
- Customer: `karim@formalevites.test`

These credentials are only for local development and must never be used in production.

## Useful commands

- Migrations: `php artisan migrate`
- Fresh local data: `php artisan migrate:fresh --seed`
- Tests: `php artisan test`
- Production frontend build: `npm.cmd run build`

## Stage 1 scope

Admin can manage customers, customer users, events, packages, and manual payments. Customers can see and manage only their authorized events. Support can view events but cannot access admin routes. Guests, RSVP, messages, invitation templates, online payments, and all other Stage 2 work are intentionally not included.
