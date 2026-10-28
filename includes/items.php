<?php
declare(strict_types=1);

/**
 * @return array{errors: array<string, string>, data: array<string, string>}
 */
function validate_item(array $input, ?int $ignoreId = null): array
{
    $data = [
        'code' => strtoupper(trim((string) ($input['code'] ?? ''))),
        'name' => trim((string) ($input['name'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
        'unit' => trim((string) ($input['unit'] ?? '')),
    ];

    $errors = [];

    if ($data['code'] === '') {
        $errors['code'] = 'Kode barang wajib diisi.';
    } elseif (strlen($data['code']) > 30) {
        $errors['code'] = 'Kode barang maksimal 30 karakter.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data['code'])) {
        $errors['code'] = 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.';
    } else {
        try {
            $sql = 'SELECT id FROM items WHERE code = ?';
            $params = [$data['code']];

            if ($ignoreId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $ignoreId;
            }

            $sql .= ' LIMIT 1';
            $statement = db()->prepare($sql);
            $statement->execute($params);

            if ($statement->fetch()) {
                $errors['code'] = 'Kode barang sudah digunakan.';
            }
        } catch (Throwable $error) {
            log_error($error);
            $errors['general'] = 'Terjadi kesalahan saat memeriksa kode barang.';
        }
    }

    if ($data['name'] === '') {
        $errors['name'] = 'Nama barang wajib diisi.';
    } elseif (mb_strlen($data['name']) > 120) {
        $errors['name'] = 'Nama barang maksimal 120 karakter.';
    }

    if ($data['category'] === '') {
        $errors['category'] = 'Kategori wajib diisi.';
    } elseif (mb_strlen($data['category']) > 60) {
        $errors['category'] = 'Kategori maksimal 60 karakter.';
    }

    if ($data['unit'] === '') {
        $errors['unit'] = 'Satuan wajib diisi.';
    } elseif (mb_strlen($data['unit']) > 20) {
        $errors['unit'] = 'Satuan maksimal 20 karakter.';
    }

    return ['errors' => $errors, 'data' => $data];
}

/**
 * @param array{q?: string, category?: string} $filter
 */
function get_items(array $filter = []): array
{
    $query = trim((string) ($filter['q'] ?? ''));
    $category = trim((string) ($filter['category'] ?? ''));

    $sql = 'SELECT id, code, name, category, unit, stock, created_at, updated_at FROM items';
    $where = [];
    $params = [];

    if ($query !== '') {
        $where[] = '(code LIKE ? OR name LIKE ?)';
        $like = '%' . escape_like($query) . '%';
        $params[] = $like;
        $params[] = $like;
    }

    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY name ASC, code ASC';

    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function get_item(int $id): ?array
{
    if ($id < 1) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, code, name, category, unit, stock, created_at, updated_at FROM items WHERE id = ? LIMIT 1'
    );
    $statement->execute([$id]);
    $item = $statement->fetch();

    return $item ?: null;
}

/**
 * @param array{code: string, name: string, category: string, unit: string} $data
 */
function create_item(array $data): int
{
    $statement = db()->prepare(
        'INSERT INTO items (code, name, category, unit, stock) VALUES (?, ?, ?, ?, 0)'
    );
    $statement->execute([
        $data['code'],
        $data['name'],
        $data['category'],
        $data['unit'],
    ]);

    return (int) db()->lastInsertId();
}

/**
 * @param array{code: string, name: string, category: string, unit: string} $data
 */
function update_item(int $id, array $data): void
{
    $statement = db()->prepare(
        'UPDATE items SET code = ?, name = ?, category = ?, unit = ? WHERE id = ?'
    );
    $statement->execute([
        $data['code'],
        $data['name'],
        $data['category'],
        $data['unit'],
        $id,
    ]);
}

/**
 * @return array{ok: bool, message: string}
 */
function delete_item(int $id): array
{
    if ($id < 1) {
        return ['ok' => false, 'message' => 'Barang tidak ditemukan.'];
    }

    try {
        $check = db()->prepare('SELECT COUNT(*) FROM stock_transactions WHERE item_id = ?');
        $check->execute([$id]);

        if ((int) $check->fetchColumn() > 0) {
            return [
                'ok' => false,
                'message' => 'Barang tidak dapat dihapus karena sudah memiliki riwayat transaksi.',
            ];
        }

        $statement = db()->prepare('DELETE FROM items WHERE id = ?');
        $statement->execute([$id]);

        if ($statement->rowCount() === 0) {
            return ['ok' => false, 'message' => 'Barang tidak ditemukan.'];
        }

        return ['ok' => true, 'message' => 'Barang berhasil dihapus.'];
    } catch (PDOException $error) {
        if ($error->getCode() === '23000') {
            return [
                'ok' => false,
                'message' => 'Barang tidak dapat dihapus karena sudah memiliki riwayat transaksi.',
            ];
        }

        log_error($error);

        return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghapus barang. Silakan coba lagi.'];
    } catch (Throwable $error) {
        log_error($error);

        return ['ok' => false, 'message' => 'Terjadi kesalahan saat menghapus barang. Silakan coba lagi.'];
    }
}

/**
 * @return array<int, string>
 */
function get_categories(): array
{
    $statement = db()->query('SELECT DISTINCT category FROM items ORDER BY category ASC');

    return $statement->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * @return array{total_items: int, total_stock: int}
 */
function get_summary(): array
{
    $row = db()->query(
        'SELECT COUNT(*) AS total_items, COALESCE(SUM(stock), 0) AS total_stock FROM items'
    )->fetch();

    return [
        'total_items' => (int) ($row['total_items'] ?? 0),
        'total_stock' => (int) ($row['total_stock'] ?? 0),
    ];
}
