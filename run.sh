#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_DIR="$ROOT_DIR/frontend"
LOG_DIR="${TMPDIR:-/tmp}/kabita-demo"
DEMO_SEED="${DEMO_SEED:-1}"
RUN_FRONTEND="${RUN_FRONTEND:-1}"

mkdir -p "$LOG_DIR"

log() { printf '\033[1;34m[KABITA]\033[0m %s\n' "$*"; }
fail() { printf '\033[1;31m[ERROR]\033[0m %s\n' "$*" >&2; exit 1; }

cleanup() {
  log "Menghentikan server demo..."
  for pid in "${APP_PID:-}" "${FRONTEND_PID:-}"; do
    if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then kill "$pid" 2>/dev/null || true; fi
  done
}
trap cleanup INT TERM EXIT

command -v php >/dev/null || fail "PHP tidak ditemukan."
command -v node >/dev/null || fail "Node.js tidak ditemukan."
command -v npm >/dev/null || fail "npm tidak ditemukan."

[[ -d "$BACKEND_DIR/vendor" ]] || fail "Dependency Laravel belum ada. Jalankan: cd backend && composer install"

cd "$BACKEND_DIR"
PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
NODE_VERSION="$(node -p 'process.versions.node')"
log "PHP $PHP_VERSION | Node $NODE_VERSION"

if ! php -m | grep -qi '^pdo_mysql$'; then
  fail "Ekstensi pdo_mysql belum aktif. Install php-mysql lalu jalankan ulang."
fi

if ! php artisan db:show --database=mariadb >/dev/null 2>&1; then
  fail "MariaDB tidak dapat diakses. Pastikan service aktif dan DB_* di backend/.env benar."
fi

log "Menjalankan migrasi MariaDB..."
php artisan migrate --force

if [[ "$DEMO_SEED" == "1" ]]; then
  log "Mengisi data demo backend..."
  php artisan db:seed --force
fi

log "Membersihkan cache Laravel..."
php artisan optimize:clear >/dev/null

log "Menjalankan Laravel di http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000 >"$LOG_DIR/laravel.log" 2>&1 &
APP_PID=$!

if [[ "$RUN_FRONTEND" == "1" ]]; then
  [[ -d "$FRONTEND_DIR/node_modules" ]] || fail "Dependency frontend belum ada. Jalankan: cd frontend && npm install"
  log "Menjalankan frontend legacy di http://127.0.0.1:5173"
  cd "$FRONTEND_DIR"
  npm run dev -- --host=127.0.0.1 --port=5173 >"$LOG_DIR/frontend.log" 2>&1 &
  FRONTEND_PID=$!
fi

sleep 2
if ! kill -0 "$APP_PID" 2>/dev/null; then
  tail -40 "$LOG_DIR/laravel.log" >&2
  fail "Laravel gagal dijalankan."
fi

log "Demo siap."
log "Laravel API: http://127.0.0.1:8000/api/v1"
if [[ "$RUN_FRONTEND" == "1" ]]; then log "Vue client: http://127.0.0.1:5173"; fi
log "Akun demo: admin@kabita.test / password"
log "Tekan Ctrl+C untuk menghentikan semua server."

wait
