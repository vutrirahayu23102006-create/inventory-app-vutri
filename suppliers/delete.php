<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

try {
    // Cegah penghapusan jika supplier masih digunakan oleh produk.
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM products WHERE supplier_id = ?"
    );
    $stmt->execute([$id]);

    if ((int)$stmt->fetchColumn() > 0) {
        echo '<p>Supplier tidak dapat dihapus karena masih digunakan oleh produk.</p>';
        echo '<p><a href="index.php">Kembali ke daftar supplier</a></p>';
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: index.php');
    exit;
} catch (PDOException $e) {
    echo '<p>Supplier gagal dihapus. Silakan periksa data dan coba lagi.</p>';
    echo '<p><a href="index.php">Kembali</a></p>';
}