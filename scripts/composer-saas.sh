#!/usr/bin/env bash
#
# Runs Composer against the SaaS manifest (composer.saas.json / composer.saas.lock).
# A plain `composer ...` uses composer.json, which is the self-hosted build.
#
# Usage:
#   scripts/composer-saas.sh                      # composer install
#   scripts/composer-saas.sh update laravel/mcp   # any other composer command
#   scripts/composer-saas.sh require foo/bar      # adds to composer.saas.json only, mirror shared packages into composer.json

set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f auth.json ]]; then
    printf 'auth.json is missing: Spark needs credentials for https://spark.laravel.com.\n' >&2
    printf 'Create it with: composer config --auth http-basic.spark.laravel.com <email> <license-key>\n' >&2
    exit 1
fi

if [[ ! -d packages/simplestats-io/laravel-client || ! -d packages/simplestats-io/filament-plugin ]]; then
    printf 'packages/simplestats-io/{laravel-client,filament-plugin} are missing (path repositories of composer.saas.json).\n' >&2
    exit 1
fi

if [[ $# -eq 0 ]]; then
    set -- install
fi

COMPOSER=composer.saas.json exec composer "$@"
