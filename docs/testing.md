# Gold Manager v2 Testing

> Aggiornato: 2026-05-19

## Test database

Unit tests use a dedicated MariaDB schema.

Default connection:

```php
mysql:host=mariadb;port=3306;dbname=gold_manager_test
```

The connection can be overridden with:

- `DB_HOST_TEST`
- `DB_PORT_TEST`
- `DB_NAME_TEST`
- `DB_USER_TEST`
- `DB_PASSWORD_TEST`

If a fresh environment is created, initialise it with:

```bash
docker compose -f /var/www/gold-manager/docker-compose.yml exec -T mariadb \
  mariadb -uroot -proot_secret -e \
  "CREATE DATABASE IF NOT EXISTS gold_manager_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL PRIVILEGES ON gold_manager_test.* TO 'gm_user'@'%'; FLUSH PRIVILEGES;"

docker compose -f /var/www/gold-manager/docker-compose.yml exec -T php \
  tests/Support/bin/yii migrate --interactive=0
```

## Mock users

Auth-related unit tests seed their own users through
`tests/Support/TestUserSeeder.php`.

Seeded identities:

| ID | Username | Role | Password / token |
|----|----------|------|------------------|
| 100 | `admin` | `admin` | `100-token`, auth key `test100key` |
| 101 | `demo` | `manager` | password `demo`, token `101-token` |

The seeder deletes and recreates only `admin`, `demo`, id `100`, and id `101`.
It does not touch production data because tests point at `gold_manager_test`.

## Commands

Run unit tests:

```bash
docker compose -f /var/www/gold-manager/docker-compose.yml exec -T php \
  vendor/bin/codecept run Unit
```

Current passing result:

```text
OK (40 tests, 640 assertions)
```

Run Go worker tests:

```bash
cd /var/www/gold-manager/v2/worker-go
GOCACHE=/tmp/gold-manager-go-cache /usr/local/go/bin/go test ./...
```

Current result: all packages compile; no Go test files yet.

## Temporary time travel (SIP-0053)

For local functional QA only, enable manager button `+1 giorno (test)`:

```bash
export GM_TEST_TIME_TRAVEL=1
docker compose -f /var/www/gold-manager/docker-compose.yml up -d --force-recreate php
```

Prerequisites:

- app running in `YII_ENV_DEV`
- authenticated manager/admin session

Behavior:

- `POST /test-time/advance-day`
- shifts all scheduled fixtures by `-86400` seconds
- blocked if live fixtures are currently playing
