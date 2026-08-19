<?php

declare(strict_types=1);
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';

requireRole('admin');
$database = db();
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verifyCsrf();
	$name = trim($_POST['name'] ?? '');
	$email = strtolower(trim($_POST['email'] ?? ''));
	$password = $_POST['password'] ?? '';
	$role = $_POST['role'] ?? 'staff';
	if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || !in_array($role, ['admin', 'staff'], true)) {
		$error = 'Enter a name, valid email, password of at least 6 characters, and a valid role.';
	} else {
		try {
			$statement = $database->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
			$statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
			$message = 'User created successfully.';
		} catch (PDOException $exception) {
			$error = $exception->getCode() === '23000' ? 'That email address is already registered.' : 'The user could not be created.';
		}
	}
}
$users = $database->query('SELECT name, email, role, active, created_at FROM users ORDER BY name')->fetchAll();
pageStart('Users', 'users');
?><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Access control</p><h1 class="display-6 fw-semibold mb-2">Users</h1><p class="text-secondary mb-0">Review who can access the Quantix operations portal.</p></div><button class="btn btn-dark" data-bs-toggle="collapse" data-bs-target="#add-user">+ Add user</button></div><?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif; ?><div class="collapse mb-4" id="add-user"><form method="post" class="panel row g-3"><div class="col-md-3"><label class="form-label">Full name</label><input class="form-control" name="name" required></div><div class="col-md-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div><div class="col-md-3"><label class="form-label">Temporary password</label><input class="form-control" type="password" name="password" minlength="6" required></div><div class="col-md-3"><label class="form-label">Role</label><select class="form-select" name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div><div class="col-12"><button class="btn btn-success">Create user</button></div></form></div><div class="panel"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><strong><?= e($user['name']) ?></strong></td><td><?= e($user['email']) ?></td><td><span class="badge <?= $user['role'] === 'admin' ? 'text-bg-success' : 'text-bg-light' ?>"><?= e(ucfirst($user['role'])) ?></span></td><td><?= $user['active'] ? '<span class="text-success">Active</span>' : '<span class="text-danger">Disabled</span>' ?></td><td><?= e(date('d M Y', strtotime($user['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php pageEnd(); ?>