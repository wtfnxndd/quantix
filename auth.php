<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(419);
        exit('The form has expired. Refresh the page and try again.');
    }
}

function requireAuth(): void
{
    if (!currentUser()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole(string $role): void
{
    requireAuth();
    if (currentUser()['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}