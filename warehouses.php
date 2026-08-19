<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statement = $database->prepare('INSERT INTO warehouses (name, code) VALUES (?, ?)');
    $statement->execute([trim($_POST['name']), strtoupper(trim($_POST['code']))]);
    $message = 'Warehouse added to the network.';
}
$warehouses = $database->query("SELECT w.*, COUNT(DISTINCT latest.product_id) AS products, COALESCE(SUM(latest.stock_after), 0) AS stock FROM warehouses w LEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.warehouse_id = w.id GROUP BY w.id ORDER BY w.name")->fetchAll();
pageStart('Warehouses', 'warehouses');
?><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Network map</p><h1 class="display-6 fw-semibold mb-2">Warehouses</h1><p class="text-secondary mb-0">See inventory concentration across every location.</p></div><button class="btn btn-dark" data-bs-toggle="collapse" data-bs-target="#add-warehouse">+ Add warehouse</button></div>
<?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><div class="collapse mb-4" id="add-warehouse"><form method="post" class="panel row g-3"><div class="col-md-8"><label class="form-label">Warehouse name</label><input class="form-control" name="name" required></div><div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" maxlength="30" required></div><div class="col-12"><button class="btn btn-success">Save warehouse</button></div></form></div><div class="row g-3"><?php foreach ($warehouses as $warehouse): ?><div class="col-md-6 col-xl-4"><div class="panel h-100"><div class="d-flex justify-content-between"><div><span class="eyebrow"><?= e($warehouse['code']) ?></span><h2 class="h5 mt-2 mb-1"><?= e($warehouse['name']) ?></h2></div><span class="brand-mark">↗</span></div><div class="row mt-3"><div class="col-6"><small class="text-secondary d-block">Products</small><strong><?= e($warehouse['products']) ?></strong></div><div class="col-6"><small class="text-secondary d-block">Units held</small><strong><?= e($warehouse['stock']) ?></strong></div></div></div></div><?php endforeach; ?></div><?php pageEnd(); ?>
