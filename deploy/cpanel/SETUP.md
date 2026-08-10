# TICH ERP on cPanel — same pattern as Leysafaris

Leysafaris layout (working reference):

```text
/home2/leylasaf/
├── leysafaris/
│   └── leysafaris/              ← Laravel root (vendor/, bootstrap/, app/)
│       └── public/              ← css/, js/, index.php
└── public_html/                 ← leysafaris domain docroot (or its addon folder)
    ├── index.php                ← absolute path → .../leysafaris/leysafaris
    ├── .htaccess                ← rewrite to index.php
    ├── css  → symlink to .../public/css
    └── js   → symlink to .../public/js
```

TICH ERP equivalent (monorepo — Laravel lives in `web/`):

```text
/home2/leylasaf/
├── tich-erp/                    ← git clone (this repository)
│   └── web/                     ← Laravel root (artisan, vendor/, bootstrap/)
│       └── public/              ← css/, js/, storage/
└── tich.africa/                 ← addon domain folder (typical cPanel name)
    └── public_html/             ← https://tich.africa document root
        ├── index.php            ← absolute path → .../tich-erp/web
        ├── .htaccess
        ├── css  → symlink
        ├── js   → symlink
        ├── images → symlink
        └── storage → symlink
```

If your addon folder is named differently, open **cPanel → Domains → tich.africa → Document Root** and use that path instead of `tich.africa/public_html`.

---

## One-time server setup

### 1. Clone repo (if not done)

```bash
cd ~
git clone YOUR_REPO_URL tich-erp
cd tich-erp/web
cp .env.example .env
# Edit .env — set APP_URL, DB_*, APP_KEY, mail, etc.
php artisan key:generate
```

### 2. Install dependencies

```bash
cd ~/tich-erp/web
/usr/local/bin/ea-php82 /opt/cpanel/composer/bin/composer install --no-dev --optimize-autoloader
/usr/local/bin/ea-php82 artisan migrate --force
/usr/local/bin/ea-php82 artisan storage:link
/usr/local/bin/ea-php82 artisan optimize
```

### 3. `public_html/index.php`

Path: `~/tich.africa/public_html/index.php` (adjust if docroot differs)

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appPath = '/home2/leylasaf/tich-erp/web';

if (file_exists($maintenance = $appPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

### 4. `public_html/.htaccess`

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 5. Symlink public assets (fixes missing CSS/JS)

```bash
cd ~/tich.africa/public_html

ln -sfn /home2/leylasaf/tich-erp/web/public/css css
ln -sfn /home2/leylasaf/tich-erp/web/public/js js
ln -sfn /home2/leylasaf/tich-erp/web/public/images images
ln -sfn /home2/leylasaf/tich-erp/web/public/storage storage
ln -sfn /home2/leylasaf/tich-erp/web/public/favicon.ico favicon.ico
ln -sfn /home2/leylasaf/tich-erp/web/public/robots.txt robots.txt
```

### 6. `.env` production values (`~/tich-erp/web/.env`)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tich.africa
```

Then:

```bash
cd ~/tich-erp/web
/usr/local/bin/ea-php82 artisan config:cache
/usr/local/bin/ea-php82 artisan route:cache
/usr/local/bin/ea-php82 artisan view:cache
```

---

## Git deploy (.cpanel.yml)

Repo root `.cpanel.yml` runs composer + artisan in `web/` after each push.  
Ensure cPanel **Git → Deploy HEAD Commit** uses the repo root (where `.cpanel.yml` lives).

---

## Verify (must not 404)

- https://tich.africa/css/tich-platform.css
- https://tich.africa/js/tich-nav.js
- https://tich.africa/careers

---

## Why you saw broken styling + 404 routes

| Symptom | Missing piece |
|---------|----------------|
| HTML but no CSS/JS | Symlinks (or docroot not pointing at `web/public`) |
| `/careers` 404 | `.htaccess` rewrite rules in `public_html` |

Same fixes applied when Leysafaris was first deployed: **index.php + .htaccess + asset symlinks**.
