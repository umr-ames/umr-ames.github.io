<?php
require_once __DIR__ . '/lib.php';
boot_session();
if (current_user()) { header('Location: tableau-de-bord.php'); exit; }

$error = '';
$old_email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $old_email = $email;

    $st = db()->prepare('SELECT * FROM researchers WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
        if ($u['status'] === 'suspended') {
            $error = "Ce compte a été suspendu. Contactez l'administration.";
        } else {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            header('Location: tableau-de-bord.php'); exit;
        }
    } else {
        $error = "E-mail ou mot de passe incorrect.";
    }
}

$page_title = 'Connexion';
require __DIR__ . '/header.php';
?>
<div class="auth-card">
  <h1 class="portal-h1">Connexion</h1>
  <p class="portal-sub">Accédez à votre espace chercheur.</p>
  <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="portal-form">
    <?= csrf_field() ?>
    <div class="form-group">
      <label>Adresse e-mail</label>
      <input type="email" name="email" value="<?= e($old_email) ?>" required>
    </div>
    <div class="form-group">
      <label>Mot de passe</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fas fa-right-to-bracket"></i> Se connecter</button>
  </form>
  <p class="portal-alt">Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
