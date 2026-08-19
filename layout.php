<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function pageStart(string $title, string $active): void
{
    requireAuth();
    $links = ['dashboard' => ['Dashboard', 'index.php'], 'stock-registration' => ['Register stock', 'stock-registration.php'], 'stock-management' => ['Stock records', 'stock-management.php'], 'customers' => ['Customers', 'customers.php'], 'releases' => ['Releases', 'releases.php'], 'search' => ['Search', 'search.php'], 'products' => ['Products', 'products.php'], 'warehouses' => ['Warehouses', 'warehouses.php'], 'movements' => ['Movements', 'movements.php'], 'partners' => ['Partners', 'partners.php'], 'orders' => ['Orders', 'orders.php'], 'reports' => ['Reports', 'reports.php']];
    if (currentUser()['role'] === 'admin') {
        $links['users'] = ['Users', 'users.php'];
        $links['sql-console'] = ['SQL console', 'sql-console.php'];
        $links['query-history'] = ['Query history', 'query-history.php'];
    }
    ?>
    <!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> | Quantix</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/app.css" rel="stylesheet"></head><body>
    <nav class="navbar navbar-expand-lg border-bottom bg-white"><div class="container py-2"><a class="navbar-brand fw-bold me-4" href="index.php"><span class="brand-mark">Q</span> Quantix</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="main-nav"><div class="navbar-nav gap-1"><?php foreach ($links as $key => [$label, $url]): ?><a class="nav-link <?= $active === $key ? 'active fw-semibold' : '' ?>" href="<?= e($url) ?>"><?= e($label) ?></a><?php endforeach; ?></div><div class="user-menu ms-auto"><span class="navbar-text small"><strong><?= e(currentUser()['name']) ?></strong> · <?= e(ucfirst(currentUser()['role'])) ?></span><a class="nav-link d-inline-block" href="logout.php">Sign out</a></div></div></div></nav><main class="container py-4 py-lg-5">
    <?php
}

function pageEnd(): void
{
    echo '</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="assets/app.js"></script></body></html>';
}
