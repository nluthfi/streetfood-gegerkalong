# Street Food Gegerkalong

Aplikasi web direktori kuliner, pemesanan, dan manajemen UMKM street food di kawasan Gegerkalong. Dibangun dengan PHP native (MySQLi), MySQL 8.0 / MariaDB, serta konfigurasi siap pakai menggunakan Docker dan Apache.

---

## Fitur Utama

### 1. Halaman Publik (`public/`)
- **Katalog & Filter Toko (`public/index.php`)**: Jelajahi daftar toko kuliner dengan pencarian nama toko/menu serta filter status halal, mitra, metode pembayaran, rasa, dan harga.
- **Detail Toko & Keranjang (`public/page_menu.php`)**: Menampilkan jam operasional, informasi mitra, metode pembayaran, daftar menu dengan pencarian & filter rasa, keranjang belanja berbasis `localStorage`, serta formulir ulasan pelanggan (`public/submit_review.php`).
- **Checkout & Pembuatan Pesanan (`public/checkout.php`, `public/process_order.php`, `public/order_success.php`)**: Validasi item keranjang dan harga di sisi server, penyimpanan transaksi ke `pesanan` dan `detail_pesanan`, serta halaman struk pesanan.
- **Lacak Status Pesanan (`public/cek_pesanan.php`)**: Pelanggan dapat memantau progres pesanan (`baru`, `diproses`, `selesai`, `dibatalkan`) menggunakan ID pesanan dan nomor telepon pemesan (mendukung format `08...` maupun `62...`).

### 2. Panel Admin (`admin/`)
- **Autentikasi (`admin/auth/`)**: Login admin dengan verifikasi hash password (`password_verify`), regenerasi ID sesi, perlindungan CSRF, dan rate limiting (maksimal 5 percobaan gagal per 15 menit).
- **Dashboard (`admin/dashboard.php`)**: Ringkasan pesanan baru/aktif, total pendapatan (tidak termasuk pesanan dibatalkan), total ulasan, katalog toko/menu, serta daftar pesanan terbaru.
- **Manajemen Pesanan (`admin/pesanan/`)**: Filter berdasarkan tab status, pencarian ID/pemesan/telepon/toko, modal rincian item pesanan, dan pembaruan status pesanan.
- **Moderasi Ulasan (`admin/ulasan/`)**: Filter ulasan berdasarkan rating, toko, dan kata kunci, melihat distribusi rating, serta menghapus ulasan dengan pembaruan otomatis rata-rata `toko.rating`.
- **Manajemen Katalog & Master Data**:
  - `admin/toko/`: CRUD toko (jam buka/tutup, lokasi, status halal, foto outlet, relasi mitra & metode pembayaran).
  - `admin/menu/`: CRUD menu (harga, deskripsi, kategori, toko, tag rasa, foto lokal/URL, serta endpoint `ajax_menu.php`).
  - `admin/kategori/`, `admin/mitra/`, `admin/bayar/`: Pengelolaan kategori makanan, mitra pengiriman, dan metode pembayaran lengkap dengan validasi relasi sebelum penghapusan.

---

## Struktur Direktori

```text
streetfood-gegerkalong/
├── admin/                  # Modul panel admin (auth, dashboard, toko, menu, pesanan, ulasan, kategori, mitra, bayar)
├── app/
│   ├── config/
│   │   ├── app.php         # Konfigurasi timezone, session cookie, helper URL & escaping HTML
│   │   ├── database.php    # Koneksi MySQLi berbasis environment variable
│   │   └── security.php    # Helper CSRF, validasi input, upload gambar, dan rate limiting
│   └── views/layout/       # Header, navigasi atas (sidebar.php), dan footer panel admin
├── database/
│   └── db_streetfood.sql   # Skema tabel lengkap + data awal (seed)
├── docker/
│   └── apache-vhost.conf   # Konfigurasi Apache VirtualHost (DocumentRoot public/, Alias /admin)
├── public/                 # Entrypoint publik, aset CSS/gambar, checkout, lacak pesanan, ulasan
├── .env.example            # Contoh environment variable untuk Docker Compose
├── .htaccess               # Aturan dasar Apache (nonaktifkan directory listing & security headers)
├── Dockerfile              # Image PHP 8.5 + Apache (mysqli, pdo, pdo_mysql, headers, rewrite)
├── docker-compose.yml      # Orkestrasi layanan app + MySQL 8.0
└── index.php               # Redirect root ke direktori public/
```

---

## Skema Database

File [`database/db_streetfood.sql`](database/db_streetfood.sql) berisi skema dan seed data untuk tabel berikut:
- **Master & Katalog**: `toko`, `menu`, `kategori`, `rasa`, `menu_rasa`, `mitra`, `toko_mitra`, `bayar`, `metode_toko`
- **Transaksi & Interaksi**: `pesanan`, `detail_pesanan`, `review`
- **Autentikasi Admin**: `users` (menyertakan akun awal `admin1` pada seed SQL)

> **Catatan Keamanan:** Segera ganti password akun admin bawaan setelah instalasi pada lingkungan produksi atau staging.

---

## Cara Menjalankan

### Opsi 1: Menggunakan Docker Compose (Direkomendasikan)

1. Salin file konfigurasi environment dan sesuaikan kredensialnya:
   ```bash
   cp .env.example .env
   ```
2. Jalankan container aplikasi dan database:
   ```bash
   docker compose up -d --build
   ```
   Layanan `db` akan mengimpor `database/db_streetfood.sql` secara otomatis saat inisialisasi volume pertama kali.
3. Akses aplikasi melalui browser:
   - Storefront publik: `http://localhost:8080/`
   - Panel admin: `http://localhost:8080/admin/auth/login.php`

### Opsi 2: Server PHP / Apache Lokal (XAMPP, LAMPP, dll.)

1. Buat database `db_streetfood` lalu impor file `database/db_streetfood.sql`.
2. Konfigurasikan environment variable (opsional, jika berbeda dari default):
   - `DB_HOST` (default: `localhost`)
   - `DB_USER` (default: `root`)
   - `DB_PASSWORD` (default: `""`)
   - `DB_NAME` (default: `db_streetfood`)
3. Arahkan VirtualHost ke `public/` (dan alias `/admin` ke `admin/` seperti di [`docker/apache-vhost.conf`](docker/apache-vhost.conf)), atau akses melalui URL proyek Anda (helper `app_base_url()` di [`app/config/app.php`](app/config/app.php) juga mendukung prefix `/tubes/`).

---

## Keamanan & Verifikasi

Beberapa perlindungan yang diterapkan pada repositori ini:
- **Proteksi CSRF & Session**: Token CSRF pada form `POST` admin dan publik, cookie sesi `HttpOnly` dan `SameSite=Lax`.
- **Query Aman**: Prepared statement MySQLi untuk query dinamis, input form, transaksi pesanan, dan moderasi.
- **Upload Gambar Tervalidasi**: Pemeriksaan ukuran maksimal 2 MB, MIME type via `finfo`, serta validasi `getimagesize` (`jpg`, `png`, `webp`).
- **Pembatasan Akses Direktori**: Apache vhost memblokir akses langsung ke `app/`, `database/`, `docker/`, dan `.git`.

Untuk memeriksa sintaks seluruh file PHP secara lokal:
```bash
find admin app public index.php -name '*.php' -print0 | xargs -0 -n1 php -l
```
