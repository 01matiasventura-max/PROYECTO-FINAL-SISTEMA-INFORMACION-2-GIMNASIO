<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['rol_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array((int)$_SESSION['rol_id'], $roles, true)) {
        header('Location: ' . BASE_URL . '/index.php?error=acceso_denegado');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['rol_id']) && (int)$_SESSION['rol_id'] === 1;
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentRolId(): int {
    return (int)($_SESSION['rol_id'] ?? 0);
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flashSuccess(string $msg): void {
    $_SESSION['flash_success'] = $msg;
}

function flashError(string $msg): void {
    $_SESSION['flash_error'] = $msg;
}

function getFlash(): array {
    $flash = [];
    if (!empty($_SESSION['flash_success'])) {
        $flash['success'] = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
    }
    if (!empty($_SESSION['flash_error'])) {
        $flash['error'] = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
    }
    return $flash;
}
