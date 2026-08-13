# Kabita E-Commerce

Marketplace ini memakai Vue 3 sebagai frontend dan Laravel 12 sebagai backend API.
Semua data bisnis mengalir melalui API Laravel; frontend tidak mengakses database secara
langsung. Database lokal default adalah SQLite sehingga proyek dapat dijalankan tanpa
MariaDB/MySQL.

## Menjalankan backend dan frontend

Prasyarat: PHP 8.2+, Composer, Node.js 22+, dan ekstensi PHP `pdo_sqlite`.

Terminal 1 — backend:

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8000
```

API tersedia di `http://localhost:8000/api/v1`. Cek koneksi dengan:

```sh
curl http://localhost:8000/api/v1/health
```

Terminal 2 — frontend:

```sh
cd frontend
npm install
cp .env.example .env
npm run dev
```

Frontend membaca `VITE_API_BASE_URL` dari `.env` (default:
`http://localhost:8000/api/v1`). Jangan membuat query database dari Vue; buat endpoint,
request validation, resource/response, service frontend, lalu hubungkan ke view/store.

## Kontrak integrasi untuk fitur baru

1. Tambahkan route di `backend/routes/api.php` di bawah prefix `v1`.
2. Implementasikan controller, FormRequest, Resource, dan test backend.
3. Tambahkan service typed di `frontend/src/services` yang memakai `apiClient`.
4. Hubungkan service ke Pinia store atau view dan tangani loading/error state.
5. Perbarui dokumentasi kontrak jika format response berubah.

Autentikasi memakai Sanctum Bearer token. `frontend/src/services/apiClient.ts` otomatis
menambahkan token dan menangani respons 401. CORS lokal dikonfigurasi melalui
`CORS_ALLOWED_ORIGINS` di backend `.env`.

## Validasi

```sh
cd backend && php artisan test
cd ../frontend && npm run lint && npm run build
```

Untuk deployment dengan MariaDB/MySQL, ubah `DB_CONNECTION` dan parameter `DB_*` di
backend `.env`; kontrak API tetap sama.
