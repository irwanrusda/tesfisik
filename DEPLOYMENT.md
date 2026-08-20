# Deployment cPanel dari GitHub

## Persiapan cPanel

1. Buat database dan user MySQL melalui cPanel, lalu berikan seluruh hak akses pada database.
2. Aktifkan SSH Access dan tambahkan public key untuk deployment.
3. Tentukan folder aplikasi, misalnya `/home/username/public_html/tesfisik`.
4. Salin `.env.example` menjadi `.env` langsung di server dan isi konfigurasi produksi.
5. Pastikan versi PHP cPanel minimal PHP 8.1 dan ekstensi `pdo_mysql` aktif.

## GitHub Actions Secrets

Tambahkan repository secrets berikut pada `Settings > Secrets and variables > Actions`:

- `CPANEL_HOST`: hostname atau IP server.
- `CPANEL_USER`: username cPanel/SSH.
- `CPANEL_PORT`: port SSH, biasanya `22`.
- `CPANEL_SSH_KEY`: private key SSH lengkap.
- `CPANEL_PATH`: lokasi absolut aplikasi di server.

Setiap push ke branch `main` akan mengunggah aplikasi dan menjalankan `php migrate.php` melalui SSH.

## Migrasi Database

Tambahkan perubahan skema sebagai file SQL baru di `database/migrations` dengan urutan meningkat, misalnya `004_add_team_to_athlete_tests.sql`. Jangan mengubah file migrasi yang telah dijalankan di produksi.

Migrasi dapat dijalankan dengan dua cara:

```bash
php migrate.php
```

Atau melalui browser jika SSH tidak tersedia:

```text
https://domain.tld/migrate.php?key=MIGRATION_KEY
```

Gunakan `MIGRATION_KEY` panjang dan acak. Cara CLI melalui workflow lebih disarankan.

## Login Awal

Saat tabel user masih kosong, migrasi membuat superadmin dari `ADMIN_USERNAME` dan `ADMIN_PASSWORD` pada `.env`. Jika variabel tersebut tidak diisi, akun fallback adalah `admin` / `Admin123!`; password ini wajib segera diganti melalui menu Manajemen User.

Migrasi juga memastikan akun operasional dari variabel berikut tersedia:

- `INPUT_USERNAME` dan `INPUT_PASSWORD`: akses Input Data, Laporan, serta Atlet & Pelatih.
- `PANITIA_USERNAME` dan `PANITIA_PASSWORD`: seluruh akses akun Input ditambah Summary dan Analisis.

Gunakan password produksi yang kuat dan berbeda untuk setiap akun.
