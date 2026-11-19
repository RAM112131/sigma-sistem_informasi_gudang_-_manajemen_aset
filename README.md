<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Pencarian Semantik

Pencarian Kelola User, Log Audit, Katalog Barang, Stok Store, dan Riwayat Penerimaan memakai Laravel AI SDK dan PostgreSQL dengan ekstensi `pgvector` bila ekstensi tersedia. Credential provider (misalnya `OPENAI_API_KEY`) serta `AI_EMBEDDINGS_PROVIDER` dan `AI_EMBEDDINGS_MODEL` dikonfigurasi di environment. Kolom pgvector menggunakan 1536 dimensi; model/provider yang dipilih harus mendukung dimensi tersebut. Mengubah ukuran vector memerlukan migrasi database yang sesuai. Pengaturan timeout dan cache embedding juga dapat diubah melalui `AI_EMBEDDINGS_TIMEOUT` dan `AI_EMBEDDINGS_CACHE_TTL`. Jika pgvector tidak terpasang atau kolom vector belum tersedia/bertipe benar, pencarian tetap berfungsi dengan fallback teks dan migration tidak gagal. Data perubahan, alamat IP, dan user-agent pada Log Audit tidak dikirim sebagai teks embedding; pencarian literal pada data tersebut tetap didukung. Jalankan migration:

```bash
php artisan migrate
```

Jika pgvector belum tersedia, proses migration akan melewati kolom vector. Untuk mengaktifkan pencarian semantik setelah memasang pgvector, buat migration baru untuk menambahkan kolom vector yang belum ada, lalu jalankan migration dan reindex. Jangan rollback atau reset migration yang sudah berjalan hanya untuk mengulang migration vector.

Jika kolom vector tersedia, indeks data lama yang belum memiliki embedding dan pastikan queue worker berjalan:

```bash
php artisan store:semantic-search-reindex
php artisan queue:work --queue=default,store-import
```

Laravel AI SDK mengelola komunikasi ke provider embedding yang dipilih. Teks pencarian serta teks indeks User, Log Audit yang telah dibatasi, Barang, dan penerimaan dikirim ke provider tersebut untuk menghasilkan embedding.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
