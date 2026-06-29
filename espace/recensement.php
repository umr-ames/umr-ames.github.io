<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/affiliation.php';
$me = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_filter') {
        set_setting('publications_ames_only', isset($_POST['publications_ames_only']) ? '1' : '0');
        flash(t('status_updated'), 'success');
        header('Location: recensement.php'); exit;
    }

    if ($action === 'recense_all') {
        $list = $pdo->query('SELECT researcher_id, orcid FROM profiles WHERE orcid IS NOT NULL AND orcid <> \'\'')->fetchAll();
        $checked = 0; $ames = 0; $people = 0;
        foreach ($list as $row) {
            try {
                $res = detect_affiliations_for($pdo, (int)$row['researcher_id'], $row['orcid']);
                $checked += $res['checked']; $ames += $res['ames']; $people++;
            } catch (Throwable $e) {}
        }
        flash(sprintf(t('recense_done'), $checked, $ames, $people), 'success');
        header('Location: recensement.php'); exit;
    }

    if ($action === 'set_ames') {
        $pid = (int)($_POST['pub_id'] ?? 0);
        $val = $_POST['val'] ?? '';
        if ($val === 'auto') {
            $pdo->prepare('UPDATE publications SET ames_manual = 0 WHERE id = ?')->execute([$pid]);
        } elseif ($val === '1' || $val === '0') {
            $pdo->prepare('UPDATE publications SET ames_manual = 1, ames_affiliation = ?, ames_checked_at = NOW() WHERE id = ?')
                ->execute([(int)$val, $pid]);
        }
        header('Location: recensement.php#p' . $pid); exit;
    }
}

$onlyAmes = publications_ames_only();
$needMigrate = false;
$rows = [];
try {
    $rows = $pdo->query(
        'SELECT pub.*, r.full_name FROM publications pub
         JOIN researchers r ON r.id = pub.researcher_id
         ORDER BY (pub.ames_affiliation IS NULL) DESC, r.full_name, pub.year DESC, pub.id DESC'
    )->fetchAll();
} catch (PDOException $ex) {
    $needMigrate = true; // colonnes pas encore créées
}

$nAmes = 0; $nNon = 0; $nVerif = 0;
foreach ($rows as $r0) {
    if ($r0['ames_affiliation'] === null) $nVerif++;
    elseif ((int)$r0['ames_affiliation'] === 1) $nAmes++;
    else $nNon++;
}

$page_title = t('recense_title');
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><i class="fas fa-clipboard-check"></i> <?= t('recense_title') ?></h1>
<p class="portal-sub"><?= t('recense_sub') ?></p>

<?php if ($needMigrate): ?>
  <div class="flash flash-error">
    La base doit d'abord être mise à jour.
    <a href="migrate.php"><strong>Cliquez ici pour lancer la mise à jour</strong></a>, puis revenez sur cette page.
  </div>
<?php require __DIR__ . '/footer.php'; exit; endif; ?>

<div class="admin-toolbar">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="recense_all">
    <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-magnifying-glass"></i> <?= t('recense_run') ?></button>
  </form>
  <span class="admin-toolbar-help"><?= t('recense_run_help') ?></span>
</div>

<div class="admin-toolbar">
  <form method="post" id="filterForm">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="toggle_filter">
    <label class="toggle-line" style="margin:0">
      <input type="checkbox" name="publications_ames_only" value="1" <?= $onlyAmes ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
      <?= t('recense_filter_label') ?>
    </label>
  </form>
  <span class="admin-toolbar-help"><?= t('recense_filter_help') ?></span>
</div>

<p class="recense-stats">
  <span class="status status-approved"><?= t('recense_yes') ?> : <?= $nAmes ?></span>
  <span class="status status-suspended"><?= t('recense_no') ?> : <?= $nNon ?></span>
  <span class="status status-pending"><?= t('recense_unknown') ?> : <?= $nVerif ?></span>
</p>

<table class="admin-table recense-table">
  <thead><tr>
    <th><?= t('col_name') ?></th><th><?= t('title') ?></th><th><?= t('year') ?></th>
    <th><?= t('recense_affil') ?></th><th><?= t('recense_status') ?></th><th><?= t('col_actions') ?></th>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $p): ?>
    <?php
      $st = $p['ames_affiliation'];
      if ($st === null) { $cls='status-pending'; $lbl=t('recense_unknown'); }
      elseif ((int)$st === 1) { $cls='status-approved'; $lbl=t('recense_yes'); }
      else { $cls='status-suspended'; $lbl=t('recense_no'); }
    ?>
    <tr id="p<?= (int)$p['id'] ?>">
      <td><?= e($p['full_name']) ?></td>
      <td><small><?= e(mb_strimwidth($p['title'],0,90,'…')) ?></small>
          <?php if (!empty($p['doi'])): ?><br><small class="muted">DOI: <?= e($p['doi']) ?></small><?php endif; ?></td>
      <td><?= e($p['year'] ?: '—') ?></td>
      <td><small class="muted"><?= e($p['affiliation_raw'] ? mb_strimwidth($p['affiliation_raw'],0,70,'…') : '—') ?></small></td>
      <td><span class="status <?= $cls ?>"><?= e($lbl) ?></span><?php if (!empty($p['ames_manual'])): ?> <small>(<?= t('recense_manual') ?>)</small><?php endif; ?></td>
      <td>
        <form method="post" class="admin-actions">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="set_ames">
          <input type="hidden" name="pub_id" value="<?= (int)$p['id'] ?>">
          <button name="val" value="1" class="btn btn-sm btn-primary" title="<?= t('recense_yes') ?>">AMES</button>
          <button name="val" value="0" class="btn btn-sm btn-outline-dark" title="<?= t('recense_no') ?>"><?= t('recense_no') ?></button>
          <button name="val" value="auto" class="btn btn-sm btn-outline-dark" title="Auto"><i class="fas fa-rotate"></i></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="text-align:center"><?= t('recense_empty') ?></td></tr><?php endif; ?>
  </tbody>
</table>
<?php require __DIR__ . '/footer.php'; ?>
