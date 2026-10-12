<?php
require_once __DIR__ . '/../helpers/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/activity_log.php';

// Hanya izinkan permintaan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validasi ID produk
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

try {
    // Cari produk
    $stmt = $pdo->prepare(
        "SELECT id, name, code FROM products WHERE id = ?"
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php');
        exit;
    }

    // Periksa apakah produk memiliki riwayat pergerakan stok
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM stock_movements WHERE product_id = ?"
    );
    $stmt->execute([$id]);
    $movementCount = (int) $stmt->fetchColumn();

    if ($movementCount > 0) {
        http_response_code(409);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport"
                  content="width=device-width, initial-scale=1">
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
                    Produk ini masih memiliki riwayat pergerakan stok.
                    Riwayat transaksi harus dipertahankan.
                </p>
                <p>
                    Kamu dapat mempertahankan produk ini dan tidak
                    menggunakannya lagi untuk transaksi baru.
                </p>
                <a href="index.php">Kembali ke Data Produk</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Hapus produk yang tidak memiliki riwayat stok
    $stmt = $pdo->prepare(
        "DELETE FROM products WHERE id = ?"
    );
    $stmt->execute([$id]);

    // Catat aktivitas jika produk berhasil dihapus
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
        <meta name="viewport"
              content="width=device-width, initial-scale=1">
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
                Produk tidak dapat dihapus karena mungkin masih
                terhubung dengan data lain.
            </p>
            <p>
                Data transaksi tidak boleh dihapus sembarangan.
            </p>
            <a href="index.php">Kembali ke Data Produk</a>
        </div>
    </body>
    </html>
    <?php
}
