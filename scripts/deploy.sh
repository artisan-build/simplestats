#!/bin/bash

# =============================================================================
# SimpleStats Deploy Script (Zero-Downtime)
#
# Usage:
#   bash ~/simplestats.io/current/scripts/deploy.sh \
#     --keep-releases=10 \
#     --build-docs \
#     --blog-warm-cache \
#     --generate-sitemap \
#     --restart-horizon \
#     --stop-ssr
#
# Domain is derived from the script path: ~/<domain>/current/scripts/deploy.sh
#
# Required Forge variables: FORGE_SITE_BRANCH, FORGE_PHP, FORGE_COMPOSER
# =============================================================================

# Stop script on error signal (-e) and undefined variables (-u)
set -eu

# --- Parse arguments ----------------------------------------------------------

SCRIPT_PATH="$(cd "$(dirname "$0")" && pwd)"
DOMAIN="$(basename "$(dirname "$(dirname "$SCRIPT_PATH")")")"
PROJECT_REPO="git@github.com:simplestats-io/app.git"
AMOUNT_KEEP_RELEASES=10
BUILD_DOCS=false
BLOG_WARM_CACHE=false
GENERATE_SITEMAP=false
RESTART_HORIZON=false
STOP_SSR=false

for arg in "$@"; do
  case $arg in
    --keep-releases=*) AMOUNT_KEEP_RELEASES="${arg#*=}" ;;
    --build-docs) BUILD_DOCS=true ;;
    --blog-warm-cache) BLOG_WARM_CACHE=true ;;
    --generate-sitemap) GENERATE_SITEMAP=true ;;
    --restart-horizon) RESTART_HORIZON=true ;;
    --stop-ssr) STOP_SSR=true ;;
    *) printf '\nError: Unknown argument %s\n' "$arg" && exit 1 ;;
  esac
done

if [ -z "$DOMAIN" ]; then
  printf '\nError: Could not determine domain from script path.\n' && exit 1
fi

RELEASE_NAME=$(date +%s--%Y_%m_%d--%H_%M_%S)
RELEASES_DIRECTORY=~/$DOMAIN/releases
DEPLOYMENT_DIRECTORY=$RELEASES_DIRECTORY/$RELEASE_NAME

# --- Pull client package -----------------------------------------------------

cd ~/$DOMAIN/shared/packages/simplestats-io/laravel-client
git pull

# --- Clone release ------------------------------------------------------------

printf '\nℹ️ Starting deployment %s\n' "$RELEASE_NAME"
mkdir -p "$RELEASES_DIRECTORY" && cd "$RELEASES_DIRECTORY"

printf '\nℹ️ Clone GIT project from %s and checkout branch %s\n' "$PROJECT_REPO" "$FORGE_SITE_BRANCH"
git clone "$PROJECT_REPO" "$RELEASE_NAME"
cd "$RELEASE_NAME"
git checkout "$FORGE_SITE_BRANCH"
git fetch origin "$FORGE_SITE_BRANCH"
git reset --hard FETCH_HEAD

# --- Link shared resources ----------------------------------------------------

printf '\nℹ️ Link env file\n'
ENV_FILE=~/"$DOMAIN"/shared/.env
if [ -f "$ENV_FILE" ]; then
  rm -rf ./env
  ln -s -n -f -T $ENV_FILE ./.env
else
  printf '\nError: env file is missing at %s.' "$ENV_FILE" && exit 1
fi

printf '\nℹ️ Link storage folder\n'
STORAGE_DIR=~/"$DOMAIN"/shared/storage
if [ -d "$STORAGE_DIR" ]; then
  rm -rf ./storage
  ln -s -n -f -T $STORAGE_DIR ./storage
else
  printf '\nError: storage dir is missing at %s.' "$STORAGE_DIR" && exit 1
fi

printf '\nℹ️ Link packages folder\n'
PACKAGES_DIR=~/"$DOMAIN"/shared/packages
if [ -d "$PACKAGES_DIR" ]; then
  rm -rf ./packages
  ln -s -n -f -T $PACKAGES_DIR ./packages
else
  printf '\nError: packages dir is missing at %s.' "$PACKAGES_DIR" && exit 1
fi

# --- Install dependencies ----------------------------------------------------

printf '\nℹ️ Install Composer Dependency Updates\n'
$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

printf '\nℹ️ Installing NPM dependencies based on "./package-lock.json"\n'
npm ci
printf '\nℹ️ Generating JS App files\n'
npm run build
if [ "$BUILD_DOCS" = "true" ]; then
  printf '\nℹ️ Building Docs\n'
  npm run docs:build
fi

# --- Pre-deploy actions -------------------------------------------------------

printf '\nℹ️ Laravel Artisan commands\n'
if [ -f artisan ]; then
  printf '\nℹ️ Link ./public/storage\n'
  $FORGE_PHP artisan storage:link

  printf '\nℹ️ Clear and cache routes, config, views, events\n'
  $FORGE_PHP artisan optimize:clear

  printf '\nℹ️ Publish Filament assets\n'
  $FORGE_PHP artisan filament:assets

  printf '\nℹ️ Cache routes, config, views, events\n'
  $FORGE_PHP artisan optimize

  if [ "$BLOG_WARM_CACHE" = "true" ]; then
    printf '\nℹ️ Blog Warm Cache\n'
    $FORGE_PHP artisan blog:warm-cache
  fi

  if [ "$GENERATE_SITEMAP" = "true" ]; then
    printf '\nℹ️ Generate Sitemap\n'
    $FORGE_PHP artisan app:generate-sitemap
  fi

  printf '\nℹ️ Database Migrations\n'
  $FORGE_PHP artisan migrate --force
fi

# --- Activate release ---------------------------------------------------------

printf '\nℹ️ !!! Link Deployment Directory !!!\n'
echo "$RELEASE_NAME" >> $RELEASES_DIRECTORY/.successes
if [ -d ~/$DOMAIN/current ] && [ ! -L ~/$DOMAIN/current ]; then
  rm -rf ~/$DOMAIN/current
fi
ln -s -n -f -T "$DEPLOYMENT_DIRECTORY" ~/$DOMAIN/current

# --- Post-deploy actions (must run after symlink to load new code) ------------

cd "$DEPLOYMENT_DIRECTORY"

if [ "$RESTART_HORIZON" = "true" ]; then
  printf '\nℹ️ Restart Horizon Queue Workers\n'
  $FORGE_PHP artisan horizon:terminate
fi

if [ "$STOP_SSR" = "true" ]; then
  $FORGE_PHP artisan inertia:stop-ssr || true
fi

# --- Clean up old releases ----------------------------------------------------

cd $RELEASES_DIRECTORY

printf '\nℹ️ Delete failed releases:\n'
if grep -qvf .successes <(ls -1)
then
  grep -vf .successes <(ls -1)
  grep -vf .successes <(ls -1) | xargs rm -rf
else
  echo "No failed releases found."
fi

printf '\nℹ️ Delete old successful releases:\n'
AMOUNT_KEEP_RELEASES=$((AMOUNT_KEEP_RELEASES-1))
LINES_STORED_RELEASES_TO_DELETE=$(find . -maxdepth 1 -mindepth 1 -type d ! -name "$RELEASE_NAME" -printf '%T@\t%f\n' | head -n -"$AMOUNT_KEEP_RELEASES" | wc -l)
if [ "$LINES_STORED_RELEASES_TO_DELETE" != 0 ]; then
  find . -maxdepth 1 -mindepth 1 -type d ! -name "$RELEASE_NAME" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$AMOUNT_KEEP_RELEASES" | cut -d $'\t' -f 2-
  find . -maxdepth 1 -mindepth 1 -type d ! -name "$RELEASE_NAME" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$AMOUNT_KEEP_RELEASES" | cut -d $'\t' -f 2- | xargs -I {} sed -i -e '/{}/d' .successes
  find . -maxdepth 1 -mindepth 1 -type d ! -name "$RELEASE_NAME" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$AMOUNT_KEEP_RELEASES" | cut -d $'\t' -f 2- | xargs rm -rf
else
  AMOUNT_KEEP_RELEASES=$((AMOUNT_KEEP_RELEASES+1))
  LINES_STORED_RELEASES_TOTAL=$(find . -maxdepth 1 -mindepth 1 -type d -printf '%T@\t%f\n' | wc -l)
  printf 'There are only %s successfully stored releases, which is less than or equal to your\ndefined %s releases to keep, so none of them got deleted.' "$LINES_STORED_RELEASES_TOTAL" "$AMOUNT_KEEP_RELEASES"
fi

printf '\nℹ️ Status - stored releases:\n'
find . -maxdepth 1 -mindepth 1 -type d -printf '%T@\t%f\n' | sort -nr | cut -f 2-

printf '\n✅ Deployment DONE: %s\n' "$DEPLOYMENT_DIRECTORY"
