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
];
$query = trim($_POST['query'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $query !== '') {
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
?><div class="mb-4"><p class="eyebrow mb-2">Administrator tools</p><h1 class="display-6 fw-semibold mb-2">SQL console</h1><p class="text-secondary mb-0">Run read-only queries against the Quantix database.</p></div><div class="alert alert-warning border-0 small">For safety, data-changing statements such as INSERT, UPDATE, DELETE, DROP, and ALTER are blocked.</div><form method="post" class="panel mb-4"><label class="form-label" for="query">SQL query</label><textarea class="form-control font-monospace" id="query" name="query" rows="5" placeholder="SELECT * FROM products LIMIT 20"><?= e($query) ?></textarea><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3"><small class="text-secondary">Allowed: SELECT, SHOW, DESCRIBE, EXPLAIN</small><div class="d-flex gap-2"><select class="form-select form-select-sm" id="query-example" aria-label="Choose a query example"><option value="">Choose example</option><?php foreach ($queryExamples as $label => $example): ?><option value="<?= e($example) ?>"><?= e($label) ?></option><?php endforeach; ?></select><button class="btn btn-outline-secondary btn-sm" id="load-query-example" type="button">Load</button><button class="btn btn-dark" type="submit">Execute query</button></div></div></form><?php if ($error): ?><div class="alert <?= str_starts_with((string) $error, 'Only') ? 'alert-warning' : 'alert-danger' ?> border-0"><?= e($error) ?></div><?php endif; ?><?php if ($rows): ?><div class="panel"><h2 class="h5 mb-3"><?= e(count($rows)) ?> result rows</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach (array_keys($rows[0]) as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?><script>document.querySelector('#load-query-example')?.addEventListener('click', () => { const example = document.querySelector('#query-example'); if (example.value) document.querySelector('#query').value = example.value; });</script><?php pageEnd(); ?>
