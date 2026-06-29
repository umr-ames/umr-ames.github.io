<?php
/* Détection automatique de l'affiliation UMR-AMES des publications,
   via OpenAlex (chaînes d'affiliation brutes des auteurs). */

require_once __DIR__ . '/orcid.php'; // orcid_normalize(), orcid_http_get()

/* Normalisation pour comparaison (minuscules, sans accents, alphanum) */
function affil_norm(string $s): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
    return trim($s);
}

/* Renvoie la chaîne d'affiliation qui mentionne AMES, ou null */
function affil_match_ames(array $strings): ?string {
    $needles = [
        'umr ames', 'umrames',
        'analysis and modeling for environment and health',
        'analysis and modelling for environment and health',
        'analyse et modelisation pour l environnement et la sante',
        'modeling for environment and health',
        'modelling for environment and health',
    ];
    foreach ($strings as $s) {
        if (!is_string($s) || $s === '') continue;
        $n = affil_norm($s);
        foreach ($needles as $needle) {
            if (strpos($n, $needle) !== false) return $s;
        }
    }
    return null;
}

function doi_key(?string $doi): string {
    if (!$doi) return '';
    $d = strtolower(trim($doi));
    $d = preg_replace('~^https?://(dx\.)?doi\.org/~', '', $d);
    return $d;
}

/**
 * Recense l'affiliation AMES des publications d'un chercheur via OpenAlex.
 * Ne touche pas aux publications marquées manuellement (ames_manual=1).
 * @return array{checked:int, ames:int}
 */
function detect_affiliations_for(PDO $pdo, int $researcherId, ?string $orcid): array {
    $id = orcid_normalize($orcid ?? '');
    if (!$id) return ['checked' => 0, 'ames' => 0];

    $url = 'https://api.openalex.org/works?filter=author.orcid:' . urlencode($id)
         . '&per-page=200&select=doi,display_name,authorships&mailto=contact@umr-ames.mr';
    $json = orcid_http_get($url);
    if ($json === null) return ['checked' => 0, 'ames' => 0];

    $data = json_decode($json, true);
    $works = $data['results'] ?? [];
    if (!is_array($works)) return ['checked' => 0, 'ames' => 0];

    $orcidUrl = 'https://orcid.org/' . $id;

    // Index des œuvres : par DOI et par titre normalisé
    $byDoi = []; $byTitle = [];
    foreach ($works as $w) {
        // Affiliations de NOTRE auteur dans cette œuvre (sinon toutes)
        $strings = [];
        foreach (($w['authorships'] ?? []) as $a) {
            $isThisAuthor = (($a['author']['orcid'] ?? '') === $orcidUrl);
            $raw = $a['raw_affiliation_strings'] ?? [];
            foreach (($a['institutions'] ?? []) as $inst) {
                if (!empty($inst['display_name'])) $raw[] = $inst['display_name'];
            }
            if ($isThisAuthor) { $strings = array_merge($strings, $raw); }
        }
        // À défaut d'auteur identifié, on regarde toutes les affiliations
        if (!$strings) {
            foreach (($w['authorships'] ?? []) as $a) {
                $strings = array_merge($strings, $a['raw_affiliation_strings'] ?? []);
                foreach (($a['institutions'] ?? []) as $inst) {
                    if (!empty($inst['display_name'])) $strings[] = $inst['display_name'];
                }
            }
        }
        $matched = affil_match_ames($strings);
        $entry = ['ames' => $matched !== null, 'raw' => $matched];

        $dk = doi_key($w['doi'] ?? null);
        if ($dk) $byDoi[$dk] = $entry;
        $tk = affil_norm($w['display_name'] ?? '');
        if ($tk) $byTitle[$tk] = $entry;
    }

    // Mise à jour des publications du chercheur (hors override manuel)
    $st = $pdo->prepare('SELECT id, title, doi FROM publications WHERE researcher_id = ? AND ames_manual = 0');
    $st->execute([$researcherId]);
    $pubs = $st->fetchAll();

    $upd = $pdo->prepare('UPDATE publications SET ames_affiliation = ?, affiliation_raw = ?, ames_checked_at = NOW() WHERE id = ?');
    $checked = 0; $ames = 0;
    foreach ($pubs as $p) {
        $hit = null;
        $dk = doi_key($p['doi'] ?? null);
        if ($dk && isset($byDoi[$dk])) $hit = $byDoi[$dk];
        if ($hit === null) {
            $tk = affil_norm($p['title'] ?? '');
            if ($tk && isset($byTitle[$tk])) $hit = $byTitle[$tk];
        }
        if ($hit === null) continue; // pas trouvé sur OpenAlex -> reste « à vérifier »
        $val = $hit['ames'] ? 1 : 0;
        $upd->execute([$val, $hit['raw'] ? mb_substr($hit['raw'], 0, 500) : null, $p['id']]);
        $checked++;
        if ($val === 1) $ames++;
    }
    return ['checked' => $checked, 'ames' => $ames];
}
