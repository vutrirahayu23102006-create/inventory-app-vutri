<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /inventory-app-vutri/login.php');
        exit;
    }
}

function isAdmin(): bool
{
    return isLoggedIn()
        && isset($_SESSION['role'])
        && $_SESSION['role'] === 'admin';
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        http_response_code(403);
        exit('403 - Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}