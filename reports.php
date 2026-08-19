<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$byType = $database->query('SELECT movement_type, COUNT(*) AS events, COALESCE(SUM(quantity), 0) AS quantity FROM inventory_movements GROUP BY movement_type ORDER BY events DESC')->fetchAll();
$byWarehouse = $database->query('SELECT w.name, COALESCE(SUM(latest.stock_after), 0) AS stock FROM warehouses w LEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.warehouse_id = w.id GROUP BY w.id, w.name ORDER BY stock DESC')->fetchAll();
pageStart('Reports', 'reports');
?><div class="mb-4"><p class="eyebrow mb-2">Business intelligence</p><h1 class="display-6 fw-semibold mb-2">Reports</h1><p class="text-secondary mb-0">Understand movement volume and inventory distribution.</p></div><div class="row g-4"><section class="col-lg-6"><div class="panel"><h2 class="h5 mb-3">Movement by type</h2><?php foreach ($byType as $item): ?><div class="stock-item"><strong><?= e($item['movement_type']) ?></strong><span class="text-secondary"><?= e($item['events']) ?> events · <?= e($item['quantity']) ?> units</span></div><?php endforeach; ?></div></section><section class="col-lg-6"><div class="panel"><h2 class="h5 mb-3">Stock by warehouse</h2><?php foreach ($byWarehouse as $item): ?><div class="stock-item"><strong><?= e($item['name']) ?></strong><strong><?= e($item['stock']) ?> units</strong></div><?php endforeach; ?></div></section></div><?php pageEnd(); ?>
