<?php
/* Export Word (.doc) des listes de membres — réservé à l'administration.
   Le fichier s'ouvre nativement dans Word. */

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/membres-data.php';
$me = require_admin();

$type = $_GET['type'] ?? 'all';
if (!in_array($type, ['permanents', 'associes', 'all'], true)) $type = 'all';

if ($type === 'permanents') {
    $blocks = [['Membres permanents', membres_permanents()]];
    $slug   = 'permanents';
} elseif ($type === 'associes') {
    $blocks = [['Membres associés', membres_associes()]];
    $slug   = 'associes';
} else {
    $blocks = [['Membres permanents', membres_permanents()], ['Membres associés', membres_associes()]];
    $slug   = 'complet';
}

$total = 0;
foreach ($blocks as $b) { $total += count($b[1]); }

$filename = 'membres-umr-ames-' . $slug . '.doc';
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
<title>Membres UMR-AMES</title>
<style>
  @page { size: A4 landscape; margin: 1.6cm; }
  body { font-family: "Calibri", "Arial", sans-serif; font-size: 10.5pt; color: #1a1a1a; }
  h1 { font-size: 17pt; color: #102a4c; margin-bottom: 2pt; }
  .sub { font-size: 9.5pt; color: #555; margin-bottom: 16pt; }
  h2 { font-size: 12.5pt; color: #0e7c6f; border-bottom: 1pt solid #cccccc;
       padding-bottom: 3pt; margin-top: 18pt; margin-bottom: 8pt; }
  table { border-collapse: collapse; width: 100%; }
  th { background: #102a4c; color: #ffffff; font-size: 9.5pt; text-align: left;
       padding: 5pt 6pt; border: 0.5pt solid #8899aa; }
  td { font-size: 9.5pt; padding: 4pt 6pt; border: 0.5pt solid #b8c4d0; vertical-align: top; }
  td.num { text-align: center; color: #666; }
  .foot { margin-top: 18pt; font-size: 8.5pt; color: #777; border-top: 1pt solid #ccc; padding-top: 5pt; }
</style>
</head>
<body>

<h1>UMR-AMES &mdash; Liste des membres</h1>
<p class="sub">
  Analyse et Mod&eacute;lisation pour l'Environnement et la Sant&eacute; &middot; ISGI, Nouakchott<br>
  Total : <strong><?= $total ?></strong> membre(s) &middot; Document g&eacute;n&eacute;r&eacute; le <?= date('d/m/Y') ?>
</p>

<?php foreach ($blocks as [$titre, $list]): ?>
  <h2><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>
      <span style="font-weight:normal;font-size:9.5pt;color:#777">(<?= count($list) ?>)</span></h2>
  <table>
    <thead><tr>
      <th style="width:5%">N&deg;</th>
      <th style="width:26%">Nom et pr&eacute;nom</th>
      <th style="width:10%">&Eacute;tabl.</th>
      <th style="width:8%">Grade</th>
      <th style="width:21%">Sp&eacute;cialit&eacute;</th>
      <th style="width:12%">T&eacute;l&eacute;phone</th>
      <th style="width:18%">E-mail</th>
    </tr></thead>
    <tbody>
    <?php foreach ($list as $i => $m): ?>
      <tr>
        <td class="num"><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($m['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['estab'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['grade'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['spec'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['phone'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endforeach; ?>

<p class="foot">
  <strong>Abr&eacute;viations :</strong> <?= htmlspecialchars(membres_abbrev(), ENT_QUOTES, 'UTF-8') ?><br>
  Document g&eacute;n&eacute;r&eacute; depuis l'espace d'administration du site umr-ames.mr.
  Il contient des coordonn&eacute;es personnelles &mdash; diffusion &agrave; usage interne.
</p>

</body>
</html>
