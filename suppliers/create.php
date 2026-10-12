<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

$error = '';
$name = '';
$phone = '';
$email = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = 'Nama supplier wajib diisi.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO suppliers (name, phone, email, address)
                 VALUES (:name, :phone, :email, :address)"
            );

            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone !== '' ? $phone : null,
                ':email' => $email !== '' ? $email : null,
                ':address' => $address !== '' ? $address : null
            ]);

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Supplier gagal disimpan. Periksa koneksi database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Supplier</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 25px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 10px; }
        h2 { color: #333; }
        label { display: block; margin-top: 15px; font-weight: bold; }
        input, textarea { width: 100%; box-sizing: border-box; padding: 10px; margin-top: 7px; border: 1px solid #ccc; border-radius: 5px; }
        textarea { min-height: 100px; resize: vertical; }
        .btn { display: inline-block; padding: 10px 15px; border: none; border-radius: 5px; color: white; text-decoration: none; cursor: pointer; margin-top: 20px; }
        .save { background: #f28c28; }
        .back { background: #6c757d; }
        .error { background: #f8d7da; color: #842029; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Tambah Supplier</h2>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Supplier *</label>
        <input type="text" name="name" maxlength="100"
               value="<?= e($name) ?>" required>

        <label>Nomor Telepon</label>
        <input type="text" name="phone" maxlength="20"
               value="<?= e($phone) ?>">

        <label>Email</label>
        <input type="email" name="email" maxlength="100"
               value="<?= e($email) ?>">

        <label>Alamat</label>
        <textarea name="address"><?= e($address) ?></textarea>

        <button type="submit" class="btn save">Simpan Supplier</button>
        <a href="index.php" class="btn back">Batal</a>
    </form>
</div>
</body>
</html>