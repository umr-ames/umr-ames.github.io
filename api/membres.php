<?php
/* API publique : liste des membres de l'unité affichée sur la page d'accueil.
   Ne renvoie que les informations publiques — jamais les téléphones ni les
   e-mails, qui restent réservés à l'espace d'administration. */
require_once __DIR__ . '/../espace/lib.php';
require_once __DIR__ . '/../espace/membres-data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

try {
    $all = membres_all();
    $out = ['chercheurs' => [], 'doctorants' => []];
    foreach ($all as $m) {
        $entry = [
            'name'  => $m['name'],
            'grade' => $m['grade'],
            'estab' => $m['estab'],
            'spec'  => $m['spec'],
        ];
        $out[$m['phd'] ? 'doctorants' : 'chercheurs'][] = $entry;
    }
    echo json_encode([
        'ok'         => true,
        'total'      => count($all),
        'chercheurs' => $out['chercheurs'],
        'doctorants' => $out['doctorants'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'chercheurs' => [], 'doctorants' => []]);
}
