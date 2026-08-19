<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$sales = $database->query('SELECT s.*, c.name AS customer_name FROM sales_orders s JOIN customers c ON c.id = s.customer_id ORDER BY s.order_date DESC')->fetchAll();
$purchases = $database->query('SELECT p.*, v.name AS vendor_name FROM purchase_orders p JOIN vendors v ON v.id = p.vendor_id ORDER BY p.order_date DESC')->fetchAll();
pageStart('Orders', 'orders');
?><div class="mb-4"><p class="eyebrow mb-2">Order operations</p><h1 class="display-6 fw-semibold mb-2">Sales & purchases</h1><p class="text-secondary mb-0">Follow demand out and supply in from one place.</p></div><div class="row g-4"><section class="col-lg-6"><div class="panel"><h2 class="h5 mb-3">Sales orders</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Customer</th><th>Status</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach ($sales as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e($order['customer_name']) ?></td><td><span class="badge text-bg-light"><?= e($order['status']) ?></span></td><td class="text-end"><?= e(number_format((float) $order['total'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div></div></section><section class="col-lg-6"><div class="panel"><h2 class="h5 mb-3">Purchase orders</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Number</th><th>Vendor</th><th>Status</th><th class="text-end">Total</th></tr></thead><tbody><?php foreach ($purchases as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e($order['vendor_name']) ?></td><td><span class="badge text-bg-light"><?= e($order['status']) ?></span></td><td class="text-end"><?= e(number_format((float) $order['total'], 2)) ?></td></tr><?php endforeach; ?></tbody></table></div></div></section></div><?php pageEnd(); ?>
