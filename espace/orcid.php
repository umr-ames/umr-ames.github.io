<?php
/* Import des publications depuis l'API publique ORCID (v3.0) */

require_once __DIR__ . '/db.php';

function orcid_normalize(string $raw): ?string {
    // Accepte un ORCID brut ou une URL ; valide le format 0000-0000-0000-0000
    if (preg_match('/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i', $raw, $m)) {
        return strtoupper($m[1]);
    }
    return null;
}

function orcid_http_get(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'UMR-AMES-Portal/1.0',
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($res !== false && $code === 200) ? $res : null;
    }
    // Fallback
    $ctx = stream_context_create(['http' => ['header' => "Accept: application/json\r\nUser-Agent: UMR-AMES-Portal/1.0\r\n", 'timeout' => 20]]);
    $res = @file_get_contents($url, false, $ctx);
    return $res ?: null;
}

/**
 * Importe les publications ORCID d'un chercheur.
 * Retourne [importées, total, message d'erreur|null].
 */
function orcid_import(int $researcher_id, string $orcid): array {
    $id = orcid_normalize($orcid);
    if (!$id) return [0, 0, 'orcid_invalid'];

    $json = orcid_http_get("https://pub.orcid.org/v3.0/$id/works");
    if ($json === null) return [0, 0, 'orcid_unreachable'];

    $data = json_decode($json, true);
    if (!isset($data['group']) || !is_array($data['group'])) {
        return [0, 0, 'orcid_none'];
    }

    $pdo = db();
    $ins = $pdo->prepare(
        'INSERT INTO publications (researcher_id, title, authors, journal, year, doi, url, source, external_id)
         VALUES (?,?,?,?,?,?,?,\'orcid\',?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), journal=VALUES(journal), year=VALUES(year), doi=VALUES(doi), url=VALUES(url)'
    );

    $imported = 0; $total = 0;
    foreach ($data['group'] as $group) {
        $summaries = $group['work-summary'] ?? [];
        if (!$summaries) continue;
        $w = $summaries[0];
        $total++;

        $putcode = isset($w['put-code']) ? (string)$w['put-code'] : null;
        $title   = $w['title']['title']['value'] ?? null;
        if (!$title) continue;
        $journal = $w['journal-title']['value'] ?? null;
        $year    = $w['publication-date']['year']['value'] ?? null;
        $year    = $year ? (int)$year : null;

        $doi = null; $url = null;
        foreach (($w['external-ids']['external-id'] ?? []) as $ext) {
            $type = strtolower($ext['external-id-type'] ?? '');
            $val  = $ext['external-id-value'] ?? '';
            if ($type === 'doi' && $val) { $doi = $val; $url = 'https://doi.org/' . $val; }
        }
        if (!$url && !empty($w['url']['value'])) $url = $w['url']['value'];

        $ins->execute([
            $researcher_id,
            mb_substr($title, 0, 500),
            null,
            $journal ? mb_substr($journal, 0, 300) : null,
            $year,
            $doi ? mb_substr($doi, 0, 120) : null,
            $url ? mb_substr($url, 0, 400) : null,
            $putcode,
        ]);
        $imported++;
    }

    return [$imported, $total, null];
}
