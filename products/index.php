<?php
require_once __DIR__ . '/../helpers/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Ambil input pencarian dan filter.
$search = trim($_GET['search'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$stockStatus = $_GET['stock_status'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

// Pengaturan pagination.
$perPage = 10;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;

// Daftar kategori.
$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// Pilihan sorting yang diizinkan.
$sortOptions = [
    'newest'     => 'p.id DESC',
    'name_asc'   => 'p.name ASC',
    'name_desc'  => 'p.name DESC',
    'price_asc'  => 'p.selling_price ASC',
    'price_desc' => 'p.selling_price DESC',
    'stock_asc'  => 'p.stock ASC',
    'stock_desc' => 'p.stock DESC'
];

if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

// Susun kondisi pencarian dan filter.
$where = " WHERE 1=1";
$params = [];

// Pencarian berdasarkan nama atau kode produk.
if ($search !== '') {
    $where .= " AND (p.name LIKE ? OR p.code LIKE ?)";
    $keyword = '%' . $search . '%';
    $params[] = $keyword;
    $params[] = $keyword;
}

// Filter kategori.
if (
    $categoryId !== '' &&
    ctype_digit((string) $categoryId) &&
    (int) $categoryId > 0
) {
    $where .= " AND p.category_id = ?";
    $params[] = (int) $categoryId;
} else {
    $categoryId = '';
}

// Filter status stok.
if ($stockStatus === 'low') {
    $where .= " AND p.stock <= p.min_stock";
} elseif ($stockStatus === 'normal') {
    $where .= " AND p.stock > p.min_stock";
} else {
    $stockStatus = 'all';
}

// Hitung jumlah produk sesuai filter.
$countSql = "
    SELECT COUNT(*)
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $where
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();

// Hitung jumlah halaman.
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// Ambil produk untuk halaman aktif.
$sql = "
    SELECT
        p.id,
        p.code,
        p.name,
        p.selling_price,
        p.stock,
        p.min_stock,
        p.unit,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $where
    ORDER BY {$sortOptions[$sort]}, p.id DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pertahankan filter saat berpindah halaman.
$queryParams = [
    'search' => $search,
    'category_id' => $categoryId,
    'stock_status' => $stockStatus,
    'sort' => $sort
];

function productPageUrl(array $params, int $page): string
{
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Produk - Inventory Management System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
            color: #1e293b;
        }

        .container {
            max-width: 1250px;
            margin: auto;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0;
            font-size: 26px;
        }

        .button {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 6px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .back {
            background: #64748b;
        }

        .back:hover {
            background: #475569;
        }

        .delete-button {
            background: #dc2626;
        }

        .delete-button:hover {
            background: #b91c1c;
        }

        .filter-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 22px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 14px;
            align-items: end;
        }

        .field label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
            font-size: 14px;
        }

        .field input,
        .field select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: white;
            font: inherit;
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .result-count {
            color: #64748b;
            margin: 16px 0;
            font-size: 14px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th, td {
            padding: 13px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #334155;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .stock-low {
            color: #dc2626;
            font-weight: bold;
        }

        .stock-normal {
            color: #15803d;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 28px;
            color: #64748b;
        }

        .action-cell {
            white-space: nowrap;
        }

        .action-cell form {
            display: inline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .pagination .current {
            background: #0f172a;
        }

        .page-info {
            width: 100%;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        @media (max-width: 900px) {
            .filter-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 16px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <h1>Data Produk</h1>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="button back" href="../dashboard.php">
                Kembali
            </a>

            <a class="button" href="create.php">
                + Tambah Produk
            </a>
        </div>
    </div>

    <div class="filter-box">
        <h3 style="margin-top:0;">Cari dan Filter Produk</h3>

        <form method="GET">
            <div class="filter-grid">

                <div class="field">
                    <label for="search">Nama atau Kode Produk</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Contoh: Laptop atau BRG001"
                        value="<?= e($search) ?>"
                    >
                </div>

                <div class="field">
                    <label for="category_id">Kategori</label>
                    <select id="category_id" name="category_id">
                        <option value="">Semua Kategori</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= (string) $categoryId === (string) $category['id'] ? 'selected' : '' ?>
                            >
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="stock_status">Status Stok</label>
                    <select id="stock_status" name="stock_status">
                        <option value="all" <?= $stockStatus === 'all' ? 'selected' : '' ?>>
                            Semua Stok
                        </option>

                        <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>
                            Stok Menipis
                        </option>

                        <option value="normal" <?= $stockStatus === 'normal' ? 'selected' : '' ?>>
                            Stok Normal
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="sort">Urutkan Berdasarkan</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                            Terbaru
                        </option>

                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>
                            Nama A–Z
                        </option>

                        <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>
                            Nama Z–A
                        </option>

                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
                            Harga Termurah
                        </option>

                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
                            Harga Termahal
                        </option>

                        <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>
                            Stok Terkecil
                        </option>

                        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>
                            Stok Terbesar
                        </option>
                    </select>
                </div>

            </div>

            <div class="filter-buttons">
                <button type="submit" class="button">
                    Cari / Terapkan Filter
                </button>

                <a href="index.php" class="button back">
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    <p class="result-count">
        Menampilkan <?= count($products) ?> dari
        <?= $totalProducts ?> produk.
        Halaman <?= $page ?> dari <?= $totalPages ?>.
    </p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kode Produk</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th>Status Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" class="empty">
                        Tidak ada produk yang sesuai dengan pencarian atau filter.
                    </td>
                </tr>
            <?php else: ?>

                <?php foreach ($products as $i => $product): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>

                        <td><?= e($product['code']) ?></td>

                        <td><?= e($product['name']) ?></td>

                        <td><?= e($product['category_name'] ?? '-') ?></td>

                        <td>
                            Rp <?= number_format(
                                (float) $product['selling_price'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td><?= (int) $product['stock'] ?></td>

                        <td><?= e($product['unit']) ?></td>

                        <td>
                            <?php if (
                                (int) $product['stock'] <= (int) $product['min_stock']
                            ): ?>
                                <span class="stock-low">Stok Menipis</span>
                            <?php else: ?>
                                <span class="stock-normal">Normal</span>
                            <?php endif; ?>
                        </td>

                        <td class="action-cell">
                            <a
                                class="button"
                                href="edit.php?id=<?= (int) $product['id'] ?>"
                            >Edit</a>

                            <form
                                action="delete.php"
                                method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $product['id'] ?>"
                                >

                                <button
                                    class="button delete-button"
                                    type="submit"
                                >Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Navigasi halaman">

            <?php if ($page > 1): ?>
                <a
                    class="button back"
                    href="<?= e(productPageUrl($queryParams, $page - 1)) ?>"
                >
                    Sebelumnya
                </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            ?>

            <?php if ($startPage > 1): ?>
                <a
                    class="button"
                    href="<?= e(productPageUrl($queryParams, 1)) ?>"
                >1</a>

                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <a
                    class="button <?= $p === $page ? 'current' : '' ?>"
                    href="<?= e(productPageUrl($queryParams, $p)) ?>"
                    <?= $p === $page ? 'aria-current="page"' : '' ?>
                >
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>

                <a
                    class="button"
                    href="<?= e(productPageUrl($queryParams, $totalPages)) ?>"
                >
                    <?= $totalPages ?>
                </a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a
                    class="button back"
                    href="<?= e(productPageUrl($queryParams, $page + 1)) ?>"
                >
                    Berikutnya
                </a>
            <?php endif; ?>

            <div class="page-info">
                Halaman <?= $page ?> dari <?= $totalPages ?>
            </div>
        </nav>
    <?php endif; ?>

</div>
</body>
</html>