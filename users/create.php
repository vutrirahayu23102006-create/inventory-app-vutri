<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireAdmin();

$error = '';
$name = '';
$username = '';
$role = 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if ($name === '' || $username === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($name) > 100 || strlen($username) > 100) {
        $error = 'Nama dan username maksimal 100 karakter.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif (!in_array($role, ['admin', 'staff'], true)) {
        $error = 'Role yang dipilih tidak valid.';
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (name, username, password, role)
                 VALUES (:name, :username, :password, :role)"
            );

            $stmt->execute([
                ':name' => $name,
                ':username' => $username,
                ':password' => $passwordHash,
                ':role' => $role
            ]);

            header('Location: index.php?success=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Username sudah digunakan. Silakan pilih username lain.';
            } else {
                error_log($e->getMessage());
                $error = 'Pengguna gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 30px;
        }
        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 24px;
            border-radius: 8px;
        }
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
        }
        input, select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            margin-top: 20px;
            padding: 11px 16px;
            background: #f28c28;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        a { color: #d66b0b; }
        .error {
            color: #b42318;
            background: #fee4e2;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Tambah Pengguna</h2>

    <?php if ($error !== ''): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="name">Nama Lengkap</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            value="<?= e($name) ?>"
            required
        >

        <label for="username">Username</label>
        <input
            type="text"
            id="username"
            name="username"
            maxlength="100"
            value="<?= e($username) ?>"
            required
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            required
        >

        <label for="role">Role</label>
        <select id="role" name="role" required>
            <option value="staff" <?= $role === 'staff' ? 'selected' : '' ?>>
                Staff
            </option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                Admin
            </option>
        </select>

        <button type="submit">Simpan Pengguna</button>
    </form>

    <p><a href="index.php">Kembali ke Manajemen Pengguna</a></p>
</div>
</body>
</html>
