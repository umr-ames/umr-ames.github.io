<?php
/* Liste des membres permanents et associés — réservé à l'administration.
   Permet d'ajouter, modifier et supprimer un membre, et d'exporter en Word. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/membres-data.php';
$me  = require_admin();
$pdo = db();

$needMigrate = false;
try {
    $pdo->query('SELECT 1 FROM unit_members LIMIT 1');
} catch (PDOException $ex) {
    $needMigrate = true;
}

if (!$needMigrate && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    $read = function (): array {
        return [
            'name'  => mb_substr(trim($_POST['name']  ?? ''), 0, 190),
            'estab' => mb_substr(trim($_POST['estab'] ?? ''), 0, 60),
            'grade' => mb_substr(trim($_POST['grade'] ?? ''), 0, 40),
            'spec'  => mb_substr(trim($_POST['spec']  ?? ''), 0, 120),
            'phone' => mb_substr(trim($_POST['phone'] ?? ''), 0, 40),
            'email' => mb_substr(trim($_POST['email'] ?? ''), 0, 190),
            'perm'  => isset($_POST['is_permanent']) ? 1 : 0,
            'phd'   => isset($_POST['is_phd']) ? 1 : 0,
        ];
    };

    if ($action === 'add') {
        $f = $read();
        if ($f['name'] !== '') {
            $max = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM unit_members')->fetchColumn();
            $pdo->prepare(
                'INSERT INTO unit_members (name, estab, grade, spec, phone, email, is_permanent, is_phd, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$f['name'], $f['estab'], $f['grade'], $f['spec'], $f['phone'], $f['email'], $f['perm'], $f['phd'], $max + 10]);
            flash(t('mem_added'), 'success');
        }
        header('Location: membres.php'); exit;
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $f  = $read();
        if ($id && $f['name'] !== '') {
            $pdo->prepare(
                'UPDATE unit_members SET name=?, estab=?, grade=?, spec=?, phone=?, email=?, is_permanent=?, is_phd=? WHERE id=?'
            )->execute([$f['name'], $f['estab'], $f['grade'], $f['spec'], $f['phone'], $f['email'], $f['perm'], $f['phd'], $id]);
            flash(t('mem_saved'), 'success');
        }
        header('Location: membres.php'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare('DELETE FROM unit_members WHERE id = ?')->execute([$id]);
            flash(t('mem_deleted'), 'success');
        }
        header('Location: membres.php'); exit;
    }
}

$perm  = $needMigrate ? [] : membres_permanents();
$assoc = $needMigrate ? [] : membres_associes();

$page_title = t('members_title');
require __DIR__ . '/header.php';

/* Formulaire d'ajout / de modification d'un membre */
function membre_form(array $m = null): void {
    $isEdit = $m !== null; ?>
  <form method="post" class="inst-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $isEdit ? 'edit' : 'add' ?>">
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><?php endif; ?>
    <input type="text" name="name"  value="<?= e($m['name']  ?? '') ?>" placeholder="<?= t('col_name') ?> *" required style="min-width:230px;flex:1">
    <input type="text" name="estab" value="<?= e($m['estab'] ?? '') ?>" placeholder="<?= t('col_estab') ?>" style="width:110px">
    <input type="text" name="grade" value="<?= e($m['grade'] ?? '') ?>" placeholder="<?= t('col_grade') ?>" style="width:90px">
    <input type="text" name="spec"  value="<?= e($m['spec']  ?? '') ?>" placeholder="<?= t('col_specialty') ?>" style="width:190px">
    <input type="text" name="phone" value="<?= e($m['phone'] ?? '') ?>" placeholder="<?= t('col_phone') ?>" style="width:120px">
    <input type="text" name="email" value="<?= e($m['email'] ?? '') ?>" placeholder="<?= t('col_email') ?>" style="width:200px">
    <label class="toggle-line" style="margin:0"><input type="checkbox" name="is_permanent" value="1" <?= !empty($m['permanent']) ? 'checked' : '' ?>> <?= t('mem_perm') ?></label>
    <label class="toggle-line" style="margin:0"><input type="checkbox" name="is_phd" value="1" <?= !empty($m['phd']) ? 'checked' : '' ?>> <?= t('members_phd') ?></label>
    <button class="btn btn-sm <?= $isEdit ? 'btn-primary' : 'btn-dark' ?>">
      <i class="fas fa-<?= $isEdit ? 'floppy-disk' : 'plus' ?>"></i> <?= $isEdit ? t('save') : t('add') ?>
    </button>
  </form>
<?php }

function membres_table(array $list): void { ?>
  <table class="admin-table membres-table">
    <thead><tr>
      <th style="width:38px">N°</th>
      <th><?= t('col_name') ?></th>
      <th><?= t('col_estab') ?></th>
      <th><?= t('col_grade') ?></th>
      <th><?= t('col_specialty') ?></th>
      <th><?= t('col_phone') ?></th>
      <th><?= t('col_email') ?></th>
      <th style="width:96px"><?= t('col_actions') ?></th>
    </tr></thead>
    <tbody>
    <?php foreach ($list as $i => $m): ?>
      <tr>
        <td class="muted"><?= $i + 1 ?></td>
        <td><?= e($m['name']) ?><?php if ($m['phd']): ?> <small class="muted">(<?= t('members_phd') ?>)</small><?php endif; ?></td>
        <td><?= e($m['estab']) ?></td>
        <td><?= e($m['grade']) ?></td>
        <td><small><?= e($m['spec']) ?></small></td>
        <td><small><?= e($m['phone'] ?: '—') ?></small></td>
        <td><small><?= e($m['email'] ?: '—') ?></small></td>
        <td>
          <?php if (!empty($m['id'])): ?>
          <div class="admin-actions" style="gap:4px">
            <button class="btn btn-sm btn-dark" type="button"
                    onclick="var r=document.getElementById('m<?= (int)$m['id'] ?>'); r.hidden=!r.hidden"
                    title="<?= t('mem_edit') ?>"><i class="fas fa-pen"></i></button>
            <form method="post" style="display:inline" onsubmit="return confirm('<?= e(t('mem_confirm_del')) ?>');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-sm btn-outline-dark" title="<?= t('delete') ?>"><i class="fas fa-trash"></i></button>
            </form>
          </div>
          <?php endif; ?>
        </td>
      </tr>
      <?php if (!empty($m['id'])): ?>
      <tr id="m<?= (int)$m['id'] ?>" hidden>
        <td colspan="8" style="background:#f7fafc"><?php membre_form($m); ?></td>
      </tr>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="8" class="muted" style="text-align:center">—</td></tr><?php endif; ?>
    </tbody>
  </table>
<?php }
?>
<h1 class="portal-h1"><i class="fas fa-users"></i> <?= t('members_title') ?></h1>
<p class="portal-sub"><?= t('members_sub') ?></p>

<?php if ($needMigrate): ?>
  <div class="flash flash-error">
    La base doit d'abord être mise à jour.
    <a href="migrate.php"><strong>Cliquez ici pour lancer la mise à jour</strong></a>, puis revenez sur cette page.
  </div>
<?php require __DIR__ . '/footer.php'; exit; endif; ?>

<div class="admin-toolbar export-bar">
  <a class="btn btn-primary btn-sm" href="export-membres.php?type=permanents"><i class="fas fa-file-word"></i> <?= t('members_export_perm') ?></a>
  <a class="btn btn-primary btn-sm" href="export-membres.php?type=associes"><i class="fas fa-file-word"></i> <?= t('members_export_assoc') ?></a>
  <a class="btn btn-dark btn-sm" href="export-membres.php?type=all"><i class="fas fa-file-word"></i> <?= t('members_export_all') ?></a>
</div>

<h2 class="portal-h2" style="margin-top:26px">
  <i class="fas fa-user-tie"></i> <?= t('members_permanent') ?>
  <span class="muted" style="font-weight:400">(<?= count($perm) ?>)</span>
</h2>
<?php membres_table($perm); ?>

<h2 class="portal-h2" style="margin-top:34px">
  <i class="fas fa-user-friends"></i> <?= t('members_associate') ?>
  <span class="muted" style="font-weight:400">(<?= count($assoc) ?>)</span>
</h2>
<?php membres_table($assoc); ?>

<h2 class="portal-h2" style="margin-top:34px"><i class="fas fa-user-plus"></i> <?= t('mem_add_title') ?></h2>
<?php membre_form(); ?>
<p class="field-help" style="margin-top:8px"><?= t('mem_add_help') ?></p>

<p class="field-help" style="margin-top:18px"><strong><?= t('members_abbrev') ?> :</strong> <?= e(membres_abbrev()) ?></p>
<?php require __DIR__ . '/footer.php'; ?>
