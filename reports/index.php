<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$report = $_GET['report'] ?? 'products';

$allowedReports = ['products', 'low-stock', 'value', 'movements'];

if (!in_array($report, $allowedReports, true)) {
    $report = 'products';
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    $startDate = date('Y-m-01');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $endDate = date('Y-m-d');
}

$rows = [];
$totalStock = 0;
$totalValue = 0;
$pageTitle = '';

if ($report === 'products' || $report === 'low-stock' || $report === 'value') {
    $sql = "
        SELECT
            p.id,
            p.code,
            p.name,
            p.stock,
            p.min_stock,
            p.unit,
            p.purchase_price,
            p.selling_price,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
    ";

    if ($report === 'low-stock') {
        $sql .= " WHERE p.stock <= p.min_stock";
        $pageTitle = 'Laporan Stok Menipis';
    } elseif ($report === 'value') {
        $pageTitle = 'Laporan Nilai Persediaan';
    } else {
        $pageTitle = 'Laporan Stok Barang';
    }

    $sql .= " ORDER BY p.name ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $totalStock += (float) $row['stock'];
        $totalValue += (float) $row['purchase_price']
            * (float) $row['stock'];
    }
} else {
    $pageTitle = 'Laporan Stok Masuk dan Keluar';

    $stmt = $pdo->prepare("
        SELECT
            sm.id,
            sm.movement_type,
            sm.quantity,
            sm.notes,
            sm.created_at,
            p.code,
            p.name,
            p.unit,
            u.username
        FROM stock_movements sm
        LEFT JOIN products p ON p.id = sm.product_id
        LEFT JOIN users u ON u.id = sm.user_id
        WHERE sm.created_at >= :start_date
          AND sm.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
        ORDER BY sm.created_at DESC, sm.id DESC
    ");

    $stmt->execute([
        ':start_date' => $startDate . ' 00:00:00',
        ':end_date' => $endDate . ' 00:00:00'
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rupiah($amount): string {
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 25px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        h2 { margin-top: 0; }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border: none;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .orange { background: #f28c28; }
        .blue { background: #2878d0; }
        .gray { background: #6c757d; }

        .active {
            outline: 3px solid #ffd7ad;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }

        .summary-card {
            flex: 1;
            min-width: 180px;
            padding: 18px;
            background: #fff4e8;
            border-left: 5px solid #f28c28;
            border-radius: 6px;
        }

        .summary-card strong {
            display: block;
            font-size: 21px;
            margin-top: 8px;
        }

        .filter {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
            margin: 20px 0;
        }

        .filter label {
            display: block;
            margin-bottom: 6px;
        }

        .filter input {
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 14px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f28c28;
            color: white;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .warning {
            color: #c0392b;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 25px;
        }

        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: none; }
            .nav, .no-print, .back-link { display: none !important; }
            th { background: #eee !important; color: black !important; }
        }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .container { padding: 12px; }
        }
    </style>
</head>

<body>
<div class="container">

    <a href="../dashboard.php" class="btn gray back-link">
        Kembali ke Dashboard
    </a>

    <h2>📊 Laporan Inventaris</h2>
    <p>Lihat dan cetak laporan persediaan barang.</p>

    <div class="nav">
        <a class="btn orange <?= $report === 'products' ? 'active' : '' ?>"
           href="index.php?report=products">
            Stok Barang
        </a>

        <a class="btn orange <?= $report === 'low-stock' ? 'active' : '' ?>"
           href="index.php?report=low-stock">
            Stok Menipis
        </a>

        <a class="btn orange <?= $report === 'value' ? 'active' : '' ?>"
           href="index.php?report=value">
            Nilai Persediaan
        </a>

        <a class="btn orange <?= $report === 'movements' ? 'active' : '' ?>"
           href="index.php?report=movements">
            Riwayat Stok
        </a>
    </div>

    <h3><?= e($pageTitle) ?></h3>

    <?php if ($report !== 'movements'): ?>

        <div class="summary">
            <div class="summary-card">
                Total Jenis Barang
                <strong><?= count($rows) ?></strong>
            </div>

            <div class="summary-card">
                Total Stok
                <strong><?= number_format($totalStock, 0, ',', '.') ?></strong>
            </div>

            <div class="summary-card">
                Nilai Persediaan
                <strong><?= rupiah($totalValue) ?></strong>
            </div>
        </div>

    <?php else: ?>

        <form method="GET" class="filter no-print">
            <input type="hidden" name="report" value="movements">

            <div>
                <label for="start_date">Dari tanggal</label>
                <input type="date" id="start_date" name="start_date"
                       value="<?= e($startDate) ?>" required>
            </div>

            <div>
                <label for="end_date">Sampai tanggal</label>
                <input type="date" id="end_date" name="end_date"
                       value="<?= e($endDate) ?>" required>
            </div>

            <button type="submit" class="btn blue">Tampilkan</button>
        </form>

    <?php endif; ?>

    <button onclick="window.print()" class="btn blue no-print">
        Cetak / Simpan PDF
    </button>

    <div class="table-wrap">
        <table>
            <thead>
            <?php if ($report !== 'movements'): ?>
                <tr>
                    <th>No.</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Stok Minimum</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <?php if ($report === 'value'): ?>
                        <th>Nilai Persediaan</th>
                    <?php endif; ?>
                </tr>
            <?php else: ?>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Catatan</th>
                    <th>Pengguna</th>
                </tr>
            <?php endif; ?>
            </thead>

            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= $report === 'value' ? 9 : ($report === 'movements' ? 8 : 8) ?>"
                        class="empty">
                        Tidak ada data untuk ditampilkan.
                    </td>
                </tr>
            <?php else: ?>

                <?php foreach ($rows as $i => $row): ?>
                    <tr>
                    <?php if ($report !== 'movements'): ?>

                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['code']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['category_name'] ?? '-') ?></td>
                        <td class="<?= (float)$row['stock'] <= (float)$row['min_stock'] ? 'warning' : '' ?>">
                            <?= number_format((float)$row['stock'], 0, ',', '.') ?>
                            <?= e($row['unit']) ?>
                        </td>
                        <td><?= number_format((float)$row['min_stock'], 0, ',', '.') ?></td>
                        <td><?= rupiah($row['purchase_price']) ?></td>
                        <td><?= rupiah($row['selling_price']) ?></td>

                        <?php if ($report === 'value'): ?>
                            <td>
                                <?= rupiah((float)$row['purchase_price'] * (float)$row['stock']) ?>
                            </td>
                        <?php endif; ?>

                    <?php else: ?>

                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['created_at']) ?></td>
                        <td><?= e($row['code'] ?? '-') ?></td>
                        <td><?= e($row['name'] ?? '-') ?></td>
                        <td><?= e(ucfirst($row['movement_type'])) ?></td>
                        <td><?= number_format((float)$row['quantity'], 0, ',', '.') ?>
                            <?= e($row['unit'] ?? '') ?>
                        </td>
                        <td><?= e($row['notes'] ?? '-') ?></td>
                        <td><?= e($row['username'] ?? '-') ?></td>

                    <?php endif; ?>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <p style="margin-top:20px;color:#777;font-size:13px">
        Laporan dibuat pada <?= date('d-m-Y H:i') ?>.
    </p>

</div>
</body>
</html>