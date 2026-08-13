# TICH ERP - cPanel Git deployment

Everything needed for production lives in this repo. **No manual File Manager steps.**

## Deploy

1. Commit and push to GitHub
2. **cPanel → Git Version Control → Update from Remote → Deploy HEAD Commit**

Each deploy automatically:

- Runs Composer, migrations, and Laravel optimize
- Copies `index.php` + `.htaccess` into `public_html` (from `deploy/cpanel/`)
- Links `css/`, `js/`, and `images/` from `web/public/` into `public_html`

## Paths (committed in git)

cPanel shows **Document Root: `/public_html`** - that is relative to your home folder, i.e. **`/home3/tichafri/public_html`** (same pattern as Leysafaris on `leylasaf`).

| | Leysafaris | TICH |
|---|------------|------|
| Document root | `/home2/leylasaf/public_html` | `/home3/tichafri/public_html` |
| Laravel root | `.../leysafaris/leysafaris` | `.../tich-erp/web` |

| File | Purpose |
|------|---------|
|------|---------|
| `deploy/cpanel/docroot.txt` | Document root: `/home3/tichafri/public_html` |
| `deploy/cpanel/public_html.index.php` | Points Laravel to `/home3/tichafri/tich-erp/web` |
| `deploy/cpanel/public_html.htaccess` | URL rewriting for Laravel routes |
| `deploy/cpanel/last-asset-sync.log` | Written on each deploy (check if CSS/JS break) |

If the host ever changes document root, edit **`docroot.txt`** in git and redeploy.

## Verify after deploy

- https://tich.africa/css/tich-platform.css
- https://tich.africa/js/tich-nav.js

Hard-refresh the homepage (Ctrl+Shift+R).

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Pages work, CSS/JS 404 | Check `deploy/cpanel/last-asset-sync.log` after deploy |
| Routes 404 | Redeploy - `.htaccess` is copied from git each time |
| Wrong document root | Update `deploy/cpanel/docroot.txt`, commit, redeploy |
