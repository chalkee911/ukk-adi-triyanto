<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesi formulir sudah berakhir. Silakan coba lagi.');
        redirect(BASE_URL . '/login.php');
    }

    $result = auth_login((string) ($_POST['username'] ?? ''), (string) ($_POST['password'] ?? ''));

    if ($result['ok']) {
        $user = auth_user();
        flash_set('success', 'Selamat datang, ' . ($user['name'] ?? 'pengguna') . '.');
        redirect(BASE_URL . '/barang.php');
    }

    flash_set('error', $result['message']);
    old_input_set(['username' => (string) ($_POST['username'] ?? '')]);
    redirect(BASE_URL . '/login.php');
}

$pageTitle = 'Masuk';
$showSidebar = false;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
    <section class="auth-card neo-card" aria-labelledby="login-title">
        <span class="auth-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <rect x="5" y="10" width="14" height="10" rx="3"/>
                <path d="M8.5 10V7.5a3.5 3.5 0 0 1 7 0V10" stroke-linecap="round"/>
                <path d="M12 14v2" stroke-linecap="round"/>
            </svg>
        </span>

        <p class="eyebrow">Stokara</p>
        <h1 class="auth-title" id="login-title">Masuk ke akun</h1>
        <p class="auth-sub">Kelola data barang dan transaksi stok dari satu tempat.</p>

        <form method="post" action="<?= BASE_URL ?>/login.php" class="auth-form" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e(old('username')) ?>" maxlength="30" autocomplete="username"
                       placeholder="contoh: admin" required autofocus>
            </div>

            <div class="field">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       autocomplete="current-password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>

        <p class="auth-switch">Belum punya akun?
            <a href="<?= BASE_URL ?>/register.php">Daftar sekarang</a>
        </p>
        <p class="auth-note">Untuk keamanan, sesi berakhir saat browser ditutup atau Anda keluar.</p>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
