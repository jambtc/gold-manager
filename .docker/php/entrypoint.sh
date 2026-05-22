#!/bin/bash
set -euo pipefail

wait_for_db() {
  local host="${DB_HOST:-mariadb}"
  local port="${DB_PORT:-3306}"
  echo "[entrypoint] Waiting for database ${host}:${port}..."
  local attempts=0
  until php -r "exit(@fsockopen('${host}', ${port}) ? 0 : 1);" >/dev/null 2>&1; do
    sleep 1
    attempts=$((attempts+1))
    if [ $attempts -ge 120 ]; then
      echo "[entrypoint] Warning: database ${host}:${port} still unreachable after ${attempts}s" >&2
      break
    fi
  done
}

run_migrations() {
  echo "[entrypoint] Running database migrations..."
  php /var/www/html/v2/yii migrate/up --interactive=0 || echo "[entrypoint] Migration run failed (maybe DB not ready)."
}

should_seed() {
  php -r '
    $host=getenv("DB_HOST") ?: "mariadb";
    $port=getenv("DB_PORT") ?: "3306";
    $db=getenv("DB_NAME") ?: "gold_manager";
    $user=getenv("DB_USER") ?: "root";
    $pass=getenv("DB_PASSWORD") ?: "";
    try {
      $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
      $count = (int)$pdo->query("SELECT COUNT(*) FROM team")->fetchColumn();
      echo $count === 0 ? "yes" : "no";
    } catch (Throwable $e) {
      fwrite(STDERR, "[entrypoint] seed check failed: {$e->getMessage()}\n");
      echo "error";
    }
  '
}

run_seed_once() {
  if [ "${GM_DISABLE_AUTO_SEED:-0}" = "1" ]; then
    return
  fi
  local result
  result=$(should_seed)
  if [ "$result" = "yes" ]; then
    echo "[entrypoint] Seeding world (first run)..."
    php /var/www/html/v2/yii game/seed || echo "[entrypoint] World seed attempt failed (maybe already seeded)."
  fi
}

PROJECT_ROOT="/var/www/html"
APP_DIR="$PROJECT_ROOT/v2"

cd "$APP_DIR"

if [ -f composer.json ]; then
  if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] Installing PHP dependencies via Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
  else
    echo "[entrypoint] Composer dependencies already present."
  fi
else
  echo "[entrypoint] composer.json not found in $APP_DIR — skipping composer install." >&2
fi

for dir in runtime runtime/cache runtime/logs web/assets; do
  mkdir -p "$dir"
  chown -R www-data:www-data "$dir"
  chmod -R 775 "$dir"
  echo "[entrypoint] Ensured writable $APP_DIR/$dir"
done

wait_for_db
run_migrations
run_seed_once

cd "$PROJECT_ROOT"
if [ $# -eq 0 ]; then
  set -- php-fpm
fi
exec "$@"
