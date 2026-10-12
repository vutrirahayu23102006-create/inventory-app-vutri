<?php
require_once __DIR__ . '/config/database.php';

$name = 'Administrator';
$username = 'admin';
$password = 'Admin123!';

$stmt = $pdo->prepare(
    "SELECT id FROM users WHERE username = ? LIMIT 1"
);
$stmt->execute([$username]);

if ($stmt->fetch()) {
    exit('Akun admin sudah tersedia. Silakan login.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (name, username, password, role)
     VALUES (?, ?, ?, 'admin')"
);

$stmt->execute([$name, $username, $hashedPassword]);

echo 'Akun admin berhasil dibuat. Silakan login.';
?>
