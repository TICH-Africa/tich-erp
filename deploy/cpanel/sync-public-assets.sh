#!/bin/bash
# Links (or copies) Laravel public assets into the domain document root after git deploy.
# Logs to deploy/cpanel/last-asset-sync.log - open in cPanel File Manager if styling breaks.

set -u

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
APP_PUBLIC="${REPO_ROOT}/web/public"
LOG_FILE="${REPO_ROOT}/deploy/cpanel/last-asset-sync.log"
DOCROOT_FILE="${REPO_ROOT}/deploy/cpanel/docroot.txt"

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

: > "$LOG_FILE"
log "Asset sync started"
log "Repo: ${REPO_ROOT}"
log "Laravel public: ${APP_PUBLIC}"

if [[ ! -f "${APP_PUBLIC}/css/tich-platform.css" ]]; then
  log "ERROR: Missing ${APP_PUBLIC}/css/tich-platform.css"
  exit 1
fi

DOCROOT=""

if [[ -f "$DOCROOT_FILE" ]]; then
  DOCROOT="$(tr -d '\r\n' < "$DOCROOT_FILE" | xargs)"
  log "Docroot from docroot.txt: ${DOCROOT}"
fi

if [[ -z "$DOCROOT" || ! -d "$DOCROOT" ]]; then
  CPANEL_USER="$(whoami 2>/dev/null || echo tichafri)"
  CANDIDATES=(
    "/home3/${CPANEL_USER}/public_html"
    "/home3/${CPANEL_USER}/tich.africa/public_html"
    "/home2/${CPANEL_USER}/public_html"
    "/home2/${CPANEL_USER}/tich.africa/public_html"
    "/home/${CPANEL_USER}/public_html"
    "/home/${CPANEL_USER}/tich.africa/public_html"
  )

  for candidate in "${CANDIDATES[@]}"; do
    if [[ -f "${candidate}/index.php" ]]; then
      DOCROOT="$candidate"
      log "Docroot auto-detected: ${DOCROOT}"
      break
    fi
  done
fi

if [[ -z "$DOCROOT" || ! -d "$DOCROOT" ]]; then
  log "ERROR: Could not find document root."
  log "Create deploy/cpanel/docroot.txt with one line: full path to tich.africa public_html"
  log "Example: /home3/tichafri/public_html"
  exit 1
fi

link_or_copy() {
  local name="$1"
  local source="${APP_PUBLIC}/${name}"
  local target="${DOCROOT}/${name}"

  if [[ ! -e "$source" ]]; then
    log "Skip missing: ${source}"
    return 0
  fi

  rm -rf "$target" 2>/dev/null || true

  if ln -sfn "$source" "$target" 2>/dev/null; then
    log "Linked ${name} -> ${source}"
    return 0
  fi

  log "Symlink failed for ${name}, copying instead"
  if [[ -d "$source" ]]; then
    mkdir -p "$target"
    cp -rf "${source}/." "$target/"
  else
    cp -f "$source" "$target"
  fi
  log "Copied ${name}"
}

link_or_copy css
link_or_copy js
link_or_copy images
link_or_copy storage

for file in favicon.ico robots.txt; do
  if [[ -f "${APP_PUBLIC}/${file}" ]]; then
    cp -f "${APP_PUBLIC}/${file}" "${DOCROOT}/${file}" 2>/dev/null \
      && log "Updated ${file}" \
      || log "WARN: could not update ${file}"
  fi
done

if [[ -f "${DOCROOT}/css/tich-platform.css" ]] || [[ -L "${DOCROOT}/css" ]]; then
  log "SUCCESS: css is available under ${DOCROOT}"
else
  log "ERROR: css still missing under ${DOCROOT}"
  exit 1
fi

if [[ -f "${DOCROOT}/js/tich-nav.js" ]] || [[ -L "${DOCROOT}/js" ]]; then
  log "SUCCESS: js is available under ${DOCROOT}"
else
  log "ERROR: js still missing under ${DOCROOT}"
  exit 1
fi

log "Asset sync finished OK"
