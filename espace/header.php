<?php
require_once __DIR__ . '/lib.php';
boot_session();
$me = current_user();
$page_title = $page_title ?? 'Espace chercheur';
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($page_title) ?> · UMR-AMES</title>
  <link rel="icon" href="/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/portal.css">
</head>
<body class="portal-body">
<header class="portal-header">
  <div class="container portal-header-inner">
    <a href="/" class="portal-brand">
      <img src="/logo/logo_unite.png" alt="UMR-AMES"> <span>UMR-AMES</span>
    </a>
    <nav class="portal-nav">
      <?php if ($me): ?>
        <a href="/espace/tableau-de-bord.php"><i class="fas fa-gauge"></i> Tableau de bord</a>
        <?php if ($me['role'] === 'admin'): ?>
          <a href="/espace/admin.php"><i class="fas fa-user-shield"></i> Administration</a>
        <?php endif; ?>
        <a href="/chercheur.php?slug=<?= e($me['slug']) ?>"><i class="fas fa-id-badge"></i> Ma page</a>
        <a href="/espace/deconnexion.php" class="portal-logout"><i class="fas fa-right-from-bracket"></i> Déconnexion</a>
      <?php else: ?>
        <a href="/espace/connexion.php">Connexion</a>
        <a href="/espace/inscription.php" class="btn btn-primary btn-sm">Créer un compte</a>
      <?php endif; ?>
      <a href="/" class="portal-back"><i class="fas fa-arrow-left"></i> Site</a>
    </nav>
  </div>
</header>
<main class="portal-main">
  <div class="container">
    <?php foreach (flashes() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
