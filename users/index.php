<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireAdmin();

$stmt = $pdo->query(
    "SELECT id, name, username, role
     FROM users
     ORDER BY id DESC"
);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f5f6fa;
        }

        h2 {
            color: #333;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            background: #f28c28;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }

        .btn:hover {
            background: #d97718;
        }

        .back {
            color: #d66b0b;
        }

        .success {
            color: #166534;
            background: #dcfce7;
            padding: 12px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f28c28;
            color: white;
        }
    </style>
</head>

<body>
    <h2>Manajemen Pengguna</h2>
    <p>Daftar akun Admin dan Staff.</p>

    <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
        <p class="success">Pengguna berhasil ditambahkan.</p>
    <?php endif; ?>

    <p>
        <a class="btn" href="create.php">+ Tambah Pengguna</a>
        <a class="back" href="../dashboard.php">Kembali ke Dashboard</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
            </tr>
        </thead>

        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4">Belum ada pengguna.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['id']) ?></td>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e(ucfirst($user['role'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
