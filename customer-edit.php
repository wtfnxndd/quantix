<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$customerId = (int) ($_GET['id'] ?? $_POST['customer_id'] ?? 0);
$statement = $database->prepare('SELECT * FROM customers WHERE id = ?');
$statement->execute([$customerId]);
$customer = $statement->fetch();
if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['action'] ?? '') === 'delete') {
            $check = $database->prepare('SELECT COUNT(*) FROM sales_orders WHERE customer_id = ?');
            $check->execute([$customerId]);
            if ((int) $check->fetchColumn() > 0) {
                throw new InvalidArgumentException('This customer has sales orders and cannot be deleted.');
            }
            $delete = $database->prepare('DELETE FROM customers WHERE id = ?');
            $delete->execute([$customerId]);
            header('Location: customers.php?deleted=1');
            exit;
        }
        $update = $database->prepare('UPDATE customers SET name = ?, code = ?, email = ?, phone = ?, address = ? WHERE id = ?');
        $update->execute([trim($_POST['name']), strtoupper(trim($_POST['code'])), trim($_POST['email']), trim($_POST['phone']), trim($_POST['address']), $customerId]);
        $message = 'Customer updated successfully.';
        $statement->execute([$customerId]);
        $customer = $statement->fetch();
    } catch (Throwable $exception) {
        $error = $exception instanceof InvalidArgumentException ? $exception->getMessage() : 'The customer could not be updated. Check that the code is unique.';
    }
}
pageStart('Edit customer', 'customers');
?><div class="mb-4"><p class="eyebrow mb-2">Customer management</p><h1 class="display-6 fw-semibold mb-2">Edit customer</h1><p class="text-secondary mb-0">Update customer contact details or remove an unused record.</p></div><?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif; ?><form method="post" class="panel row g-3 mb-3"><input type="hidden" name="customer_id" value="<?= e($customer['id']) ?>"><div class="col-md-4"><label class="form-label">Customer name</label><input class="form-control" name="name" value="<?= e($customer['name']) ?>" required></div><div class="col-md-2"><label class="form-label">Code</label><input class="form-control" name="code" value="<?= e($customer['code']) ?>" required></div><div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($customer['email']) ?>"></div><div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($customer['phone']) ?>"></div><div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= e($customer['address']) ?>"></div><div class="col-12 d-flex gap-2"><button class="btn btn-success">Save changes</button><a class="btn btn-light" href="customers.php">Cancel</a></div></form><form method="post" onsubmit="return confirm('Delete this customer?');"><input type="hidden" name="customer_id" value="<?= e($customer['id']) ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-outline-danger">Delete customer</button></form><?php pageEnd(); ?>
