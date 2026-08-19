<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireAuth();

$error = null;
$summary = ['products' => 0, 'warehouses' => 0, 'movement_count' => 0, 'stock' => 0];
$movements = [];
$lowStock = [];
$stockByCategory = [];
$movementTrend = [];
$trendDays = in_array((int) ($_GET['days'] ?? 7), [7, 30, 90], true) ? (int) $_GET['days'] : 7;

try {
    $database = db();
    $summary = $database->query(
        "SELECT
            (SELECT COUNT(*) FROM products) AS products,
            (SELECT COUNT(*) FROM warehouses) AS warehouses,
            (SELECT COUNT(*) FROM inventory_movements) AS movement_count,
            COALESCE((SELECT SUM(latest.stock_after) FROM inventory_movements latest
                WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer
                    WHERE newer.product_id = latest.product_id AND newer.warehouse_id = latest.warehouse_id
                    AND (newer.movement_date > latest.movement_date OR (newer.movement_date = latest.movement_date AND newer.id > latest.id)))), 0) AS stock"
    )->fetch();

    $movements = $database->query(
        "SELECT m.movement_number, m.movement_type, m.quantity, m.stock_after, m.reference,
                m.movement_date, p.name AS product_name, p.sku, w.name AS warehouse_name
         FROM inventory_movements m
         INNER JOIN products p ON p.id = m.product_id
         INNER JOIN warehouses w ON w.id = m.warehouse_id
         ORDER BY m.movement_date DESC LIMIT 25"
    )->fetchAll();

    $lowStock = $database->query(
        "SELECT p.name, p.sku, p.reorder_level, COALESCE(SUM(latest.stock_after), 0) AS stock
         FROM products p
         LEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m
             WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id
             AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date
             OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.product_id = p.id
         GROUP BY p.id, p.name, p.sku, p.reorder_level
         HAVING stock <= p.reorder_level
         ORDER BY stock ASC"
    )->fetchAll();

    $stockByCategory = $database->query(
        "SELECT p.category, COALESCE(SUM(latest.stock_after), 0) AS stock
         FROM products p
         LEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m
             WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id
             AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date
             OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.product_id = p.id
         GROUP BY p.category ORDER BY stock DESC"
    )->fetchAll();

    $movementTrend = $database->query(
        "SELECT DATE(movement_date) AS movement_day,
                SUM(CASE WHEN movement_type = 'IN' THEN ABS(quantity) ELSE 0 END) AS inbound,
                SUM(CASE WHEN movement_type = 'OUT' THEN ABS(quantity) ELSE 0 END) AS outbound
         FROM inventory_movements
         WHERE movement_date >= CURRENT_DATE - INTERVAL " . ($trendDays - 1) . " DAY
         GROUP BY DATE(movement_date) ORDER BY movement_day"
    )->fetchAll();
} catch (Throwable $exception) {
    $error = 'Connect to MySQL and import schema.sql to load live inventory data.';
}

function movementClass(string $type): string
{
    return match ($type) {
        'IN' => 'text-success',
        'OUT' => 'text-danger',
        'TRANSFER' => 'text-primary',
        default => 'text-warning',
    };
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quantix | Stock movement intelligence</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/app.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg border-bottom bg-white">
    <div class="container py-2"><a class="navbar-brand fw-bold me-4" href="index.php"><span class="brand-mark">Q</span> Quantix</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="main-nav"><div class="navbar-nav gap-1"><a class="nav-link active fw-semibold" href="index.php">Dashboard</a><a class="nav-link" href="stock-registration.php">Register stock</a><a class="nav-link" href="stock-management.php">Stock records</a><a class="nav-link" href="customers.php">Customers</a><a class="nav-link" href="releases.php">Releases</a><a class="nav-link" href="search.php">Search</a><?php if (currentUser()['role'] === 'admin'): ?><a class="nav-link" href="sql-console.php">SQL console</a><a class="nav-link" href="query-history.php">Query history</a><?php endif; ?></div><div class="user-menu ms-auto"><span class="navbar-text small"><strong><?= e(currentUser()['name']) ?></strong> · <?= e(ucfirst(currentUser()['role'])) ?></span><a class="nav-link d-inline-block" href="logout.php">Sign out</a></div></div>
    </div>
</nav>
<main class="container py-4 py-lg-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Operations overview</p>
            <h1 class="display-6 fw-semibold mb-2">Inventory, in motion.</h1>
            <p class="text-secondary mb-0">A clear pulse on what is moving through your warehouses today.</p>
        </div>
        <div class="text-lg-end text-secondary small">Updated <?= e(date('d M Y, H:i')) ?></div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-warning border-0 shadow-sm" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="row g-3 mb-4">
        <?php foreach ([
            ['Products tracked', $summary['products'], 'catalog'],
            ['Warehouses', $summary['warehouses'], 'locations'],
            ['Movement events', $summary['movement_count'], 'events'],
            ['Units in stock', number_format((float) $summary['stock']), 'stock'],
        ] as [$label, $value, $key]): ?>
            <div class="col-6 col-xl-3"><div class="metric h-100"><span class="metric-label"><?= e($label) ?></span><strong><?= e($value) ?></strong><span class="metric-dot <?= e($key) ?>"></span></div></div>
        <?php endforeach; ?>
    </section>

    <section class="quick-actions mb-4" aria-label="Quick actions">
        <a class="quick-action" href="stock-registration.php"><span class="action-symbol">+</span><span><strong>Register stock</strong><small>Add a new item</small></span></a>
        <a class="quick-action" href="movement-add.php"><span class="action-symbol">↗</span><span><strong>Record movement</strong><small>Update stock levels</small></span></a>
        <a class="quick-action" href="customers.php"><span class="action-symbol">◎</span><span><strong>Add customer</strong><small>Manage relationships</small></span></a>
        <a class="quick-action action-accent" href="search.php"><span class="action-symbol">⌕</span><span><strong>Search inventory</strong><small>Find anything fast</small></span></a>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-lg-7"><div class="panel chart-panel"><div class="panel-heading"><div><p class="eyebrow mb-1">Current balance</p><h2 class="h5 mb-0">Stock by category</h2></div><span class="chart-note">Live data</span></div><div class="chart-wrap"><canvas id="stock-category-chart" aria-label="Stock by category chart"></canvas></div></div></div>
        <div class="col-lg-5"><div class="panel chart-panel"><div class="panel-heading"><div><p class="eyebrow mb-1">Last <?= e($trendDays) ?> days</p><h2 class="h5 mb-0">Movement activity</h2></div><form method="get"><label class="visually-hidden" for="dashboard-days">Activity period</label><select class="form-select form-select-sm" id="dashboard-days" name="days" onchange="this.form.submit()"><option value="7" <?= $trendDays === 7 ? 'selected' : '' ?>>7 days</option><option value="30" <?= $trendDays === 30 ? 'selected' : '' ?>>30 days</option><option value="90" <?= $trendDays === 90 ? 'selected' : '' ?>>90 days</option></select></form></div><div class="chart-wrap"><canvas id="movement-trend-chart" aria-label="Movement activity chart"></canvas></div></div></div>
    </section>

    <div class="row g-4">
        <section class="col-xl-8">
            <div class="panel h-100">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <div><h2 class="h5 mb-1">Recent movement</h2><p class="small text-secondary mb-0">The latest signals from your inventory network.</p></div><a class="btn btn-sm btn-outline-secondary" href="movements.php">View full ledger</a>
                    <select id="movement-filter" class="form-select form-select-sm filter-select" aria-label="Filter movement type">
                        <option value="ALL">All movement</option><option value="IN">Inbound</option><option value="OUT">Outbound</option><option value="TRANSFER">Transfers</option><option value="ADJUSTMENT">Adjustments</option>
                    </select>
                </div>
                <div class="table-responsive"><table class="table align-middle" id="movement-table"><thead><tr><th>Movement</th><th>Product</th><th>Warehouse</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Stock</th></tr></thead><tbody>
                <?php foreach ($movements as $movement): ?>
                    <tr data-type="<?= e($movement['movement_type']) ?>"><td><strong><?= e($movement['movement_number']) ?></strong><small class="d-block text-secondary"><?= e($movement['reference']) ?></small></td><td><?= e($movement['product_name']) ?><small class="d-block text-secondary"><?= e($movement['sku']) ?></small></td><td><?= e($movement['warehouse_name']) ?></td><td><span class="fw-semibold <?= e(movementClass($movement['movement_type'])) ?>"><?= e($movement['movement_type']) ?></span></td><td class="text-end <?= e(movementClass($movement['movement_type'])) ?>"><?= $movement['quantity'] > 0 ? '+' : '' ?><?= e($movement['quantity']) ?></td><td class="text-end fw-semibold"><?= e($movement['stock_after']) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            </div>
        </section>
        <aside class="col-xl-4"><div class="panel h-100"><div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Needs attention</h2><p class="small text-secondary mb-0">Reorder threshold watchlist.</p></div><span class="badge rounded-pill text-bg-light"><?= count($lowStock) ?></span></div>
            <?php if ($lowStock): foreach ($lowStock as $item): ?><div class="stock-item"><div><strong><?= e($item['name']) ?></strong><small class="d-block text-secondary"><?= e($item['sku']) ?></small></div><div class="text-end"><strong class="text-danger"><?= e($item['stock']) ?></strong><small class="d-block text-secondary">min <?= e($item['reorder_level']) ?></small></div></div><?php endforeach; else: ?><div class="empty-state">All tracked products are above their reorder level.</div><?php endif; ?>
        </div></aside>
    </div>
</main>
<script src="assets/app.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const chartFont = { family: 'Manrope', size: 11 };
const categoryData = <?= json_encode($stockByCategory, JSON_THROW_ON_ERROR) ?>;
const trendData = <?= json_encode($movementTrend, JSON_THROW_ON_ERROR) ?>;
new Chart(document.querySelector('#stock-category-chart'), {
    type: 'bar',
    data: { labels: categoryData.map((item) => item.category), datasets: [{ data: categoryData.map((item) => item.stock), backgroundColor: ['#176b4d', '#ef795c', '#5e9fbd', '#b28a3d', '#8b78a9'], borderRadius: 5, borderSkipped: false }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { font: chartFont } }, y: { beginAtZero: true, grid: { color: '#e5ebe6' }, ticks: { font: chartFont } } } }
});
new Chart(document.querySelector('#movement-trend-chart'), {
    type: 'line',
    data: { labels: trendData.map((item) => item.movement_day), datasets: [{ label: 'Inbound', data: trendData.map((item) => item.inbound), borderColor: '#176b4d', backgroundColor: 'rgba(23,107,77,.1)', fill: true, tension: .35 }, { label: 'Outbound', data: trendData.map((item) => item.outbound), borderColor: '#ef795c', backgroundColor: 'transparent', tension: .35 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, font: chartFont } } }, scales: { x: { grid: { display: false }, ticks: { font: chartFont } }, y: { beginAtZero: true, grid: { color: '#e5ebe6' }, ticks: { font: chartFont } } } }
});
</script>
</body>
</html>
