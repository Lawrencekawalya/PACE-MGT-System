#!/usr/bin/env bash

set -Eeuo pipefail
umask 0002

readonly APP_ROOT="/var/www/pace"
readonly REPOSITORY="$APP_ROOT/repository"
readonly RELEASES="$APP_ROOT/releases"
readonly SHARED="$APP_ROOT/shared"
readonly CURRENT="$APP_ROOT/current"
readonly HEALTH_URL="https://pace.syntaxsystems.co/ready"
readonly KEEP_RELEASES=5

release_sha="${1:-}"
if [[ ! "$release_sha" =~ ^[0-9a-f]{40}$ ]]; then
    echo "A full 40-character Git commit SHA is required." >&2
    exit 1
fi

if [[ ! -f "$SHARED/.env" ]]; then
    echo "Missing production environment file: $SHARED/.env" >&2
    exit 1
fi

git -C "$REPOSITORY" cat-file -e "$release_sha^{commit}"

release="$RELEASES/$release_sha"
previous_release=""
maintenance_enabled=0
switched_release=0

if [[ -L "$CURRENT" ]]; then
    previous_release="$(readlink -f "$CURRENT")"
fi

recover() {
    exit_code=$?

    if (( switched_release == 1 )) && [[ -n "$previous_release" ]] && [[ -d "$previous_release" ]]; then
        ln -s "$previous_release" "$APP_ROOT/current.rollback"
        mv -Tf "$APP_ROOT/current.rollback" "$CURRENT"
        php "$CURRENT/artisan" reload || true
    fi

    if (( maintenance_enabled == 1 )) && [[ -f "$CURRENT/artisan" ]]; then
        php "$CURRENT/artisan" up || true
    fi

    echo "Deployment failed for $release_sha." >&2
    exit "$exit_code"
}
trap recover ERR

rm -rf "$release"
mkdir -p "$release"
git -C "$REPOSITORY" archive "$release_sha" | tar -x -C "$release"

ln -s "$SHARED/.env" "$release/.env"
if [[ ! -d "$SHARED/storage/framework" ]]; then
    mkdir -p "$SHARED/storage"
    cp -a "$release/storage/." "$SHARED/storage/"
fi
rm -rf "$release/storage"
ln -s "$SHARED/storage" "$release/storage"

mkdir -p \
    "$SHARED/storage/app/private" \
    "$SHARED/storage/app/public" \
    "$SHARED/storage/framework/cache" \
    "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" \
    "$SHARED/storage/logs" \
    "$release/bootstrap/cache"

chmod 2770 \
    "$SHARED/storage" \
    "$SHARED/storage/app" \
    "$SHARED/storage/app/private" \
    "$SHARED/storage/app/public" \
    "$SHARED/storage/framework" \
    "$SHARED/storage/framework/cache" \
    "$SHARED/storage/framework/sessions" \
    "$SHARED/storage/framework/views" \
    "$SHARED/storage/logs"
chmod -R ug+rwX "$release/bootstrap/cache"

cd "$release"
composer install --no-dev --classmap-authoritative --no-interaction --no-progress
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

if [[ -f "$CURRENT/artisan" ]]; then
    php "$CURRENT/artisan" down --retry=60
    maintenance_enabled=1
    php "$CURRENT/artisan" backup:database
fi

php artisan migrate --force
php artisan db:seed --class='Database\Seeders\AccessControlSeeder' --force
php artisan db:seed --class='Database\Seeders\SchoolSettingSeeder' --force
php artisan db:seed --class='Database\Seeders\PaceCatalogueSeeder' --force
php artisan db:seed --class='Database\Seeders\InventoryItemSeeder' --force
php artisan storage:link
php artisan optimize

ln -s "$release" "$APP_ROOT/current.next"
mv -Tf "$APP_ROOT/current.next" "$CURRENT"
switched_release=1

php "$CURRENT/artisan" reload
php "$CURRENT/artisan" up
maintenance_enabled=0

curl --fail --silent --show-error --retry 6 --retry-delay 5 "$HEALTH_URL" > /dev/null

trap - ERR

find "$RELEASES" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' \
    | sort -rn \
    | tail -n "+$((KEEP_RELEASES + 1))" \
    | cut -d' ' -f2- \
    | xargs --no-run-if-empty rm -rf

echo "Deployment completed: $release_sha"
