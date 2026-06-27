<?php
require_once __DIR__ . '/lib.php';
boot_session();
$me = current_user();
$lang = portal_lang();
$page_title = $page_title ?? 'Espace chercheur';
?><!DOCTYPE html>
<html lang="<?= e($lang) ?>">
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
  <link rel="stylesheet" href="/css/style.css?v=20260626">
  <link rel="stylesheet" href="/css/portal.css?v=20260626">
</head>
<body class="portal-body">
<header class="portal-header">
  <div class="container portal-header-inner">
    <a href="/" class="portal-brand">
      <img src="/logo/logo_unite.png" alt="UMR-AMES"> <span>UMR-AMES</span>
    </a>
    <nav class="portal-nav">
      <?php if ($me): ?>
        <a href="<?= e(lang_url('/espace/tableau-de-bord.php')) ?>"><i class="fas fa-gauge"></i> <?= t('dashboard') ?></a>
        <?php if ($me['role'] === 'admin'): ?>
          <a href="<?= e(lang_url('/espace/admin.php')) ?>"><i class="fas fa-user-shield"></i> <?= t('admin') ?></a>
        <?php endif; ?>
        <a href="<?= e(lang_url('/chercheur.php?slug=' . $me['slug'])) ?>"><i class="fas fa-id-badge"></i> <?= t('my_page') ?></a>
        <a href="/espace/deconnexion.php" class="portal-logout"><i class="fas fa-right-from-bracket"></i> <?= t('logout') ?></a>
      <?php else: ?>
        <a href="<?= e(lang_url('/espace/connexion.php')) ?>"><?= t('login') ?></a>
        <a href="<?= e(lang_url('/espace/inscription.php')) ?>" class="btn btn-primary btn-sm"><?= t('create_account') ?></a>
      <?php endif; ?>
      <span class="portal-lang">
        <a href="?lang=fr" class="<?= $lang==='fr'?'active':'' ?>">FR</a><span aria-hidden="true">|</span><a href="?lang=en" class="<?= $lang==='en'?'active':'' ?>">EN</a>
      </span>
      <a href="/" class="portal-back"><i class="fas fa-arrow-left"></i> <?= t('back_site') ?></a>
    </nav>
  </div>
</header>
<main class="portal-main">
  <div class="container">
    <?php foreach (flashes() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
