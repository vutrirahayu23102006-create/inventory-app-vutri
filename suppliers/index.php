<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Supplier - Inventory</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }
        h2 { color: #333; }
        .btn {
            display: inline-block;
            padding: 9px 13px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .add { background: #f28c28; margin-bottom: 20px; }
        .edit { background: #2878d0; }
        .delete { background: #dc3545; }
        .back { background: #6c757d; margin-bottom: 15px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th { background: #f28c28; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .actions { display: flex; gap: 6px; }
        .empty { text-align: center; color: #777; }
        @media (max-width: 700px) {
            body { padding: 10px; }
            .container { padding: 12px; overflow-x: auto; }
            table { min-width: 700px; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="../dashboard.php" class="btn back">Kembali ke Dashboard</a>

    <h2>Data Supplier</h2>
    <p>Kelola data pemasok barang pada aplikasi inventaris.</p>

    <a href="create.php" class="btn add">+ Tambah Supplier</a>

    <table>
        <thead>
        <tr>
            <th>No.</th>
            <th>Nama Supplier</th>
            <th>Telepon</th>
            <th>Email</th>
            <th>Alamat</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($suppliers)): ?>
            <tr>
                <td colspan="6" class="empty">Belum ada data supplier.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($suppliers as $i => $supplier): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($supplier['name']) ?></td>
                    <td><?= e($supplier['phone'] ?? '-') ?></td>
                    <td><?= e($supplier['email'] ?? '-') ?></td>
                    <td><?= e($supplier['address'] ?? '-') ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn edit"
                               href="edit.php?id=<?= (int)$supplier['id'] ?>">Edit</a>

                            <form action="delete.php" method="POST"
                                  onsubmit="return confirm('Yakin ingin menghapus supplier ini?');">
                                <input type="hidden" name="id"
                                       value="<?= (int)$supplier['id'] ?>">
                                <button type="submit" class="btn delete">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>