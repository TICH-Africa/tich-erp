#!/bin/bash
# Sync Laravel public assets into the domain document root (Leysafaris-style hosting).
set -euo pipefail

CPANEL_USER="${CPANEL_USER:-leylasaf}"
REPO_ROOT="${REPO_ROOT:-/home2/${CPANEL_USER}/tich-erp}"
APP_PUBLIC="${REPO_ROOT}/web/public"

if [[ -d "/home2/${CPANEL_USER}/tich.africa/public_html" ]]; then
  DOCROOT="/home2/${CPANEL_USER}/tich.africa/public_html"
elif [[ -d "/home2/${CPANEL_USER}/public_html" ]]; then
  DOCROOT="/home2/${CPANEL_USER}/public_html"
else
  echo "Could not find document root. Set DOCROOT manually."
  exit 1
fi

if [[ ! -d "$APP_PUBLIC/css" ]]; then
  echo "Missing Laravel public folder: $APP_PUBLIC"
  exit 1
fi

mkdir -p "$DOCROOT/css" "$DOCROOT/js" "$DOCROOT/images"

cp -rf "$APP_PUBLIC/css/." "$DOCROOT/css/"
cp -rf "$APP_PUBLIC/js/." "$DOCROOT/js/"

if [[ -d "$APP_PUBLIC/images" ]]; then
  cp -rf "$APP_PUBLIC/images/." "$DOCROOT/images/"
fi

cp -f "$APP_PUBLIC/favicon.ico" "$DOCROOT/" 2>/dev/null || true
cp -f "$APP_PUBLIC/robots.txt" "$DOCROOT/" 2>/dev/null || true
ln -sfn "$APP_PUBLIC/storage" "$DOCROOT/storage" 2>/dev/null || true

echo "Synced assets to $DOCROOT"
ls -la "$DOCROOT/css/tich-platform.css" "$DOCROOT/js/tich-nav.js"
