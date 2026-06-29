<?php
/* API publique : publications des chercheurs approuvés (JSON) */
require_once __DIR__ . '/../espace/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

try {
    $amesOnly = publications_ames_only() ? ' AND pub.ames_affiliation = 1' : '';
    $sql = 'SELECT pub.title, pub.authors, pub.journal, pub.year, pub.doi, pub.url, pub.axis,
                   r.full_name AS researcher, r.slug
            FROM publications pub
            JOIN researchers r ON r.id = pub.researcher_id
            WHERE r.status = \'approved\'' . $amesOnly . '
            ORDER BY pub.year DESC, pub.id DESC';
    $rows = db()->query($sql)->fetchAll();
    echo json_encode(['ok' => true, 'count' => count($rows), 'publications' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'publications' => []]);
}
