#!/bin/bash
# ---------------------------------------------------------------------
# docker/db-entrypoint.sh
# Starts MySQL with the credentials from .env.local, the project's only
# file with real values. The official mysql image only reads MYSQL_*
# variables, so the app's DB_* keys are mapped onto them here.
#
# Only these four keys are read. Nothing else from .env.local (mail,
# PayPal, Google secrets) reaches this container's environment.
#
# The MYSQL_* values are used only when the data volume is empty: they
# create the database, the app user and the root password. On an
# existing volume, change a password with ALTER USER, then update
# .env.local.
# ---------------------------------------------------------------------
set -euo pipefail

ENV_FILE=/najminet/.env.local

# Same rules as includes/load_env.php: KEY=VALUE lines, comments and
# blank lines skipped, surrounding quotes removed, last occurrence wins.
env_value() {
    awk -v key="$1" '
        { sub(/^[ \t]+/, ""); sub(/[ \t\r]+$/, "") }
        /^#/ || index($0, "=") == 0 { next }
        {
            name = substr($0, 1, index($0, "=") - 1)
            sub(/[ \t]+$/, "", name)
        }
        name == key {
            value = substr($0, index($0, "=") + 1)
            sub(/^[ \t]+/, "", value)
        }
        END {
            if (value ~ /^".*"$/ || value ~ /^\047.*\047$/) {
                value = substr(value, 2, length(value) - 2)
            }
            printf "%s", value
        }' "$ENV_FILE"
}

MYSQL_DATABASE="$(env_value DB_NAME)"
MYSQL_USER="$(env_value DB_USER)"
MYSQL_PASSWORD="$(env_value DB_PASS)"
MYSQL_ROOT_PASSWORD="$(env_value MYSQL_ROOT_PASSWORD)"
export MYSQL_DATABASE MYSQL_USER MYSQL_PASSWORD MYSQL_ROOT_PASSWORD

exec docker-entrypoint.sh "$@"
