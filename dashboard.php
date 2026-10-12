
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';

requireLogin();

// Ringkasan data inventory
$totalProducts = (int) $pdo->query(
    'SELECT COUNT(*) FROM products'
)->fetchColumn();

$totalCategories = (int) $pdo->query(
    'SELECT COUNT(*) FROM categories'
)->fetchColumn();

$totalSuppliers = (int) $pdo->query(
    'SELECT COUNT(*) FROM suppliers'
)->fetchColumn();

$totalStock = (int) $pdo->query(
    'SELECT COALESCE(SUM(stock), 0) FROM products'
)->fetchColumn();

$lowStock = (int) $pdo->query(
    'SELECT COUNT(*) FROM products WHERE stock <= min_stock'
)->fetchColumn();

// Nilai persediaan dihitung dari harga beli x jumlah stok
$inventoryValue = (float) $pdo->query(
    'SELECT COALESCE(SUM(purchase_price * stock), 0) FROM products'
)->fetchColumn();

// Produk dengan stok menipis
$lowStockProducts = $pdo->query(
    "SELECT p.id, p.code, p.name, p.stock, p.min_stock, p.unit,
            c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.stock <= p.min_stock
     ORDER BY p.stock ASC, p.name ASC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

// Transaksi stok terbaru
$recentMovements = $pdo->query(
    "SELECT sm.id, sm.movement_type, sm.quantity, sm.notes,
            sm.created_at, p.code, p.name, p.unit
     FROM stock_movements sm
     INNER JOIN products p ON p.id = sm.product_id
     ORDER BY sm.id DESC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

// Ringkasan transaksi hari ini
$todayIn = $pdo->query(
    "SELECT COALESCE(SUM(quantity), 0)
     FROM stock_movements
     WHERE movement_type = 'in'
       AND created_at >= CURDATE()
       AND created_at < CURDATE() + INTERVAL 1 DAY"
)->fetchColumn();

$todayOut = $pdo->query(
    "SELECT COALESCE(SUM(quantity), 0)
     FROM stock_movements
     WHERE movement_type = 'out'
       AND created_at >= CURDATE()
       AND created_at < CURDATE() + INTERVAL 1 DAY"
)->fetchColumn();

$todayIn = (int) $todayIn;
$todayOut = (int) $todayOut;

$username = $_SESSION['username'] ?? $_SESSION['name'] ?? 'Pengguna';

function rupiah($number): string
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Inventory</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6fa;
            color: #253047;
        }

        .sidebar {
            width: 235px;
            position: fixed;
            inset: 0 auto 0 0;
            background: #202b3c;
            color: white;
            padding: 25px 16px;
            overflow-y: auto;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            margin: 0 10px 8px;
        }

        .brand-subtitle {
            font-size: 12px;
            color: #aeb9c9;
            margin: 0 10px 30px;
        }

        .menu-label {
            color: #aeb9c9;
            font-size: 11px;
            margin: 22px 10px 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu a {
            display: block;
            padding: 12px;
            margin: 5px 0;
            border-radius: 7px;
            text-decoration: none;
            color: #e8edf5;
            font-size: 14px;
        }

        .menu a:hover,
        .menu a.active {
            background: #f28c28;
            color: white;
        }

        .main {
            margin-left: 235px;
            padding: 28px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            font-size: 27px;
            margin: 0 0 8px;
        }

        .muted {
            color: #7b8495;
            font-size: 14px;
        }

        .user {
            background: white;
            padding: 12px 16px;
            border-radius: 9px;
            box-shadow: 0 2px 8px #1d2c4010;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 22px;
        }

        .stat {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #1d2c400a;
            border-top: 4px solid #f28c28;
        }

        .stat-title {
            color: #737e90;
            font-size: 13px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: bold;
            overflow-wrap: anywhere;
        }

        .stat-note {
            color: #858fa0;
            font-size: 12px;
            margin-top: 9px;
        }

        .orange { border-top-color: #f28c28; }
        .blue { border-top-color: #3984e8; }
        .green { border-top-color: #20a36b; }
        .red { border-top-color: #e45b5b; }

        .section-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 20px;
            margin-bottom: 22px;
        }

        .panel {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #1d2c400a;
            min-width: 0;
        }

        .panel h2 {
            font-size: 17px;
            margin: 0 0 18px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #edf0f5;
            text-align: left;
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            color: #687386;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-low {
            background: #fff0d8;
            color: #a45d00;
        }

        .badge-in {
            background: #d9f7e5;
            color: #17683a;
        }

        .badge-out {
            background: #ffe2e2;
            color: #a52020;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .btn {
            display: inline-block;
            background: #f28c28;
            color: white;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        .btn:hover {
            background: #db7818;
        }

        .btn-dark {
            background: #344256;
        }

        .btn-dark:hover {
            background: #253047;
        }

        .empty {
            color: #7b8495;
            text-align: center;
            padding: 24px;
        }

        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .section-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: static;
                width: 100%;
                padding: 18px;
            }

            .brand-subtitle {
                margin-bottom: 15px;
            }

            .menu-label {
                margin-top: 15px;
            }

            .menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 3px;
            }

            .main {
                margin-left: 0;
                padding: 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            h1 {
                font-size: 23px;
            }

            .stats {
                gap: 10px;
            }

            .stat {
                padding: 15px;
            }

            .stat-value {
                font-size: 22px;
            }

            .panel {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">Inventory App</div>
    <div class="brand-subtitle">Sistem Manajemen Persediaan</div>

    <nav class="menu">
        <div class="menu-label" style="grid-column:1/-1">Menu Utama</div>

        <a class="active" href="dashboard.php">▦ Dashboard</a>
        <a href="products/index.php">▤ Data Produk</a>
        <a href="products/create.php">＋ Tambah Produk</a>
        <a href="products/stock.php">⇅ Stok Masuk/Keluar</a>
        <a href="products/movements.php">◷ Riwayat Stok</a>
        <a href="reports/index.php">Laporan Inventaris</a>
        

        <div class="menu-label" style="grid-column:1/-1">Master Data</div>

        <a href="categories/index.php">▣ Kategori</a>
        <a href="suppliers/index.php">♙ Supplier</a>
        
<?php if (isAdmin()): ?>
    <a href="/inventory-app-vutri/users/create.php"
       style="display:inline-block;
              background:#f28c28;
              color:white;
              padding:10px 16px;
              border-radius:6px;
              text-decoration:none;
              margin:5px;">
        + Tambah Pengguna
    </a>
<?php endif; ?>


        <div class="menu-label" style="grid-column:1/-1">Akun</div>

        <a href="logout.php"
           onclick="return confirm('Yakin ingin keluar?')">
            ⇥ Logout
        </a>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <div>
            <h1>Dashboard</h1>
            <div class="muted">
                Ringkasan kondisi persediaan barang kamu.
            </div>
        </div>

        <div class="user">
            Halo, <strong><?= e($username) ?></strong>
        </div>
    </header>

    <div class="stats">
        <div class="stat orange">
            <div class="stat-title">Total Produk</div>
            <div class="stat-value">
                <?= number_format($totalProducts) ?>
            </div>
            <div class="stat-note">Jenis produk terdaftar</div>
        </div>

        <div class="stat blue">
            <div class="stat-title">Total Kategori</div>
            <div class="stat-value">
                <?= number_format($totalCategories) ?>
            </div>
            <div class="stat-note">Kategori produk</div>
        </div>

        <div class="stat green">
            <div class="stat-title">Total Stok</div>
            <div class="stat-value">
                <?= number_format($totalStock) ?>
            </div>
            <div class="stat-note">Jumlah seluruh unit barang</div>
        </div>

        <div class="stat red">
            <div class="stat-title">Stok Menipis</div>
            <div class="stat-value">
                <?= number_format($lowStock) ?>
            </div>
            <div class="stat-note">Produk mencapai batas minimum</div>
        </div>
    </div>

    <div class="stats">
        <div class="stat blue">
            <div class="stat-title">Total Supplier</div>
            <div class="stat-value">
                <?= number_format($totalSuppliers) ?>
            </div>
            <div class="stat-note">Pemasok terdaftar</div>
        </div>

        <div class="stat orange">
            <div class="stat-title">Nilai Persediaan</div>
            <div class="stat-value" style="font-size:22px">
                <?= e(rupiah($inventoryValue)) ?>
            </div>
            <div class="stat-note">Harga beli × jumlah stok</div>
        </div>

        <div class="stat green">
            <div class="stat-title">Stok Masuk Hari Ini</div>
            <div class="stat-value">
                <?= number_format($todayIn) ?>
            </div>
            <div class="stat-note">Unit masuk hari ini</div>
        </div>

        <div class="stat red">
            <div class="stat-title">Stok Keluar Hari Ini</div>
            <div class="stat-value">
                <?= number_format($todayOut) ?>
            </div>
            <div class="stat-note">Unit keluar hari ini</div>
        </div>
    </div>

    <div class="quick-actions">
        <a class="btn" href="products/create.php">+ Tambah Produk</a>
        <a class="btn" href="products/stock.php">⇅ Catat Pergerakan Stok</a>
        <a class="btn btn-dark" href="products/index.php">Lihat Semua Produk</a>
        <a class="btn btn-dark" href="products/movements.php">Lihat Riwayat</a>
    </div>

    <div class="section-grid">
        <section class="panel">
            <h2>⚠ Produk dengan Stok Menipis</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Produk</th>
                        <th>Stok</th>
                        <th>Minimum</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$lowStockProducts): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Semua stok berada di atas batas minimum.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?= e($product['code']) ?></td>
                                <td><?= e($product['name']) ?></td>
                                <td>
                                    <?= number_format((int) $product['stock']) ?>
                                    <?= e($product['unit']) ?>
                                </td>
                                <td>
                                    <?= number_format((int) $product['min_stock']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-low">
                                        Stok Menipis
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <h2>◷ Transaksi Stok Terbaru</h2>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$recentMovements): ?>
                        <tr>
                            <td colspan="4" class="empty">
                                Belum ada transaksi stok.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentMovements as $movement): ?>
                            <tr>
                                <td>
                                    <?= e($movement['name']) ?>
                                    <br>
                                    <span class="muted">
                                        <?= e($movement['code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($movement['movement_type'] === 'in'): ?>
                                        <span class="badge badge-in">Masuk</span>
                                    <?php elseif ($movement['movement_type'] === 'out'): ?>
                                        <span class="badge badge-out">Keluar</span>
                                    <?php else: ?>
                                        <span class="badge">Penyesuaian</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= number_format((int) $movement['quantity']) ?>
                                    <?= e($movement['unit']) ?>
                                </td>
                                <td><?= e($movement['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="muted">
        Dashboard Inventory &copy; <?= date('Y') ?>
    </div>
</main>

</body>
</html>
