<?php
declare(strict_types=1);

/**
 * Keluarkan teks ke HTML dengan aman.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Token CSRF untuk sesi aktif. Token dibuat sekali per sesi.
 */
function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || $token === '') {
        return false;
    }

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    if (!isset($flash['type'], $flash['message'])) {
        return null;
    }

    return [
        'type' => (string) $flash['type'],
        'message' => (string) $flash['message'],
    ];
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Nilai lama dari POST yang gagal divalidasi.
 */
function old(string $key, string $default = ''): string
{
    $data = $_SESSION['old_input'] ?? [];

    if (!is_array($data) || !array_key_exists($key, $data)) {
        return $default;
    }

    $value = $data[$key];

    return is_scalar($value) ? (string) $value : $default;
}

function old_input_set(array $data): void
{
    unset($data['csrf_token'], $data['password'], $data['password_confirm']);
    $_SESSION['old_input'] = $data;
}

function old_input_clear(): void
{
    unset($_SESSION['old_input']);
}

/**
 * Error validasi per field, ditampilkan di form setelah redirect (PRG).
 */
function form_errors_set(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

function form_errors_get(): array
{
    $errors = $_SESSION['form_errors'] ?? [];

    return is_array($errors) ? $errors : [];
}

function field_error(string $field): ?string
{
    $errors = form_errors_get();

    if (!isset($errors[$field])) {
        return null;
    }

    return (string) $errors[$field];
}

function form_errors_clear(): void
{
    unset($_SESSION['form_errors']);
}

/**
 * Netralkan karakter khusus LIKE agar input pengguna tidak menjadi wildcard.
 */
function escape_like(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function is_date_ymd(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value;
}

/**
 * Catat error ke logs/app.log. Tidak pernah ditampilkan ke pengguna.
 */
function log_error(Throwable|string $error): void
{
    if ($error instanceof Throwable) {
        $message = get_class($error) . ': ' . $error->getMessage()
            . ' di ' . $error->getFile() . ':' . $error->getLine()
            . PHP_EOL . $error->getTraceAsString();
    } else {
        $message = $error;
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    $directory = APP_ROOT . '/logs';

    if (!is_dir($directory)) {
        @mkdir($directory, 0755, true);
    }

    $logFile = $directory . '/app.log';
    $written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    if ($written === false) {
        error_log($line);
    }
}
