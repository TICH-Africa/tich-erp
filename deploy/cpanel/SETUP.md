# TICH ERP — cPanel deploy (Option C, no terminal)

Git **Deploy HEAD Commit** runs `.cpanel.yml` on the server. You never need SSH.

---

## One-time setup (File Manager only)

### 1. Tell deploy where `public_html` is

1. **File Manager** → open folder `tich-erp/deploy/cpanel/`
2. Copy `docroot.txt.example` → rename copy to **`docroot.txt`**
3. Edit **`docroot.txt`** — **one line only**, full path to your domain folder.

To find the path:

- **cPanel → Domains → tich.africa → Document Root**  
  Example: `/home2/leylasaf/tich.africa/public_html`

4. Save.

### 2. Custom `index.php` + `.htaccess`

Keep these in your domain `public_html` (see `deploy/cpanel/public_html.index.php` and `public_html.htaccess`).

---

## Every deployment

1. Push code to the branch cPanel tracks.
2. **cPanel → Git Version Control**
3. **Update from Remote**
4. **Deploy HEAD Commit**

Deploy will:

- `composer install`
- `artisan migrate` / cache
- **Link css, js, images** into `public_html` (same idea as Leysafaris symlinks)

No manual copying.

---

## If styling still missing after deploy

1. **File Manager** → `tich-erp/deploy/cpanel/last-asset-sync.log`
2. Read the last run — it shows detected paths and errors.

Common fixes:

| Log message | Fix |
|-------------|-----|
| `Could not find document root` | Fix path in `docroot.txt` |
| `Missing .../css/tich-platform.css` | Run Deploy again after git pull; confirm repo has `web/public/css/` |
| `Symlink failed, copying instead` | Normal on some hosts — copy still runs |
| `SUCCESS: css is available` but site unstyled | Hard refresh browser; check wrong domain docroot in `docroot.txt` |

---

## Verify

- https://tich.africa/css/tich-platform.css
- https://tich.africa/js/tich-nav.js

Both must **not** be 404.

---

## Why the old `.cpanel.yml` failed

cPanel runs **each YAML line in a new shell**. Lines like:

```yaml
- export DOCROOT=...
- /bin/cp ... $DOCROOT/css/
```

…left `$DOCROOT` **empty** on the copy step, so css/js never reached `public_html`.

The new setup uses **one bash script** with full paths and a log file.
