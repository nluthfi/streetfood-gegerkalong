# Street Food Gegerkalong - Audited Build

## Perbaikan utama
- Search homepage sekarang mencakup nama/deskripsi menu.
- Filter rasa `Berkuah` ditambahkan ke database dan mapping menu berkuah.
- Cart disimpan di localStorage per toko agar tidak hilang saat refresh.
- Checkout memvalidasi ulang menu, harga, toko, dan jumlah di server.
- Pesanan disimpan dalam `pesanan` + `detail_pesanan` dan tersedia di panel admin.
- Review publik ditambahkan dengan CSRF dan rating 1-5.
- Login memiliki rate limit 5 percobaan per 15 menit per session.
- Logout menggunakan POST + CSRF.
- Database connection memakai mysqli strict error reporting dan tidak membocorkan error internal.
- Harga dinormalisasi dari data lama yang masih dalam satuan ribuan.
- Pagination dan pencarian admin menu ditambahkan.
- Security headers, `.git` blocking, dan Docker config diperbaiki.
- Session menggunakan konfigurasi cookie yang lebih aman.
- Nomor telepon, foto, lokasi, dan rating memakai tipe database yang lebih sesuai.

## Menjalankan dengan Docker

1. Salin `.env.example` menjadi `.env`.
2. Ganti password database di `.env`.
3. Jalankan:

```bash
docker compose up --build
```

4. Buka `http://localhost:8080`.

Database diinisialisasi otomatis dari `database/db_streetfood.sql` pada volume database baru.

## Catatan penting

Jika database Docker lama masih memakai volume sebelumnya, hapus volume sebelum menguji dump SQL terbaru:

```bash
docker compose down -v
docker compose up --build
```

Perintah tersebut menghapus data database Docker lama. Jangan dipakai pada database yang berisi data produksi.

## Admin

Login admin tetap memakai akun yang sudah ada di dump database. Password tidak diubah oleh patch ini.
