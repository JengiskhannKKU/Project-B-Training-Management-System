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

## Email setup (Mailtrap)

You can use Mailtrap to deliver email verification and reset links:

1) Create a Mailtrap account and generate an **Email Sending** API token.
2) Verify your sending domain in Mailtrap, and use a From address on that domain (e.g., `no-reply@yourdomain.com`).
3) Update `.env` with the SDK transport:
```
MAIL_MAILER=mailtrap-sdk
MAILTRAP_HOST=send.api.mailtrap.io
MAILTRAP_API_KEY=<your_sending_api_token>
MAILTRAP_USE_SANDBOX=false
MAILTRAP_INBOX_ID=
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="Training Management"
QUEUE_CONNECTION=sync
```
4) Clear config cache: `php artisan config:clear && php artisan cache:clear`
5) Register and check the Mailtrap Sending inbox for the verification email.

If your domain verification is pending, temporarily set `MAIL_MAILER=log` so mail is written to `storage/logs/laravel.log` until your sending domain is approved.

## Google authentication (OAuth)

Google sign-in is wired via Socialite and the `auth/google` + `auth/google/callback` routes.

1) In Google Cloud Console, create a project (or reuse one) and open **APIs & Services** -> **OAuth consent screen**. Choose **External**, provide app details/support email, and save. Add test users if the app is not published.
2) In **Credentials** -> **Create credentials** -> **OAuth client ID**, choose **Web application**. Add an authorized redirect URI that matches your local callback, e.g. `http://127.0.0.1:8000/auth/google/callback` (adjust if you run `php artisan serve` on another host/port).
3) Place the credentials in `.env`:
```
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URL=http://127.0.0.1:8000/auth/google/callback
```
4) Clear cached config: `php artisan config:clear && php artisan cache:clear`.
5) Use **Continue with Google** on the login/register pages to test; successful sign-in lands on the student dashboard by default.
