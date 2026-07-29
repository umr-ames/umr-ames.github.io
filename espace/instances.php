<?php
/* Gestion des instances de gouvernance — réservé à l'administration.
   Permet de modifier la composition et de publier ou masquer la rubrique. */
require_once __DIR__ . '/lib.php';
$me = require_admin();
$pdo = db();

$blocs = instance_blocs();
$needMigrate = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_public') {
        set_setting('instances_public', isset($_POST['instances_public']) ? '1' : '0');
        flash(t('status_updated'), 'success');
        header('Location: instances.php'); exit;
    }

    if ($action === 'add') {
        $bloc = $_POST['bloc'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $note = isset($_POST['is_note']) ? 1 : 0;
        if (isset($blocs[$bloc]) && $name !== '') {
            $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM instance_members WHERE bloc = ?');
            $max->execute([$bloc]);
            $pdo->prepare('INSERT INTO instance_members (bloc, role, name, is_note, sort_order) VALUES (?,?,?,?,?)')
                ->execute([$bloc, $note ? null : (mb_substr($role, 0, 80) ?: null), mb_substr($name, 0, 190), $note, (int)$max->fetchColumn() + 10]);
            flash(t('inst_added'), 'success');
        }
        header('Location: instances.php#' . e($bloc)); exit;
    }

    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $note = isset($_POST['is_note']) ? 1 : 0;
        if ($id && $name !== '') {
            $pdo->prepare('UPDATE instance_members SET role = ?, name = ?, is_note = ? WHERE id = ?')
                ->execute([$note ? null : (mb_substr($role, 0, 80) ?: null), mb_substr($name, 0, 190), $note, $id]);
            flash(t('inst_saved'), 'success');
        }
        header('Location: instances.php'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM instance_members WHERE id = ?')->execute([$id]);
            flash(t('inst_deleted'), 'success');
        }
        header('Location: instances.php'); exit;
    }

    /* Déplacement : on échange le rang avec la ligne voisine du même bloc */
    if ($action === 'move') {
        $id  = (int)($_POST['id'] ?? 0);
        $dir = ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
        $st = $pdo->prepare('SELECT id, bloc, sort_order FROM instance_members WHERE id = ?');
        $st->execute([$id]);
        if ($cur = $st->fetch()) {
            $sql = $dir === 'up'
                ? 'SELECT id, sort_order FROM instance_members WHERE bloc = ? AND sort_order < ? ORDER BY sort_order DESC LIMIT 1'
                : 'SELECT id, sort_order FROM instance_members WHERE bloc = ? AND sort_order > ? ORDER BY sort_order ASC LIMIT 1';
            $nb = $pdo->prepare($sql);
            $nb->execute([$cur['bloc'], $cur['sort_order']]);
            if ($other = $nb->fetch()) {
                $upd = $pdo->prepare('UPDATE instance_members SET sort_order = ? WHERE id = ?');
                $upd->execute([$other['sort_order'], $cur['id']]);
                $upd->execute([$cur['sort_order'], $other['id']]);
            }
        }
        header('Location: instances.php'); exit;
    }
}

try {
    $rows = $pdo->query('SELECT * FROM instance_members ORDER BY bloc, sort_order, id')->fetchAll();
} catch (PDOException $ex) {
    $needMigrate = true; $rows = [];
}
$isPublic = instances_public();

$page_title = t('inst_title');
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><i class="fas fa-landmark"></i> <?= t('inst_title') ?></h1>
<p class="portal-sub"><?= t('inst_sub') ?></p>

<?php if ($needMigrate): ?>
  <div class="flash flash-error">
    La base doit d'abord être mise à jour.
    <a href="migrate.php"><strong>Cliquez ici pour lancer la mise à jour</strong></a>, puis revenez sur cette page.
  </div>
<?php require __DIR__ . '/footer.php'; exit; endif; ?>

<div class="admin-toolbar">
  <form method="post" id="pubForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="toggle_public">
    <label class="toggle-line" style="margin:0">
      <input type="checkbox" name="instances_public" value="1" <?= $isPublic ? 'checked' : '' ?>
             onchange="document.getElementById('pubForm').submit()">
      <?= t('inst_public_label') ?>
    </label>
  </form>
  <span class="admin-toolbar-help">
    <?php if ($isPublic): ?>
      <span style="color:#0e7c6f"><i class="fas fa-eye"></i> <?= t('inst_state_public') ?></span>
    <?php else: ?>
      <span style="color:#b45309"><i class="fas fa-eye-slash"></i> <?= t('inst_state_hidden') ?></span>
    <?php endif; ?>
  </span>
</div>

<?php foreach ($blocs as $key => $meta):
  $items = array_values(array_filter($rows, fn($r) => $r['bloc'] === $key)); ?>
  <h2 class="portal-h2" id="<?= e($key) ?>" style="margin-top:30px">
    <i class="fas <?= e($meta['icon']) ?>"></i> <?= e($meta['label']) ?>
    <span class="muted" style="font-weight:400">(<?= count($items) ?>)</span>
  </h2>

  <table class="admin-table">
    <thead><tr>
      <th style="width:150px"><?= t('inst_role') ?></th>
      <th><?= t('col_name') ?></th>
      <th style="width:190px"><?= t('col_actions') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($items as $i => $it): ?>
      <tr>
        <td><?= $it['is_note'] ? '<small class="muted">' . t('inst_note') . '</small>' : e($it['role'] ?: '—') ?></td>
        <td><?= e($it['name']) ?></td>
        <td>
          <div class="admin-actions" style="gap:4px">
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="move">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button name="dir" value="up" class="btn btn-sm btn-outline-dark" title="<?= t('inst_up') ?>" <?= $i === 0 ? 'disabled' : '' ?>><i class="fas fa-arrow-up"></i></button>
              <button name="dir" value="down" class="btn btn-sm btn-outline-dark" title="<?= t('inst_down') ?>" <?= $i === count($items) - 1 ? 'disabled' : '' ?>><i class="fas fa-arrow-down"></i></button>
            </form>
            <button class="btn btn-sm btn-dark" type="button" onclick="document.getElementById('ed<?= (int)$it['id'] ?>').hidden = !document.getElementById('ed<?= (int)$it['id'] ?>').hidden"><i class="fas fa-pen"></i></button>
            <form method="post" style="display:inline" onsubmit="return confirm('<?= e(t('confirm_del')) ?>');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn btn-sm btn-outline-dark" title="<?= t('delete') ?>"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </td>
      </tr>
      <tr id="ed<?= (int)$it['id'] ?>" hidden>
        <td colspan="3" style="background:#f7fafc">
          <form method="post" class="inst-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <input type="text" name="role" value="<?= e($it['role'] ?? '') ?>" placeholder="<?= t('inst_role') ?>" style="width:150px">
            <input type="text" name="name" value="<?= e($it['name']) ?>" placeholder="<?= t('col_name') ?>" required style="min-width:320px;flex:1">
            <label class="toggle-line" style="margin:0"><input type="checkbox" name="is_note" value="1" <?= $it['is_note'] ? 'checked' : '' ?>> <?= t('inst_note') ?></label>
            <button class="btn btn-sm btn-primary"><i class="fas fa-floppy-disk"></i> <?= t('save') ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="3" class="muted" style="text-align:center"><?= t('inst_empty') ?></td></tr><?php endif; ?>
    </tbody>
  </table>

  <form method="post" class="inst-form" style="margin-top:8px">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="bloc" value="<?= e($key) ?>">
    <input type="text" name="role" placeholder="<?= t('inst_role') ?>" style="width:150px">
    <input type="text" name="name" placeholder="<?= t('inst_new_name') ?>" required style="min-width:320px;flex:1">
    <label class="toggle-line" style="margin:0"><input type="checkbox" name="is_note" value="1"> <?= t('inst_note') ?></label>
    <button class="btn btn-sm btn-dark"><i class="fas fa-plus"></i> <?= t('add') ?></button>
  </form>
<?php endforeach; ?>

<p class="field-help" style="margin-top:22px"><?= t('inst_note_help') ?></p>
<?php require __DIR__ . '/footer.php'; ?>
