<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Beranda';
$activePage = $activePage ?? '';
$showSidebar = $showSidebar ?? auth_check();
$bodyClass = $bodyClass ?? '';
$user = auth_user();
$flash = flash_get();
$flashClass = 'flash-info';

if ($flash !== null) {
    $flashClass = match ($flash['type']) {
        'success' => 'flash-success',
        'error', 'danger' => 'flash-danger',
        'warning' => 'flash-warning',
        default => 'flash-info',
    };
}

$initial = 'S';
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

if ($user !== null) {
    $initial = mb_strtoupper(mb_substr((string) $user['name'], 0, 1));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= e($pageTitle) ?> &middot; Stokara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#konten">Lewati ke konten</a>

<header class="app-header">
    <div class="header-inner">
        <?php if ($showSidebar): ?>
            <button class="menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Buka menu navigasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                </svg>
            </button>
        <?php endif; ?>

        <a class="brand" href="<?= BASE_URL ?>/<?= $user !== null ? 'barang.php' : 'login.php' ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                    <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="brand-text">
                <span class="brand-name">Stokara</span>
                <span class="brand-sub">Inventaris barang</span>
            </span>
        </a>

        <div class="header-right">
            <?php if ($user !== null): ?>
                <div class="header-user">
                    <span class="avatar avatar-sm"><?= e($initial) ?></span>
                    <span class="header-user-text">
                        <strong><?= e((string) $user['name']) ?></strong>
                        <span>@<?= e((string) $user['username']) ?></span>
                    </span>
                </div>
            <?php else: ?>
                <div class="header-auth-links">
                    <?php if ($currentScript !== 'login.php'): ?>
                        <a class="btn btn-soft btn-sm" href="<?= BASE_URL ?>/login.php">Masuk</a>
                    <?php endif; ?>
                    <?php if ($currentScript !== 'register.php'): ?>
                        <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/register.php">Daftar</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="app-body<?= $showSidebar ? '' : ' app-body-auth' ?>">
    <?php if ($showSidebar): ?>
        <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarMenu"
               aria-label="Menu aplikasi">
            <div class="offcanvas-header d-lg-none">
                <span class="offcanvas-title">Menu</span>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                        data-bs-target="#sidebarMenu" aria-label="Tutup menu"></button>
            </div>

            <div class="offcanvas-body sidebar-body">
                <nav class="side-nav" aria-label="Navigasi utama">
                    <p class="side-label">Data</p>
                    <a class="side-link<?= $activePage === 'barang' ? ' active' : '' ?>"
                       href="<?= BASE_URL ?>/barang.php"
                       <?= $activePage === 'barang' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                            <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
                        </svg>
                        <span>Data Barang</span>
                    </a>
                    <a class="side-link<?= $activePage === 'tambah' ? ' active' : '' ?>"
                       href="<?= BASE_URL ?>/barang_form.php?mode=create"
                       <?= $activePage === 'tambah' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="4" y="4" width="16" height="16" rx="3"/>
                            <path d="M12 8.5v7M8.5 12h7" stroke-linecap="round"/>
                        </svg>
                        <span>Tambah Barang</span>
                    </a>

                    <p class="side-label">Transaksi</p>
                    <a class="side-link<?= $activePage === 'masuk' ? ' active' : '' ?>"
                       href="<?= BASE_URL ?>/transaksi_masuk.php"
                       <?= $activePage === 'masuk' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M12 4v11" stroke-linecap="round"/>
                            <path d="m8 11 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 19h14" stroke-linecap="round"/>
                        </svg>
                        <span>Barang Masuk</span>
                    </a>
                    <a class="side-link<?= $activePage === 'keluar' ? ' active' : '' ?>"
                       href="<?= BASE_URL ?>/transaksi_keluar.php"
                       <?= $activePage === 'keluar' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M12 15V4" stroke-linecap="round"/>
                            <path d="m8 8 4-4 4 4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 19h14" stroke-linecap="round"/>
                        </svg>
                        <span>Barang Keluar</span>
                    </a>
                    <a class="side-link<?= $activePage === 'riwayat' ? ' active' : '' ?>"
                       href="<?= BASE_URL ?>/riwayat.php"
                       <?= $activePage === 'riwayat' ? 'aria-current="page"' : '' ?>>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="12" cy="12" r="8"/>
                            <path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Riwayat Transaksi</span>
                    </a>
                </nav>

                <div class="sidebar-user">
                    <span class="avatar"><?= e($initial) ?></span>
                    <div class="sidebar-user-text">
                        <strong><?= e((string) $user['name']) ?></strong>
                        <span>@<?= e((string) $user['username']) ?></span>
                    </div>
                    <form method="post" action="<?= BASE_URL ?>/logout.php" class="logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="logout-button" aria-label="Keluar dari akun">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M14 5h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-4" stroke-linecap="round"/>
                                <path d="M10 8 6 12l4 4M6 12h8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
    <?php endif; ?>

    <main class="app-main" id="konten">
        <div class="page-wrap">
            <?php if ($flash !== null): ?>
                <div class="flash <?= e($flashClass) ?> alert alert-dismissible fade show" role="alert">
                    <span class="flash-symbol" aria-hidden="true">
                        <?php if ($flashClass === 'flash-success'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m5 12.5 4.5 4.5L19 7.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        <?php elseif ($flashClass === 'flash-danger'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v5M12 16.5v.5" stroke-linecap="round"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        <?php elseif ($flashClass === 'flash-warning'): ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 4 21 19H3L12 4Z" stroke-linejoin="round"/>
                                <path d="M12 9v4M12 16v.5" stroke-linecap="round"/>
                            </svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 11v5M12 7.5v.5" stroke-linecap="round"/>
                            </svg>
                        <?php endif; ?>
                    </span>
                    <span class="flash-message"><?= e((string) $flash['message']) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
