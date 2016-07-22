#!/bin/bash

set -e

export FS_ENV=${FS_ENV:-dev}

MYSQL_USERNAME=${MYSQL_USERNAME:-root}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-root}

dir=$(dirname "$0")

function dc() {
  $dir/docker-compose "$@"
}

function sql-query() {
  local database=$1 query=$2;
  dc exec db sh -c "mysql -p$MYSQL_PASSWORD $database -e \"$query\""
}

function sql-file() {
  local database=$1 filename=$2;
  echo "Executing sql file $FS_ENV/$database $filename"
  dc exec db sh -c "mysql -p$MYSQL_PASSWORD $database < /app/$filename"
}

function run-in-container() {
  local container=$1; shift;
  local command=$@;
  dc exec $container sh -c "$command"
}

function dropdb() {
  local database=$1;
  echo "Dropping database $FS_ENV/$database"
  sql-query mysql "drop database if exists $database"
}

function createdb() {
  local database=$1;
  echo "Creating database $FS_ENV/$database"
  sql-query mysql "create database if not exists $database"
}

function recreatedb() {
  local database=$1;
  dropdb "$database"
  createdb "$database"
}

function migratedb() {
  local database=$1;
  echo "Migrating database $FS_ENV/$database"
  sql-file $database migrations/initial.sql
  sql-file $database migrations/static.sql
}

function wait-for-mysql() {
  run-in-container db "while ! mysql -p$MYSQL_PASSWORD --silent -e 'select 1' >/dev/null 2>&1; do sleep 1; done"
}