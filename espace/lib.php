<?php
/* Bibliothèque commune : session, CSRF, auth, helpers */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/i18n.php';

/* Détection HTTPS fiable derrière le proxy/load-balancer de l'hébergeur */
function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return true;
    if (($_SERVER['SERVER_PORT'] ?? '') == 443) return true;
    $fwd = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if (strtolower($fwd) === 'https') return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return true;
    return false;
}

function boot_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => is_https(),
            'samesite' => 'Lax',
        ]);
        session_name('amesportal');
        ini_set('session.use_strict_mode', '1'); // refuse les identifiants de session non générés par PHP
        session_start();

        /* Expiration après 2 h d'inactivité + rotation périodique de l'identifiant */
        $now = time();
        if (isset($_SESSION['last_seen']) && ($now - $_SESSION['last_seen']) > 7200) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['last_seen'] = $now;
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = $now;
        } elseif (($now - $_SESSION['created']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = $now;
        }
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
    /* Une suspension doit prendre effet immédiatement, sans attendre que
       le chercheur se déconnecte : on ferme la session en cours. */
    if ($u['status'] === 'suspended') {
        $_SESSION = [];
        session_destroy();
        header('Location: connexion.php'); exit;
    }
    // Compte « pending » : accès à son tableau de bord, mais averti (page non publiée)
    return $u;
}
function require_admin(): array {
    $u = require_login();
    if ($u['role'] !== 'admin') { http_response_code(403); exit('Accès réservé à l\'administration.'); }
    return $u;
}

/* --- Limitation des tentatives de connexion (anti-force brute) --- */
function client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr($ip, 0, 45);
}

/** Nombre d'échecs récents pour ce couple IP + e-mail. */
function login_failures(string $email): int {
    try {
        $st = db()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND email = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $st->execute([client_ip(), mb_substr($email, 0, 190)]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) {
        return 0; // table absente : ne pas bloquer la connexion
    }
}

function login_record_failure(string $email): void {
    try {
        db()->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')
            ->execute([client_ip(), mb_substr($email, 0, 190)]);
        // Purge opportuniste des entrées anciennes
        db()->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    } catch (Throwable $e) { /* table absente : ignorer */ }
}

function login_clear_failures(string $email): void {
    try {
        db()->prepare('DELETE FROM login_attempts WHERE ip = ? AND email = ?')
            ->execute([client_ip(), mb_substr($email, 0, 190)]);
    } catch (Throwable $e) { /* ignorer */ }
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
/* Rubrique « Instances » visible sur le site public ? (masquée par défaut) */
function instances_public(): bool {
    return get_setting('instances_public', '0') === '1';
}

/* Blocs de gouvernance : clé => libellé + icône */
function instance_blocs(): array {
    return [
        'direction' => ['label' => 'Direction',                 'icon' => 'fa-user-tie'],
        'conseil'   => ['label' => 'Conseil Scientifique',      'icon' => 'fa-flask'],
        'copil'     => ['label' => 'Comité de Pilotage (CoPil)', 'icon' => 'fa-sitemap'],
    ];
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
