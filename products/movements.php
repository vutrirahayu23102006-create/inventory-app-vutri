
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$stmt = $pdo->query(
    "SELECT sm.id, sm.movement_type, sm.quantity, sm.notes,
            sm.created_at, p.code, p.name, p.unit,
            u.username
     FROM stock_movements sm
     INNER JOIN products p ON p.id = sm.product_id
     LEFT JOIN users u ON u.id = sm.user_id
     ORDER BY sm.id DESC"
);

$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Stok</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 25px;
            background: #f4f6f9;
            color: #253047;
        }
        .container { max-width: 1200px; margin: auto; }
        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }
        a, .badge {
            display: inline-block;
            padding: 9px 13px;
            border-radius: 6px;
            text-decoration: none;
        }
        a { background: #344256; color: white; margin-bottom: 18px; }
        .badge { font-weight: bold; padding: 6px 9px; }
        .in { color: #17683a; background: #d9f7e5; }
        .out { color: #a52020; background: #ffe2e2; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e7eaf0;
            white-space: nowrap;
        }
        th { background: #f8fafc; }
    </style>
</head>
<body>
<div class="container">
    <h1>Riwayat Pergerakan Stok</h1>
    <a href="stock.php">← Kembali ke Manajemen Stok</a>

    <div class="card">
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
                    <th>Pengguna</th>
                    <th>Catatan</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$movements): ?>
                    <tr>
                        <td colspan="8">Belum ada riwayat stok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $i => $movement): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($movement['created_at']) ?></td>
                            <td><?= e($movement['code']) ?></td>
                            <td><?= e($movement['name']) ?></td>
                            <td>
                                <?php if ($movement['movement_type'] === 'in'): ?>
                                    <span class="badge in">Stok Masuk</span>
                                <?php elseif ($movement['movement_type'] === 'out'): ?>
                                    <span class="badge out">Stok Keluar</span>
                                <?php else: ?>
                                    <span class="badge">Penyesuaian</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int) $movement['quantity'] ?>
                                <?= e($movement['unit']) ?>
                            </td>
                            <td><?= e($movement['username'] ?? 'Tidak diketahui') ?></td>
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
