<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'rename') {
        $warehouseId = filter_var($_POST['warehouse_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($warehouseId === false || $name === '' || strlen($name) > 120) {
            $error = 'Enter a valid warehouse name.';
        } else {
            $statement = $database->prepare('UPDATE warehouses SET name = ? WHERE id = ?');
            $statement->execute([$name, $warehouseId]);
            $message = 'Warehouse renamed successfully.';
        }
    } else {
    $name = trim((string) ($_POST['name'] ?? ''));
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    if ($name === '' || strlen($name) > 120 || !preg_match('/^[A-Z0-9-]{2,30}$/', $code)) {
        $error = 'Enter a warehouse name and a valid code using letters, numbers, or hyphens.';
    } else {
        try {
            $statement = $database->prepare('INSERT INTO warehouses (name, code) VALUES (?, ?)');
            $statement->execute([$name, $code]);
            $message = 'Warehouse added to the network.';
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'That warehouse code is already registered.' : 'The warehouse could not be saved.';
        }
    }
    }
}
$warehouses = $database->query("SELECT w.*, COUNT(DISTINCT latest.product_id) AS products, COALESCE(SUM(latest.stock_after), 0) AS stock FROM warehouses w LEFT JOIN (SELECT m.product_id, m.warehouse_id, m.stock_after FROM inventory_movements m WHERE NOT EXISTS (SELECT 1 FROM inventory_movements newer WHERE newer.product_id = m.product_id AND newer.warehouse_id = m.warehouse_id AND (newer.movement_date > m.movement_date OR (newer.movement_date = m.movement_date AND newer.id > m.id)))) latest ON latest.warehouse_id = w.id GROUP BY w.id ORDER BY w.name")->fetchAll();
pageStart('Warehouses', 'warehouses');
?>
<button class="btn btn-outline-secondary btn-sm mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#rename-warehouse">Rename an existing warehouse</button>
<div class="collapse mb-4" id="rename-warehouse"><form method="post" class="panel row g-3"><input type="hidden" name="action" value="rename"><div class="col-md-5"><label class="form-label">Warehouse</label><select class="form-select" name="warehouse_id" required><?php foreach ($warehouses as $warehouse): ?><option value="<?= e($warehouse['id']) ?>"><?= e($warehouse['name']) ?> (<?= e($warehouse['code']) ?>)</option><?php endforeach; ?></select></div><div class="col-md-5"><label class="form-label">New name</label><input class="form-control" name="name" maxlength="120" required></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-success w-100" type="submit">Rename</button></div></form></div>
<?php if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Network map</p><h1 class="display-6 fw-semibold mb-2">Warehouses</h1><p class="text-secondary mb-0">See inventory concentration across every location.</p></div><button class="btn btn-dark" data-bs-toggle="collapse" data-bs-target="#add-warehouse">+ Add warehouse</button></div>
<?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><div class="collapse mb-4" id="add-warehouse"><form method="post" class="panel row g-3"><div class="col-md-8"><label class="form-label">Warehouse name</label><input class="form-control" name="name" required></div><div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" maxlength="30" required></div><div class="col-12"><button class="btn btn-success">Save warehouse</button></div></form></div><div class="row g-3"><?php foreach ($warehouses as $warehouse): ?><div class="col-md-6 col-xl-4"><div class="panel h-100"><div class="d-flex justify-content-between"><div><span class="eyebrow"><?= e($warehouse['code']) ?></span><h2 class="h5 mt-2 mb-1"><?= e($warehouse['name']) ?></h2></div><span class="brand-mark">↗</span></div><div class="row mt-3"><div class="col-6"><small class="text-secondary d-block">Products</small><strong><?= e($warehouse['products']) ?></strong></div><div class="col-6"><small class="text-secondary d-block">Units held</small><strong><?= e($warehouse['stock']) ?></strong></div></div></div></div><?php endforeach; ?></div><?php pageEnd(); ?>
