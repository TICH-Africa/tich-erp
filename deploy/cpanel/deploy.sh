#!/bin/bash
# HostPinnacle / cPanel Git deploy for TICH ERP (monorepo root = tich-erp/).
# Invoked from .cpanel.yml. Writes deploy/cpanel/last-deploy.log

set -u
# Without pipefail, "composer install | tee" reports tee's exit status, so a
# failed/OOM'd install looks successful and the site ships a partial vendor
# (this is how PhpSpreadsheet went missing on production).
set -o pipefail

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

# Composer refuses to run under LiteSpeed/cPanel PHP without HOME.
export HOME="${HOME:-/home3/tichafri}"
export COMPOSER_HOME="${COMPOSER_HOME:-${HOME}/.composer}"
mkdir -p "$COMPOSER_HOME" 2>/dev/null || true
log "HOME=${HOME} COMPOSER_HOME=${COMPOSER_HOME}"

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

log "composer install --no-dev (fresh autoload, no mockery)…"
# Broken autoload referencing mockery/phpunit is a common HostPinnacle 500.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true
if ! COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress 2>&1 | tee -a "$LOG"; then
  log "ERROR: composer install failed"
  exit 1
fi
"$PHP_BIN" "$COMPOSER_BIN" dump-autoload --no-dev --optimize --no-interaction 2>&1 | tee -a "$LOG" || true

if [[ ! -f vendor/autoload.php ]]; then
  log "ERROR: vendor/autoload.php still missing after composer install"
  exit 1
fi

# PhpSpreadsheet is required for Chart of Accounts Excel export/import.
SPREADSHEET_FILE="vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php"
if [[ ! -f "$SPREADSHEET_FILE" ]]; then
  log "WARN: PhpSpreadsheet missing after install - forcing require…"
  COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" require phpoffice/phpspreadsheet:^5.9 --no-interaction --update-with-dependencies 2>&1 | tee -a "$LOG" || true
  "$PHP_BIN" "$COMPOSER_BIN" dump-autoload --no-dev --optimize --no-interaction 2>&1 | tee -a "$LOG" || true
fi
if [[ ! -f "$SPREADSHEET_FILE" ]]; then
  log "ERROR: PhpSpreadsheet still missing at ${SPREADSHEET_FILE}"
  log "Chart of Accounts Excel export will fail until this package is present."
  exit 1
fi
log "PhpSpreadsheet OK"

# Always regenerate production autoload so require-dev (mockery/phpunit) cannot linger.
log "composer dump-autoload --no-dev (strip mockery/phpunit from autoload)…"
COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" dump-autoload --no-dev --optimize --no-interaction 2>&1 | tee -a "$LOG" || true

autoload_has_dev() {
  local f
  for f in vendor/composer/autoload_files.php vendor/composer/autoload_psr4.php vendor/composer/autoload_classmap.php vendor/composer/autoload_static.php; do
    if [[ -f "$f" ]] && grep -E "mockery/mockery|phpunit/phpunit|nunomaduro/collision" "$f" >/dev/null 2>&1; then
      return 0
    fi
  done
  return 1
}

if [[ -d vendor/mockery ]] || autoload_has_dev; then
  log "ERROR: autoload/vendor still references require-dev (mockery/phpunit) - wiping vendor and reinstalling"
  rm -rf vendor
  if ! COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress 2>&1 | tee -a "$LOG"; then
    log "ERROR: composer reinstall failed"
    exit 1
  fi
  COMPOSER_MEMORY_LIMIT=-1 "$PHP_BIN" "$COMPOSER_BIN" dump-autoload --no-dev --optimize --no-interaction 2>&1 | tee -a "$LOG" || true
fi

if autoload_has_dev; then
  log "ERROR: autoload STILL references mockery/phpunit after reinstall - refusing to finish deploy"
  exit 1
fi
log "Autoload OK (no mockery/phpunit)"

log "artisan down / migrate / storage:link…"
"$PHP_BIN" artisan down --retry=120 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan migrate --force --no-interaction 2>&1 | tee -a "$LOG" || {
  log "WARN: migrate failed - check DB_* in .env (site may still boot)"
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
/bin/cp -f "${REPO_ROOT}/deploy/cpanel/tich-mpesa-stk-callback.php" "${DOCROOT}/tich-mpesa-stk-callback.php"
/bin/cp -f "${REPO_ROOT}/deploy/cpanel/tich-diagnose.php" "${DOCROOT}/tich-diagnose.php"
/bin/cp -f "${REPO_ROOT}/deploy/cpanel/tich-fix-autoload.php" "${DOCROOT}/tich-fix-autoload.php"
log "Copied index.php, .htaccess, diagnose, and fix-autoload into docroot"

# Prefer fresh config from .env over a stale config.php cache after ChatGPT/manual edits.
rm -f "${WEB}/bootstrap/cache/config.php" "${WEB}/bootstrap/cache/routes-v7.php" "${WEB}/bootstrap/cache/routes.php" "${WEB}/bootstrap/cache/events.php" 2>/dev/null || true
log "Cleared bootstrap cache files before rebuild"

/bin/bash "${REPO_ROOT}/deploy/cpanel/sync-public-assets.sh" 2>&1 | tee -a "$LOG" || {
  log "WARN: asset sync had issues - index.php can still serve css/js as fallback"
}

log "Clearing caches…"
"$PHP_BIN" artisan config:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan route:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan view:clear --no-interaction 2>&1 | tee -a "$LOG" || true
"$PHP_BIN" artisan event:clear --no-interaction 2>&1 | tee -a "$LOG" || true
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/routes.php bootstrap/cache/events.php 2>/dev/null || true

"$PHP_BIN" artisan up 2>&1 | tee -a "$LOG" || true

log "Deploy finished OK"
exit 0
