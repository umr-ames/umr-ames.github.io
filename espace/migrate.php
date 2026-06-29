<?php
/* =====================================================
   Mise à jour automatique de la base (réservé admin)
   À ouvrir après chaque déploiement nécessitant une évolution BDD :
   https://umr-ames.mr/espace/migrate.php
   Idempotent : n'ajoute que ce qui manque.
   ===================================================== */
require_once __DIR__ . '/lib.php';
$me = require_admin();
$pdo = db();

$done = [];
$skip = [];

function col_exists(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $col]);
    return (int)$st->fetchColumn() > 0;
}
function table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $st->execute([$table]);
    return (int)$st->fetchColumn() > 0;
}
function add_col(PDO $pdo, array &$done, array &$skip, string $table, string $col, string $definition) {
    if (col_exists($pdo, $table, $col)) { $skip[] = "$table.$col"; return; }
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $definition");
    $done[] = "$table.$col";
}

// --- researchers ---
add_col($pdo, $done, $skip, 'researchers', 'first_name', 'VARCHAR(80) NULL AFTER full_name');
add_col($pdo, $done, $skip, 'researchers', 'last_name',  'VARCHAR(80) NULL AFTER first_name');

// --- profiles ---
add_col($pdo, $done, $skip, 'profiles', 'research_axes',      'TEXT NULL');
add_col($pdo, $done, $skip, 'profiles', 'name_clickable',     'TINYINT(1) NOT NULL DEFAULT 1');
add_col($pdo, $done, $skip, 'profiles', 'citations',          'INT NULL');
add_col($pdo, $done, $skip, 'profiles', 'h_index',            'INT NULL');
add_col($pdo, $done, $skip, 'profiles', 'i10_index',          'INT NULL');
add_col($pdo, $done, $skip, 'profiles', 'metrics_manual',     'TINYINT(1) NOT NULL DEFAULT 0');
add_col($pdo, $done, $skip, 'profiles', 'metrics_updated_at', 'DATETIME NULL');

// --- publications : affiliation AMES ---
add_col($pdo, $done, $skip, 'publications', 'ames_affiliation', 'TINYINT(1) NULL');        // NULL=à vérifier, 1=oui, 0=non
add_col($pdo, $done, $skip, 'publications', 'ames_manual',      'TINYINT(1) NOT NULL DEFAULT 0');
add_col($pdo, $done, $skip, 'publications', 'affiliation_raw',  'VARCHAR(500) NULL');
add_col($pdo, $done, $skip, 'publications', 'ames_checked_at',  'DATETIME NULL');

// --- settings ---
if (!table_exists($pdo, 'settings')) {
    $pdo->exec('CREATE TABLE settings (k VARCHAR(60) PRIMARY KEY, v VARCHAR(255) DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $done[] = 'table settings';
} else {
    $skip[] = 'table settings';
}
$pdo->prepare('INSERT INTO settings (k, v) VALUES (\'metrics_public\', \'1\') ON DUPLICATE KEY UPDATE v = v')->execute();
$pdo->prepare('INSERT INTO settings (k, v) VALUES (\'publications_ames_only\', \'0\') ON DUPLICATE KEY UPDATE v = v')->execute();

$page_title = 'Mise à jour BDD';
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><i class="fas fa-database"></i> Mise à jour de la base</h1>
<?php if ($done): ?>
  <div class="flash flash-success"><strong>Ajouté :</strong> <?= e(implode(', ', $done)) ?></div>
<?php else: ?>
  <div class="flash flash-info">Aucune modification nécessaire — la base est déjà à jour. ✅</div>
<?php endif; ?>
<?php if ($skip): ?>
  <p class="field-help">Déjà présent : <?= e(implode(', ', $skip)) ?></p>
<?php endif; ?>
<p style="margin-top:18px"><a href="tableau-de-bord.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a></p>
<?php require __DIR__ . '/footer.php'; ?>
