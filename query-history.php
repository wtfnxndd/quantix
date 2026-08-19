<?php

declare(strict_types=1);
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';

requireRole('admin');
$history = db()->query('SELECT q.*, u.name AS user_name FROM query_history q JOIN users u ON u.id = q.user_id ORDER BY q.executed_at DESC LIMIT 100')->fetchAll();
pageStart('Query history', 'query-history');
?><div class="mb-4"><p class="eyebrow mb-2">Administrator tools</p><h1 class="display-6 fw-semibold mb-2">Query history</h1><p class="text-secondary mb-0">Review SQL queries executed by administrators.</p></div><div class="panel"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Executed</th><th>User</th><th>Query</th><th>Status</th><th class="text-end">Rows</th><th>Message</th></tr></thead><tbody><?php foreach ($history as $item): ?><tr><td class="text-nowrap"><?= e(date('d M Y H:i', strtotime($item['executed_at']))) ?></td><td><?= e($item['user_name']) ?></td><td><code><?= e($item['query_text']) ?></code></td><td><span class="badge <?= $item['status'] === 'SUCCESS' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e($item['status']) ?></span></td><td class="text-end"><?= e($item['row_count']) ?></td><td><?= e($item['error_message']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php pageEnd(); ?>
