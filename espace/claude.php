<?php
/* Vérification de l'affiliation AMES via l'API Claude (Anthropic). */

function claude_verify_ames(string $apiKey, array $pub): ?int {
    if (!$apiKey || !function_exists('curl_init')) return null;

    $prompt = "Tu dois déterminer si une publication scientifique est affiliée à l'UMR-AMES "
        . "(Analyse et Modélisation pour l'Environnement et la Santé, ISGI, Nouakchott, Mauritanie).\n\n"
        . "Titre : "            . ($pub['title']           ?? '—') . "\n"
        . "Auteurs : "          . ($pub['authors']         ?? '—') . "\n"
        . "Journal : "          . ($pub['journal']         ?? '—') . "\n"
        . "Année : "            . ($pub['year']            ?? '—') . "\n"
        . "DOI : "              . ($pub['doi']             ?? '—') . "\n"
        . "Affiliation brute : ". ($pub['affiliation_raw'] ?? '—') . "\n\n"
        . "Réponds UNIQUEMENT par un objet JSON, sans explication : "
        . "{\"ames\":true} si affiliée AMES, {\"ames\":false} si non affiliée, {\"ames\":null} si impossible à déterminer.";

    $body = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 60,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$res) return null;

    $data = json_decode($res, true);
    $text = trim($data['content'][0]['text'] ?? '');

    // Extraire le JSON même si Claude ajoute du texte autour
    if (preg_match('/\{[^}]+\}/', $text, $m)) $text = $m[0];
    $parsed = json_decode($text, true);

    if (!is_array($parsed) || !array_key_exists('ames', $parsed)) return null;
    if ($parsed['ames'] === null) return null;
    return $parsed['ames'] ? 1 : 0;
}
