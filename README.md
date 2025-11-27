PHP 8.4.1 (cli) (built: Nov 21 2024 08:58:25) (NTS)
Copyright (c) The PHP Group
Zend Engine v4.4.1, Copyright (c) Zend Technologies
Composer version 2.8.12 2025-09-19 13:41:59
PHP version 8.4.1 (/home/codejeng/.config/herd-lite/bin/php)
Run the "diagnose" command to get more detailed diagnostics output.
v20.19.6
10.8.2

DB_DATABASE=training_management
DB_USERNAME=root
DB_PASSWORD=      # ถ้าไม่มีรหัสให้เว้นว่าง

CREATE DATABASE training_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

#bash
composer install
php artisan key:generate
npm install --legacy-peer-deps

optional-------------------------------------
ถ้าใคร npm ชอบงอแงมาก ๆ ให้ลองลบของเก่าแล้วรันใหม่:
rm -rf node_modules
rm -f package-lock.json
npm install --legacy-peer-deps

php artisan migrate

---------------------------------------------
run project

-Laravel backend-
php artisan serve
npm run dev
