#!/bin/bash
# Run once on the server after cloning tich-erp (Leysafaris-style asset symlinks).
set -euo pipefail

CPANEL_USER="${CPANEL_USER:-leylasaf}"
APP_PUBLIC="/home2/${CPANEL_USER}/tich-erp/web/public"
DOCROOT="/home2/${CPANEL_USER}/tich.africa/public_html"

if [[ ! -d "$APP_PUBLIC" ]]; then
  echo "Laravel public not found: $APP_PUBLIC"
  exit 1
fi

if [[ ! -d "$DOCROOT" ]]; then
  echo "Document root not found: $DOCROOT"
  echo "Set DOCROOT to your tich.africa path from cPanel → Domains."
  exit 1
fi

cd "$DOCROOT"

ln -sfn "$APP_PUBLIC/css" css
ln -sfn "$APP_PUBLIC/js" js
ln -sfn "$APP_PUBLIC/images" images
ln -sfn "$APP_PUBLIC/storage" storage
ln -sfn "$APP_PUBLIC/favicon.ico" favicon.ico
ln -sfn "$APP_PUBLIC/robots.txt" robots.txt

echo "Symlinks created in $DOCROOT"
ls -la css js images storage 2>/dev/null || true
