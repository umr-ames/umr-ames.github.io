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

    if (mb_strlen($full_name) < 3)          $errors[] = t('err_name');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('err_email');
    else {
        // Réservé au domaine institutionnel @umr-ames.mr (l'admin est l'exception)
        $cfg = config();
        $isAdminEmail = (strcasecmp($email, $cfg['admin_email']) === 0);
        $domainOk = (bool)preg_match('/@umr-ames\.mr$/i', $email);
        if (!$domainOk && !$isAdminEmail) {
            $errors[] = t('err_domain');
        }
    }
    if (strlen($pass) < 8)                  $errors[] = t('err_pass8');
    if ($pass !== $pass2)                   $errors[] = t('err_pass_match');

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM researchers WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $errors[] = t('err_exists');
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
            flash(t('ok_pending'), 'success');
        } else {
            flash(t('ok_admin'), 'success');
        }
        header('Location: tableau-de-bord.php'); exit;
    }
}

$page_title = t('reg_title');
require __DIR__ . '/header.php';
?>
<div class="auth-card">
  <h1 class="portal-h1"><?= t('reg_title') ?></h1>
  <p class="portal-sub"><?= t('reg_sub') ?></p>

  <?php foreach ($errors as $err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" class="portal-form" autocomplete="off">
    <?= csrf_field() ?>
    <div class="form-group">
      <label><?= t('full_name') ?></label>
      <input type="text" name="full_name" value="<?= e($old['full_name']) ?>" required>
    </div>
    <div class="form-group">
      <label><?= t('email') ?></label>
      <input type="email" name="email" value="<?= e($old['email']) ?>" required>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label><?= t('password8') ?></label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label><?= t('password_confirm') ?></label>
        <input type="password" name="password2" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><i class="fas fa-user-plus"></i> <?= t('reg_btn') ?></button>
  </form>
  <p class="portal-alt"><?= t('already') ?> <a href="<?= e(lang_url('connexion.php')) ?>"><?= t('login_btn') ?></a></p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
