<?php
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/config/database.php';

requireLogin();

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Management</title>
</head>
<body>
    <h1>Dashboard Inventory</h1>
    <p>Selamat datang, <?= e($_SESSION['name']) ?>!</p>
    <p>Total jenis produk: <?= e($totalProducts) ?></p>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>
