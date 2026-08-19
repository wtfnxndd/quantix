<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category']);
    $sku = trim($_POST['sku']) ?: generateSku($database, $category);
    $statement = $database->prepare('INSERT INTO products (name, sku, category, stock_type, unit, reorder_level) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([trim($_POST['name']), $sku, $category, trim($_POST['stock_type']), trim($_POST['unit']), (float) $_POST['reorder_level']]);
    $message = 'Stock item registered successfully.';
}
$recent = $database->query('SELECT name, sku, category, stock_type, unit, reorder_level FROM products ORDER BY id DESC LIMIT 10')->fetchAll();
pageStart('Stock registration', 'stock-registration');
?><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Inventory setup</p><h1 class="display-6 fw-semibold mb-2">Stock registration</h1><p class="text-secondary mb-0">Register a new item before recording its movements.</p></div><a class="btn btn-light" href="products.php">View full catalog</a></div><?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><form method="post" class="panel row g-3 mb-4"><div class="col-md-4"><label class="form-label">Item name</label><input class="form-control" name="name" placeholder="Bottled drinking water" required></div><div class="col-md-2"><label class="form-label">SKU</label><input class="form-control" name="sku" placeholder="GRO-008" required></div><div class="col-md-2"><label class="form-label">Category</label><input class="form-control" name="category" placeholder="Groceries" required></div><div class="col-md-2"><label class="form-label">Stock type</label><select class="form-select" name="stock_type" required><?php foreach (['Raw Materials', 'Finished Goods', 'Consumables', 'Packaging', 'Spare Parts', 'Safety Equipment', 'Office Supplies', 'MRO Supplies'] as $type): ?><option><?= e($type) ?></option><?php endforeach; ?></select></div><div class="col-md-1"><label class="form-label">Unit</label><input class="form-control" name="unit" value="units" required></div><div class="col-md-1"><label class="form-label">Reorder</label><input class="form-control" type="number" min="0" step="0.01" name="reorder_level" value="0" required></div><div class="col-12"><button class="btn btn-success">Register stock item</button></div></form><div class="panel"><h2 class="h5 mb-3">Recently registered</h2><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Item</th><th>Category</th><th>Stock type</th><th>Unit</th><th>Reorder level</th></tr></thead><tbody><?php foreach ($recent as $item): ?><tr><td><strong><?= e($item['name']) ?></strong><small class="d-block text-secondary"><?= e($item['sku']) ?></small></td><td><?= e($item['category']) ?></td><td><span class="badge text-bg-light"><?= e($item['stock_type']) ?></span></td><td><?= e($item['unit']) ?></td><td><?= e($item['reorder_level']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php pageEnd(); ?>
