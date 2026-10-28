<?php
declare(strict_types=1);

define('BASE_URL', '/inventaris-ukk');
define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'Stokara');
define('APP_TIMEZONE', 'Asia/Jakarta');

date_default_timezone_set(APP_TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL . '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('STOKARA_SESSION');
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/items.php';
require_once __DIR__ . '/transactions.php';

set_exception_handler(static function (Throwable $error): void {
    log_error($error);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Terjadi kesalahan</title></head>'
        . '<body style="margin:0;min-height:100vh;display:grid;place-items:center;'
        . 'background:#E8EDF3;color:#25303F;font-family:Inter,Segoe UI,system-ui,sans-serif;">'
        . '<main style="max-width:460px;padding:32px;text-align:center;background:#EEF2F7;'
        . 'border-radius:20px;box-shadow:8px 8px 16px #C5CEDA,-8px -8px 16px #FFFFFF;">'
        . '<h1 style="font-size:20px;margin:0 0 8px;">Terjadi kesalahan</h1>'
        . '<p style="font-size:14px;line-height:1.6;color:#5A6678;margin:0 0 20px;">'
        . 'Permintaan tidak dapat diproses saat ini. Silakan coba lagi.</p>'
        . '<a href="' . e(BASE_URL) . '/barang.php" style="display:inline-block;padding:12px 18px;'
        . 'border-radius:12px;background:#4A6D94;color:#fff;text-decoration:none;font-size:14px;">'
        . 'Kembali ke Data Barang</a></main></body></html>';
});
