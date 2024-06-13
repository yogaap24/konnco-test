<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400">
  </a>
</p>

## Instalasi Laravel

Langkah pertama, **clone repository** Laravel dari [https://github.com/yogaap24/konnco-test](https://github.com/yogaap24/konnco-test). Setelah berhasil clone, masuk ke direktori proyek dan pastikan **web server Anda aktif**.

Langkah kedua, pastikan **Composer terinstal** di sistem Anda. Untuk menginstal semua dependensi PHP yang dibutuhkan, jalankan perintah `composer install --ignore-platform-reqs`.

Langkah ketiga, duplikat file `.env.example` dan ubah namanya menjadi `.env`, atau buat file `.env` baru dengan mengcopy isi dari `.env.example`.

Langkah keempat, **generate kunci aplikasi Laravel** dengan perintah `php artisan key:generate`. Pastikan file `.env` terkonfigurasi dengan benar, khususnya untuk setup database dan Redis sesuai kebutuhan aplikasi Anda.

Langkah kelima, untuk memastikan koneksi database, jalankan `php artisan db:monitor` dan lakukan **migrasi database** dengan perintah `php artisan migrate --seed` untuk melakukan migrasi database dan seeder.
> **ℹ️ Info:** kata sandi untuk semua user atau pengguna dari seeder adalah ```password```.

Langkah keenam, **instal Passport untuk autentikasi** dengan `php artisan passport:install --force`. Pastikan semua pertanyaan dijawab dengan `yes`.

> **⚠️ Penting:** Tambahkan atau pastika extension ```pcntl``` ada di php.ini anda terlebih dahulu pada php anda jika anda menggunakan MacOS/Ubunt/WSL. (Extension `pcntl` tidak mendukung windows). 

Langkah ketujuh, untuk **memantau proses pekerjaan latar belakang**, instal Horizon dengan perintah `php artisan horizon:install` dan jalankan dengan `php artisan horizon`.

> **⚠️ Penting:** Horizon **tidak dapat digunakan di Windows**. Jika Anda menggunakan Windows, gunakan WSL (Windows Subsystem for Linux).

Terakhir, pastikan **pekerjaan antrian berjalan** dengan `php artisan queue:work`. Untuk **melakukan pengujian**, jalankan `php artisan test`. Jika Anda menggunakan Windows dan tidak menggunakan WSL, tambahkan opsi `--without-tty` pada perintah pengujian.

Apabila ingin melakukan test jalankan `php artisan test` (jika di Windows dan tidak pakai WSL jalankan `php artisan test --without-tty`). Dan jangan lupa untuk uncomment baris kode berikut di `phpunit.xml`:

```xml
<!-- <env name="DB_CONNECTION" value="sqlite"/> -->
<!-- <env name="DB_DATABASE" value=":memory:"/> -->
```

Atau buat file .env.testing dengan nilai:
```
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```
> **⚠️ Penting:** Pastikan php anda memiliki extension sqlite3 dan pdo_sqlite agar bisa melakukan koneksi ke sqlite.

Sisanya bisa diabaikan. Anda bisa memverifikasi apakah database pengujian digunakan dengan menjalankan pengujian di awal sebelum langkah `ke enam` atau sebelum anda menjalankan ```php artisan migrate --seed```.

Jika Anda ingin mencoba API, Anda bisa melihat dokumentasi di [Postman Documentation](https://documenter.getpostman.com/view/4450235/2sA3XPEPE7) untuk lebih detailnya.

Jika Anda menggunakan Docker, kami telah menyiapkan `docker-compose` dan `Dockerfile`. Anda hanya perlu mengisi konfigurasi berikut pada file `.env`:

```env
APP_URL=
APP_HOST=
IMAGE_NAME=
```
Setelah itu, jalankan perintah berikut untuk memulai layanan Docker:
```docker-compose up -d```