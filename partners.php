<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$customers = $database->query('SELECT * FROM customers ORDER BY name')->fetchAll();
$vendors = $database->query('SELECT * FROM vendors ORDER BY name')->fetchAll();
pageStart('Customers and vendors', 'partners');
?><div class="mb-4"><p class="eyebrow mb-2">Business network</p><h1 class="display-6 fw-semibold mb-2">Customers & vendors</h1><p class="text-secondary mb-0">Manage the people and companies connected to your inventory.</p></div><div class="row g-4"><section class="col-lg-6"><div class="panel"><div class="d-flex justify-content-between mb-3"><h2 class="h5">Customers</h2><span class="badge text-bg-light"><?= count($customers) ?></span></div><?php foreach ($customers as $customer): ?><div class="stock-item"><div><strong><?= e($customer['name']) ?></strong><small class="d-block text-secondary"><?= e($customer['code']) ?> · <?= e($customer['email']) ?></small></div><span class="text-secondary small"><?= e($customer['phone']) ?></span></div><?php endforeach; ?></div></section><section class="col-lg-6"><div class="panel"><div class="d-flex justify-content-between mb-3"><h2 class="h5">Vendors</h2><span class="badge text-bg-light"><?= count($vendors) ?></span></div><?php foreach ($vendors as $vendor): ?><div class="stock-item"><div><strong><?= e($vendor['name']) ?></strong><small class="d-block text-secondary"><?= e($vendor['code']) ?> · <?= e($vendor['email']) ?></small></div><span class="text-secondary small"><?= e($vendor['phone']) ?></span></div><?php endforeach; ?></div></section></div><?php pageEnd(); ?>
