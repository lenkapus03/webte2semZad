#!/bin/bash
set -e

host="$1"
shift

MYSQL_USER=${MYSQL_USER:-myuser}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-mypassword}
MYSQL_DATABASE=${MYSQL_DATABASE:-pdf_db}

echo "Waiting for MySQL at $host..."

timeout=60
start_time=$(date +%s)

until mysql -h"$host" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "USE $MYSQL_DATABASE" > /dev/null 2>&1
do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 2

  current_time=$(date +%s)
  elapsed=$((current_time - start_time))
  if [ "$elapsed" -ge "$timeout" ]; then
    >&2 echo "Timeout after $timeout seconds waiting for MySQL"
    exit 1
  fi
done

>&2 echo "MySQL is up - executing command"
exec "$@"
