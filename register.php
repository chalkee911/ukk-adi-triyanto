<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesi formulir sudah berakhir. Silakan coba lagi.');
        redirect(BASE_URL . '/register.php');
    }

    $result = auth_register($_POST);

    if ($result['ok']) {
        flash_set('success', $result['message']);
        redirect(BASE_URL . '/login.php');
    }

    form_errors_set($result['errors']);
    old_input_set($_POST);
    flash_set('error', $result['message']);
    redirect(BASE_URL . '/register.php');
}

$pageTitle = 'Daftar';
$showSidebar = false;
require __DIR__ . '/includes/header.php';
?>
<div class="auth-body">
    <section class="auth-card neo-card auth-card-wide" aria-labelledby="register-title">
        <span class="auth-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <circle cx="10" cy="8" r="3.2"/>
                <path d="M4.5 19c.6-3 2.7-4.7 5.5-4.7s4.9 1.7 5.5 4.7" stroke-linecap="round"/>
                <path d="M17 8v5M14.5 10.5h5" stroke-linecap="round"/>
            </svg>
        </span>

        <p class="eyebrow">Stokara</p>
        <h1 class="auth-title" id="register-title">Buat akun</h1>
        <p class="auth-sub">Akun diperlukan untuk mencatat barang dan transaksi stok.</p>

        <?php if (field_error('general') !== null): ?>
            <div class="inline-error" role="alert"><?= e((string) field_error('general')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/register.php" class="auth-form" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label class="form-label" for="name">Nama lengkap</label>
                <input type="text" class="form-control<?= field_error('name') !== null ? ' is-invalid' : '' ?>"
                       id="name" name="name" value="<?= e(old('name')) ?>" maxlength="100"
                       autocomplete="name" placeholder="Nama Anda" required>
                <?php if (field_error('name') !== null): ?>
                    <p class="field-error"><?= e((string) field_error('name')) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label class="form-label" for="username">Username</label>
                <input type="text" class="form-control<?= field_error('username') !== null ? ' is-invalid' : '' ?>"
                       id="username" name="username" value="<?= e(old('username')) ?>" maxlength="30"
                       autocomplete="username" placeholder="contoh: admin" required>
                <p class="form-hint">3-30 karakter, huruf kecil, angka, atau garis bawah.</p>
                <?php if (field_error('username') !== null): ?>
                    <p class="field-error"><?= e((string) field_error('username')) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label class="form-label" for="password">Password</label>
                <input type="password" class="form-control<?= field_error('password') !== null ? ' is-invalid' : '' ?>"
                       id="password" name="password" autocomplete="new-password"
                       placeholder="Minimal 8 karakter" required>
                <?php if (field_error('password') !== null): ?>
                    <p class="field-error"><?= e((string) field_error('password')) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label class="form-label" for="password_confirm">Ulangi password</label>
                <input type="password" class="form-control<?= field_error('password_confirm') !== null ? ' is-invalid' : '' ?>"
                       id="password_confirm" name="password_confirm" autocomplete="new-password"
                       placeholder="Ketik ulang password" required>
                <?php if (field_error('password_confirm') !== null): ?>
                    <p class="field-error"><?= e((string) field_error('password_confirm')) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Daftar</button>
        </form>

        <p class="auth-switch">Sudah punya akun?
            <a href="<?= BASE_URL ?>/login.php">Masuk</a>
        </p>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
