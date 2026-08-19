<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    if ($name === '' || strlen($name) > 160 || !preg_match('/^[A-Z0-9-]{2,40}$/', $code) || ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160)) || strlen($phone) > 40 || strlen($address) > 255) {
        $error = 'Enter a valid customer name, code, email, phone, and address.';
    } else {
        try {
            $statement = $database->prepare('INSERT INTO customers (name, code, email, phone, address) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$name, $code, $email, $phone, $address]);
            $message = 'Customer added successfully.';
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'That customer code is already registered.' : 'The customer could not be saved.';
        }
    }
}
$search = trim($_GET['search'] ?? '');
$statement = $database->prepare('SELECT * FROM customers WHERE name LIKE ? OR code LIKE ? OR email LIKE ? ORDER BY name');
$pattern = '%' . $search . '%';
$statement->execute([$pattern, $pattern, $pattern]);
$customers = $statement->fetchAll();
pageStart('Customer management', 'customers');
if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif;
?><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Relationship management</p><h1 class="display-6 fw-semibold mb-2">Customers</h1><p class="text-secondary mb-0">Maintain the customers connected to your stock operations.</p></div><button class="btn btn-dark" data-bs-toggle="collapse" data-bs-target="#add-customer">+ Add customer</button></div><?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><div class="collapse mb-4" id="add-customer"><form method="post" class="panel row g-3"><div class="col-md-4"><label class="form-label">Customer name</label><input class="form-control" name="name" required></div><div class="col-md-2"><label class="form-label">Code</label><input class="form-control" name="code" placeholder="CUS-003" required></div><div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div><div class="col-md-3"><label class="form-label">Phone</label><input class="form-control" name="phone"></div><div class="col-12"><label class="form-label">Address</label><input class="form-control" name="address"></div><div class="col-12"><button class="btn btn-success">Save customer</button></div></form></div><div class="panel"><form class="d-flex gap-2 mb-3"><input class="form-control" name="search" value="<?= e($search) ?>" placeholder="Search customers by name, code, or email"><button class="btn btn-dark">Search</button></form><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Address</th></tr></thead><tbody><?php foreach ($customers as $customer): ?><tr><td><strong><?= e($customer['name']) ?></strong><small class="d-block text-secondary"><?= e($customer['code']) ?></small></td><td><?= e($customer['email']) ?></td><td><?= e($customer['phone']) ?></td><td><?= e($customer['address']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php pageEnd(); ?>
