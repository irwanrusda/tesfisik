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

## Deployment Otomatis dengan GitHub Webhook

Endpoint webhook tersedia di `deploy-webhook.php`. Endpoint ini memverifikasi signature GitHub, hanya menerima push branch `main` dari repository yang dikonfigurasi, menjalankan `git pull --ff-only`, lalu menjalankan migrasi.

Tambahkan konfigurasi berikut ke `.env` produksi:

```env
GITHUB_WEBHOOK_SECRET=secret-panjang-dan-acak
GITHUB_REPOSITORY=irwanrusda/tesfisik
DEPLOY_BRANCH=main
DEPLOY_REPOSITORY_PATH=/home/username/public_html/tesfisik
DEPLOY_GIT_BINARY=/usr/bin/git
DEPLOY_PHP_BINARY=/usr/local/bin/php
```

Pastikan `DEPLOY_REPOSITORY_PATH` adalah folder aplikasi yang memiliki direktori `.git`. Jika repository cPanel berada di luar `public_html`, isi path repository tersebut dan pastikan aplikasi dijalankan dari lokasi yang sama atau tambahkan mekanisme penyalinan terpisah.

Atur webhook pada GitHub melalui `Settings > Webhooks > Add webhook`:

- Payload URL: `https://domain.tld/tesfisik/deploy-webhook.php`
- Content type: `application/json`
- Secret: harus sama persis dengan `GITHUB_WEBHOOK_SECRET`
- Events: `Just the push event`
- Active: aktif

Log deployment disimpan di `storage/logs/deploy.log` dan dilindungi oleh `storage/.htaccess`. Jika respons menyatakan `proc_open` dinonaktifkan, hosting tidak mengizinkan metode webhook ini dan deployment harus menggunakan SSH, cron, atau fitur Git cPanel.

## Penulisan Atlet ke Google Sheet

Fitur Tambah Atlet menggunakan Google Sheets API dengan service account. Izin publik "semua orang dapat mengedit" tidak diperlukan dan sebaiknya diubah menjadi Viewer setelah integrasi aktif.

1. Aktifkan Google Sheets API pada project Google Cloud.
2. Cabut JSON key yang pernah terekspos dan buat key baru.
3. Bagikan spreadsheet sebagai Editor hanya ke email service account.
4. Simpan JSON key baru di luar `public_html`, misalnya `/home/username/.secrets/google-service-account.json`.
5. Atur permission file menjadi `600`.

Tambahkan konfigurasi berikut ke `.env` produksi:

```env
GOOGLE_SHEET_NAME="Atlit dan Pelatih"
GOOGLE_SERVICE_ACCOUNT_JSON=/home/username/.secrets/google-service-account.json
```

Pastikan ekstensi PHP OpenSSL aktif. Hanya superadmin aplikasi yang dapat membuka dan mengirim formulir Tambah Atlet.
