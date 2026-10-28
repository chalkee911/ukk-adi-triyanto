<?php
declare(strict_types=1);

if (!isset($type) || !in_array($type, ['IN', 'OUT'], true)) {
    throw new RuntimeException('Jenis transaksi tidak valid.');
}

$isIn = $type === 'IN';
$pageTitle = $isIn ? 'Barang Masuk' : 'Barang Keluar';
$activePage = $isIn ? 'masuk' : 'keluar';
$selfUrl = BASE_URL . ($isIn ? '/transaksi_masuk.php' : '/transaksi_keluar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesi formulir sudah berakhir. Silakan ulangi pengisian.');
        redirect($selfUrl);
    }

    $input = $_POST;
    $input['type'] = $type;
    $validation = validate_transaction($input);

    if ($validation['errors'] !== []) {
        form_errors_set($validation['errors']);
        old_input_set($_POST);
        flash_set('error', 'Periksa kembali isian transaksi.');
        redirect($selfUrl);
    }

    $result = create_transaction($validation['data']);

    if ($result['ok'] && $result['trx_number'] !== null) {
        $label = $isIn ? 'Transaksi barang masuk' : 'Transaksi barang keluar';
        flash_set('success', $label . ' berhasil disimpan. Nomor transaksi: ' . $result['trx_number'] . '.');
        redirect($selfUrl);
    }

    $errorKey = str_starts_with($result['message'], 'Stok tidak mencukupi') ? 'quantity' : 'general';
    form_errors_set([$errorKey => $result['message']]);
    old_input_set($_POST);
    flash_set('error', $result['message']);
    redirect($selfUrl);
}

$items = get_item_options();
$today = date('Y-m-d');
$selectedItem = old('item_id');

require __DIR__ . '/header.php';
?>
<div class="page-head">
    <div class="page-head-text">
        <p class="eyebrow">Transaksi Stok</p>
        <h1><?= $isIn ? 'Barang Masuk' : 'Barang Keluar' ?></h1>
        <p class="page-sub">
            <?= $isIn
                ? 'Catat barang yang diterima agar stok bertambah secara otomatis.'
                : 'Catat barang yang keluar agar stok berkurang secara otomatis.' ?>
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-soft" href="<?= BASE_URL ?>/riwayat.php">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="8"/>
                <path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Riwayat
        </a>
    </div>
</div>

<?php if ($items === []): ?>
    <section class="empty-card neo-card">
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                    <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
                </svg>
            </span>
            <h2>Belum ada barang</h2>
            <p>Tambahkan barang terlebih dahulu sebelum mencatat transaksi stok.</p>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/barang_form.php?mode=create">Tambah Barang</a>
        </div>
    </section>
    <?php
    require __DIR__ . '/footer.php';
    return;
    ?>
<?php endif; ?>

<div class="transaction-layout">
    <section class="form-card neo-card" aria-labelledby="transaction-form-title">
        <div class="card-head">
            <div>
                <h2 class="card-title" id="transaction-form-title">Form <?= $isIn ? 'barang masuk' : 'barang keluar' ?></h2>
                <p class="card-sub">Nomor transaksi dibuat otomatis setelah data disimpan.</p>
            </div>
            <span class="badge-soft <?= $isIn ? 'badge-in' : 'badge-out' ?>"><?= $isIn ? 'Masuk' : 'Keluar' ?></span>
        </div>

        <?php if (field_error('general') !== null): ?>
            <div class="inline-error" role="alert"><?= e((string) field_error('general')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= $selfUrl ?>" class="item-form" novalidate>
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="field field-span-5">
                    <label class="form-label" for="trx_date">Tanggal transaksi <span class="req">*</span></label>
                    <input type="date" class="form-control<?= field_error('trx_date') !== null ? ' is-invalid' : '' ?>"
                           id="trx_date" name="trx_date" value="<?= e(old('trx_date', $today)) ?>"
                           max="<?= e($today) ?>" required>
                    <?php if (field_error('trx_date') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('trx_date')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-7">
                    <label class="form-label" for="itemSelect">Barang <span class="req">*</span></label>
                    <select class="form-select<?= field_error('item_id') !== null ? ' is-invalid' : '' ?>"
                            id="itemSelect" name="item_id" required>
                        <option value="">Pilih barang</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['id'] ?>"
                                    data-stock="<?= (int) $item['stock'] ?>"
                                    data-unit="<?= e((string) $item['unit']) ?>"
                                <?= $selectedItem === (string) $item['id'] ? 'selected' : '' ?>>
                                <?= e((string) $item['code']) ?> &mdash; <?= e((string) $item['name']) ?> (<?= e((string) $item['unit']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (field_error('item_id') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('item_id')) ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!$isIn): ?>
                    <div class="field field-span-12">
                        <div class="stock-panel" aria-live="polite">
                            <span class="stock-panel-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                                    <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <p class="stock-info" id="stockInfo">Pilih barang untuk melihat stok tersedia.</p>
                                <p class="stock-warning" id="stockWarning" hidden>Jumlah melebihi stok tersedia.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field field-span-5">
                    <label class="form-label" for="quantity">Jumlah <span class="req">*</span></label>
                    <input type="number" class="form-control<?= field_error('quantity') !== null ? ' is-invalid' : '' ?>"
                           id="quantity" name="quantity" value="<?= e(old('quantity')) ?>"
                           min="1" max="1000000" step="1" inputmode="numeric"
                           placeholder="contoh: 10" required>
                    <?php if (field_error('quantity') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('quantity')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-7">
                    <label class="form-label" for="party">
                        <?= $isIn ? 'Supplier' : 'Tujuan / penerima' ?> <span class="req">*</span>
                    </label>
                    <input type="text" class="form-control<?= field_error('party') !== null ? ' is-invalid' : '' ?>"
                           id="party" name="party" value="<?= e(old('party')) ?>" maxlength="100"
                           autocomplete="off"
                           placeholder="<?= $isIn ? 'contoh: PT Sumber Makmur' : 'contoh: Bagian Umum' ?>" required>
                    <?php if (field_error('party') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('party')) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field field-span-12">
                    <label class="form-label" for="notes">Catatan <span class="optional">(opsional)</span></label>
                    <textarea class="form-control<?= field_error('notes') !== null ? ' is-invalid' : '' ?>"
                              id="notes" name="notes" maxlength="255" rows="3"
                              placeholder="Keterangan tambahan, misalnya nomor surat jalan"><?= e(old('notes')) ?></textarea>
                    <p class="form-hint">Maksimal 255 karakter.</p>
                    <?php if (field_error('notes') !== null): ?>
                        <p class="field-error"><?= e((string) field_error('notes')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn btn-soft" href="<?= BASE_URL ?>/barang.php">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isIn ? 'Simpan Barang Masuk' : 'Simpan Barang Keluar' ?>
                </button>
            </div>
        </form>
    </section>

    <aside class="side-card neo-card">
        <h2 class="card-title"><?= $isIn ? 'Pencatatan masuk' : 'Pencatatan keluar' ?></h2>
        <p class="side-card-text">
            <?= $isIn
                ? 'Stok bertambah setelah transaksi disimpan. Pastikan barang dan jumlah sesuai dokumen penerimaan.'
                : 'Stok berkurang setelah transaksi disimpan. Sistem menolak jumlah yang melebihi stok tersedia.' ?>
        </p>

        <ul class="guide-list">
            <li>Tanggal tidak boleh melewati hari ini.</li>
            <li>Jumlah berupa bilangan bulat 1 sampai 1.000.000.</li>
            <li>Nomor transaksi dibuat otomatis oleh sistem.</li>
        </ul>

        <a class="side-link-inline" href="<?= BASE_URL ?>/riwayat.php">
            Lihat riwayat transaksi
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>
