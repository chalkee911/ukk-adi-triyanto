<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_auth();

$formUrl = static function (string $mode, ?int $id = null): string {
    if ($mode === 'edit' && $id !== null) {
        return BASE_URL . '/barang_form.php?mode=edit&id=' . $id;
    }

    return BASE_URL . '/barang_form.php?mode=create';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesi formulir sudah berakhir. Silakan ulangi pengisian.');
        redirect(BASE_URL . '/barang.php');
    }

    $formMode = (string) ($_POST['mode'] ?? '');

    if (!in_array($formMode, ['create', 'update'], true)) {
        flash_set('error', 'Mode formulir tidak valid.');
        redirect(BASE_URL . '/barang.php');
    }

    $itemId = null;

    if ($formMode === 'update') {
        $rawId = filter_var((string) ($_POST['id'] ?? ''), FILTER_VALIDATE_INT);

        if ($rawId === false || $rawId < 1 || get_item((int) $rawId) === null) {
            flash_set('error', 'Data barang tidak ditemukan.');
            redirect(BASE_URL . '/barang.php');
        }

        $itemId = (int) $rawId;
    }

    $validation = validate_item($_POST, $itemId);

    if ($validation['errors'] !== []) {
        form_errors_set($validation['errors']);
        old_input_set($_POST);
        flash_set('error', 'Periksa kembali isian barang.');
        redirect($formUrl($formMode === 'update' ? 'edit' : 'create', $itemId));
    }

    try {
        if ($formMode === 'create') {
            create_item($validation['data']);
            flash_set('success', 'Barang berhasil ditambahkan dengan stok awal 0.');
        } else {
            update_item((int) $itemId, $validation['data']);
            flash_set('success', 'Data barang berhasil diperbarui.');
        }

        redirect(BASE_URL . '/barang.php');
    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            form_errors_set(['code' => 'Kode barang sudah digunakan.']);
            old_input_set($_POST);
            flash_set('error', 'Kode barang sudah digunakan.');
        } else {
            log_error($error);
            form_errors_set(['general' => 'Terjadi kesalahan saat menyimpan barang. Silakan coba lagi.']);
            old_input_set($_POST);
            flash_set('error', 'Barang gagal disimpan.');
        }

        redirect($formUrl($formMode === 'update' ? 'edit' : 'create', $itemId));
    } catch (Throwable $error) {
        log_error($error);
        form_errors_set(['general' => 'Terjadi kesalahan saat menyimpan barang. Silakan coba lagi.']);
        old_input_set($_POST);
        flash_set('error', 'Barang gagal disimpan.');
        redirect($formUrl($formMode === 'update' ? 'edit' : 'create', $itemId));
    }
}

$mode = (string) ($_GET['mode'] ?? 'create');

if (!in_array($mode, ['create', 'edit'], true)) {
    flash_set('error', 'Mode formulir tidak valid.');
    redirect(BASE_URL . '/barang.php');
}

$item = null;

if ($mode === 'edit') {
    $rawId = filter_var((string) ($_GET['id'] ?? ''), FILTER_VALIDATE_INT);

    if ($rawId === false || $rawId < 1) {
        flash_set('error', 'ID barang tidak valid.');
        redirect(BASE_URL . '/barang.php');
    }

    $item = get_item((int) $rawId);

    if ($item === null) {
        flash_set('error', 'Barang tidak ditemukan.');
        redirect(BASE_URL . '/barang.php');
    }
}

$isEdit = $mode === 'edit';
$categories = get_categories();
$values = [
    'code' => old('code', (string) ($item['code'] ?? '')),
    'name' => old('name', (string) ($item['name'] ?? '')),
    'category' => old('category', (string) ($item['category'] ?? '')),
    'unit' => old('unit', (string) ($item['unit'] ?? '')),
];

$pageTitle = $isEdit ? 'Ubah Barang' : 'Tambah Barang';
$activePage = $isEdit ? 'barang' : 'tambah';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="page-head-text">
        <p class="eyebrow">Data Barang</p>
        <h1><?= $isEdit ? 'Ubah Barang' : 'Tambah Barang' ?></h1>
        <p class="page-sub">
            <?= $isEdit
                ? 'Perbarui informasi barang. Stok tidak dapat diubah dari halaman ini.'
                : 'Lengkapi informasi barang. Stok awal otomatis bernilai 0.' ?>
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-soft" href="<?= BASE_URL ?>/barang.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 12H5M11 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Kembali
        </a>
    </div>
</div>

<div class="content-narrow">
    <section class="form-card neo-card" aria-labelledby="item-form-title">
        <div class="card-head">
            <div>
                <h2 class="card-title" id="item-form-title">Informasi barang</h2>
                <p class="card-sub">Kolom bertanda <span class="req">*</span> wajib diisi.</p>
            </div>
            <?php if ($isEdit): ?>
                <span class="badge-soft stock-ok">Stok: <?= number_format((int) $item['stock'], 0, ',', '.') ?> <?= e((string) $item['unit']) ?></span>
            <?php else: ?>
                <span class="badge-soft stock-ok">Stok awal: 0</span>
            <?php endif; ?>
        </div>

        <?php if (field_error('general') !== null): ?>
            <div class="inline-error" role="alert"><?= e((string) field_error('general')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/barang_form.php" class="item-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="<?= $isEdit ? 'update' : 'create' ?>">
            <input type="hidden" name="id" value="<?= $isEdit ? (int) $item['id'] : '' ?>">

            <div class="form-grid">
                <div class="field field-span-4">
                    <label class="form-label" for="code">Kode barang <span class="req">*</span></label>
                    <input type="text" class="form-control text-uppercase<?= field_error('code') !== null ? ' is-invalid' : '' ?>"
                           id="code" name="code" value="<?= e($values['code']) ?>" maxlength="30"
                           autocomplete="off" placeholder="contoh: BRG-001" required>
                    <p class="form-hint">Huruf, angka, tanda hubung, atau garis bawah. Maksimal 30 karakter.</p>
                    <?php if (field_error('code') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('code')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-8">
                    <label class="form-label" for="name">Nama barang <span class="req">*</span></label>
                    <input type="text" class="form-control<?= field_error('name') !== null ? ' is-invalid' : '' ?>"
                           id="name" name="name" value="<?= e($values['name']) ?>" maxlength="120"
                           autocomplete="off" placeholder="contoh: Kertas HVS A4" required>
                    <?php if (field_error('name') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('name')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-6">
                    <label class="form-label" for="category">Kategori <span class="req">*</span></label>
                    <input type="text" class="form-control<?= field_error('category') !== null ? ' is-invalid' : '' ?>"
                           id="category" name="category" value="<?= e($values['category']) ?>" maxlength="60"
                           list="categoryOptions" autocomplete="off" placeholder="contoh: Alat Tulis" required>
                    <datalist id="categoryOptions">
                        <?php foreach ($categories as $categoryOption): ?>
                            <option value="<?= e((string) $categoryOption) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <?php if (field_error('category') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('category')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-6">
                    <label class="form-label" for="unit">Satuan <span class="req">*</span></label>
                    <input type="text" class="form-control<?= field_error('unit') !== null ? ' is-invalid' : '' ?>"
                           id="unit" name="unit" value="<?= e($values['unit']) ?>" maxlength="20"
                           autocomplete="off" placeholder="contoh: rim, pcs, box" required>
                    <?php if (field_error('unit') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('unit')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isEdit): ?>
                <div class="note-panel">
                    <span class="note-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 11v5M12 7.5v.5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <p>Stok hanya berubah melalui transaksi barang masuk atau keluar. Gunakan menu transaksi untuk menyesuaikan stok.</p>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <a class="btn btn-soft" href="<?= BASE_URL ?>/barang.php"><?= $isEdit ? 'Batal' : 'Kembali' ?></a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Barang' ?>
                </button>
            </div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
