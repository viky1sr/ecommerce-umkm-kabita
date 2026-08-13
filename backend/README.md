# Kabita E-Commerce Backend API

Sistem Informasi E-Commerce UMKM Kabita adalah platform marketplace yang memberdayakan penjual UMKM lokal. Backend ini menyediakan RESTful API yang aman, scalable, dan terdokumentasi dengan baik untuk mendukung fitur e-commerce standar serta dashboard analitik portofolio produk.

Proyek ini dikembangkan menggunakan metode **Waterfall** sebagai bagian dari tugas akhir/skripsi.

## ️ Tech Stack

- **Bahasa Pemrograman:** PHP 8.5
- **Framework:** Laravel 12
- **Database:** SQLite untuk development (MariaDB/MySQL juga didukung untuk deployment)
- **Autentikasi:** Laravel Sanctum (Token-based)
- **API Documentation:** Scramble (OpenAPI 3.1 / Swagger UI)
- **Testing:** Pest PHP / PHPUnit

## 📚 API Documentation

The API is fully documented using **Scramble** (OpenAPI 3.1). Documentation is available at:

- **Swagger UI:** `http://localhost:8000/docs/api`
- **OpenAPI JSON:** `http://localhost:8000/docs/api.json`

### Quick Start with API

1. **Register a new account:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/register \
     -H "Content-Type: application/json" \
     -d '{"name":"John Doe","email":"john@example.com","password":"secret","role":"buyer"}'
   ```

2. **Login and get token:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"john@example.com","password":"secret"}'
   ```

3. **Use token in subsequent requests:**
   ```bash
   curl -X GET http://localhost:8000/api/v1/products \
     -H "Authorization: Bearer {your_token}"
   ```

See [docs/api-documentation.md](docs/api-documentation.md) for complete API reference.

## ✨ Fitur Utama

### 1. Autentikasi & Otorisasi

- Registrasi multi-role (Pembeli & Penjual) dengan verifikasi email via kode OTP 6 digit.
- Login dengan fitur "Remember Me" dan manajemen sesi.
- Role-based Access Control (RBAC): Admin, Seller, Buyer.

### 2. Manajemen Toko & Produk

- Seller dapat membuat toko, mengupload produk, dan mengelola stok.
- Admin melakukan kurasi/verifikasi toko dan produk untuk mencegah barang ilegal.
- Upload gambar produk dengan penyimpanan lokal (Storage Link).

### 3. Sistem Transaksi (Checkout & Pembayaran)

- Keranjang belanja (Cart) yang persisten per user.
- Checkout dengan pilihan metode pengiriman (COD / Kurir Ekspedisi).
- Metode pembayaran: Transfer Bank (Upload Bukti) atau Cash on Delivery (COD).
- Alur status pesanan: Pending -> Processing -> Shipped -> Delivered.

### 4. Dashboard Analitik Portofolio Produk

- Agregasi data penjualan harian (`daily_product_sales`).
- Endpoint khusus untuk menghitung total revenue, profit, dan qty sold per produk/toko/kategori.

## Prasyarat

Sebelum memulai, pastikan Anda telah menginstal:

- [PHP >= 8.5](https://www.php.net/downloads.php)
- [Composer](https://getcomposer.org/)
- PHP extension `pdo_sqlite` (untuk development lokal)
- [Node.js & NPM](https://nodejs.org/) (Untuk menjalankan frontend)
- [XAMPP / Laragon](https://laragon.org/) (Opsional, untuk manajemen server lokal)

## 🚀 Instalasi & Setup

### 1. Clone Repository

```bash
git clone https://github.com/username/kabita-ecommerce.git
cd kabita-ecommerce/backend
```

### 2. Instalasi Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

Salin file environment dan generate application key:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi Database

Development menggunakan SQLite. Buat file database lalu gunakan konfigurasi berikut:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Untuk deployment MariaDB/MySQL, ganti `DB_CONNECTION` dan parameter koneksi `DB_*` sesuai
server database.

### 5. Jalankan Migrasi & Seeding

Membuat tabel database sesuai skema yang telah dirancang:

```bash
php artisan migrate --seed
```

### 6. Buat Storage Link

Untuk mengakses gambar produk dan bukti pembayaran yang diupload:

```bash
php artisan storage:link
```

### 7. Jalankan Server

```bash
php artisan serve
```

API akan berjalan di `http://localhost:8000`.

## Dokumentasi API (Swagger UI)

Proyek ini menggunakan **Scramble** untuk menghasilkan dokumentasi API secara otomatis berdasarkan FormRequest dan API Resource.

1. Pastikan server Laravel berjalan (`php artisan serve`).
2. Buka browser dan akses:
   👉 **[http://localhost:8000/docs/api](http://localhost:8000/docs/api)**
3. Untuk menguji endpoint yang membutuhkan autentikasi, klik tombol **Authorize** di pojok kanan atas Swagger UI, dan masukkan token Bearer Anda (Format: `Bearer <token_anda>`).

## 🗄️ Skema Database

Berikut adalah entitas utama dalam database `kabita_ecommerce`:

| Tabel                      | Deskripsi                                              |
| -------------------------- | ------------------------------------------------------ |
| `users`                    | Menyimpan data Admin, Seller, dan Buyer.               |
| `shops`                    | Data toko milik Seller (membutuhkan verifikasi Admin). |
| `categories`               | Kategori produk (Makanan, Fashion, dll).               |
| `products`                 | Data produk UMKM (membutuhkan verifikasi Admin).       |
| `carts` & `cart_items`     | Keranjang belanja milik Buyer.                         |
| `orders` & `order_items`   | Data pesanan dan detail item pesanan.                  |
| `payments`                 | Bukti pembayaran dan status verifikasi.                |
| `daily_product_sales`      | Tabel agregasi untuk Dashboard Analitik.               |
| `email_verification_codes` | Menyimpan kode OTP 6 digit untuk verifikasi email.     |

## 👥 Hak Akses (Roles)

| Role       | Deskripsi                                                      |
| ---------- | -------------------------------------------------------------- |
| **Admin**  | Mengelola sistem, memverifikasi Toko, Produk, dan Pembayaran.  |
| **Seller** | Mengelola Toko, mengupload Produk, dan memproses Pesanan.      |
| **Buyer**  | Berbelanja, mengelola Keranjang, Checkout, dan Review Pesanan. |

## 🧪 Testing

Menjalankan unit test dan feature test menggunakan Pest/PHPUnit:

```bash
php artisan test
```

## 📁 Struktur Folder Penting

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   # Controller untuk endpoint API
│   │   ├── Requests/          # Form Request untuk validasi & Swagger
│   │   └── Resources/         # API Resource untuk formatting JSON
│   ├── Models/                # Eloquent Models & Relasi
│   └── Services/              # Business Logic (Opsional)
├── database/
│   ├── migrations/            # Skema database
│   └── seeders/               # Data dummy
├── routes/
│   └── api.php                # Definisi endpoint API
└── storage/
    └── app/public/            # Upload gambar produk & bukti bayar
```

## 📝 Lisensi

Proyek ini dibuat untuk keperluan akademis (Skripsi) dan bersifat open-source di bawah lisensi [MIT](https://opensource.org/licenses/MIT).

---

**Dikembangkan oleh:** [Nama Kamu]  
**Tahun:** 2026

---

### 💡 Tips Tambahan untuk Skripsi:

1. **Ganti Placeholder:** Jangan lupa ubah `https://github.com/username/kabita-ecommerce.git` dan `[Nama Kamu]` di bagian bawah dengan data asli kamu.
2. **Lampiran Skripsi:** Kamu bisa langsung _screenshot_ atau _print-to-PDF_ halaman `README.md` ini (atau hasil render-nya di GitHub) untuk dijadikan lampiran **"Dokumentasi Setup Proyek"** di Bab 4 (Implementasi) skripsi kamu. Dosen sangat suka dokumentasi yang rapi seperti ini!

Sudah siap? Kalau sudah, kita bisa lanjut ke **pembuatan API Produk & Toko** atau **setup Swagger (Scramble)** seperti yang dibahas sebelumnya. Mau yang mana dulu? 🚀

### Tips untuk publish API docs

`php artisan vendor:publish --tag=scramble-config`
