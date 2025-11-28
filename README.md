# Training Management System

Laravel 12 + Inertia + Vue 3 application for managing training sessions.  
The repository ships with a helper script that provisions both the PHP and
Vite stacks so you can start building immediately.

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+ and npm 10+
- SQLite (already bundled with PHP, only requires file creation)

## Quick start (recommended)

Run the helper script from the project root. It installs Composer/NPM
dependencies, creates `.env`, prepares the SQLite database, runs migrations +
seeders, and builds the front-end assets.

```bash
chmod +x setup.sh
./setup.sh
```

> **Windows:** run the script from Git Bash or WSL (PowerShell cannot execute
> `*.sh` files directly). Example: `bash setup.sh`

The script finishes by reminding you to start the dev servers:

```
php artisan serve
npm run dev
```

## Manual installation (if you cannot run the script)

```bash
composer install
cp .env.example .env         # update values as needed
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
npm install
npm run build                # once for production assets, or skip for dev-only
```

## Daily workflow

- `php artisan serve` – serve the Laravel API/UI.
- `npm run dev` – run Vite dev server with hot reload.
- `php artisan test` – execute the backend test suite.
- `npm run build` – produce production-ready assets (CI / deployment).

If you need to start over locally, delete `database/database.sqlite` and rerun
the migrations (`php artisan migrate --seed`). Happy coding!
