#!/usr/bin/env bash
# Installs one release on the VPS. Called by .github/workflows/deploy.yml over SSH, as the "deploy" user:
#   bash release.sh <release-name>
# The archive /var/www/wrestlingstory/releases/<release-name>.tar.gz has already been uploaded.
#
# Steps: unpack -> link the shared .env and storage -> migrate -> cache the config ->
# switch the "current" link in one move -> reload PHP-FPM -> keep the last 3 releases.
# If a step fails before the switch, the site keeps running on the previous release.
set -euo pipefail

RELEASE="${1:?release name expected}"
APP_DIR="/var/www/wrestlingstory"
SHARED="$APP_DIR/shared"
TARGET="$APP_DIR/releases/$RELEASE"
ARCHIVE="$TARGET.tar.gz"
KEEP=3

echo "==> Décompression de $RELEASE"
rm -rf "$TARGET"
mkdir -p "$TARGET"
tar -xzf "$ARCHIVE" -C "$TARGET"
rm -f "$ARCHIVE"

echo "==> Liens vers .env et storage partagés"
rm -rf "$TARGET/storage"
ln -s "$SHARED/storage" "$TARGET/storage"
ln -sfn "$SHARED/.env" "$TARGET/.env"
mkdir -p "$TARGET/bootstrap/cache"

cd "$TARGET"

echo "==> Migrations"
php8.4 artisan migrate --force --no-interaction

echo "==> Caches de production (config, routes, vues, événements)"
php8.4 artisan optimize

echo "==> Bascule de current vers $RELEASE"
ln -sfn "$TARGET" "$APP_DIR/current.tmp"
mv -T "$APP_DIR/current.tmp" "$APP_DIR/current"

echo "==> Rechargement de PHP-FPM"
sudo -n /usr/bin/systemctl reload php8.4-fpm

echo "==> Nettoyage : on garde les $KEEP dernières versions"
cd "$APP_DIR/releases"
CURRENT="$(basename "$(readlink -f "$APP_DIR/current")")"
ls -1t | grep -v -x -e "$CURRENT" -e 'release.sh' | grep -v '\.tar\.gz$' | tail -n +"$KEEP" | while read -r old; do
    rm -rf -- "$old"
done

echo "==> Version en ligne : $CURRENT"
