#!/bin/bash
# HostPinnacle / cPanel Git deploy for TICH ERP (monorepo root = tich-erp/).
# Invoked from .cpanel.yml. Writes deploy/cpanel/last-deploy.log

set -u

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WEB="${REPO_ROOT}/web"
LOG="${REPO_ROOT}/deploy/cpanel/last-deploy.log"
DOCROOT_FILE="${REPO_ROOT}/deploy/cpanel/docroot.txt"

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"
}

: > "$LOG"
log "Deploy started"
log "Repo: ${REPO_ROOT}"

PHP_BIN=""
for candidate in /usr/local/bin/ea-php82 /usr/local/bin/ea-php83 /usr/local/bin/ea-php81 /usr/bin/php; do
  if [[ -x "$candidate" ]]; then
    PHP_BIN="$candidate"
    break
  fi
done

if [[ -z "$PHP_BIN" ]]; then
  log "ERROR: No PHP CLI found (need ea-php82+ for Laravel 12)"
  exit 1
fi

log "Using PHP: ${PHP_BIN}"
echo "$PHP_BIN" > "${REPO_ROOT}/deploy/cpanel/.php-bin"

if [[ ! -d "$WEB" || ! -f "$WEB/artisan" ]]; then
  log "ERROR: Laravel app missing at ${WEB}"
  exit 1
fi

cd "$WEB"

mkdir -p \
  storage/framework/cache \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  storage/app/public \
  bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [[ ! -f .env ]]; then
  log "ERROR: ${WEB}/.env is missing."
  log "In File Manager: copy web/.env.example to web/.env, set APP_URL=https://tich.africa, DB_*, then run: ${PHP_BIN} artisan key:generate"
  exit 1
fi

COMPOSER_BIN="/opt/cpanel/composer/bin/composer"
if [[ ! -f "$COMPOSER_BIN" ]]; then
  COMPOSER_BIN="$(command -v composer || true)"
fi
if [[ -z "$COMPOSER_BIN" ]]; then
  log "ERROR: Composer not found"
  exit 1
fi

log "composer install…"
if ! "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress 2>&1 | tee -a "$LOG"; then
  log "ERROR: composer install failed"
  exit 1
fi

if [[ ! -f vendor/autoload.php ]]; then
  log "ERROR: vendor/autoload.php still missing after composer install"
  exit 1
fi

log "artisan down / migrate / storage:link…"
"$PHP_BIN" artisan down --retry=120 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan migrate --force --no-interaction 2>&1 | tee -a "$LOG" || {
  log "WARN: migrate failed — check DB_* in .env (site may still boot)"
}
"$PHP_BIN" artisan storage:link --force 2>&1 | tee -a "$LOG" || true

DOCROOT=""
if [[ -f "$DOCROOT_FILE" ]]; then
  DOCROOT="$(tr -d '\r\n' < "$DOCROOT_FILE" | xargs)"
fi
if [[ -z "$DOCROOT" || ! -d "$DOCROOT" ]]; then
  DOCROOT="${HOME}/public_html"
fi
log "Docroot: ${DOCROOT}"

/bin/cp -f "${REPO_ROOT}/deploy/cpanel/public_html.index.php" "${DOCROOT}/index.php"
/bin/cp -f "${REPO_ROOT}/deploy/cpanel/public_html.htaccess" "${DOCROOT}/.htaccess"
log "Copied index.php + .htaccess into docroot"

/bin/bash "${REPO_ROOT}/deploy/cpanel/sync-public-assets.sh" 2>&1 | tee -a "$LOG" || {
  log "WARN: asset sync had issues — index.php can still serve css/js as fallback"
}

log "Clearing / rebuilding caches…"
"$PHP_BIN" artisan config:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan route:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan view:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan config:cache --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan route:cache --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan view:cache --no-interaction 2>&1 | tee -a "$LOG" || true

"$PHP_BIN" artisan up 2>&1 | tee -a "$LOG" || true

log "Deploy finished OK"
exit 0
