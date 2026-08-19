<?php

declare(strict_types=1);
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';

requireRole('admin');
$database = db();
$rows = [];
$error = null;
$queryExamples = [
    'Products and reorder levels' => "SELECT name, sku, category, reorder_level FROM products ORDER BY category, name;",
    'Stock by warehouse' => "SELECT w.name AS warehouse, p.name AS product, p.sku, latest.stock_after AS stock\nFROM inventory_movements latest\nJOIN products p ON p.id = latest.product_id\nJOIN warehouses w ON w.id = latest.warehouse_id\nWHERE NOT EXISTS (\n    SELECT 1 FROM inventory_movements newer\n    WHERE newer.product_id = latest.product_id\n      AND newer.warehouse_id = latest.warehouse_id\n      AND (newer.movement_date > latest.movement_date\n        OR (newer.movement_date = latest.movement_date AND newer.id > latest.id))\n)\nORDER BY w.name, p.name;",
    'Recent inventory activity' => "SELECT movement_number, movement_type, quantity, stock_after, reference, movement_date\nFROM inventory_movements\nORDER BY movement_date DESC\nLIMIT 10;",
    'Movement totals by type' => "SELECT movement_type, COUNT(*) AS events, SUM(ABS(quantity)) AS units\nFROM inventory_movements\nGROUP BY movement_type\nORDER BY units DESC;",
    'Customer order summary' => "SELECT c.name AS customer, COUNT(o.id) AS orders, SUM(o.total) AS order_value\nFROM customers c\nLEFT JOIN sales_orders o ON o.customer_id = c.id\nGROUP BY c.id, c.name\nORDER BY order_value DESC;",
    'Low-stock watchlist' => "SELECT p.name, p.sku, p.reorder_level, COALESCE(SUM(latest.stock_after), 0) AS stock\nFROM products p\nLEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m\n    WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id\n    AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date\n    OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.product_id = p.id\nGROUP BY p.id, p.name, p.sku, p.reorder_level\nHAVING stock <= p.reorder_level\nORDER BY stock ASC;",
    'Warehouse stock totals' => "SELECT w.name AS warehouse, COUNT(DISTINCT latest.product_id) AS products, COALESCE(SUM(latest.stock_after), 0) AS units\nFROM warehouses w\nLEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m\n    WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id\n    AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date\n    OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.warehouse_id = w.id\nGROUP BY w.id, w.name\nORDER BY units DESC;",
    'Vendor purchase summary' => "SELECT v.name AS vendor, COUNT(o.id) AS purchase_orders, COALESCE(SUM(o.total), 0) AS order_value\nFROM vendors v\nLEFT JOIN purchase_orders o ON o.vendor_id = v.id\nGROUP BY v.id, v.name\nORDER BY order_value DESC;",
    'Sales orders by status' => "SELECT status, COUNT(*) AS orders, SUM(total) AS order_value\nFROM sales_orders\nGROUP BY status\nORDER BY orders DESC;",
    'Purchase orders by status' => "SELECT status, COUNT(*) AS orders, SUM(total) AS order_value\nFROM purchase_orders\nGROUP BY status\nORDER BY orders DESC;",
    'Products with no movement' => "SELECT p.name, p.sku, p.category\nFROM products p\nLEFT JOIN inventory_movements m ON m.product_id = p.id\nWHERE m.id IS NULL\nORDER BY p.name;",
    'Category stock totals' => "SELECT p.category, COUNT(DISTINCT p.id) AS products, COALESCE(SUM(latest.stock_after), 0) AS units\nFROM products p\nLEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m\n    WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id\n    AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date\n    OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.product_id = p.id\nGROUP BY p.category\nORDER BY units DESC;",
    'Release roadmap' => "SELECT version, title, status, release_date, created_at\nFROM releases\nORDER BY release_date IS NULL, release_date, version;",
    'Recent admin query history' => "SELECT q.executed_at, u.name AS administrator, q.status, q.row_count, q.query_text\nFROM query_history q\nJOIN users u ON u.id = q.user_id\nORDER BY q.executed_at DESC\nLIMIT 20;",
];
$query = trim($_POST['query'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $query !== '') {
    verifyCsrf();
    $normalized = strtoupper(ltrim($query));
    $status = 'SUCCESS';
    $rowCount = 0;
    $errorMessage = null;
    if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/', $normalized)) {
        $status = 'BLOCKED';
        $errorMessage = 'Only read-only queries are allowed in the SQL Console.';
    } else {
        try {
            $rows = $database->query($query)->fetchAll();
            $rowCount = count($rows);
        } catch (Throwable $exception) {
            $status = 'ERROR';
            $errorMessage = $exception->getMessage();
        }
    }
    $history = $database->prepare('INSERT INTO query_history (user_id, query_text, status, row_count, error_message) VALUES (?, ?, ?, ?, ?)');
    $history->execute([currentUser()['id'], $query, $status, $rowCount, $errorMessage]);
    $error = $errorMessage;
}
pageStart('SQL console', 'sql-console');
?>
<div class="mb-4"><p class="eyebrow mb-2">Administrator tools</p><h1 class="display-6 fw-semibold mb-2">SQL console</h1><p class="text-secondary mb-0">Run read-only queries against the Quantix database.</p></div>
<div class="alert alert-warning border-0 small">For safety, data-changing statements such as INSERT, UPDATE, DELETE, DROP, and ALTER are blocked.</div>
<form method="post" class="panel mb-4">
    <label class="form-label" for="query">SQL query</label>
    <textarea class="form-control font-monospace" id="query" name="query" rows="5" placeholder="SELECT * FROM products LIMIT 20"><?= e($query) ?></textarea>
    <div class="mt-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-2">
            <small class="text-secondary">Allowed: SELECT, SHOW, DESCRIBE, EXPLAIN</small>
            <button class="btn btn-dark" type="submit">Execute query</button>
        </div>
        <div class="d-flex flex-wrap gap-2" aria-label="SQL query examples">
            <?php foreach ($queryExamples as $label => $example): ?>
                <button class="btn btn-outline-secondary btn-sm query-example" type="button" data-query="<?= e($example) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</form>
<?php if ($error): ?><div class="alert <?= str_starts_with((string) $error, 'Only') ? 'alert-warning' : 'alert-danger' ?> border-0"><?= e($error) ?></div><?php endif; ?>
<?php if ($rows): ?><div class="panel"><h2 class="h5 mb-3"><?= e(count($rows)) ?> result rows</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach (array_keys($rows[0]) as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
<script>document.querySelectorAll('.query-example').forEach((button) => button.addEventListener('click', () => { const query = document.querySelector('#query'); query.value = button.dataset.query ?? ''; query.form.requestSubmit(); }));</script>
<?php pageEnd(); ?>
