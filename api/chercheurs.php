<?php
/* API publique : liste des chercheurs approuvés (nom + slug) pour rendre
   les noms cliquables sur le site. JSON. */
require_once __DIR__ . '/../espace/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

try {
    $rows = db()->query('SELECT full_name, slug FROM researchers WHERE status = \'approved\' ORDER BY full_name')->fetchAll();
    echo json_encode(['ok' => true, 'researchers' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'researchers' => []]);
}
