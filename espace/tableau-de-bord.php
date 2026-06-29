<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/orcid.php';
require_once __DIR__ . '/metrics.php';
require_once __DIR__ . '/affiliation.php';
$me = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---- Enregistrer le profil ---- */
    if ($action === 'save_profile') {
        // Nom : prénom + nom -> full_name + slug
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $fullName = trim($first . ' ' . $last);
        if ($fullName !== '' && $fullName !== $me['full_name']) {
            $newSlug = unique_slug($fullName, (int)$me['id']);
            try {
                $pdo->prepare('UPDATE researchers SET first_name=?, last_name=?, full_name=?, slug=? WHERE id=?')
                    ->execute([$first ?: null, $last ?: null, $fullName, $newSlug, $me['id']]);
                $me['full_name'] = $fullName; $me['slug'] = $newSlug;
            } catch (PDOException $ex) {
                // colonnes first_name/last_name absentes : repli sur full_name + slug
                $pdo->prepare('UPDATE researchers SET full_name=?, slug=? WHERE id=?')
                    ->execute([$fullName, $newSlug, $me['id']]);
                $me['full_name'] = $fullName; $me['slug'] = $newSlug;
            }
        } elseif ($first !== '' || $last !== '') {
            try {
                $pdo->prepare('UPDATE researchers SET first_name=?, last_name=? WHERE id=?')
                    ->execute([$first ?: null, $last ?: null, $me['id']]);
            } catch (PDOException $ex) { /* colonnes absentes : ignorer */ }
        }

        // Axes de recherche : axes de l'unité (cases) + axes libres (texte)
        $selectedUnit = (array)($_POST['axes'] ?? []);
        $axesLabels = [];
        $primaryAxis = null;
        foreach ($selectedUnit as $k) {
            if (array_key_exists($k, axes())) {
                $axesLabels[] = axes()[$k];
                if ($primaryAxis === null) $primaryAxis = $k;
            }
        }
        foreach (explode(',', (string)($_POST['axes_custom'] ?? '')) as $c) {
            $c = trim($c);
            if ($c !== '') $axesLabels[] = mb_substr($c, 0, 80);
        }
        $axesLabels = array_values(array_unique($axesLabels));
        $researchAxes = $axesLabels ? json_encode($axesLabels, JSON_UNESCAPED_UNICODE) : null;

        $fields = [
            'title'            => trim($_POST['title'] ?? ''),
            'affiliation'      => trim($_POST['affiliation'] ?? ''),
            'discipline'       => trim($_POST['discipline'] ?? ''),
            'axis'             => $primaryAxis,
            'research_axes'    => $researchAxes,
            'bio'              => trim($_POST['bio'] ?? ''),
            'phone'            => trim($_POST['phone'] ?? ''),
            'public_email'     => trim($_POST['public_email'] ?? ''),
            'orcid'            => trim($_POST['orcid'] ?? ''),
            'researchgate_url' => trim($_POST['researchgate_url'] ?? ''),
            'scholar_url'      => trim($_POST['scholar_url'] ?? ''),
            'linkedin_url'     => trim($_POST['linkedin_url'] ?? ''),
            'website_url'      => trim($_POST['website_url'] ?? ''),
            'name_clickable'   => isset($_POST['name_clickable']) ? 1 : 0,
            'metrics_manual'   => isset($_POST['metrics_manual']) ? 1 : 0,
        ];
        if ($fields['public_email'] && !filter_var($fields['public_email'], FILTER_VALIDATE_EMAIL)) {
            flash(t('err_pub_email'), 'error');
            header('Location: tableau-de-bord.php'); exit;
        }
        foreach (['researchgate_url','scholar_url','linkedin_url','website_url'] as $k) {
            if ($fields[$k] && !preg_match('~^https?://~i', $fields[$k])) $fields[$k] = 'https://' . $fields[$k];
        }

        // Photo
        $photoSql = ''; $photoVal = [];
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $cfg = config();
            $tmp = $_FILES['photo']['tmp_name'];
            $info = @getimagesize($tmp);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!$info || !isset($allowed[$info['mime']])) {
                flash(t('err_photo_format'), 'error');
                header('Location: tableau-de-bord.php'); exit;
            }
            if ($_FILES['photo']['size'] > 4 * 1024 * 1024) {
                flash(t('err_photo_size'), 'error');
                header('Location: tableau-de-bord.php'); exit;
            }
            if (!is_dir($cfg['uploads_dir'])) @mkdir($cfg['uploads_dir'], 0775, true);
            $ext = $allowed[$info['mime']];
            $name = 'chercheur-' . $me['id'] . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = rtrim($cfg['uploads_dir'], '/') . '/' . $name;
            if (move_uploaded_file($tmp, $dest)) {
                $photoSql = ', photo = ?';
                $photoVal = [$name];
            }
        }

        $sql = 'UPDATE profiles SET title=?, affiliation=?, discipline=?, axis=?, research_axes=?, bio=?, phone=?, public_email=?, orcid=?, researchgate_url=?, scholar_url=?, linkedin_url=?, website_url=?, name_clickable=?, metrics_manual=?' . $photoSql . ' WHERE researcher_id=?';
        $vals = array_merge(array_values($fields), $photoVal, [$me['id']]);
        try {
            $pdo->prepare($sql)->execute($vals);

            // Indicateurs bibliométriques
            $manual = (bool)$fields['metrics_manual'];
            if ($manual) {
                $cit = max(0, (int)($_POST['citations'] ?? 0));
                $h   = max(0, (int)($_POST['h_index'] ?? 0));
                $i10 = max(0, (int)($_POST['i10_index'] ?? 0));
                $pdo->prepare('UPDATE profiles SET citations=?, h_index=?, i10_index=?, metrics_updated_at=NOW() WHERE researcher_id=?')
                    ->execute([$cit, $h, $i10, $me['id']]);
            } else {
                // récupération automatique via OpenAlex (ORCID)
                refresh_metrics_for($pdo, (int)$me['id'], $fields['orcid'] ?: null, false);
            }
            flash(t('ok_profile'), 'success');
        } catch (PDOException $ex) {
            flash(t('err_db_migration'), 'error');
        }
        header('Location: tableau-de-bord.php'); exit;
    }

    /* ---- Ajouter une publication ---- */
    if ($action === 'add_pub') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') { flash(t('err_pub_title'), 'error'); header('Location: tableau-de-bord.php#pubs'); exit; }
        $year = (int)($_POST['year'] ?? 0); $year = ($year >= 1950 && $year <= (int)date('Y')+1) ? $year : null;
        $axis = (array_key_exists($_POST['axis'] ?? '', axes()) ? $_POST['axis'] : null);
        $url  = trim($_POST['url'] ?? ''); if ($url && !preg_match('~^https?://~i',$url)) $url = 'https://'.$url;
        $pdo->prepare('INSERT INTO publications (researcher_id,title,authors,journal,year,doi,url,axis,source) VALUES (?,?,?,?,?,?,?,?,\'manual\')')
            ->execute([$me['id'], mb_substr($title,0,500), mb_substr(trim($_POST['authors']??''),0,500) ?: null,
                       mb_substr(trim($_POST['journal']??''),0,300) ?: null, $year,
                       mb_substr(trim($_POST['doi']??''),0,120) ?: null, $url ? mb_substr($url,0,400):null, $axis]);
        flash(t('ok_pub_added'), 'success');
        header('Location: tableau-de-bord.php#pubs'); exit;
    }

    /* ---- Supprimer une publication ---- */
    if ($action === 'del_pub') {
        $pid = (int)($_POST['pub_id'] ?? 0);
        $pdo->prepare('DELETE FROM publications WHERE id=? AND researcher_id=?')->execute([$pid, $me['id']]);
        flash(t('ok_pub_deleted'), 'success');
        header('Location: tableau-de-bord.php#pubs'); exit;
    }

    /* ---- Import ORCID ---- */
    if ($action === 'import_orcid') {
        $orcid = trim($_POST['orcid'] ?? '');
        if ($orcid === '') {
            $st = $pdo->prepare('SELECT orcid FROM profiles WHERE researcher_id=?');
            $st->execute([$me['id']]); $orcid = $st->fetchColumn() ?: '';
        }
        [$imp, $tot, $err] = orcid_import((int)$me['id'], $orcid);
        if ($err) flash(t($err), 'error');
        else {
            $norm = orcid_normalize($orcid);
            if ($norm) $pdo->prepare('UPDATE profiles SET orcid=? WHERE researcher_id=?')->execute([$norm, $me['id']]);
            try { detect_affiliations_for($pdo, (int)$me['id'], $norm ?: $orcid); } catch (Throwable $e) {}
            flash(sprintf(t('orcid_done'), $imp, $tot), 'success');
        }
        header('Location: tableau-de-bord.php#pubs'); exit;
    }

    /* ---- Actualiser les indicateurs (OpenAlex) ---- */
    if ($action === 'refresh_metrics') {
        $st = $pdo->prepare('SELECT orcid FROM profiles WHERE researcher_id=?');
        $st->execute([$me['id']]); $orcid = $st->fetchColumn() ?: '';
        if (!$orcid) {
            flash(t('metrics_need_orcid'), 'error');
        } elseif (refresh_metrics_for($pdo, (int)$me['id'], $orcid, false)) {
            flash(t('metrics_updated'), 'success');
        } else {
            flash(t('metrics_failed'), 'error');
        }
        header('Location: tableau-de-bord.php#metrics'); exit;
    }
}

// Données
$st = $pdo->prepare('SELECT * FROM profiles WHERE researcher_id=?'); $st->execute([$me['id']]); $p = $st->fetch() ?: [];

// Actualisation automatique des indicateurs si périmés (>7 j) et non manuels
if (!empty($p['orcid']) && empty($p['metrics_manual']) && metrics_are_stale($p['metrics_updated_at'] ?? null)) {
    try {
        if (refresh_metrics_for($pdo, (int)$me['id'], $p['orcid'], false)) {
            $st = $pdo->prepare('SELECT * FROM profiles WHERE researcher_id=?'); $st->execute([$me['id']]); $p = $st->fetch() ?: $p;
        }
    } catch (Throwable $e) { /* colonnes absentes ou réseau : on ignore */ }
}

$st = $pdo->prepare('SELECT * FROM publications WHERE researcher_id=? ORDER BY year DESC, id DESC'); $st->execute([$me['id']]); $pubs = $st->fetchAll();
$cfg = config();
$photoUrl = !empty($p['photo']) ? $cfg['uploads_url'].'/'.$p['photo'] : null;

// Prénom / Nom (repli : découpe de full_name si colonnes absentes/vides)
$firstName = $me['first_name'] ?? '';
$lastName  = $me['last_name'] ?? '';
if ($firstName === '' && $lastName === '') {
    $parts = preg_split('/\s+/', trim($me['full_name']), 2);
    $firstName = $parts[0] ?? '';
    $lastName  = $parts[1] ?? '';
}

$page_title = t('dashboard');
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1"><?= t('hello') ?>, <?= e($me['full_name']) ?></h1>
<?php if ($me['status'] === 'pending'): ?>
  <div class="flash flash-info"><?= t('pending_notice') ?></div>
<?php endif; ?>

<div class="dash-grid">
  <!-- PROFIL -->
  <section class="dash-card">
    <h2 class="portal-h2"><i class="fas fa-id-card"></i> <?= t('my_profile') ?></h2>
    <form method="post" enctype="multipart/form-data" class="portal-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_profile">

      <div class="photo-row">
        <div class="photo-preview">
          <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="Photo"><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
        </div>
        <div class="form-group" style="flex:1">
          <label><?= t('photo_label') ?></label>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group"><label><?= t('first_name') ?></label><input type="text" name="first_name" value="<?= e($firstName) ?>" required></div>
        <div class="form-group"><label><?= t('last_name') ?></label><input type="text" name="last_name" value="<?= e($lastName) ?>" required></div>
      </div>

      <div class="form-row">
        <div class="form-group"><label><?= t('grade') ?></label><input type="text" name="title" value="<?= e($p['title']??'') ?>" placeholder="Professeur, Maître de conférences, Doctorant…"></div>
        <div class="form-group"><label><?= t('affiliation') ?></label><input type="text" name="affiliation" value="<?= e($p['affiliation']??'') ?>" placeholder="ISGI, Université de Nouakchott…"></div>
      </div>
      <div class="form-group"><label><?= t('discipline') ?></label><input type="text" name="discipline" value="<?= e($p['discipline']??'') ?>"></div>

      <?php
        $currentAxes = !empty($p['research_axes']) ? (json_decode($p['research_axes'], true) ?: []) : [];
        if (!$currentAxes && !empty($p['axis'])) $currentAxes = [axis_label($p['axis'])];
        $unitLabels = array_values(axes());
        $customAxes = array_values(array_diff($currentAxes, $unitLabels));
      ?>
      <div class="form-group">
        <label><?= t('axes_unit') ?></label>
        <div class="axes-checks">
          <?php foreach (axes() as $k=>$lbl): ?>
            <label class="axis-check"><input type="checkbox" name="axes[]" value="<?= e($k) ?>" <?= in_array($lbl,$currentAxes,true)?'checked':'' ?>> <?= e($lbl) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group">
        <label><?= t('axes_other') ?></label>
        <input type="text" name="axes_custom" value="<?= e(implode(', ', $customAxes)) ?>" placeholder="<?= e(t('axes_other_ph')) ?>">
      </div>
      <div class="form-group"><label><?= t('bio') ?></label><textarea name="bio" rows="5"><?= e($p['bio']??'') ?></textarea></div>

      <div class="form-row">
        <div class="form-group"><label><?= t('public_email') ?></label><input type="email" name="public_email" value="<?= e($p['public_email']??'') ?>"></div>
        <div class="form-group"><label><?= t('phone') ?></label><input type="text" name="phone" value="<?= e($p['phone']??'') ?>"></div>
      </div>

      <h3 class="portal-h3"><?= t('ext_ids') ?></h3>
      <div class="form-row">
        <div class="form-group"><label>ORCID</label><input type="text" name="orcid" value="<?= e($p['orcid']??'') ?>" placeholder="0000-0000-0000-0000"></div>
        <div class="form-group"><label>Google Scholar (URL)</label><input type="text" name="scholar_url" value="<?= e($p['scholar_url']??'') ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>ResearchGate (URL)</label><input type="text" name="researchgate_url" value="<?= e($p['researchgate_url']??'') ?>"></div>
        <div class="form-group"><label>LinkedIn (URL)</label><input type="text" name="linkedin_url" value="<?= e($p['linkedin_url']??'') ?>"></div>
      </div>
      <div class="form-group"><label><?= t('website') ?></label><input type="text" name="website_url" value="<?= e($p['website_url']??'') ?>"></div>

      <h3 class="portal-h3" id="metrics"><?= t('metrics_title') ?></h3>
      <label class="toggle-line">
        <input type="checkbox" name="name_clickable" value="1" <?= (!isset($p['name_clickable']) || $p['name_clickable']) ? 'checked' : '' ?>>
        <?= t('name_clickable_label') ?>
      </label>
      <label class="toggle-line">
        <input type="checkbox" name="metrics_manual" value="1" id="metricsManual" <?= !empty($p['metrics_manual']) ? 'checked' : '' ?>>
        <?= t('metrics_manual_label') ?>
      </label>
      <p class="field-help"><?= t('metrics_help') ?></p>
      <div class="form-row metrics-fields">
        <div class="form-group"><label><?= t('citations') ?></label><input type="number" name="citations" min="0" value="<?= e($p['citations'] ?? '') ?>"></div>
        <div class="form-group"><label><?= t('h_index') ?></label><input type="number" name="h_index" min="0" value="<?= e($p['h_index'] ?? '') ?>"></div>
        <div class="form-group"><label><?= t('i10_index') ?></label><input type="number" name="i10_index" min="0" value="<?= e($p['i10_index'] ?? '') ?>"></div>
      </div>

      <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> <?= t('save_profile') ?></button>
    </form>

    <div class="metrics-refresh">
      <span class="metrics-date">
        <i class="fas fa-clock"></i>
        <?php if (!empty($p['metrics_updated_at'])): ?>
          <?= t('metrics_updated_on') ?> <?= e(date('d/m/Y', strtotime($p['metrics_updated_at']))) ?>
          <?= !empty($p['metrics_manual']) ? '· '.t('metrics_src_manual') : '· '.t('metrics_src_openalex') ?>
        <?php else: ?>
          <?= t('metrics_auto_note') ?>
        <?php endif; ?>
      </span>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="refresh_metrics">
        <button class="btn btn-dark btn-sm" type="submit"><i class="fas fa-rotate"></i> <?= t('metrics_refresh') ?></button>
      </form>
    </div>
  </section>

  <!-- PUBLICATIONS -->
  <section class="dash-card" id="pubs">
    <h2 class="portal-h2"><i class="fas fa-book"></i> <?= t('my_pubs') ?> (<?= count($pubs) ?>)</h2>

    <div class="orcid-box">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_orcid">
        <label><?= t('orcid_import') ?></label>
        <div class="orcid-row">
          <input type="text" name="orcid" value="<?= e($p['orcid']??'') ?>" placeholder="0000-0000-0000-0000">
          <button class="btn btn-dark" type="submit"><i class="fas fa-cloud-arrow-down"></i> <?= t('import') ?></button>
        </div>
        <small><?= t('orcid_help') ?></small>
      </form>
    </div>

    <details class="add-pub">
      <summary><i class="fas fa-plus"></i> <?= t('add_pub_manual') ?></summary>
      <form method="post" class="portal-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_pub">
        <div class="form-group"><label><?= t('title') ?> *</label><input type="text" name="title" required></div>
        <div class="form-group"><label><?= t('authors') ?></label><input type="text" name="authors" placeholder="A. Ahmed, M. Benioug…"></div>
        <div class="form-row">
          <div class="form-group"><label><?= t('journal') ?></label><input type="text" name="journal"></div>
          <div class="form-group"><label><?= t('year') ?></label><input type="number" name="year" min="1950" max="<?= (int)date('Y')+1 ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>DOI</label><input type="text" name="doi" placeholder="10.xxxx/xxxxx"></div>
          <div class="form-group"><label><?= t('axis') ?></label>
            <select name="axis"><option value="">—</option>
              <?php foreach (axes() as $k=>$lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label><?= t('link') ?></label><input type="text" name="url"></div>
        <button class="btn btn-primary" type="submit"><?= t('add') ?></button>
      </form>
    </details>

    <ul class="dash-pub-list">
      <?php if (!$pubs): ?><li class="empty"><?= t('no_pubs') ?></li><?php endif; ?>
      <?php foreach ($pubs as $pub): ?>
        <li>
          <div class="dash-pub-main">
            <span class="dash-pub-title"><?= e($pub['title']) ?></span>
            <span class="dash-pub-meta"><?= e(trim(($pub['journal']?:'').' '.($pub['year']?'· '.$pub['year']:''))) ?>
              <?php if ($pub['source']==='orcid'): ?><span class="src-badge">ORCID</span><?php endif; ?></span>
          </div>
          <form method="post" onsubmit="return confirm('<?= e(t('confirm_del')) ?>');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="del_pub">
            <input type="hidden" name="pub_id" value="<?= (int)$pub['id'] ?>">
            <button class="icon-btn" title="<?= e(t('delete')) ?>"><i class="fas fa-trash"></i></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php require __DIR__ . '/footer.php'; ?>
