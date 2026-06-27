<?php
/* API publique : liste des chercheurs approuvés (nom + slug) pour rendre
   les noms cliquables sur le site. JSON. */
require_once __DIR__ . '/../espace/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

try {
    $rows = db()->query(
        'SELECT r.full_name, r.slug
         FROM researchers r
         JOIN profiles p ON p.researcher_id = r.id
         WHERE r.status = \'approved\' AND COALESCE(p.name_clickable, 1) = 1
         ORDER BY r.full_name'
    )->fetchAll();
    echo json_encode(['ok' => true, 'researchers' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'researchers' => []]);
}
