<?php
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
    $result = $stmt->fetch();

    echo "<h1>Inventory Management System</h1>";
    echo "<p>Koneksi database berhasil!</p>";
    echo "<p>Jumlah tabel products berhasil dibaca.</p>";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
