<?php
declare(strict_types=1);

function auth_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function require_auth(): void
{
    if (!auth_check()) {
        flash_set('warning', 'Silakan masuk untuk membuka halaman ini.');
        redirect(BASE_URL . '/login.php');
    }
}

function require_guest(): void
{
    if (auth_check()) {
        redirect(BASE_URL . '/barang.php');
    }
}

/**
 * @return array{ok: bool, message: string}
 */
function auth_login(string $username, string $password): array
{
    $username = strtolower(trim($username));

    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'Username dan password wajib diisi.'];
    }

    try {
        $statement = db()->prepare(
            'SELECT id, name, username, password_hash FROM users WHERE username = ? LIMIT 1'
        );
        $statement->execute([$username]);
        $user = $statement->fetch();
    } catch (Throwable $error) {
        log_error($error);

        return ['ok' => false, 'message' => 'Terjadi kesalahan saat memproses masuk. Silakan coba lagi.'];
    }

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        return ['ok' => false, 'message' => 'Username atau password salah.'];
    }

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        try {
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        } catch (Throwable $error) {
            log_error($error);
        }
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'username' => (string) $user['username'],
    ];

    return ['ok' => true, 'message' => 'Berhasil masuk.'];
}

/**
 * @return array{ok: bool, message: string, errors: array<string, string>}
 */
function auth_register(array $input): array
{
    $name = trim((string) ($input['name'] ?? ''));
    $username = strtolower(trim((string) ($input['username'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $passwordConfirm = (string) ($input['password_confirm'] ?? '');

    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Nama lengkap wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Nama lengkap maksimal 100 karakter.';
    }

    if ($username === '') {
        $errors['username'] = 'Username wajib diisi.';
    } elseif (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        $errors['username'] = 'Username 3-30 karakter, hanya huruf kecil, angka, dan garis bawah.';
    } else {
        try {
            $check = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->execute([$username]);

            if ($check->fetch()) {
                $errors['username'] = 'Username sudah digunakan.';
            }
        } catch (Throwable $error) {
            log_error($error);
            $errors['general'] = 'Terjadi kesalahan saat memeriksa username. Silakan coba lagi.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password maksimal 72 karakter.';
    }

    if ($passwordConfirm === '') {
        $errors['password_confirm'] = 'Konfirmasi password wajib diisi.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
    }

    if ($errors !== []) {
        return [
            'ok' => false,
            'message' => 'Periksa kembali data pendaftaran.',
            'errors' => $errors,
        ];
    }

    try {
        $statement = db()->prepare(
            'INSERT INTO users (name, username, password_hash) VALUES (?, ?, ?)'
        );
        $statement->execute([
            $name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            return [
                'ok' => false,
                'message' => 'Username sudah digunakan.',
                'errors' => ['username' => 'Username sudah digunakan.'],
            ];
        }

        log_error($error);

        return [
            'ok' => false,
            'message' => 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.',
            'errors' => [],
        ];
    } catch (Throwable $error) {
        log_error($error);

        return [
            'ok' => false,
            'message' => 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.',
            'errors' => [],
        ];
    }

    return [
        'ok' => true,
        'message' => 'Akun berhasil dibuat. Silakan masuk.',
        'errors' => [],
    ];
}
