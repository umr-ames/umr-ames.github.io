<?php
/* Liste des membres permanents et associés — réservé à l'administration. */
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/membres-data.php';
$me = require_admin();

$perm   = membres_permanents();
$assoc  = membres_associes();

$page_title = t('members_title');
require __DIR__ . '/header.php';

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
      </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="7" class="muted" style="text-align:center">—</td></tr><?php endif; ?>
    </tbody>
  </table>
<?php }
?>
<h1 class="portal-h1"><i class="fas fa-users"></i> <?= t('members_title') ?></h1>
<p class="portal-sub"><?= t('members_sub') ?></p>

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

<p class="field-help" style="margin-top:18px"><strong><?= t('members_abbrev') ?> :</strong> <?= e(membres_abbrev()) ?></p>
<?php require __DIR__ . '/footer.php'; ?>
