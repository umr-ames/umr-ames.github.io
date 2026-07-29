<?php
/* API publique : composition des instances de gouvernance.
   Ne renvoie le contenu que si l'administration a rendu la rubrique publique. */
require_once __DIR__ . '/../espace/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

try {
    if (!instances_public()) {
        echo json_encode(['ok' => true, 'public' => false, 'blocs' => []]);
        exit;
    }

    $rows = db()->query(
        'SELECT bloc, role, name, is_note FROM instance_members ORDER BY sort_order, id'
    )->fetchAll();

    $blocs = [];
    foreach (instance_blocs() as $key => $meta) {
        $items = [];
        foreach ($rows as $r) {
            if ($r['bloc'] !== $key) continue;
            $items[] = [
                'role' => $r['role'],
                'name' => $r['name'],
                'note' => (bool)$r['is_note'],
            ];
        }
        if ($items) {
            $blocs[] = ['key' => $key, 'label' => $meta['label'], 'icon' => $meta['icon'], 'items' => $items];
        }
    }

    echo json_encode(['ok' => true, 'public' => true, 'blocs' => $blocs], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'public' => false, 'blocs' => []]);
}
