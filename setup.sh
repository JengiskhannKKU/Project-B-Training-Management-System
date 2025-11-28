#!/bin/bash

set -euo pipefail

echo "===================================="
echo " Training Management Setup Script"
echo "===================================="

if [ ! -f artisan ]; then
    echo "❌ This script must be run from the project root (artisan not found)."
    exit 1
fi

function require_cmd() {
    local cmd=$1
    local friendly=$2

    if ! command -v "$cmd" >/dev/null 2>&1; then
        echo "❌ $friendly ($cmd) is not installed or not in PATH."
        exit 1
    fi
}

echo "[1/9] Checking required tools..."
require_cmd php "PHP 8.2+"
require_cmd composer "Composer"
require_cmd node "Node.js >= 20"
require_cmd npm "npm"

NODE_MAJOR=$(node -v | sed 's/v\([0-9]*\).*/\1/')
if [ "$NODE_MAJOR" -lt 20 ]; then
    echo "❌ Node version $NODE_MAJOR detected. Please use Node 20 or newer (nvm install 20 && nvm use 20)."
    exit 1
fi

echo "[2/9] Installing PHP dependencies with Composer..."
composer install --no-interaction --prefer-dist

echo "[3/9] Ensuring .env exists..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "   Created .env from .env.example"
else
    echo "   .env already present."
fi

echo "[4/9] Preparing SQLite database file..."
DB_PATH="database/database.sqlite"
if [ ! -f "$DB_PATH" ]; then
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    echo "   Created $DB_PATH"
else
    echo "   $DB_PATH already exists."
fi

echo "[5/9] Generating application key..."
php artisan key:generate --force

echo "[6/9] Running database migrations + seeders..."
php artisan migrate --force --seed

echo "[7/9] Installing Node dependencies..."
npm install

echo "[8/9] Building front-end assets (production build)..."
npm run build

echo "[9/9] Setup complete 🎉"
echo "---------------------------------------"
echo "Start the dev servers in two terminals:"
echo " 1) php artisan serve"
echo " 2) npm run dev"
echo "---------------------------------------"
