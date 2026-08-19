<?php

declare(strict_types=1);
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';

requireRole('admin');
$database = db();
$rows = [];
$error = null;
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
?><div class="mb-4"><p class="eyebrow mb-2">Administrator tools</p><h1 class="display-6 fw-semibold mb-2">SQL console</h1><p class="text-secondary mb-0">Run read-only queries against the Quantix database.</p></div><div class="alert alert-warning border-0 small">For safety, data-changing statements such as INSERT, UPDATE, DELETE, DROP, and ALTER are blocked.</div><form method="post" class="panel mb-4"><label class="form-label" for="query">SQL query</label><textarea class="form-control font-monospace" id="query" name="query" rows="5" placeholder="SELECT * FROM products LIMIT 20"><?= e($query) ?></textarea><div class="d-flex justify-content-between align-items-center mt-3"><small class="text-secondary">Allowed: SELECT, SHOW, DESCRIBE, EXPLAIN</small><button class="btn btn-dark">Execute query</button></div></form><?php if ($error): ?><div class="alert <?= str_starts_with((string) $error, 'Only') ? 'alert-warning' : 'alert-danger' ?> border-0"><?= e($error) ?></div><?php endif; ?><?php if ($rows): ?><div class="panel"><h2 class="h5 mb-3"><?= e(count($rows)) ?> result rows</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><?php foreach (array_keys($rows[0]) as $column): ?><th><?= e($column) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><?php foreach ($row as $value): ?><td><?= e($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?><?php pageEnd(); ?>
