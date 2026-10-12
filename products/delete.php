<?php
require_once __DIR__ . '/../helpers/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/activity_log.php';

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
    // Cari produk sebelum dihapus
    $stmt = $pdo->prepare(
        "SELECT id, name, code FROM products WHERE id = ?"
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {Get-ChildItem C:\xampp2026 -Directory
        header('Location: index.php');
        exit;
    }

    // Hapus produk
    $stmt = $pdo->prepare(
        "DELETE FROM products WHERE id = ?"
    );
    $stmt->execute([$id]);

    // Pastikan produk benar-benar terhapus
    if ($stmt->rowCount() > 0) {
        logActivity(
            $pdo,
            'Hapus Produk',
            'Menghapus produk "' . $product['name'] .
            '" (Kode: ' . $product['code'] . ').'
        );
    }

    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    error_log('Gagal menghapus produk: ' . $e->getMessage());

    http_response_code(409);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Produk Tidak Dapat Dihapus</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f1f5f9;
                padding: 30px;
            }

            .box {
                max-width: 500px;
                margin: 40px auto;
                background: white;
                padding: 25px;
                border-radius: 12px;
            }

            a {
                display: inline-block;
                margin-top: 12px;
                background: #2563eb;
                color: white;
                padding: 10px 14px;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Produk Tidak Dapat Dihapus</h2>
            <p>
                Produk mungkin masih digunakan oleh catatan stok
                atau data transaksi. Data belum berhasil dihapus.
            </p>
            <p>
                Periksa relasi tabel di database jika produk
                masih terhubung dengan data lain.
            </p>
            <a href="index.php">Kembali ke Data Produk</a>
        </div>
    </body>
    </html>
    <?php
}