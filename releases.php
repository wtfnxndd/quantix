<?php

declare(strict_types=1);
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$database = db();
$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $statement = $database->prepare('INSERT INTO releases (version, title, notes, status, release_date, created_by) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([trim($_POST['version']), trim($_POST['title']), trim($_POST['notes']), $_POST['status'], $_POST['release_date'] ?: null, currentUser()['id']]);
    $message = 'Release added to the roadmap.';
}
$releases = $database->query('SELECT r.*, u.name AS creator FROM releases r JOIN users u ON u.id = r.created_by ORDER BY r.created_at DESC')->fetchAll();
pageStart('Release management', 'releases');
?><div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"><div><p class="eyebrow mb-2">Delivery planning</p><h1 class="display-6 fw-semibold mb-2">Releases</h1><p class="text-secondary mb-0">Track Quantix versions, changes, and deployment status.</p></div><button class="btn btn-dark" data-bs-toggle="collapse" data-bs-target="#add-release">+ Add release</button></div><?php if ($message): ?><div class="alert alert-success border-0"><?= e($message) ?></div><?php endif; ?><div class="collapse mb-4" id="add-release"><form method="post" class="panel row g-3"><div class="col-md-2"><label class="form-label">Version</label><input class="form-control" name="version" placeholder="1.1.0" required></div><div class="col-md-5"><label class="form-label">Title</label><input class="form-control" name="title" required></div><div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status"><option>PLANNED</option><option>IN PROGRESS</option><option>RELEASED</option></select></div><div class="col-md-2"><label class="form-label">Release date</label><input class="form-control" type="date" name="release_date"></div><div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="3" required></textarea></div><div class="col-12"><button class="btn btn-success">Save release</button></div></form></div><div class="row g-3"><?php foreach ($releases as $release): ?><div class="col-lg-6"><article class="panel h-100"><div class="d-flex justify-content-between gap-3"><div><span class="eyebrow">Version <?= e($release['version']) ?></span><h2 class="h5 mt-2 mb-1"><?= e($release['title']) ?></h2></div><span class="badge text-bg-light"><?= e($release['status']) ?></span></div><p class="small text-secondary mt-3 mb-3"><?= nl2br(e($release['notes'])) ?></p><small class="text-secondary">By <?= e($release['creator']) ?> · <?= e($release['release_date'] ?: 'Date pending') ?></small></article></div><?php endforeach; ?></div><?php pageEnd(); ?>
