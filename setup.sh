#!/bin/bash

echo "===================================="
echo "   Training Management Setup Script  "
echo "===================================="

# 1) Check working directory
echo "[1/10] Checking directory..."
PROJECT_DIR=$(pwd)
echo "Directory: $PROJECT_DIR"

# 2) Check PHP
echo "[2/10] Checking PHP..."
php -v || { echo "❌ PHP not installed. Exiting."; exit 1; }

# 3) Check Composer
echo "[3/10] Checking Composer..."
composer -V || { echo "❌ Composer not installed. Exiting."; exit 1; }

# 4) Check Node version (must be >= 20)
echo "[4/10] Checking Node version..."
node -v || { echo "❌ Node.js not installed. Exiting."; exit 1; }

NODE_MAJOR=$(node -v | grep -oP '(?<=v)\d+(?=\.)')

if [ "$NODE_MAJOR" -lt 20 ]; then
    echo "❌ Node version is too low. Required Node >= 20."
    echo "   If you use nvm:"
    echo "   nvm install 20"
    echo "   nvm use 20"
    exit 1
fi

echo "Node version OK."

# 5) Install Laravel dependencies
echo "[5/10] Installing PHP dependencies..."
composer install || { echo "❌ Composer install failed"; exit 1; }

# 6) Setup .env
echo "[6/10] Setting up .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo ".env created."
else
    echo ".env already exists. Skipping."
fi

# 7) Generate APP_KEY
echo "[7/10] Generating App Key..."
php artisan key:generate

# 8) Install Node packages
echo "[8/10] Installing Node dependencies..."
rm -rf node_modules package-lock.json
npm install --legacy-peer-deps || { echo "❌ npm install failed"; exit 1; }

# 9) Run DB migrations
echo "[9/10] Running migrations..."
php artisan migrate

# 10) Complete
echo "[10/10] Setup complete 🎉"
echo "---------------------------------------"
echo " Run dev servers with:"
echo "   php artisan serve"
echo "   npm run dev"
echo "---------------------------------------"
