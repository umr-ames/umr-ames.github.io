<?php
/* Indicateurs bibliométriques via l'API OpenAlex (gratuite, officielle).
   Recherche l'auteur par son ORCID et renvoie citations, indice h, indice i10. */

require_once __DIR__ . '/orcid.php'; // orcid_normalize(), orcid_http_get()

/**
 * @return array{citations:int,h_index:int,i10_index:int}|null
 */
function fetch_openalex_metrics(string $orcid): ?array {
    $id = orcid_normalize($orcid);
    if (!$id) return null;

    // Pool « poli » d'OpenAlex : on s'identifie via mailto
    $url = 'https://api.openalex.org/authors?filter=orcid:' . urlencode($id) . '&mailto=contact@umr-ames.mr';
    $json = orcid_http_get($url);
    if ($json === null) return null;

    $data = json_decode($json, true);
    $a = $data['results'][0] ?? null;
    if (!$a) return null;

    return [
        'citations' => (int)($a['cited_by_count'] ?? 0),
        'h_index'   => (int)($a['summary_stats']['h_index'] ?? 0),
        'i10_index' => (int)($a['summary_stats']['i10_index'] ?? 0),
    ];
}

/**
 * Met à jour en base les indicateurs d'un chercheur depuis OpenAlex.
 * Respecte l'override manuel (ne touche pas si metrics_manual = 1).
 * @return bool true si une mise à jour automatique a été faite
 */
function refresh_metrics_for(PDO $pdo, int $researcherId, ?string $orcid, bool $manual): bool {
    if ($manual) return false;
    if (!$orcid) return false;
    $m = fetch_openalex_metrics($orcid);
    if ($m === null) return false;
    $pdo->prepare('UPDATE profiles SET citations=?, h_index=?, i10_index=?, metrics_updated_at=NOW() WHERE researcher_id=?')
        ->execute([$m['citations'], $m['h_index'], $m['i10_index'], $researcherId]);
    return true;
}

/** Indicateurs périmés ? (plus de 7 jours, ou jamais récupérés) */
function metrics_are_stale(?string $updatedAt): bool {
    if (empty($updatedAt)) return true;
    return (time() - strtotime($updatedAt)) > 7 * 24 * 3600;
}
