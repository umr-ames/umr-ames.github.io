<?php
/* Bibliothèque commune : session, CSRF, auth, helpers */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';

function boot_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax',
        ]);
        session_name('amesportal');
        session_start();
    }
}

/* --- Échappement HTML --- */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* --- CSRF --- */
function csrf_token(): string {
    boot_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void {
    boot_session();
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
    }
}

/* --- Authentification --- */
function current_user(): ?array {
    boot_session();
    if (empty($_SESSION['uid'])) return null;
    static $u = null;
    if ($u === null) {
        $st = db()->prepare('SELECT * FROM researchers WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: connexion.php'); exit; }
    if ($u['status'] !== 'approved') {
        // Compte en attente : autorisé à voir son tableau de bord mais averti
    }
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Accès réservé à l\'administration.'); }
    return $u;
}

/* --- Slug --- */
function make_slug(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'chercheur';
}
function unique_slug(string $base, ?int $exceptId = null): string {
    $slug = make_slug($base);
    $try = $slug; $i = 2;
    while (true) {
        if ($exceptId !== null) {
            $st = db()->prepare('SELECT 1 FROM researchers WHERE slug = ? AND id <> ?');
            $st->execute([$try, $exceptId]);
        } else {
            $st = db()->prepare('SELECT 1 FROM researchers WHERE slug = ?');
            $st->execute([$try]);
        }
        if (!$st->fetch()) return $try;
        $try = $slug . '-' . $i; $i++;
    }
}

/* --- Messages flash --- */
function flash(string $msg, string $type = 'info'): void {
    boot_session();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}
function flashes(): array {
    boot_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* --- Réglages globaux (table settings) --- */
function get_setting(string $k, $default = null) {
    try {
        $st = db()->prepare('SELECT v FROM settings WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return ($v === false) ? $default : $v;
    } catch (Throwable $e) {
        return $default;
    }
}
function set_setting(string $k, string $v): void {
    db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute([$k, $v]);
}
function metrics_public(): bool {
    return get_setting('metrics_public', '1') === '1';
}
function publications_ames_only(): bool {
    return get_setting('publications_ames_only', '0') === '1';
}

/* --- Axes (libellés) --- */
function axes(): array {
    return [
        'env'   => 'Environnement',
        'sante' => 'Santé & Épidémiologie',
        'math'  => 'Modélisation Mathématique',
        'ia'    => 'Statistiques & IA',
    ];
}
function axis_label(?string $k): string {
    return axes()[$k] ?? '';
}
