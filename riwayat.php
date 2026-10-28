<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_auth();

$query = trim((string) ($_GET['q'] ?? ''));
$type = strtoupper(trim((string) ($_GET['type'] ?? 'all')));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

if (!in_array($type, ['all', 'IN', 'OUT'], true)) {
    $type = 'all';
}

if ($dateFrom !== '' && !is_date_ymd($dateFrom)) {
    flash_set('error', 'Format tanggal awal tidak valid.');
    redirect(BASE_URL . '/riwayat.php');
}

if ($dateTo !== '' && !is_date_ymd($dateTo)) {
    flash_set('error', 'Format tanggal akhir tidak valid.');
    redirect(BASE_URL . '/riwayat.php');
}

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    flash_set('error', 'Tanggal awal tidak boleh melebihi tanggal akhir.');
    redirect(BASE_URL . '/riwayat.php');
}

$rows = get_transactions([
    'q' => $query,
    'type' => $type,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
]);
$totals = sum_transactions($rows);
$hasFilter = $query !== '' || $type !== 'all' || $dateFrom !== '' || $dateTo !== '';

$typeLabel = match ($type) {
    'IN' => 'Masuk',
    'OUT' => 'Keluar',
    default => 'Semua jenis',
};

$filterParts = ['Jenis: ' . $typeLabel];

if ($query !== '') {
    $filterParts[] = 'Kata kunci: "' . $query . '"';
}

if ($dateFrom !== '' || $dateTo !== '') {
    $filterParts[] = 'Tanggal: ' . ($dateFrom !== '' ? $dateFrom : 'awal')
        . ' s.d. ' . ($dateTo !== '' ? $dateTo : 'akhir');
} else {
    $filterParts[] = 'Tanggal: semua';
}

$filterInfo = implode(' | ', $filterParts);
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$rowsJson = json_encode($rows, $jsonFlags);

if ($rowsJson === false) {
    log_error('Gagal mengubah data riwayat menjadi JSON.');
    $rowsJson = '[]';
}

$pageTitle = 'Riwayat Transaksi';
$activePage = 'riwayat';
$includePdf = true;
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="page-head-text">
        <p class="eyebrow">Transaksi Stok</p>
        <h1>Riwayat Transaksi</h1>
        <p class="page-sub">Seluruh transaksi masuk dan keluar yang tersimpan, tanpa pagination.</p>
    </div>
</div>

<div class="summary-grid">
    <article class="summary-card neo-card">
        <span class="summary-icon summary-icon-in" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 4v11" stroke-linecap="round"/>
                <path d="m8 11 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 19h14" stroke-linecap="round"/>
            </svg>
        </span>
        <div class="summary-body">
            <p class="summary-label">Total masuk</p>
            <p class="summary-value value-in">+<?= number_format($totals['in'], 0, ',', '.') ?></p>
            <p class="summary-hint">Dari hasil filter saat ini</p>
        </div>
    </article>

    <article class="summary-card neo-card">
        <span class="summary-icon summary-icon-out" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 15V4" stroke-linecap="round"/>
                <path d="m8 8 4-4 4 4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 19h14" stroke-linecap="round"/>
            </svg>
        </span>
        <div class="summary-body">
            <p class="summary-label">Total keluar</p>
            <p class="summary-value value-out">-<?= number_format($totals['out'], 0, ',', '.') ?></p>
            <p class="summary-hint">Dari hasil filter saat ini</p>
        </div>
    </article>
</div>

<section class="filter-card neo-card" aria-label="Filter riwayat transaksi">
    <form method="get" action="<?= BASE_URL ?>/riwayat.php" class="filter-grid filter-grid-riwayat">
        <div class="filter-field">
            <label class="form-label" for="q">Cari transaksi</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= e($query) ?>"
                   placeholder="No. transaksi, kode, atau nama" maxlength="120">
        </div>

        <div class="filter-field">
            <label class="form-label" for="type">Jenis</label>
            <select class="form-select" id="type" name="type">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="IN" <?= $type === 'IN' ? 'selected' : '' ?>>Masuk</option>
                <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>Keluar</option>
            </select>
        </div>

        <div class="filter-field">
            <label class="form-label" for="date_from">Dari tanggal</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
        </div>

        <div class="filter-field">
            <label class="form-label" for="date_to">Sampai tanggal</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <?php if ($hasFilter): ?>
                <a class="btn btn-soft" href="<?= BASE_URL ?>/riwayat.php">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="table-card neo-card" aria-labelledby="history-table-title">
    <div class="card-head">
        <div>
            <h2 class="card-title" id="history-table-title">Daftar transaksi</h2>
            <p class="card-sub"><?= count($rows) ?> transaksi ditampilkan</p>
        </div>
        <button type="button" class="btn btn-primary" id="pdfBtn"
                data-filter-info="<?= e($filterInfo) ?>"
                <?= $rows === [] ? 'disabled' : '' ?>>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M12 4v10" stroke-linecap="round"/>
                <path d="m8 10 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 18v1.5h14V18" stroke-linecap="round"/>
            </svg>
            Cetak PDF
        </button>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="8"/>
                    <path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <?php if ($hasFilter): ?>
                <h3>Tidak ada transaksi yang cocok</h3>
                <p>Ubah kata kunci, jenis, atau rentang tanggal untuk melihat transaksi lain.</p>
                <a class="btn btn-soft" href="<?= BASE_URL ?>/riwayat.php">Hapus filter</a>
            <?php else: ?>
                <h3>Belum ada transaksi</h3>
                <p>Transaksi muncul di sini setelah barang masuk atau keluar dicatat.</p>
                <div class="empty-actions">
                    <a class="btn btn-primary" href="<?= BASE_URL ?>/transaksi_masuk.php">Barang Masuk</a>
                    <a class="btn btn-soft" href="<?= BASE_URL ?>/transaksi_keluar.php">Barang Keluar</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-flat align-middle">
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">No. Transaksi</th>
                        <th scope="col" class="hide-sm">Kode</th>
                        <th scope="col">Nama Barang</th>
                        <th scope="col">Jenis</th>
                        <th scope="col" class="text-end">Jumlah</th>
                        <th scope="col">Supplier / Tujuan</th>
                        <th scope="col" class="hide-sm">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $isInRow = $row['type'] === 'IN';
                        $quantity = (int) $row['quantity'];
                        ?>
                        <tr>
                            <td class="text-nowrap"><?= e(date('d/m/Y', strtotime((string) $row['trx_date']))) ?></td>
                            <td><span class="code-chip"><?= e((string) $row['trx_number']) ?></span></td>
                            <td class="hide-sm"><?= e((string) $row['code']) ?></td>
                            <td>
                                <span class="item-name"><?= e((string) $row['name']) ?></span>
                                <span class="item-meta"><?= e((string) $row['unit']) ?></span>
                            </td>
                            <td>
                                <span class="badge-soft <?= $isInRow ? 'badge-in' : 'badge-out' ?>">
                                    <?= $isInRow ? 'Masuk' : 'Keluar' ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="<?= $isInRow ? 'qty-in' : 'qty-out' ?>">
                                    <?= $isInRow ? '+' : '-' ?><?= number_format($quantity, 0, ',', '.') ?>
                                </span>
                            </td>
                            <td><?= e((string) $row['party']) ?></td>
                            <td class="hide-sm">
                                <?php if ((string) ($row['notes'] ?? '') !== ''): ?>
                                    <?= e((string) $row['notes']) ?>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<script type="application/json" id="trx-data"><?= $rowsJson ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
