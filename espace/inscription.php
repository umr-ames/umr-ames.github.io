<?php
require_once __DIR__ . '/lib.php';
boot_session();
if (current_user()) { header('Location: tableau-de-bord.php'); exit; }

$errors = [];
$old = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $pass      = (string)($_POST['password'] ?? '');
    $pass2     = (string)($_POST['password2'] ?? '');
    $old = ['full_name' => $full_name, 'email' => $email];

    if (mb_strlen($full_name) < 3)          $errors[] = "Indiquez votre nom complet.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Adresse e-mail invalide.";
    if (strlen($pass) < 8)                  $errors[] = "Le mot de passe doit faire au moins 8 caractères.";
    if ($pass !== $pass2)                   $errors[] = "Les deux mots de passe ne correspondent pas.";

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM researchers WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $errors[] = "Un compte existe déjà avec cette adresse.";
        }
    }

    if (!$errors) {
        $cfg = config();
        $isAdmin = (strcasecmp($email, $cfg['admin_email']) === 0);
        $slug = unique_slug($full_name);
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $role = $isAdmin ? 'admin' : 'researcher';
        $status = $isAdmin ? 'approved' : 'pending';

        $pdo = db();
        $pdo->prepare('INSERT INTO researchers (email, password_hash, full_name, slug, role, status) VALUES (?,?,?,?,?,?)')
            ->execute([$email, $hash, $full_name, $slug, $role, $status]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO profiles (researcher_id) VALUES (?)')->execute([$id]);

        $_SESSION['uid'] = $id;
        session_regenerate_id(true);
        $_SESSION['uid'] = $id;

        if ($status === 'pending') {
            flash("Compte créé ! Il doit être validé par l'administration avant d'apparaître publiquement. Vous pouvez déjà compléter votre profil.", 'success');
        } else {
            flash("Compte administrateur créé et activé.", 'success');
        }
        header('Location: tableau-de-bord.php'); exit;
    }
}

$page_title = 'Créer un compte';
require __DIR__ . '/header.php';
?>
<div class="auth-card">
  <h1 class="portal-h1">Créer un compte chercheur</h1>
  <p class="portal-sub">Réservé aux membres de l'UMR-AMES. Votre compte sera validé par l'administration.</p>

  <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" class="portal-form" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>Nom complet</label>
      <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required>
    </div>
    <div class="form-group">
      <label>Adresse e-mail</label>
      <input type="email" name="email" value="<?= e($old['email']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Mot de passe (8+ caractères)</label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label>Confirmer le mot de passe</label>
        <input type="password" name="password2" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fas fa-user-plus"></i> Créer mon compte</button>
  </form>
  <p class="portal-alt">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
