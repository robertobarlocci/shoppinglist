#!/bin/sh
# Creates the database the test suite uses (phpunit.xml -> DB_DATABASE=chnubber_test).
#
# Postgres runs everything in /docker-entrypoint-initdb.d ONCE, when the data directory is
# first initialised. An existing volume therefore never sees this script — use `make test-db`
# for those.
set -e

psql --username "$POSTGRES_USER" --dbname postgres <<-SQL
    SELECT 'CREATE DATABASE chnubber_test'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'chnubber_test')\gexec
SQL
