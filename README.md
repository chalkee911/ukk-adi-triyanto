# Stokara

Sistem inventaris barang dan transaksi stok untuk praktik demonstrasi sertifikasi Pengembang Web Pratama. Aplikasi berjalan di XAMPP dengan PHP native, PDO, dan MySQL/MariaDB. Tidak memakai Composer, npm, atau build tool.

Stokara mencatat barang, transaksi masuk, dan transaksi keluar. Stok tidak diisi manual: stok awal selalu 0 dan hanya berubah ketika transaksi disimpan.

## Tampilan

Enam tampilan inti aplikasi:

| Halaman | File | Isi |
| --- | --- | --- |
| Data Barang | `barang.php` | Ringkasan jumlah barang dan total stok, pencarian, filter kategori, tabel, edit, hapus |
| Tambah Barang | `barang_form.php?mode=create` | Form barang baru dengan stok awal 0 |
| Ubah Barang | `barang_form.php?mode=edit&id=N` | Form edit tanpa field stok |
| Barang Masuk | `transaksi_masuk.php` | Form transaksi masuk dan penambahan stok |
| Barang Keluar | `transaksi_keluar.php` | Form transaksi keluar, info stok tersedia, validasi kecukupan stok |
| Riwayat Transaksi | `riwayat.php` | Semua transaksi, filter, total masuk/keluar, cetak PDF |

Dua halaman akun:

| Halaman | File | Isi |
| --- | --- | --- |
| Masuk | `login.php` | Validasi akun dan pembuatan sesi |
| Daftar | `register.php` | Pendaftaran akun baru |

`index.php` hanya mengarahkan ke `barang.php`. Halaman utama dilindungi login. Pengguna yang belum punya akun dapat mendaftar dari halaman Daftar.

## Kebutuhan

- XAMPP dengan Apache dan MySQL/MariaDB aktif.
- PHP 8.x dengan ekstensi `pdo_mysql` dan `mbstring` (keduanya sudah ada di XAMPP standar).
- Browser modern.
- Koneksi internet. Bootstrap 5.3.3, jsPDF 2.5.1, dan jsPDF-AutoTable 3.8.2 dimuat dari CDN. Tanpa internet, tampilan dasar masih terbaca, tetapi modal Bootstrap dan tombol Cetak PDF tidak akan berfungsi.

## Cara memasang

1. Buka XAMPP Control Panel, lalu klik **Start** pada Apache dan MySQL.
2. Salin folder `inventaris-ukk` ke `C:\xampp\htdocs\inventaris-ukk`. Jangan mengubah nama foldernya karena `BASE_URL` disetel ke `/inventaris-ukk`.
3. Buka `http://localhost/phpmyadmin`.
4. Pilih tab **Import**, pilih file `database/inventaris.sql`, lalu klik **Go**. File ini membuat database `inventaris_ukk` beserta tabel `users`, `items`, dan `stock_transactions`.
5. Buka `http://localhost/inventaris-ukk/`.
6. Klik **Daftar**, buat akun pertama, lalu masuk. Tidak ada akun bawaan di file SQL.
7. Setelah masuk, tambahkan barang melalui menu **Tambah Barang**.

Konfigurasi database ada di `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventaris_ukk');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Nilai di atas cocok untuk XAMPP bawaan. Jika MySQL Anda memakai password, ubah `DB_PASS`.

## Aturan data

- Stok awal barang selalu 0.
- Stok hanya berubah melalui `create_transaction()` di `includes/transactions.php`.
- Transaksi masuk menambah stok, transaksi keluar mengurangi stok.
- Transaksi keluar ditolak jika jumlahnya melebihi stok tersedia.
- Barang yang sudah memiliki riwayat transaksi tidak dapat dihapus. Sistem akan menampilkan pesan penolakan.
- Barang tanpa riwayat transaksi dapat dihapus permanen.
- Transaksi yang sudah tersimpan tidak dapat diedit atau dihapus.
- Nomor transaksi dibuat otomatis dengan format `IN-YYYYMMDD-00001` atau `OUT-YYYYMMDD-00001`.

## Keamanan

- Password disimpan dengan `password_hash()` dan diperiksa dengan `password_verify()`.
- Sesi memakai cookie `HttpOnly`, `SameSite=Lax`, dan ID sesi dibuat ulang setelah login.
- Cookie sesi tidak memiliki masa berlaku permanen, sehingga menutup browser mengakhiri sesi. Tombol keluar menghancurkan sesi di server.
- Semua form POST memakai token CSRF dan diperiksa dengan `hash_equals()`.
- Semua query memakai PDO prepared statement. Karakter `%` dan `_` pada pencarian di-escape.
- Semua output melewati `e()` (`htmlspecialchars` dengan `ENT_QUOTES`).
- Setelah POST, halaman selalu dialihkan (PRG), sehingga refresh tidak mengirim ulang data.
- Error tidak ditampilkan ke pengguna. Detail error dicatat ke `logs/app.log`; folder ini dilindungi `.htaccess`.

Folder `config`, `includes`, dan `logs` memakai `Require all denied`. File root memakai `Options -Indexes`.

## Log

Log aplikasi berada di:

```
logs/app.log
```

File dibuat otomatis saat terjadi exception. Jika folder `logs` tidak bisa ditulis, aplikasi memakai `error_log` bawaan PHP. Jangan mengirim `logs/app.log` ke asesor karena isinya hanya catatan error lokal.

## Cetak PDF

Halaman Riwayat Transaksi menyimpan hasil filter ke elemen JSON, lalu `assets/js/app.js` membuat PDF dengan jsPDF dan AutoTable. PDF memuat seluruh baris hasil filter, bukan hanya yang terlihat di layar, beserta total masuk dan total keluar. Nama file: `riwayat-transaksi-YYYYMMDD.pdf`.

Tombol Cetak PDF dinonaktifkan jika hasil filter kosong. Jika library gagal dimuat, muncul pesan: "Library PDF gagal dimuat, periksa koneksi internet."

## Struktur folder

```
inventaris-ukk/
├── index.php
├── login.php
├── register.php
├── logout.php
├── barang.php
├── barang_form.php
├── transaksi_masuk.php
├── transaksi_keluar.php
├── riwayat.php
├── README.md
├── .htaccess
├── config/
│   ├── database.php
│   └── .htaccess
├── database/
│   └── inventaris.sql
├── includes/
│   ├── bootstrap.php
│   ├── db.php
│   ├── auth.php
│   ├── functions.php
│   ├── items.php
│   ├── transactions.php
│   ├── header.php
│   ├── footer.php
│   ├── transaction_page.php
│   └── .htaccess
├── logs/
│   └── .htaccess
└── assets/
    ├── css/style.css
    └── js/app.js
```

## Checklist uji manual

Lakukan pengujian di XAMPP setelah database diimpor.

1. Tambah barang valid. Pastikan barang muncul di tabel dengan stok 0.
2. Tambah barang dengan kode yang sama. Pastikan ditolak dan muncul pesan kode sudah digunakan.
3. Kirim form barang dengan field wajib kosong. Pastikan validasi muncul dan data tidak tersimpan.
4. Edit nama barang. Pastikan stok tidak berubah.
5. Hapus barang yang belum punya transaksi. Pastikan barang hilang dari tabel.
6. Catat transaksi untuk sebuah barang, lalu coba hapus barang tersebut. Pastikan penghapusan ditolak.
7. Catat barang masuk sebanyak 50. Pastikan stok menjadi 50.
8. Catat barang keluar sebanyak 20. Pastikan stok menjadi 30.
9. Catat barang keluar sebanyak 31. Pastikan ditolak dengan pesan stok tersedia 30, dan stok tetap 30.
10. Coba jumlah 0, -5, dan `abc`. Pastikan ketiganya ditolak.
11. Isi nama barang dengan `<script>alert(1)</script>`. Pastikan teks tampil apa adanya dan tidak dijalankan.
12. Uji filter riwayat (kata kunci, jenis, rentang tanggal), lalu klik Cetak PDF. Pastikan isi PDF sama dengan hasil filter.
13. Buka semua tautan sidebar, tombol Kembali/Batal, dan halaman login/daftar. Kecilkan browser sampai lebar HP, lalu pastikan layout tetap rapi, sidebar dapat dibuka, dan tabel tidak merusak halaman.

Query pemeriksa konsistensi stok:

```sql
SELECT i.code, i.stock,
       COALESCE(SUM(CASE WHEN t.type = 'IN' THEN t.quantity ELSE -t.quantity END), 0) AS computed
FROM items i
LEFT JOIN stock_transactions t ON t.item_id = i.id
GROUP BY i.id
HAVING i.stock <> computed;
```

Hasil kosong berarti stok setiap barang sama dengan total transaksi masuk dikurangi transaksi keluar.

## Mengemas hasil untuk asesor

Pilihan 1: kirim ZIP berisi folder `inventaris-ukk` (tanpa `logs/app.log`) dan pastikan `database/inventaris.sql` ikut di dalamnya. Asesor cukup menyalin folder ke `htdocs`, mengimpor SQL, lalu membuka `http://localhost/inventaris-ukk/`.

Pilihan 2: kirim tautan repositori:

```
https://github.com/chalkee911/ukk-adi-triyanto
```

Sertakan keterangan akun dibuat melalui halaman Daftar, bukan dari data bawaan.

## Jika terjadi masalah

- **Halaman putih atau error 500:** periksa `logs/app.log`, lalu pastikan `config/database.php` sesuai dengan MySQL Anda.
- **Tidak bisa terhubung ke database:** pastikan MySQL berjalan dan database `inventaris_ukk` sudah diimpor.
- **Tampilan berantakan:** pastikan komputer terhubung internet karena CSS Bootstrap dimuat dari CDN.
- **PDF tidak muncul:** periksa koneksi internet dan izin pop-up browser.
- **Sesi terus berakhir:** pastikan browser tidak memblokir cookie untuk `localhost`.
