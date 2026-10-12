<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function logActivity(PDO $pdo, string $action, string $details = ''): bool
{
    if (empty($_SESSION['user_id'])) {
        error_log('Log aktivitas dilewati: user_id tidak ditemukan di sesi.');
        return false;
    }

    try {
        $sql = "INSERT INTO activity_logs (user_id, action, details)
                VALUES (:user_id, :action, :details)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':user_id' => (int) $_SESSION['user_id'],
            ':action'  => $action,
            ':details' => $details
        ]);
    } catch (Throwable $e) {
        error_log('Gagal mencatat aktivitas: ' . $e->getMessage());
        return false;
    }
}
