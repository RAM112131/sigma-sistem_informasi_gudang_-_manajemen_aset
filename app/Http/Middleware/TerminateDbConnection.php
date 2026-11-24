<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup koneksi database secara eksplisit setelah setiap response selesai dikirim.
 *
 * Di Vercel serverless, setiap PHP function invocation adalah proses baru.
 * Tanpa middleware ini, koneksi bisa tetap "menggantung" di sisi DB sampai timeout,
 * yang dengan cepat menghabiskan slot koneksi gratis (mis. filess.io).
 *
 * Dengan terminate(), koneksi diputus segera setelah response dikirim ke client,
 * membebaskan slot lebih cepat dan mengurangi "too many connections".
 *
 * Jika DB_PERSISTENT=true, PDO akan mengembalikan koneksi ke pool PHP
 * (bukan menutupnya) sehingga warm Vercel instance berikutnya bisa reuse.
 */
class TerminateDbConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Hanya putuskan koneksi jika tidak menggunakan persistent connection,
        // karena persistent connection di-manage oleh PDO pool secara otomatis.
        if (! config('database.connections.pgsql.options.'.\PDO::ATTR_PERSISTENT, false)) {
            DB::disconnect();
        }
    }
}
