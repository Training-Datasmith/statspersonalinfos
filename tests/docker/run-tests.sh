#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
REPO_ROOT=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
DOCKER_BIN=${DOCKER:-docker}

PHPUNIT_PHAR_SHA256=ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e
PHPUNIT_GPG_FPR=D8406D0D82947747293778314AA394086372C20A
MARIA_DIGEST=${MARIA_DIGEST:-docker.io/library/mariadb@sha256:f1c56f035ed268a0d7c2c2e06dc879f6319b997cb434cf822a4cfd72a7aa184d}
MARIADB_NAME=stats-mariadb

TMP_DIR=$(mktemp -d)
chmod 0700 "$TMP_DIR"
cleanup() {
  "$DOCKER_BIN" rm -f "$MARIADB_NAME" >/dev/null 2>&1 || true
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT INT TERM

PHPUNIT_PHAR="$TMP_DIR/phpunit-4.8.36.phar"
PHPUNIT_ASC="$TMP_DIR/phpunit-4.8.36.phar.asc"
GNUPGHOME=$(mktemp -d)
chmod 0700 "$GNUPGHOME"

curl -fsSL -o "$PHPUNIT_PHAR" https://phar.phpunit.de/phpunit-4.8.36.phar
curl -fsSL -o "$PHPUNIT_ASC" https://phar.phpunit.de/phpunit-4.8.36.phar.asc
ACTUAL_SHA=$(sha256sum "$PHPUNIT_PHAR" | awk '{print $1}')
if [ "$ACTUAL_SHA" != "$PHPUNIT_PHAR_SHA256" ]; then
  echo "PHPUnit PHAR sha256 mismatch" >&2
  exit 1
fi

export GNUPGHOME
curl -fsSL https://sebastian-bergmann.de/gpg.asc | gpg --batch --import >/dev/null
VERIFY_OUT=$(gpg --batch --status-fd 1 --verify "$PHPUNIT_ASC" "$PHPUNIT_PHAR" 2>&1) || {
  echo "$VERIFY_OUT" >&2
  exit 1
}
echo "$VERIFY_OUT" | grep -q 'VALIDSIG .*'"$PHPUNIT_GPG_FPR"
echo "$VERIFY_OUT" | grep -q 'GOODSIG'
rm -rf "$GNUPGHOME"

"$DOCKER_BIN" build -t statspersonalinfos-php:5.6.40 -f "$SCRIPT_DIR/Dockerfile" "$SCRIPT_DIR"

"$DOCKER_BIN" rm -f "$MARIADB_NAME" >/dev/null 2>&1 || true
"$DOCKER_BIN" run -d --name "$MARIADB_NAME" \
  -e MARIADB_DATABASE=stats_personal_infos_test \
  -e MARIADB_USER=stats \
  -e MARIADB_PASSWORD=stats \
  -e MARIADB_ROOT_PASSWORD=root \
  "$MARIA_DIGEST" \
  --sql-mode= \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_unicode_ci

MARIADB_READY=0
TRIES=0
while [ "$TRIES" -lt 60 ]; do
  if "$DOCKER_BIN" exec "$MARIADB_NAME" mariadb-admin ping -h127.0.0.1 -uroot -proot --silent >/dev/null 2>&1; then
    MARIADB_READY=1
    break
  fi
  TRIES=$((TRIES + 1))
  sleep 1
done
if [ "$MARIADB_READY" -ne 1 ]; then
  echo "MariaDB did not become ready in time" >&2
  exit 1
fi

"$DOCKER_BIN" run --rm --network "container:${MARIADB_NAME}" \
  -v "$REPO_ROOT:/work:ro" \
  -e STATS_DB_HOST=127.0.0.1 \
  -e STATS_DB_PORT=3306 \
  -e STATS_DB_NAME=stats_personal_infos_test \
  -e STATS_DB_USER=stats \
  -e STATS_DB_PASSWORD=stats \
  -e PHPUNIT_PHAR=/phpunit.phar \
  -v "$PHPUNIT_PHAR:/phpunit.phar:ro" \
  statspersonalinfos-php:5.6.40 \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 /work/tests/run-suite.php
