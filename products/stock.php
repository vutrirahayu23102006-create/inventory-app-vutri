
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $movementType = $_POST['movement_type'] ?? '';
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $notes = trim($_POST['notes'] ?? '');

    if (!$productId || !in_array($movementType, ['in', 'out'], true) || !$quantity || $quantity < 1) {
        $message = 'Pilih produk, jenis transaksi, dan jumlah yang valid.';
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // Kunci baris produk selama transaksi berlangsung.
            $stmt = $pdo->prepare(
                'SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            $currentStock = (int) $product['stock'];

            if ($movementType === 'out' && $quantity > $currentStock) {
                throw new RuntimeException(
                    'Stok tidak mencukupi. Stok tersedia: ' . $currentStock
                );
            }

            if ($movementType === 'in') {
                $update = $pdo->prepare(
                    'UPDATE products SET stock = stock + ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
            } else {
                $update = $pdo->prepare(
                    'UPDATE products SET stock = stock - ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
            }

            $update->execute([$quantity, $productId]);

            $userId = $_SESSION['user_id'] ?? null;

            $insert = $pdo->prepare(
                'INSERT INTO stock_movements
                (product_id, user_id, movement_type, quantity, notes)
                VALUES (?, ?, ?, ?, ?)'
            );

            $insert->execute([
                $productId,
                $userId,
                $movementType,
                $quantity,
                $notes !== '' ? $notes : null
            ]);

            $pdo->commit();

            header('Location: stock.php?success=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Transaksi gagal. Periksa konfigurasi database dan struktur tabel.';

            $messageType = 'error';
        }
    }
}

if (isset($_GET['success'])) {
    $message = 'Transaksi stok berhasil disimpan!';
    $messageType = 'success';
}

$products = $pdo->query(
    'SELECT id, code, name, stock, unit
     FROM products
     ORDER BY name ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$recentMovements = $pdo->query(
    "SELECT sm.id, sm.movement_type, sm.quantity, sm.notes,
            sm.created_at, p.code, p.name, p.unit
     FROM stock_movements sm
     INNER JOIN products p ON p.id = sm.product_id
     ORDER BY sm.id DESC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stok Masuk dan Keluar</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 25px;
            background: #f4f6f9;
            color: #253047;
            font-family: Arial, sans-serif;
        }
        .container { max-width: 1100px; margin: auto; }
        h1 { margin-bottom: 8px; }
        .subtitle { color: #687386; margin-bottom: 25px; }
        .card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #d6dce5;
            border-radius: 7px;
            font-size: 15px;
        }
        textarea { resize: vertical; }
        .full { grid-column: 1 / -1; }
        button, .link {
            display: inline-block;
            border: 0;
            border-radius: 7px;
            padding: 11px 17px;
            color: white;
            background: #f28c28;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }
        .link { background: #344256; }
        .alert {
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 18px;
        }
        .success { background: #d9f7e5; color: #17683a; }
        .error { background: #ffe2e2; color: #a52020; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e7eaf0;
            text-align: left;
            white-space: nowrap;
        }
        th { background: #f8fafc; }
        .in { color: #168044; font-weight: bold; }
        .out { color: #c0392b; font-weight: bold; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        @media (max-width: 650px) {
            body { padding: 12px; }
            .form-grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Manajemen Stok</h1>
    <p class="subtitle">Catat barang masuk dan barang keluar dengan pembaruan stok otomatis.</p>

    <div class="actions" style="margin-bottom:20px">
        <a class="link" href="index.php">← Kembali ke Data Produk</a>
        <a class="link" href="movements.php">Lihat Semua Riwayat</a>
    </div>

    <div class="card">
        <h2>Form Transaksi Stok</h2>

        <?php if ($message !== ''): ?>
            <div class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="stock.php">
            <div class="form-grid">
                <div class="full">
                    <label for="product_id">Pilih Produk</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">-- Pilih produk --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= (int) $product['id'] ?>">
                                <?= e($product['code']) ?> -
                                <?= e($product['name']) ?> |
                                Stok: <?= (int) $product['stock'] ?>
                                <?= e($product['unit']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="movement_type">Jenis Transaksi</label>
                    <select id="movement_type" name="movement_type" required>
                        <option value="">-- Pilih jenis --</option>
                        <option value="in">Stok Masuk (+)</option>
                        <option value="out">Stok Keluar (-)</option>
                    </select>
                </div>

                <div>
                    <label for="quantity">Jumlah</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        step="1"
                        required
                        placeholder="Contoh: 10"
                    >
                </div>

                <div class="full">
                    <label for="notes">Catatan (opsional)</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="Contoh: Pembelian dari supplier atau barang terjual"
                    ></textarea>
                </div>

                <div class="full">
                    <button type="submit"
                        onclick="return confirm('Simpan transaksi stok ini?')">
                        Simpan Transaksi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>10 Transaksi Terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Catatan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$recentMovements): ?>
                    <tr>
                        <td colspan="7">Belum ada transaksi stok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentMovements as $i => $movement): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($movement['created_at']) ?></td>
                            <td><?= e($movement['code']) ?></td>
                            <td><?= e($movement['name']) ?></td>
                            <td class="<?= e($movement['movement_type']) ?>">
                                <?= $movement['movement_type'] === 'in'
                                    ? 'Masuk'
                                    : ($movement['movement_type'] === 'out'
                                        ? 'Keluar' : 'Penyesuaian') ?>
                            </td>
                            <td>
                                <?= (int) $movement['quantity'] ?>
                                <?= e($movement['unit']) ?>
                            </td>
                            <td><?= e($movement['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
