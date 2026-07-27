<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/orcid.php';
require_once __DIR__ . '/claude.php';
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

    // Import ORCID uniquement (sans détection d'affiliation)
    if ($action === 'recense_all') {
        $list = $pdo->query('SELECT researcher_id, orcid FROM profiles WHERE orcid IS NOT NULL AND orcid <> \'\'')->fetchAll();
        $imported = 0; $people = 0;
        foreach ($list as $row) {
            try {
                [$imp] = orcid_import((int)$row['researcher_id'], $row['orcid']);
                $imported += $imp;
                $people++;
            } catch (Throwable $e) {}
        }
        flash(sprintf('%d publication(s) importée(s) depuis ORCID pour %d chercheur(s).', $imported, $people), 'success');
        header('Location: recensement.php'); exit;
    }

    if ($action === 'set_ames') {
        $pid = (int)($_POST['pub_id'] ?? 0);
        $val = $_POST['val'] ?? '';
        if ($val === 'auto') {
            $pdo->prepare('UPDATE publications SET ames_manual = 0, ames_affiliation = NULL WHERE id = ?')->execute([$pid]);
        } elseif ($val === '1' || $val === '0') {
            $pdo->prepare('UPDATE publications SET ames_manual = 1, ames_affiliation = ?, ames_checked_at = NOW() WHERE id = ?')
                ->execute([(int)$val, $pid]);
        }
        header('Location: recensement.php#p' . $pid); exit;
    }

    // Vérification Claude : publication unique
    if ($action === 'verify_claude') {
        $apiKey = get_setting('anthropic_api_key', '');
        $pid    = (int)($_POST['pub_id'] ?? 0);
        if ($apiKey && $pid) {
            $st = $pdo->prepare('SELECT * FROM publications WHERE id = ?');
            $st->execute([$pid]);
            $p = $st->fetch();
            if ($p && !$p['ames_manual']) {
                $val = claude_verify_ames($apiKey, $p);
                if ($val !== null) {
                    $pdo->prepare('UPDATE publications SET ames_affiliation = ?, ames_checked_at = NOW() WHERE id = ?')
                        ->execute([$val, $pid]);
                    flash($val === 1 ? 'Claude : publication affiliée AMES.' : 'Claude : publication non affiliée.', 'success');
                } else {
                    flash('Claude n\'a pas pu déterminer l\'affiliation (résultat incertain).', 'info');
                }
            }
        }
        header('Location: recensement.php#p' . $pid); exit;
    }

    // Vérification Claude : toutes les publications "À vérifier"
    if ($action === 'verify_claude_pending') {
        $apiKey = get_setting('anthropic_api_key', '');
        if ($apiKey) {
            set_time_limit(180);
            $pending = $pdo->query('SELECT * FROM publications WHERE ames_affiliation IS NULL AND ames_manual = 0')->fetchAll();
            $done = 0; $ames = 0; $uncertain = 0;
            foreach ($pending as $p) {
                $val = claude_verify_ames($apiKey, $p);
                if ($val !== null) {
                    $pdo->prepare('UPDATE publications SET ames_affiliation = ?, ames_checked_at = NOW() WHERE id = ?')
                        ->execute([$val, $p['id']]);
                    $done++;
                    if ($val === 1) $ames++;
                } else {
                    $uncertain++;
                }
            }
            flash(sprintf(
                'Claude a vérifié %d publication(s) : %d AMES, %d non affiliées, %d incertaines.',
                $done + $uncertain, $ames, $done - $ames, $uncertain
            ), 'success');
        } else {
            flash('Clé API Anthropic non configurée (voir Administration).', 'error');
        }
        header('Location: recensement.php'); exit;
    }
}

$onlyAmes    = publications_ames_only();
$claudeReady = get_setting('anthropic_api_key', '') !== '';
$needMigrate = false;
$rows        = [];

$filterFrom = trim($_GET['from'] ?? '');
$filterYear = 0;
if ($filterFrom !== '') {
    $ts = strtotime($filterFrom);
    if ($ts) $filterYear = (int)date('Y', $ts);
}

try {
    if ($filterYear > 0) {
        $st = $pdo->prepare(
            'SELECT pub.*, r.full_name FROM publications pub
             JOIN researchers r ON r.id = pub.researcher_id
             WHERE pub.year >= ?
             ORDER BY pub.year DESC, r.full_name ASC, pub.id DESC'
        );
        $st->execute([$filterYear]);
        $rows = $st->fetchAll();
    } else {
        $rows = $pdo->query(
            'SELECT pub.*, r.full_name FROM publications pub
             JOIN researchers r ON r.id = pub.researcher_id
             ORDER BY pub.year DESC, r.full_name ASC, pub.id DESC'
        )->fetchAll();
    }
} catch (PDOException $ex) {
    $needMigrate = true;
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
    <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-rotate"></i> Actualiser depuis ORCID</button>
  </form>
  <span class="admin-toolbar-help">Importe les nouvelles publications depuis ORCID pour tous les chercheurs ayant un compte et un ORCID.</span>
</div>

<?php if ($claudeReady): ?>
<div class="admin-toolbar">
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="verify_claude_pending">
    <button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px;padding:6px 14px;cursor:pointer" type="submit">
      <i class="fas fa-robot"></i> Vérifier les "À vérifier" avec Claude
    </button>
  </form>
  <span class="admin-toolbar-help"><?= $nVerif ?> publication(s) à vérifier — Claude analyse chaque affiliation via l'API Anthropic.</span>
</div>
<?php else: ?>
<div class="admin-toolbar">
  <span class="admin-toolbar-help" style="color:#b45309"><i class="fas fa-triangle-exclamation"></i> Clé API Anthropic non configurée — rendez-vous dans <a href="admin.php">Administration</a> pour l'activer.</span>
</div>
<?php endif; ?>

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

<div class="admin-toolbar export-bar">
  <form method="get" action="recensement.php" class="export-form" style="margin-bottom:6px">
    <label class="export-label"><?= t('export_from') ?>
      <input type="date" name="from" value="<?= e($filterFrom ?: (date('Y') - 5) . '-01-01') ?>">
    </label>
    <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-filter"></i> Filtrer le tableau</button>
    <?php if ($filterYear > 0): ?>
      <a href="recensement.php" class="btn btn-sm btn-outline-dark">✕ Tout afficher</a>
    <?php endif; ?>
  </form>
  <form method="get" action="export-publications.php" class="export-form">
    <input type="hidden" name="from" value="<?= e($filterFrom ?: (date('Y') - 5) . '-01-01') ?>">
    <label class="toggle-line" style="margin:0"><input type="checkbox" name="ames_only" value="1"> <?= t('export_ames_only') ?></label>
    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-file-word"></i> <?= t('export_btn') ?></button>
  </form>
  <span class="admin-toolbar-help"><?= t('export_help') ?></span>
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
      $affilVal = $p['ames_affiliation'];
      if ($affilVal === null) { $cls='status-pending'; $lbl=t('recense_unknown'); }
      elseif ((int)$affilVal === 1) { $cls='status-approved'; $lbl=t('recense_yes'); }
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
          <button name="val" value="auto" class="btn btn-sm btn-outline-dark" title="Réinitialiser"><i class="fas fa-rotate"></i></button>
        </form>
        <?php if ($claudeReady && $affilVal === null && !$p['ames_manual']): ?>
        <form method="post" class="admin-actions" style="margin-top:4px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="verify_claude">
          <input type="hidden" name="pub_id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-sm" style="background:#7c3aed;color:#fff;border:none;border-radius:6px;padding:4px 10px;cursor:pointer" title="Vérifier avec Claude">
            <i class="fas fa-robot"></i> Claude
          </button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="muted" style="text-align:center"><?= t('recense_empty') ?></td></tr><?php endif; ?>
  </tbody>
</table>
<?php require __DIR__ . '/footer.php'; ?>
