<?php
require_once __DIR__ . '/lib.php';
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && $id !== (int)$me['id']) {
        if ($action === 'approve')  $pdo->prepare('UPDATE researchers SET status=\'approved\' WHERE id=?')->execute([$id]);
        if ($action === 'suspend')  $pdo->prepare('UPDATE researchers SET status=\'suspended\' WHERE id=?')->execute([$id]);
        if ($action === 'pending')  $pdo->prepare('UPDATE researchers SET status=\'pending\' WHERE id=?')->execute([$id]);
        flash(t('status_updated'), 'success');
    }
    header('Location: admin.php'); exit;
}

$rows = $pdo->query('SELECT r.*, p.title FROM researchers r LEFT JOIN profiles p ON p.researcher_id=r.id ORDER BY (r.status=\'pending\') DESC, r.created_at DESC')->fetchAll();
$page_title = t('admin');
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><i class="fas fa-user-shield"></i> <?= t('admin_title') ?></h1>
<p class="portal-sub"><?= t('admin_sub') ?></p>

<table class="admin-table">
  <thead><tr><th><?= t('col_name') ?></th><th><?= t('col_email') ?></th><th><?= t('col_role') ?></th><th><?= t('col_status') ?></th><th><?= t('col_actions') ?></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="/chercheur.php?slug=<?= e($r['slug']) ?>"><?= e($r['full_name']) ?></a><?php if ($r['title']): ?><br><small><?= e($r['title']) ?></small><?php endif; ?></td>
      <td><?= e($r['email']) ?></td>
      <td><?= e($r['role']) ?></td>
      <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td>
        <?php if ($r['id'] != $me['id']): ?>
        <form method="post" class="admin-actions">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <?php if ($r['status'] !== 'approved'): ?><button name="action" value="approve" class="btn btn-sm btn-primary"><?= t('approve') ?></button><?php endif; ?>
          <?php if ($r['status'] !== 'suspended'): ?><button name="action" value="suspend" class="btn btn-sm btn-outline-dark"><?= t('suspend') ?></button><?php endif; ?>
        </form>
        <?php else: ?><em><?= t('you') ?></em><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php require __DIR__ . '/footer.php'; ?>
