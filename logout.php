<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hapus data sesi pengguna
$_SESSION = [];

// Hapus cookie sesi jika digunakan
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Akhiri sesi login
session_destroy();

// Arahkan kembali ke halaman login
header('Location: /inventory-app-vutri/login.php');
exit;
