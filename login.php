<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare(
            "SELECT id, name, username, password, role
             FROM users
             WHERE username = :username
             LIMIT 1"
        );

        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            header('Location: dashboard.php');
            exit;
        }

        $error = 'Username atau password salah.';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Terjadi kesalahan saat memproses login.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Inventory</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .box {
            background: white;
            width: 360px;
            max-width: 92%;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px #0001;
        }

        h2 {
            color: #1e293b;
            margin-top: 0;
        }

        p {
            color: #475569;
        }

        label {
            display: block;
            margin-top: 16px;
            color: #334155;
        }

        input {
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        input:focus {
            outline: 2px solid #93c5fd;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            color: white;
            background: #2563eb;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            color: #dc2626;
            background: #fef2f2;
            padding: 10px;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Inventory Management</h2>
        <p>Silakan login untuk melanjutkan.</p>

        <?php if ($error !== ''): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Username</label>
            <input
                id="username"
                name="username"
                type="text"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
