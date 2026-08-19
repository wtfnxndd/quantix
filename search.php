<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$search = trim($_GET['q'] ?? '');
$products = $customers = $movements = [];
if ($search !== '') {
    $pattern = '%' . $search . '%';
    $statement = db()->prepare('SELECT name, sku, category, stock_type FROM products WHERE name LIKE ? OR sku LIKE ? OR category LIKE ? ORDER BY name LIMIT 25');
    $statement->execute([$pattern, $pattern, $pattern]);
    $products = $statement->fetchAll();
    $statement = db()->prepare('SELECT name, code, email, phone FROM customers WHERE name LIKE ? OR code LIKE ? OR email LIKE ? ORDER BY name LIMIT 25');
    $statement->execute([$pattern, $pattern, $pattern]);
    $customers = $statement->fetchAll();
    $statement = db()->prepare('SELECT movement_number, reference, movement_type, quantity FROM inventory_movements WHERE movement_number LIKE ? OR reference LIKE ? ORDER BY movement_date DESC LIMIT 25');
    $statement->execute([$pattern, $pattern]);
    $movements = $statement->fetchAll();
}
pageStart('Search', 'search');
?><div class="mb-4"><p class="eyebrow mb-2">Find anything</p><h1 class="display-6 fw-semibold mb-2">Search</h1><p class="text-secondary mb-0">Search products, customers, and stock movement references.</p></div><form class="panel d-flex gap-2 mb-4"><input class="form-control form-control-lg" name="q" value="<?= e($search) ?>" placeholder="Try water, GRO-001, CUS-001, or PO-8031" autofocus><button class="btn btn-dark">Search</button></form><?php if ($search !== ''): ?><div class="row g-4"><section class="col-lg-4"><div class="panel h-100"><h2 class="h5 mb-3">Products <span class="text-secondary">(<?= count($products) ?>)</span></h2><?php foreach ($products as $item): ?><div class="stock-item"><div><strong><?= e($item['name']) ?></strong><small class="d-block text-secondary"><?= e($item['sku']) ?> · <?= e($item['stock_type']) ?></small></div></div><?php endforeach; ?></div></section><section class="col-lg-4"><div class="panel h-100"><h2 class="h5 mb-3">Customers <span class="text-secondary">(<?= count($customers) ?>)</span></h2><?php foreach ($customers as $item): ?><div class="stock-item"><div><strong><?= e($item['name']) ?></strong><small class="d-block text-secondary"><?= e($item['code']) ?> · <?= e($item['phone']) ?></small></div></div><?php endforeach; ?></div></section><section class="col-lg-4"><div class="panel h-100"><h2 class="h5 mb-3">Movements <span class="text-secondary">(<?= count($movements) ?>)</span></h2><?php foreach ($movements as $item): ?><div class="stock-item"><div><strong><?= e($item['movement_number']) ?></strong><small class="d-block text-secondary"><?= e($item['reference']) ?> · <?= e($item['movement_type']) ?></small></div><strong><?= e($item['quantity']) ?></strong></div><?php endforeach; ?></div></section></div><?php endif; ?><?php pageEnd(); ?>
