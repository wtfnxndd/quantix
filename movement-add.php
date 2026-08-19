<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$error = null;
$products = $database->query('SELECT id, name, sku FROM products ORDER BY name')->fetchAll();
$warehouses = $database->query('SELECT id, name, code FROM warehouses ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productId = (int) $_POST['product_id'];
        $warehouseId = (int) $_POST['warehouse_id'];
        $destinationId = (int) ($_POST['destination_id'] ?? 0);
        $type = $_POST['movement_type'];
        $quantity = (float) $_POST['quantity'];
        $quantity = $type === 'IN' || $type === 'TRANSFER' ? abs($quantity) : ($type === 'OUT' ? -abs($quantity) : $quantity);
        $reference = trim($_POST['reference']);
        if (!in_array($type, ['IN', 'OUT', 'TRANSFER', 'ADJUSTMENT'], true) || $quantity === 0 || $reference === '' || ($type === 'TRANSFER' && ($destinationId === $warehouseId || $destinationId === 0))) {
            throw new InvalidArgumentException('Choose a type, enter a non-zero quantity, and provide a reference.');
        }

        $latest = $database->prepare('SELECT stock_after FROM inventory_movements WHERE product_id = ? AND warehouse_id = ? ORDER BY movement_date DESC, id DESC LIMIT 1');
        $latest->execute([$productId, $warehouseId]);
        $currentStock = (float) ($latest->fetchColumn() ?: 0);
        $insert = $database->prepare('INSERT INTO inventory_movements (movement_number, product_id, warehouse_id, movement_type, quantity, stock_after, reference, movement_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $database->beginTransaction();
        if ($type === 'TRANSFER') {
            if ($currentStock < $quantity) {
                throw new InvalidArgumentException('The source warehouse does not have enough stock for this transfer.');
            }
            $latest->execute([$productId, $destinationId]);
            $destinationStock = (float) ($latest->fetchColumn() ?: 0);
            $movementNumber = 'MOV-' . date('ymdHis') . random_int(10, 99);
            $insert->execute([$movementNumber . '-OUT', $productId, $warehouseId, 'OUT', -$quantity, $currentStock - $quantity, $reference]);
            $insert->execute([$movementNumber . '-IN', $productId, $destinationId, 'IN', $quantity, $destinationStock + $quantity, $reference]);
        } else {
            if ($currentStock + $quantity < 0) {
                throw new InvalidArgumentException('This movement would make stock negative.');
            }
            $movementNumber = 'MOV-' . date('ymdHis') . random_int(10, 99);
            $insert->execute([$movementNumber, $productId, $warehouseId, $type, $quantity, $currentStock + $quantity, $reference]);
        }
        $database->commit();
        header('Location: movements.php?created=1');
        exit;
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        $error = $exception->getMessage();
    }
}

pageStart('Record movement', 'movements');
?><div class="mb-4"><p class="eyebrow mb-2">Inventory control</p><h1 class="display-6 fw-semibold mb-2">Record movement</h1><p class="text-secondary mb-0">Add a traceable event to the stock ledger.</p></div><?php if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif; ?><form method="post" class="panel row g-3"><div class="col-md-6"><label class="form-label">Product</label><select class="form-select" name="product_id" required><option value="">Choose product</option><?php foreach ($products as $product): ?><option value="<?= e($product['id']) ?>"><?= e($product['name']) ?> (<?= e($product['sku']) ?>)</option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Source warehouse</label><select class="form-select" name="warehouse_id" required><option value="">Choose warehouse</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= e($warehouse['id']) ?>"><?= e($warehouse['name']) ?> (<?= e($warehouse['code']) ?>)</option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Destination warehouse <span class="text-secondary">(transfers only)</span></label><select class="form-select" name="destination_id"><option value="">Not applicable</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= e($warehouse['id']) ?>"><?= e($warehouse['name']) ?> (<?= e($warehouse['code']) ?>)</option><?php endforeach; ?></select></div><div class="col-md-3"><label class="form-label">Movement type</label><select class="form-select" name="movement_type" required><option value="IN">Inbound</option><option value="OUT">Outbound</option><option value="TRANSFER">Transfer</option><option value="ADJUSTMENT">Adjustment</option></select></div><div class="col-md-3"><label class="form-label">Quantity</label><input class="form-control" type="number" name="quantity" step="0.01" min="0.01" required><div class="form-text">Adjustments may be negative.</div></div><div class="col-12"><label class="form-label">Reference</label><input class="form-control" name="reference" placeholder="PO-8040 or ADJ-043" required></div><div class="col-12 d-flex gap-2"><button class="btn btn-success">Save movement</button><a class="btn btn-light" href="movements.php">Cancel</a></div></form><?php pageEnd(); ?>
