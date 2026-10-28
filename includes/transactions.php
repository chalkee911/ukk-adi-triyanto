<?php
declare(strict_types=1);

/**
 * @return array{errors: array<string, string>, data: array{type: string, trx_date: string, item_id: int, quantity: int, party: string, notes: string}}
 */
function validate_transaction(array $input): array
{
    $type = strtoupper(trim((string) ($input['type'] ?? '')));
    $trxDate = trim((string) ($input['trx_date'] ?? ''));
    $itemId = filter_var(trim((string) ($input['item_id'] ?? '')), FILTER_VALIDATE_INT);
    $quantity = filter_var(trim((string) ($input['quantity'] ?? '')), FILTER_VALIDATE_INT);
    $party = trim((string) ($input['party'] ?? ''));
    $notes = trim((string) ($input['notes'] ?? ''));

    $errors = [];

    if (!in_array($type, ['IN', 'OUT'], true)) {
        $errors['type'] = 'Jenis transaksi tidak valid.';
    }

    if ($trxDate === '') {
        $errors['trx_date'] = 'Tanggal transaksi wajib diisi.';
    } elseif (!is_date_ymd($trxDate)) {
        $errors['trx_date'] = 'Format tanggal transaksi tidak valid.';
    } elseif ($trxDate > date('Y-m-d')) {
        $errors['trx_date'] = 'Tanggal transaksi tidak boleh melewati hari ini.';
    }

    if ($itemId === false || $itemId < 1) {
        $errors['item_id'] = 'Barang wajib dipilih.';
    }

    if ($quantity === false || $quantity < 1 || $quantity > 1000000) {
        $errors['quantity'] = 'Jumlah harus berupa bilangan bulat antara 1 sampai 1.000.000.';
    }

    if ($party === '') {
        $errors['party'] = $type === 'OUT'
            ? 'Tujuan atau penerima wajib diisi.'
            : 'Supplier wajib diisi.';
    } elseif (mb_strlen($party) > 100) {
        $errors['party'] = 'Supplier atau tujuan maksimal 100 karakter.';
    }

    if (mb_strlen($notes) > 255) {
        $errors['notes'] = 'Catatan maksimal 255 karakter.';
    }

    return [
        'errors' => $errors,
        'data' => [
            'type' => $type,
            'trx_date' => $trxDate,
            'item_id' => $itemId === false ? 0 : (int) $itemId,
            'quantity' => $quantity === false ? 0 : (int) $quantity,
            'party' => $party,
            'notes' => $notes,
        ],
    ];
}

/**
 * Menyimpan transaksi dan memperbarui stok dalam satu transaksi database.
 *
 * @param array{type?: string, trx_date?: string, item_id?: mixed, quantity?: mixed, party?: string, notes?: string} $data
 * @return array{ok: bool, message: string, trx_number: ?string}
 */
function create_transaction(array $data): array
{
    $validation = validate_transaction($data);

    if ($validation['errors'] !== []) {
        $firstError = reset($validation['errors']);

        return [
            'ok' => false,
            'message' => is_string($firstError) ? $firstError : 'Data transaksi tidak valid.',
            'trx_number' => null,
        ];
    }

    $validated = $validation['data'];
    $pdo = db();

    try {
        $pdo->beginTransaction();

        $itemStatement = $pdo->prepare('SELECT id, stock FROM items WHERE id = ? FOR UPDATE');
        $itemStatement->execute([$validated['item_id']]);
        $item = $itemStatement->fetch();

        if (!$item) {
            $pdo->rollBack();

            return [
                'ok' => false,
                'message' => 'Barang tidak ditemukan.',
                'trx_number' => null,
            ];
        }

        $currentStock = (int) $item['stock'];

        if ($validated['type'] === 'OUT' && $validated['quantity'] > $currentStock) {
            $pdo->rollBack();

            return [
                'ok' => false,
                'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $currentStock . '.',
                'trx_number' => null,
            ];
        }

        $temporaryNumber = 'TMP-' . bin2hex(random_bytes(8));

        $insert = $pdo->prepare(
            'INSERT INTO stock_transactions (trx_number, trx_date, item_id, type, quantity, party, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $temporaryNumber,
            $validated['trx_date'],
            $validated['item_id'],
            $validated['type'],
            $validated['quantity'],
            $validated['party'],
            $validated['notes'] === '' ? null : $validated['notes'],
        ]);

        $transactionId = (int) $pdo->lastInsertId();

        $numberUpdate = $pdo->prepare(
            "UPDATE stock_transactions
             SET trx_number = CONCAT(type, '-', DATE_FORMAT(trx_date, '%Y%m%d'), '-', LPAD(id, 5, '0'))
             WHERE id = ?"
        );
        $numberUpdate->execute([$transactionId]);

        if ($validated['type'] === 'IN') {
            $stockUpdate = $pdo->prepare('UPDATE items SET stock = stock + ? WHERE id = ?');
        } else {
            $stockUpdate = $pdo->prepare('UPDATE items SET stock = stock - ? WHERE id = ?');
        }

        $stockUpdate->execute([$validated['quantity'], $validated['item_id']]);

        $pdo->commit();

        $finalNumber = $validated['type']
            . '-' . date('Ymd', strtotime($validated['trx_date']))
            . '-' . str_pad((string) $transactionId, 5, '0', STR_PAD_LEFT);

        return [
            'ok' => true,
            'message' => 'Transaksi berhasil disimpan.',
            'trx_number' => $finalNumber,
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        log_error($error);

        return [
            'ok' => false,
            'message' => 'Terjadi kesalahan saat menyimpan transaksi. Silakan coba lagi.',
            'trx_number' => null,
        ];
    }
}

/**
 * @param array{q?: string, type?: string, date_from?: string, date_to?: string} $filter
 */
function get_transactions(array $filter = []): array
{
    $query = trim((string) ($filter['q'] ?? ''));
    $type = strtoupper(trim((string) ($filter['type'] ?? 'all')));
    $dateFrom = trim((string) ($filter['date_from'] ?? ''));
    $dateTo = trim((string) ($filter['date_to'] ?? ''));

    $sql = 'SELECT t.id, t.trx_number, t.trx_date, t.type, t.quantity, t.party, t.notes, t.created_at,
                   i.code, i.name, i.unit
            FROM stock_transactions t
            INNER JOIN items i ON i.id = t.item_id';
    $where = [];
    $params = [];

    if ($query !== '') {
        $where[] = '(t.trx_number LIKE ? OR i.code LIKE ? OR i.name LIKE ?)';
        $like = '%' . escape_like($query) . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (in_array($type, ['IN', 'OUT'], true)) {
        $where[] = 't.type = ?';
        $params[] = $type;
    }

    if ($dateFrom !== '') {
        $where[] = 't.trx_date >= ?';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 't.trx_date <= ?';
        $params[] = $dateTo;
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY t.trx_date DESC, t.id DESC';

    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

/**
 * @return array<int, array<string, mixed>>
 */
function get_item_options(): array
{
    $statement = db()->query(
        'SELECT id, code, name, unit, stock FROM items ORDER BY name ASC, code ASC'
    );

    return $statement->fetchAll();
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array{in: int, out: int}
 */
function sum_transactions(array $rows): array
{
    $totalIn = 0;
    $totalOut = 0;

    foreach ($rows as $row) {
        $quantity = (int) ($row['quantity'] ?? 0);

        if (($row['type'] ?? '') === 'IN') {
            $totalIn += $quantity;
        } elseif (($row['type'] ?? '') === 'OUT') {
            $totalOut += $quantity;
        }
    }

    return ['in' => $totalIn, 'out' => $totalOut];
}
