<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/metrics.php';
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'refresh_all_metrics') {
        $list = $pdo->query('SELECT researcher_id, orcid FROM profiles WHERE orcid IS NOT NULL AND orcid <> \'\' AND (metrics_manual = 0 OR metrics_manual IS NULL)')->fetchAll();
        $ok = 0;
        foreach ($list as $row) {
            try { if (refresh_metrics_for($pdo, (int)$row['researcher_id'], $row['orcid'], false)) $ok++; } catch (Throwable $e) {}
        }
        flash(sprintf(t('metrics_all_done'), $ok, count($list)), 'success');
        header('Location: admin.php'); exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id && $id !== (int)$me['id']) {
        if ($action === 'approve')  $pdo->prepare('UPDATE researchers SET status=\'approved\' WHERE id=?')->execute([$id]);
        if ($action === 'suspend')  $pdo->prepare('UPDATE researchers SET status=\'suspended\' WHERE id=?')->execute([$id]);
        if ($action === 'pending')  $pdo->prepare('UPDATE researchers SET status=\'pending\' WHERE id=?')->execute([$id]);
        flash(t('status_updated'), 'success');
    }
    header('Location: admin.php'); exit;
}

$rows = $pdo->query('SELECT r.*, p.title, p.orcid, p.citations, p.h_index, p.i10_index FROM researchers r LEFT JOIN profiles p ON p.researcher_id=r.id ORDER BY (r.status=\'pending\') DESC, r.created_at DESC')->fetchAll();
$page_title = t('admin');
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><i class="fas fa-user-shield"></i> <?= t('admin_title') ?></h1>
<p class="portal-sub"><?= t('admin_sub') ?></p>

<div class="admin-toolbar">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="refresh_all_metrics">
    <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-rotate"></i> <?= t('metrics_refresh_all') ?></button>
  </form>
  <span class="admin-toolbar-help"><?= t('metrics_refresh_all_help') ?></span>
</div>

<table class="admin-table">
  <thead><tr><th><?= t('col_name') ?></th><th><?= t('col_email') ?></th><th><?= t('col_status') ?></th><th title="Citations / indice h / i10">Cit. / h / i10</th><th><?= t('col_actions') ?></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="/chercheur.php?slug=<?= e($r['slug']) ?>" target="_blank"><?= e($r['full_name']) ?></a><?php if ($r['title']): ?><br><small><?= e($r['title']) ?></small><?php endif; ?></td>
      <td><?= e($r['email']) ?><?php if (!empty($r['orcid'])): ?><br><small><i class="fab fa-orcid"></i> <?= e($r['orcid']) ?></small><?php endif; ?></td>
      <td><span class="status status-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td class="admin-metrics"><?php if (isset($r['citations']) || isset($r['h_index']) || isset($r['i10_index'])): ?><?= (int)($r['citations']??0) ?> / <?= (int)($r['h_index']??0) ?> / <?= (int)($r['i10_index']??0) ?><?php else: ?>—<?php endif; ?></td>
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
