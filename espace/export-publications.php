<?php
/* Export Word (.doc) de l'ensemble des publications à partir d'une date donnée.
   Réservé à l'administration. Le fichier s'ouvre nativement dans Word. */

require_once __DIR__ . '/lib.php';
$me = require_admin();
$pdo = db();

$from     = trim($_GET['from'] ?? '');
$amesOnly = !empty($_GET['ames_only']);

$year = 0;
if ($from !== '') { $ts = strtotime($from); if ($ts) $year = (int)date('Y', $ts); }
if (!$year) $year = (int)date('Y') - 5;

$sql = 'SELECT pub.*, r.full_name FROM publications pub
        JOIN researchers r ON r.id = pub.researcher_id
        WHERE pub.year IS NOT NULL AND pub.year >= ?';
$params = [$year];
if ($amesOnly) { $sql .= ' AND pub.ames_affiliation = 1'; }
$sql .= ' ORDER BY pub.year DESC, pub.title ASC';

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
} catch (PDOException $ex) {
    // Colonne ames_affiliation absente : on retente sans le filtre
    $st = $pdo->prepare('SELECT pub.*, r.full_name FROM publications pub
                         JOIN researchers r ON r.id = pub.researcher_id
                         WHERE pub.year IS NOT NULL AND pub.year >= ?
                         ORDER BY pub.year DESC, pub.title ASC');
    $st->execute([$year]);
    $rows = $st->fetchAll();
}

/* Dédoublonnage (une même publication peut être déclarée par 2 membres) */
$seen = []; $pubs = [];
foreach ($rows as $p) {
    $key = !empty($p['doi'])
        ? strtolower(trim($p['doi']))
        : trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($p['title'])));
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $pubs[] = $p;
}

/* Regroupement par année */
$byYear = [];
foreach ($pubs as $p) { $byYear[(int)$p['year']][] = $p; }
krsort($byYear);

$filename = 'publications-umr-ames-depuis-' . $year . ($amesOnly ? '-AMES' : '') . '.doc';

header('Content-Type: application/msword; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Publications UMR-AMES</title>
<style>
  @page { size: A4; margin: 2cm; }
  body { font-family: "Calibri", "Arial", sans-serif; font-size: 11pt; color: #1a1a1a; }
  h1 { font-size: 18pt; color: #102a4c; margin-bottom: 2pt; }
  .sub { font-size: 10pt; color: #555; margin-bottom: 18pt; }
  h2 { font-size: 13pt; color: #0e7c6f; border-bottom: 1pt solid #cccccc;
       padding-bottom: 3pt; margin-top: 18pt; margin-bottom: 8pt; }
  ol { margin: 0 0 0 18pt; padding: 0; }
  li { margin-bottom: 8pt; text-align: justify; }
  .t { font-weight: bold; }
  .j { font-style: italic; }
  .doi { color: #555; font-size: 9.5pt; }
  .foot { margin-top: 22pt; font-size: 9pt; color: #777; border-top: 1pt solid #ccc; padding-top: 6pt; }
</style>
</head>
<body>

<h1>UMR-AMES &mdash; Liste des publications</h1>
<p class="sub">
  Analyse et Modélisation pour l'Environnement et la Santé &middot; ISGI, Nouakchott<br>
  Publications à partir de <strong><?= (int)$year ?></strong><?= $amesOnly ? ' &middot; <strong>affiliation UMR-AMES uniquement</strong>' : '' ?><br>
  Total : <strong><?= count($pubs) ?></strong> publication(s) &middot; Document généré le <?= date('d/m/Y') ?>
</p>

<?php if (!$pubs): ?>
  <p><em>Aucune publication trouvée pour cette période.</em></p>
<?php endif; ?>

<?php foreach ($byYear as $y => $list): ?>
  <h2><?= (int)$y ?> <span style="font-weight:normal;font-size:10pt;color:#777">(<?= count($list) ?>)</span></h2>
  <ol>
    <?php foreach ($list as $p): ?>
      <li>
        <?php
          $authors = trim((string)$p['authors']);
          if ($authors === '') $authors = $p['full_name'];
        ?>
        <?= htmlspecialchars($authors, ENT_QUOTES, 'UTF-8') ?>.
        <span class="t"><?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>.</span>
        <?php if (!empty($p['journal'])): ?><span class="j"><?= htmlspecialchars($p['journal'], ENT_QUOTES, 'UTF-8') ?></span>, <?php endif; ?>
        <?= (int)$p['year'] ?>.
        <?php if (!empty($p['doi'])): ?><br><span class="doi">DOI : <?= htmlspecialchars($p['doi'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endforeach; ?>

<p class="foot">
  Document généré automatiquement depuis l'espace chercheur du site umr-ames.mr.
  Les doublons (même DOI ou même titre) ont été fusionnés.
</p>

</body>
</html>
