<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']);
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function generateSku(PDO $database, string $category): string
{
    $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', $category) ?: 'STK');
    $prefix = substr($prefix, 0, 3);
    $statement = $database->prepare('SELECT sku FROM products WHERE sku LIKE ? ORDER BY sku DESC LIMIT 1');
    $statement->execute([$prefix . '-%']);
    $lastSku = (string) ($statement->fetchColumn() ?: '');
    $number = preg_match('/-(\d+)$/', $lastSku, $matches) ? ((int) $matches[1] + 1) : 1;
    return sprintf('%s-%03d', $prefix, $number);
}
