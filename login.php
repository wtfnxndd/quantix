<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $statement = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? AND active = 1 LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'The email or password is incorrect.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in | Quantix</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/app.css" rel="stylesheet"></head><body><main class="auth-shell"><section class="auth-panel"><div class="brand-lockup"><span class="brand-mark">Q</span><strong>Quantix</strong></div><p class="eyebrow mt-5 mb-2">Operations portal</p><h1 class="h2 mb-2">Welcome back.</h1><p class="text-secondary mb-4">Sign in to monitor stock movement.</p><?php if ($error): ?><div class="alert alert-danger border-0"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrfField() ?><div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control form-control-lg" id="email" type="email" name="email" autocomplete="username" required></div><div class="mb-4"><label class="form-label" for="password">Password</label><input class="form-control form-control-lg" id="password" type="password" name="password" autocomplete="current-password" required></div><button class="btn btn-dark btn-lg w-100">Sign in</button></form><p class="small text-secondary mt-4 mb-0">Use your assigned Quantix account.</p></section></main></body></html>