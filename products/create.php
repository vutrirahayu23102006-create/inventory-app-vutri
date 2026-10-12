
<?php
require_once __DIR__ . '/../helpers/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/activity_log.php';

$error = '';

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$suppliers = $pdo->query(
    "SELECT id, name FROM suppliers ORDER BY name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $supplierId = $_POST['supplier_id'] ?? '';
    $supplierPrice = trim($_POST['supplier_price'] ?? '');
    $purchasePrice = trim($_POST['purchase_price'] ?? '');
    $sellingPrice = trim($_POST['selling_price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $minStock = trim($_POST['min_stock'] ?? '5');
    $unit = trim($_POST['unit'] ?? 'pcs');
    $description = trim($_POST['description'] ?? '');

    if (
        $code === '' ||
        $name === '' ||
        $purchasePrice === '' ||
        $sellingPrice === '' ||
        !is_numeric($purchasePrice) ||
        !is_numeric($sellingPrice) ||
        !ctype_digit($stock) ||
        !ctype_digit($minStock) ||
        (float) $purchasePrice < 0 ||
        (float) $sellingPrice < 0 ||
        $unit === ''
    ) {
        $error = 'Periksa kembali data produk. Pastikan semua nilai benar.';
    } elseif (
        $categoryId !== '' &&
        !ctype_digit((string) $categoryId)
    ) {
        $error = 'Kategori yang dipilih tidak valid.';
    } elseif (
        $supplierId !== '' &&
        !ctype_digit((string) $supplierId)
    ) {
        $error = 'Supplier yang dipilih tidak valid.';
    } elseif (
        $supplierId !== '' &&
        (
            $supplierPrice === '' ||
            !is_numeric($supplierPrice) ||
            (float) $supplierPrice < 0
        )
    ) {
        $error = 'Isi harga supplier dengan angka nol atau lebih.';
    } elseif (
        $supplierId === '' &&
        $supplierPrice !== '' &&
        (
            !is_numeric($supplierPrice) ||
            (float) $supplierPrice < 0
        )
    ) {
        $error = 'Harga supplier tidak valid.';
    } else {
        try {
            // Pastikan kategori benar-benar ada.
            if ($categoryId !== '') {
                $checkCategory = $pdo->prepare(
                    "SELECT id FROM categories WHERE id = ?"
                );
                $checkCategory->execute([(int) $categoryId]);

                if (!$checkCategory->fetch()) {
                    throw new RuntimeException(
                        'Kategori yang dipilih tidak ditemukan.'
                    );
                }
            }

            // Pastikan supplier benar-benar ada.
            if ($supplierId !== '') {
                $checkSupplier = $pdo->prepare(
                    "SELECT id FROM suppliers WHERE id = ?"
                );
                $checkSupplier->execute([(int) $supplierId]);

                if (!$checkSupplier->fetch()) {
                    throw new RuntimeException(
                        'Supplier yang dipilih tidak ditemukan.'
                    );
                }
            }

            $pdo->beginTransaction();

            // Simpan produk.
            $stmt = $pdo->prepare(
                "INSERT INTO products
                (
                    code, name, category_id, supplier_id,
                    purchase_price, selling_price, stock,
                    min_stock, unit, description
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $code,
                $name,
                $categoryId !== '' ? (int) $categoryId : null,
                $supplierId !== '' ? (int) $supplierId : null,
                $purchasePrice,
                $sellingPrice,
                (int) $stock,
                (int) $minStock,
                $unit,
                $description !== '' ? $description : null
            ]);

            $productId = (int) $pdo->lastInsertId();

            // Simpan relasi produk dan supplier.
            if ($supplierId !== '') {
                $stmtSupplier = $pdo->prepare(
                    "INSERT INTO product_suppliers
                    (product_id, supplier_id, supplier_price)
                    VALUES (?, ?, ?)"
                );

                $stmtSupplier->execute([
                    $productId,
                    (int) $supplierId,
                    $supplierPrice
                ]);
            }

            // Catat aktivitas sebelum commit.
            $details =
                'Menambahkan produk "' . $name . '"' .
                ' | Kode: ' . $code .
                ' | Stok awal: ' . (int) $stock . ' ' . $unit;

            if ($supplierId !== '') {
                $details .=
                    ' | Supplier ID: ' . (int) $supplierId .
                    ' | Harga supplier: Rp ' . $supplierPrice;
            }

            $logged = logActivity(
                $pdo,
                'Tambah Produk',
                $details
            );

            if (!$logged) {
                error_log(
                    'Produk berhasil diproses, tetapi log aktivitas gagal.'
                );
            }

            $pdo->commit();

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof PDOException) {
                error_log(
                    'Gagal menyimpan produk: ' . $e->getMessage()
                );

                if ($e->getCode() === '23000') {
                    $error =
                        'Kode produk sudah digunakan atau data terkait tidak valid.';
                } else {
                    $error =
                        'Produk gagal disimpan. Periksa struktur database.';
                }
            } else {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>

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
            max-width: 750px;
            margin: auto;
            padding: 28px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px #0001;
        }

        h1 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 16px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            resize: vertical;
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

        .back {
            background: #64748b;
        }

        .error {
            padding: 12px;
            background: #fef2f2;
            color: #b91c1c;
            border-radius: 6px;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Tambah Produk</h1>
    <p>Masukkan informasi produk yang ingin disimpan.</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-grid">
            <div class="field">
                <label for="code">Kode Produk *</label>
                <input
                    id="code"
                    name="code"
                    required
                    maxlength="50"
                    value="<?= e($_POST['code'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="name">Nama Produk *</label>
                <input
                    id="name"
                    name="name"
                    required
                    maxlength="150"
                    value="<?= e($_POST['name'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="category_id">Kategori</label>
                <select id="category_id" name="category_id">
                    <option value="">-- Pilih kategori --</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= e($category['id']) ?>"
                            <?= (string) ($_POST['category_id'] ?? '') ===
                                (string) $category['id'] ? 'selected' : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">-- Pilih supplier --</option>

                    <?php foreach ($suppliers as $supplier): ?>
                        <option
                            value="<?= e($supplier['id']) ?>"
                            <?= (string) ($_POST['supplier_id'] ?? '') ===
                                (string) $supplier['id'] ? 'selected' : '' ?>
                        >
                            <?= e($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="supplier_price">Harga Supplier (Rp)</label>
                <input
                    id="supplier_price"
                    name="supplier_price"
                    type="number"
                    min="0"
                    step="0.01"
                    value="<?= e($_POST['supplier_price'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="unit">Satuan *</label>
                <input
                    id="unit"
                    name="unit"
                    required
                    maxlength="30"
                    value="<?= e($_POST['unit'] ?? 'pcs') ?>"
                >
            </div>

            <div class="field">
                <label for="purchase_price">Harga Beli (Rp) *</label>
                <input
                    id="purchase_price"
                    name="purchase_price"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value="<?= e($_POST['purchase_price'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="selling_price">Harga Jual (Rp) *</label>
                <input
                    id="selling_price"
                    name="selling_price"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    value="<?= e($_POST['selling_price'] ?? '') ?>"
                >
            </div>

            <div class="field">
                <label for="stock">Stok Awal *</label>
                <input
                    id="stock"
                    name="stock"
                    type="number"
                    min="0"
                    step="1"
                    required
                    value="<?= e($_POST['stock'] ?? '0') ?>"
                >
            </div>

            <div class="field">
                <label for="min_stock">Batas Stok Minimum *</label>
                <input
                    id="min_stock"
                    name="min_stock"
                    type="number"
                    min="0"
                    step="1"
                    required
                    value="<?= e($_POST['min_stock'] ?? '5') ?>"
                >
            </div>

            <div class="field full">
                <label for="description">Deskripsi</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                ><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="buttons">
            <button type="submit" class="button">
                Simpan Produk
            </button>

            <a href="index.php" class="button back">
                Batal
            </a>
        </div>
    </form>
</div>
</body>
</html>
