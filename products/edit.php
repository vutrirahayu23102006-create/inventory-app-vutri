```php
<?php
require_once __DIR__ . '/../helpers/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/activity_log.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query(
    "SELECT id, name FROM suppliers ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    "SELECT supplier_id, supplier_price
     FROM product_suppliers
     WHERE product_id = ?"
);
$stmt->execute([$id]);
$productSuppliers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $purchasePrice = trim($_POST['purchase_price'] ?? '');
    $sellingPrice = trim($_POST['selling_price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $minStock = trim($_POST['min_stock'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $supplierIds = $_POST['supplier_ids'] ?? [];
    $supplierPrices = $_POST['supplier_prices'] ?? [];

    if (!is_array($supplierIds) || !is_array($supplierPrices)) {
        $supplierIds = [];
        $supplierPrices = [];
        $error = 'Data pemasok tidak valid.';
    }

    $selectedSuppliers = [];

    if ($error === '') {
        foreach ($supplierIds as $supplierId) {
            $supplierId = (string) $supplierId;

            if (!ctype_digit($supplierId) || (int) $supplierId < 1) {
                $error = 'Pilihan pemasok tidak valid.';
                break;
            }

            if (isset($selectedSuppliers[$supplierId])) {
                $error = 'Pemasok yang sama tidak boleh dipilih dua kali.';
                break;
            }

            $price = trim((string) ($supplierPrices[$supplierId] ?? ''));

            if ($price === '' || !is_numeric($price) || (float) $price < 0) {
                $error = 'Masukkan harga yang valid untuk setiap pemasok yang dipilih.';
                break;
            }

            $selectedSuppliers[$supplierId] = (float) $price;
        }
    }

    if (
        $error === '' &&
        (
            $code === '' ||
            $name === '' ||
            $unit === '' ||
            !is_numeric($purchasePrice) ||
            !is_numeric($sellingPrice) ||
            !ctype_digit($stock) ||
            !ctype_digit($minStock) ||
            (float) $purchasePrice < 0 ||
            (float) $sellingPrice < 0 ||
            (int) $stock < 0 ||
            (int) $minStock < 0
        )
    ) {
        $error = 'Periksa kembali data produk.';
    }

    if (
        $error === '' &&
        $categoryId !== '' &&
        (!ctype_digit((string) $categoryId) || (int) $categoryId < 1)
    ) {
        $error = 'Kategori yang dipilih tidak valid.';
    }

    if ($error === '') {
        try {
            $oldName = $product['name'];
            $oldCode = $product['code'];

            $pdo->beginTransaction();

            // Pastikan kategori benar-benar tersedia.
            if ($categoryId !== '') {
                $check = $pdo->prepare(
                    "SELECT id FROM categories WHERE id = ?"
                );
                $check->execute([$categoryId]);

                if (!$check->fetchColumn()) {
                    throw new RuntimeException('Kategori tidak ditemukan.');
                }
            }

            // Pastikan semua pemasok benar-benar tersedia.
            if (!empty($selectedSuppliers)) {
                $check = $pdo->prepare(
                    "SELECT id FROM suppliers WHERE id = ?"
                );

                foreach (array_keys($selectedSuppliers) as $supplierId) {
                    $check->execute([$supplierId]);

                    if (!$check->fetchColumn()) {
                        throw new RuntimeException(
                            'Salah satu pemasok tidak ditemukan.'
                        );
                    }
                }
            }

            // Kolom supplier_id lama tetap dipakai untuk kompatibilitas.
            $primarySupplierId = !empty($selectedSuppliers)
                ? (int) array_key_first($selectedSuppliers)
                : null;

            $stmt = $pdo->prepare(
                "UPDATE products
                 SET code = ?,
                     name = ?,
                     category_id = ?,
                     supplier_id = ?,
                     purchase_price = ?,
                     selling_price = ?,
                     stock = ?,
                     min_stock = ?,
                     unit = ?,
                     description = ?,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            );

            $stmt->execute([
                $code,
                $name,
                $categoryId !== '' ? $categoryId : null,
                $primarySupplierId,
                $purchasePrice,
                $sellingPrice,
                $stock,
                $minStock,
                $unit,
                $description !== '' ? $description : null,
                $id
            ]);

            // Ganti hubungan pemasok produk dengan pilihan terbaru.
            $stmt = $pdo->prepare(
                "DELETE FROM product_suppliers WHERE product_id = ?"
            );
            $stmt->execute([$id]);

            $stmt = $pdo->prepare(
                "INSERT INTO product_suppliers
                 (product_id, supplier_id, supplier_price)
                 VALUES (?, ?, ?)"
            );

            foreach ($selectedSuppliers as $supplierId => $price) {
                $stmt->execute([$id, $supplierId, $price]);
            }

            logActivity(
                $pdo,
                'Edit Produk',
                'Memperbarui produk "' . $oldName .
                '" (Kode lama: ' . $oldCode .
                ') menjadi "' . $name .
                '" (Kode baru: ' . $code .
                '). Jumlah pemasok: ' . count($selectedSuppliers) . '.'
            );

            $pdo->commit();

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                error_log($e->getMessage());

                if ($e->getCode() === '23000') {
                    $error = 'Kode produk sudah digunakan atau data terkait tidak valid.';
                } else {
                    $error = 'Produk gagal diperbarui.';
                }
            } else {
                $error = $e->getMessage();
            }
        }
    }

    // Pertahankan data formulir jika terjadi kesalahan.
    $product = array_merge($product, [
        'code' => $code,
        'name' => $name,
        'category_id' => $categoryId,
        'purchase_price' => $purchasePrice,
        'selling_price' => $sellingPrice,
        'stock' => $stock,
        'min_stock' => $minStock,
        'unit' => $unit,
        'description' => $description
    ]);

    $productSuppliers = [];

    foreach ($selectedSuppliers as $supplierId => $price) {
        $productSuppliers[$supplierId] = $price;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 30px;
            color: #1e293b;
        }

        .container {
            max-width: 750px;
            margin: auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px #0001;
        }

        h1 { margin-top: 0; }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .field { margin-bottom: 14px; }
        .full { grid-column: 1 / -1; }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        textarea { resize: vertical; }

        .supplier-list {
            display: grid;
            gap: 12px;
        }

        .supplier-item {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
        }

        .supplier-item label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .supplier-item input[type="checkbox"] {
            width: auto;
        }

        .supplier-price {
            margin-top: 10px;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .button {
            display: inline-block;
            padding: 11px 16px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }

        .back { background: #64748b; }

        .error {
            padding: 12px;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 6px;
        }

        .hint {
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            body { padding: 12px; }
            .container { padding: 18px; }
            .grid { grid-template-columns: 1fr; }
            .full { grid-column: auto; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Edit Produk</h1>
    <p>Perbarui informasi produk dan pilih satu atau beberapa pemasok.</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="grid">
            <div class="field">
                <label for="code">Kode Produk *</label>
                <input id="code" name="code" maxlength="50" required
                       value="<?= e($product['code']) ?>">
            </div>

            <div class="field">
                <label for="name">Nama Produk *</label>
                <input id="name" name="name" maxlength="150" required
                       value="<?= e($product['name']) ?>">
            </div>

            <div class="field">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id">
                    <option value="">-- Pilih kategori --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>"
                            <?= (string)$product['category_id'] === (string)$category['id'] ? 'selected' : '' ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="unit">Satuan *</label>
                <input id="unit" name="unit" maxlength="30" required
                       value="<?= e($product['unit']) ?>">
            </div>

            <div class="field full">
                <label>Pemasok Produk</label>
                <p class="hint">Centang pemasok yang menyediakan produk ini, lalu isi harga dari masing-masing pemasok.</p>

                <div class="supplier-list">
                    <?php foreach ($suppliers as $supplier): ?>
                        <?php
                        $sid = (string) $supplier['id'];
                        $isSelected = array_key_exists($sid, $productSuppliers);
                        $supplierPrice = $productSuppliers[$sid] ?? '';
                        ?>
                        <div class="supplier-item">
                            <label>
                                <input
                                    type="checkbox"
                                    name="supplier_ids[]"
                                    value="<?= e($supplier['id']) ?>"
                                    <?= $isSelected ? 'checked' : '' ?>
                                    onchange="toggleSupplierPrice(this)"
                                >
                                <?= e($supplier['name']) ?>
                            </label>

                            <div class="supplier-price">
                                <label for="supplier_price_<?= e($supplier['id']) ?>">
                                    Harga Pemasok (Rp)
                                </label>
                                <input
                                    id="supplier_price_<?= e($supplier['id']) ?>"
                                    type="number"
                                    name="supplier_prices[<?= e($supplier['id']) ?>]"
                                    min="0"
                                    step="0.01"
                                    value="<?= e($supplierPrice) ?>"
                                    <?= $isSelected ? 'required' : 'disabled' ?>
                                >
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($suppliers)): ?>
                        <p>Belum ada pemasok. Tambahkan pemasok terlebih dahulu.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field">
                <label for="purchase_price">Harga Beli Utama (Rp) *</label>
                <input id="purchase_price" name="purchase_price"
                       type="number" min="0" step="0.01" required
                       value="<?= e($product['purchase_price']) ?>">
            </div>

            <div class="field">
                <label for="selling_price">Harga Jual (Rp) *</label>
                <input id="selling_price" name="selling_price"
                       type="number" min="0" step="0.01" required
                       value="<?= e($product['selling_price']) ?>">
            </div>

            <div class="field">
                <label for="stock">Stok *</label>
                <input id="stock" name="stock" type="number"
                       min="0" step="1" required
                       value="<?= e($product['stock']) ?>">
            </div>

            <div class="field">
                <label for="min_stock">Stok Minimum *</label>
                <input id="min_stock" name="min_stock" type="number"
                       min="0" step="1" required
                       value="<?= e($product['min_stock']) ?>">
            </div>

            <div class="field full">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description"
                          rows="3"><?= e($product['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="buttons">
            <button class="button" type="submit">Simpan Perubahan</button>
            <a class="button back" href="index.php">Batal</a>
        </div>
    </form>
</div>

<script>
function toggleSupplierPrice(checkbox) {
    const item = checkbox.closest('.supplier-item');
    const priceInput = item.querySelector('.supplier-price input');

    priceInput.disabled = !checkbox.checked;
    priceInput.required = checkbox.checked;

    if (!checkbox.checked) {
        priceInput.value = '';
    }
}
</script>
</body>
</html>
```