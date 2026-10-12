<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$name = '';
$description = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Nama kategori maksimal 100 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name, description) VALUES (?, ?)'
            );
            $stmt->execute([
                $name,
                $description !== '' ? $description : null
            ]);

            header('Location: index.php?success=created');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Nama kategori sudah ada atau tidak dapat disimpan.';
            } else {
                $error = 'Gagal menyimpan kategori. Periksa struktur tabel.';
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
<title>Tambah Kategori</title>
<style>
body{margin:0;padding:25px;background:#f4f6fa;font-family:Arial,sans-serif;color:#253047}
.card{max-width:650px;margin:20px auto;background:white;padding:25px;border-radius:12px;box-shadow:0 3px 12px #1d2c400a}
label{display:block;font-weight:bold;margin:17px 0 8px}
input,textarea{width:100%;padding:12px;border:1px solid #d6dce5;border-radius:7px;font-size:15px;box-sizing:border-box}
textarea{resize:vertical}
button,a{display:inline-block;padding:11px 16px;border:0;border-radius:7px;background:#f28c28;color:white;text-decoration:none;cursor:pointer;font-size:14px}
a{background:#344256}
.actions{display:flex;gap:10px;margin-top:20px}
.error{background:#ffe2e2;color:#a52020;padding:12px;border-radius:7px}
</style>
</head>
<body>
<div class="card">
    <h1>Tambah Kategori</h1>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Nama Kategori *</label>
        <input id="name" name="name" maxlength="100"
               value="<?= e($name) ?>" required
               placeholder="Contoh: Elektronik">

        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="4"
                  placeholder="Deskripsi kategori (opsional)"><?= e($description) ?></textarea>

        <div class="actions">
            <button type="submit">Simpan Kategori</button>
            <a href="index.php">Kembali</a>
        </div>
    </form>
</div>
</body>
</html>
