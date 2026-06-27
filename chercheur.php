<?php
require_once __DIR__ . '/espace/lib.php';
require_once __DIR__ . '/espace/metrics.php';
$cfg = config();
$pdo = db();

$slug = $_GET['slug'] ?? '';
$st = $pdo->prepare('SELECT r.*, p.* FROM researchers r LEFT JOIN profiles p ON p.researcher_id=r.id WHERE r.slug=?');
$st->execute([$slug]);
$r = $st->fetch();

$viewer = current_user();
$canView = $r && ($r['status'] === 'approved' || ($viewer && ($viewer['role']==='admin' || (int)$viewer['id']===(int)$r['id'])));

if (!$canView) {
    http_response_code(404);
    $r = null;
}

if ($r) {
    // Actualisation automatique des indicateurs (OpenAlex) si périmés et ORCID renseigné
    if (!empty($r['orcid']) && empty($r['metrics_manual']) && metrics_are_stale($r['metrics_updated_at'] ?? null)) {
        try {
            if (refresh_metrics_for($pdo, (int)$r['id'], $r['orcid'], false)) {
                $mq = $pdo->prepare('SELECT citations, h_index, i10_index, metrics_updated_at FROM profiles WHERE researcher_id=?');
                $mq->execute([$r['id']]);
                if ($mm = $mq->fetch()) $r = array_merge($r, $mm);
            }
        } catch (Throwable $e) { /* réseau / colonnes : on ignore */ }
    }

    $st = $pdo->prepare('SELECT * FROM publications WHERE researcher_id=? ORDER BY year DESC, id DESC');
    $st->execute([$r['id']]);
    $pubs = $st->fetchAll();
    $photoUrl = !empty($r['photo']) ? $cfg['uploads_url'].'/'.$r['photo'] : null;
    $initials = strtoupper(mb_substr($r['full_name'],0,1));
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $r ? e($r['full_name']) : t('not_found') ?> · UMR-AMES</title>
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
    <a href="/" class="portal-brand"><img src="/logo/logo_unite.png" alt="UMR-AMES"> <span>UMR-AMES</span></a>
    <nav class="portal-nav"><a href="/#membres" class="portal-back"><i class="fas fa-arrow-left"></i> <?= t('back_members') ?></a></nav>
  </div>
</header>
<main class="portal-main">
  <div class="container">
  <?php if (!$r): ?>
    <div class="profile-404">
      <h1 class="portal-h1"><?= t('not_found') ?></h1>
      <p><?= t('not_found_txt') ?></p>
      <a href="/#membres" class="btn btn-primary"><?= t('see_members') ?></a>
    </div>
  <?php else: ?>
    <?php if ($r['status'] !== 'approved'): ?>
      <div class="flash flash-info"><?= t('private_preview') ?></div>
    <?php endif; ?>

    <article class="profile">
      <aside class="profile-side">
        <div class="profile-photo">
          <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="<?= e($r['full_name']) ?>"><?php else: ?><span class="profile-initials"><?= e($initials) ?></span><?php endif; ?>
        </div>
        <h1 class="profile-name"><?= e($r['full_name']) ?></h1>
        <?php if ($r['title']): ?><p class="profile-title"><?= e($r['title']) ?></p><?php endif; ?>
        <?php if ($r['affiliation']): ?><p class="profile-affil"><i class="fas fa-building-columns"></i> <?= e($r['affiliation']) ?></p><?php endif; ?>
        <?php
          $axesList = !empty($r['research_axes']) ? (json_decode($r['research_axes'], true) ?: []) : [];
          if (!$axesList && !empty($r['axis'])) $axesList = [axis_label($r['axis'])];
        ?>
        <?php if ($axesList): ?>
          <div class="profile-axes">
            <?php foreach ($axesList as $ax): ?><span class="profile-axis"><?= e($ax) ?></span><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="profile-links">
          <?php if ($r['public_email']): ?><a href="mailto:<?= e($r['public_email']) ?>" title="E-mail"><i class="fas fa-envelope"></i></a><?php endif; ?>
          <?php if ($r['phone']): ?><a href="tel:<?= e($r['phone']) ?>" title="Téléphone"><i class="fas fa-phone"></i></a><?php endif; ?>
          <?php if ($r['orcid']): ?><a href="https://orcid.org/<?= e($r['orcid']) ?>" target="_blank" rel="noopener" title="ORCID"><i class="fab fa-orcid"></i></a><?php endif; ?>
          <?php if ($r['scholar_url']): ?><a href="<?= e($r['scholar_url']) ?>" target="_blank" rel="noopener" title="Google Scholar"><i class="fas fa-graduation-cap"></i></a><?php endif; ?>
          <?php if ($r['researchgate_url']): ?><a href="<?= e($r['researchgate_url']) ?>" target="_blank" rel="noopener" title="ResearchGate"><i class="fab fa-researchgate"></i></a><?php endif; ?>
          <?php if ($r['linkedin_url']): ?><a href="<?= e($r['linkedin_url']) ?>" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
          <?php if ($r['website_url']): ?><a href="<?= e($r['website_url']) ?>" target="_blank" rel="noopener" title="Site web"><i class="fas fa-globe"></i></a><?php endif; ?>
        </div>
      </aside>

      <div class="profile-body">
        <?php
          $hasMetrics = isset($r['citations']) || isset($r['h_index']) || isset($r['i10_index']);
          $hasMetrics = $hasMetrics && (($r['citations']??null) !== null || ($r['h_index']??null) !== null || ($r['i10_index']??null) !== null);
        ?>
        <?php if ($hasMetrics): ?>
          <section class="profile-section">
            <h2 class="portal-h2"><?= t('metrics_title') ?></h2>
            <div class="metrics-cards">
              <div class="metric-card"><span class="metric-num"><?= (int)($r['citations'] ?? 0) ?></span><span class="metric-lbl"><?= t('citations') ?></span></div>
              <div class="metric-card"><span class="metric-num"><?= (int)($r['h_index'] ?? 0) ?></span><span class="metric-lbl"><?= t('h_index') ?></span></div>
              <div class="metric-card"><span class="metric-num"><?= (int)($r['i10_index'] ?? 0) ?></span><span class="metric-lbl"><?= t('i10_index') ?></span></div>
            </div>
            <?php if (!empty($r['metrics_updated_at'])): ?>
              <p class="metrics-source"><?= !empty($r['metrics_manual']) ? t('metrics_src_manual') : t('metrics_src_openalex') ?> · <?= t('metrics_updated_on') ?> <?= e(date('d/m/Y', strtotime($r['metrics_updated_at']))) ?></p>
            <?php endif; ?>
          </section>
        <?php endif; ?>

        <?php if ($r['bio']): ?>
          <section class="profile-section">
            <h2 class="portal-h2"><?= t('bio') ?></h2>
            <p class="profile-bio"><?= nl2br(e($r['bio'])) ?></p>
          </section>
        <?php endif; ?>

        <section class="profile-section">
          <h2 class="portal-h2"><?= t('publications') ?> <span class="count">(<?= count($pubs) ?>)</span></h2>
          <?php if (!$pubs): ?>
            <p class="muted"><?= t('no_pubs_public') ?></p>
          <?php else: ?>
            <ol class="profile-pubs">
              <?php foreach ($pubs as $pub): ?>
                <li>
                  <span class="pp-title"><?php if ($pub['url']): ?><a href="<?= e($pub['url']) ?>" target="_blank" rel="noopener"><?= e($pub['title']) ?></a><?php else: ?><?= e($pub['title']) ?><?php endif; ?></span>
                  <span class="pp-meta"><?= e(trim(($pub['authors']?$pub['authors'].' · ':'').($pub['journal']?:'').($pub['year']?' ('.$pub['year'].')':''))) ?></span>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endif; ?>
        </section>
      </div>
    </article>
  <?php endif; ?>
  </div>
</main>
<footer class="portal-footer"><div class="container"><p>© <?= date('Y') ?> UMR-AMES · <a href="/">Accueil</a></p></div></footer>
</body>
</html>
