<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$stmt = $pdo->query(
    "SELECT c.id, c.name, c.description, c.created_at,
            COUNT(p.id) AS total_products
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name, c.description, c.created_at
     ORDER BY c.id DESC"
);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
if (isset($_GET['success'])) {
    $messages = [
        'created' => 'Kategori berhasil ditambahkan.',
        'updated' => 'Kategori berhasil diperbarui.',
        'deleted' => 'Kategori berhasil dihapus.'
    ];
    $message = $messages[$_GET['success']] ?? '';
}

if (isset($_GET['error']) && $_GET['error'] === 'used') {
    $message = 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.';
} elseif (isset($_GET['error']) && $_GET['error'] === 'failed') {
    $message = 'Kategori gagal dihapus. Silakan periksa kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Kategori</title>
<style>
*{box-sizing:border-box}
body{margin:0;padding:25px;background:#f4f6fa;color:#253047;font-family:Arial,sans-serif}
.container{max-width:1100px;margin:auto}
.header{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:22px}
h1{margin:0 0 8px;font-size:27px}
.muted{color:#788396;font-size:14px}
.card{background:white;padding:22px;border-radius:12px;box-shadow:0 3px 12px #1d2c400a}
.actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{display:inline-block;border:0;border-radius:6px;padding:10px 13px;background:#f28c28;color:white;text-decoration:none;cursor:pointer;font-size:13px}
.btn-dark{background:#344256}
.btn-red{background:#d94b4b}
.alert{background:#d9f7e5;color:#17683a;padding:13px;border-radius:7px;margin-bottom:16px}
.alert-error{background:#ffe2e2;color:#a52020}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:13px 10px;border-bottom:1px solid #edf0f5;font-size:14px}
th{background:#f8fafc;color:#687386}
td{overflow-wrap:anywhere}
.empty{text-align:center;padding:25px;color:#788396}
form{display:inline}
@media(max-width:600px){body{padding:12px}.card{padding:14px}h1{font-size:23px}}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>Data Kategori</h1>
            <div class="muted">Kelola kategori produk inventory.</div>
        </div>
        <div class="actions">
            <a class="btn btn-dark" href="../dashboard.php">← Dashboard</a>
            <a class="btn" href="create.php">+ Tambah Kategori</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert <?= isset($_GET['error']) ? 'alert-error' : '' ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>No.</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah Produk</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="5" class="empty">Belum ada kategori.</td></tr>
            <?php else: ?>
                <?php foreach ($categories as $i => $category): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($category['name']) ?></strong></td>
                    <td><?= e($category['description'] ?? '-') ?></td>
                    <td><?= (int)$category['total_products'] ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn" href="edit.php?id=<?= (int)$category['id'] ?>">Edit</a>
                            <form method="POST" action="delete.php"
                                  onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                <button class="btn btn-red" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
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
