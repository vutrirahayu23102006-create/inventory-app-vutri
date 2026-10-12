<?php
require_once __DIR__ . '/config/database.php';

$username = 'admin';
$password = 'Admin123!';
$name = 'Administrator';

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->fetch()) {
    exit('Akun admin sudah ada. Hapus file create_admin.php setelah selesai.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (name, username, password, role)
     VALUES (?, ?, ?, 'admin')"
);
$stmt->execute([$name, $username, $hash]);

echo 'Admin berhasil dibuat. Username: admin | Password: Admin123!';
echo '<br>Hapus file create_admin.php sekarang demi keamanan.';
