# TICH ERP - cPanel / HostPinnacle deployment

## Correct layout

```
/home3/tichafri/
├── tich-erp/                 ← Git repo root (cPanel "Repository Path")
│   ├── .cpanel.yml
│   ├── deploy/cpanel/
│   └── web/                  ← Laravel app (.env lives here)
│       └── public/           ← css, js, images
└── public_html/              ← Fixed document root for tich.africa
    ├── index.php             ← bridge (copied on deploy)
    ├── .htaccess
    └── css, js, images, storage  ← symlinks into web/public
```

Do **not** put a second full Laravel copy inside `public_html`.

## Deploy

1. Push to GitHub `main`
2. cPanel → **Git Version Control** → repo `/home3/tichafri/tich-erp`
3. **Update from Remote** → **Deploy HEAD Commit**
4. Open `deploy/cpanel/last-deploy.log` if anything fails

## One-time: `.env`

In File Manager create `tich-erp/web/.env` (never commit it):

```env
APP_NAME="TICH ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tich.africa
FORCE_HTTPS=true

APP_KEY=   # generate with: ea-php82 artisan key:generate

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…
```

## Verify

- https://tich.africa/ - homepage
- https://tich.africa/css/tich-platform.css - must be CSS, not HTML
- https://tich.africa/js/tich-nav.js

If the site shows a **TICH deploy error** page, follow the hint on that page (usually missing `.env` or `vendor`).

## Troubleshooting HTTP 500

| Cause | Fix |
|-------|-----|
| Deploy never ran / old HEAD | Update from Remote → Deploy HEAD |
| Missing `web/.env` or `APP_KEY` | Create `.env`, `key:generate` |
| Composer / vendor missing | Re-deploy; check `last-deploy.log` |
| Wrong PHP version | Need PHP 8.2+ (`ea-php82`) |
| Blank 500 after ChatGPT edits | Re-deploy so `public_html/index.php` is replaced from git |
| CSS 404 | Re-deploy (symlinks) - bridge also serves assets as fallback |

Ignore advice that empties `public_html` permanently or rewrites assets via URL-path `.htaccess` rules to `/tich-erp/web/public/…` - that breaks under LiteSpeed/cPanel. Use this repo’s deploy bridge instead.
