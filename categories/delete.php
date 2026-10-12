<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: index.php?error=failed');
    exit;
}

try {
    // Cegah penghapusan kategori yang masih dipakai produk.
    $check = $pdo->prepare(
        'SELECT COUNT(*) FROM products WHERE category_id = ?'
    );
    $check->execute([$id]);

    if ((int)$check->fetchColumn() > 0) {
        header('Location: index.php?error=used');
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        header('Location: index.php?success=deleted');
    } else {
        header('Location: index.php?error=failed');
    }
    exit;
} catch (PDOException $e) {
    header('Location: index.php?error=failed');
    exit;
}

