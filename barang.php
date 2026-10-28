<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesi formulir sudah berakhir. Silakan ulangi aksi.');
        redirect(BASE_URL . '/barang.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = filter_var((string) ($_POST['id'] ?? ''), FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            flash_set('error', 'Barang yang ingin dihapus tidak valid.');
        } else {
            $result = delete_item((int) $id);
            flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        }
    } else {
        flash_set('error', 'Aksi tidak dikenali.');
    }

    redirect(BASE_URL . '/barang.php');
}

$query = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$items = get_items(['q' => $query, 'category' => $category]);
$summary = get_summary();
$categories = get_categories();
$hasFilter = $query !== '' || $category !== '';

$pageTitle = 'Data Barang';
$activePage = 'barang';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="page-head-text">
        <p class="eyebrow">Inventaris</p>
        <h1>Data Barang</h1>
        <p class="page-sub">Daftar seluruh barang beserta stok yang tersedia saat ini.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/barang_form.php?mode=create">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
            </svg>
            Tambah Barang
        </a>
    </div>
</div>

<div class="summary-grid">
    <article class="summary-card neo-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
            </svg>
        </span>
        <div class="summary-body">
            <p class="summary-label">Jenis barang</p>
            <p class="summary-value"><?= number_format($summary['total_items'], 0, ',', '.') ?></p>
            <p class="summary-hint">Barang terdaftar</p>
        </div>
    </article>

    <article class="summary-card neo-card">
        <span class="summary-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M5 19V9M12 19V5M19 19v-7" stroke-linecap="round"/>
                <path d="M3 19h18" stroke-linecap="round"/>
            </svg>
        </span>
        <div class="summary-body">
            <p class="summary-label">Total stok</p>
            <p class="summary-value"><?= number_format($summary['total_stock'], 0, ',', '.') ?></p>
            <p class="summary-hint">Akumulasi seluruh barang</p>
        </div>
    </article>
</div>

<section class="filter-card neo-card" aria-label="Filter data barang">
    <form method="get" action="<?= BASE_URL ?>/barang.php" class="filter-grid">
        <div class="filter-field filter-search">
            <label class="form-label" for="q">Cari barang</label>
            <input type="search" class="form-control" id="q" name="q" value="<?= e($query) ?>"
                   placeholder="Kode atau nama barang" maxlength="120">
        </div>

        <div class="filter-field">
            <label class="form-label" for="category">Kategori</label>
            <select class="form-select" id="category" name="category">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $categoryOption): ?>
                    <option value="<?= e((string) $categoryOption) ?>"
                        <?= $category === (string) $categoryOption ? 'selected' : '' ?>>
                        <?= e((string) $categoryOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <?php if ($hasFilter): ?>
                <a class="btn btn-soft" href="<?= BASE_URL ?>/barang.php">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="table-card neo-card" aria-labelledby="item-table-title">
    <div class="card-head">
        <div>
            <h2 class="card-title" id="item-table-title">Daftar barang</h2>
            <p class="card-sub"><?= count($items) ?> barang ditampilkan</p>
        </div>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round"/>
                    <path d="M4 8.5 12 13l8-4.5M12 13v7" stroke-linejoin="round"/>
                </svg>
            </span>
            <?php if ($hasFilter): ?>
                <h3>Barang tidak ditemukan</h3>
                <p>Tidak ada barang yang cocok dengan pencarian atau kategori tersebut.</p>
                <a class="btn btn-soft" href="<?= BASE_URL ?>/barang.php">Hapus filter</a>
            <?php else: ?>
                <h3>Belum ada barang</h3>
                <p>Mulai dengan menambahkan barang pertama. Stok awal akan bernilai 0.</p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>/barang_form.php?mode=create">Tambah Barang</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-flat align-middle">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama</th>
                        <th scope="col" class="hide-sm">Kategori</th>
                        <th scope="col" class="hide-sm">Satuan</th>
                        <th scope="col" class="text-end">Stok</th>
                        <th scope="col" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $stock = (int) $item['stock'];
                        $stockClass = $stock === 0 ? 'stock-zero' : ($stock <= 5 ? 'stock-low' : 'stock-ok');
                        ?>
                        <tr>
                            <td><span class="code-chip"><?= e((string) $item['code']) ?></span></td>
                            <td>
                                <span class="item-name"><?= e((string) $item['name']) ?></span>
                                <span class="item-meta d-sm-none"><?= e((string) $item['category']) ?> &middot; <?= e((string) $item['unit']) ?></span>
                            </td>
                            <td class="hide-sm"><?= e((string) $item['category']) ?></td>
                            <td class="hide-sm"><?= e((string) $item['unit']) ?></td>
                            <td class="text-end">
                                <span class="badge-soft <?= e($stockClass) ?>"><?= number_format($stock, 0, ',', '.') ?></span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-soft btn-sm"
                                       href="<?= BASE_URL ?>/barang_form.php?mode=edit&amp;id=<?= (int) $item['id'] ?>">
                                        Edit
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm"
                                            data-delete-id="<?= (int) $item['id'] ?>"
                                            data-delete-name="<?= e((string) $item['name']) ?>">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content neo-modal">
            <div class="modal-body">
                <span class="modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 7h14M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7" stroke-linecap="round"/>
                        <path d="M7 7l.8 11a1.5 1.5 0 0 0 1.5 1.4h5.4a1.5 1.5 0 0 0 1.5-1.4L17 7" stroke-linecap="round"/>
                        <path d="M10 11v4M14 11v4" stroke-linecap="round"/>
                    </svg>
                </span>
                <h2 class="modal-title" id="deleteModalTitle">Hapus barang?</h2>
                <p class="modal-text">
                    <strong id="deleteName">Barang ini</strong> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                </p>
                <p class="modal-note">Barang yang sudah memiliki riwayat transaksi tidak dapat dihapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
                <form method="post" action="<?= BASE_URL ?>/barang.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId" value="">
                    <button type="submit" class="btn btn-danger">Hapus Barang</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
