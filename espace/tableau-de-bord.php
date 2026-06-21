<?php
require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/orcid.php';
$me = require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---- Enregistrer le profil ---- */
    if ($action === 'save_profile') {
        $fields = [
            'title'            => trim($_POST['title'] ?? ''),
            'affiliation'      => trim($_POST['affiliation'] ?? ''),
            'discipline'       => trim($_POST['discipline'] ?? ''),
            'axis'             => (array_key_exists($_POST['axis'] ?? '', axes()) ? $_POST['axis'] : null),
            'bio'              => trim($_POST['bio'] ?? ''),
            'phone'            => trim($_POST['phone'] ?? ''),
            'public_email'     => trim($_POST['public_email'] ?? ''),
            'orcid'            => trim($_POST['orcid'] ?? ''),
            'researchgate_url' => trim($_POST['researchgate_url'] ?? ''),
            'scholar_url'      => trim($_POST['scholar_url'] ?? ''),
            'linkedin_url'     => trim($_POST['linkedin_url'] ?? ''),
            'website_url'      => trim($_POST['website_url'] ?? ''),
        ];
        if ($fields['public_email'] && !filter_var($fields['public_email'], FILTER_VALIDATE_EMAIL)) {
            flash("Adresse e-mail publique invalide.", 'error');
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
                flash("La photo doit être au format JPG, PNG ou WebP.", 'error');
                header('Location: tableau-de-bord.php'); exit;
            }
            if ($_FILES['photo']['size'] > 4 * 1024 * 1024) {
                flash("La photo ne doit pas dépasser 4 Mo.", 'error');
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

        $sql = 'UPDATE profiles SET title=?, affiliation=?, discipline=?, axis=?, bio=?, phone=?, public_email=?, orcid=?, researchgate_url=?, scholar_url=?, linkedin_url=?, website_url=?' . $photoSql . ' WHERE researcher_id=?';
        $vals = array_merge(array_values($fields), $photoVal, [$me['id']]);
        $pdo->prepare($sql)->execute($vals);
        flash("Profil enregistré.", 'success');
        header('Location: tableau-de-bord.php'); exit;
    }

    /* ---- Ajouter une publication ---- */
    if ($action === 'add_pub') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') { flash("Le titre est obligatoire.", 'error'); header('Location: tableau-de-bord.php#pubs'); exit; }
        $year = (int)($_POST['year'] ?? 0); $year = ($year >= 1950 && $year <= (int)date('Y')+1) ? $year : null;
        $axis = (array_key_exists($_POST['axis'] ?? '', axes()) ? $_POST['axis'] : null);
        $url  = trim($_POST['url'] ?? ''); if ($url && !preg_match('~^https?://~i',$url)) $url = 'https://'.$url;
        $pdo->prepare('INSERT INTO publications (researcher_id,title,authors,journal,year,doi,url,axis,source) VALUES (?,?,?,?,?,?,?,?,\'manual\')')
            ->execute([$me['id'], mb_substr($title,0,500), mb_substr(trim($_POST['authors']??''),0,500) ?: null,
                       mb_substr(trim($_POST['journal']??''),0,300) ?: null, $year,
                       mb_substr(trim($_POST['doi']??''),0,120) ?: null, $url ? mb_substr($url,0,400):null, $axis]);
        flash("Publication ajoutée.", 'success');
        header('Location: tableau-de-bord.php#pubs'); exit;
    }

    /* ---- Supprimer une publication ---- */
    if ($action === 'del_pub') {
        $pid = (int)($_POST['pub_id'] ?? 0);
        $pdo->prepare('DELETE FROM publications WHERE id=? AND researcher_id=?')->execute([$pid, $me['id']]);
        flash("Publication supprimée.", 'success');
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
        if ($err) flash($err, 'error');
        else {
            $norm = orcid_normalize($orcid);
            if ($norm) $pdo->prepare('UPDATE profiles SET orcid=? WHERE researcher_id=?')->execute([$norm, $me['id']]);
            flash("Import ORCID terminé : $imp publication(s) importée(s) sur $tot.", 'success');
        }
        header('Location: tableau-de-bord.php#pubs'); exit;
    }
}

// Données
$st = $pdo->prepare('SELECT * FROM profiles WHERE researcher_id=?'); $st->execute([$me['id']]); $p = $st->fetch() ?: [];
$st = $pdo->prepare('SELECT * FROM publications WHERE researcher_id=? ORDER BY year DESC, id DESC'); $st->execute([$me['id']]); $pubs = $st->fetchAll();
$cfg = config();
$photoUrl = !empty($p['photo']) ? $cfg['uploads_url'].'/'.$p['photo'] : null;

$page_title = 'Tableau de bord';
require __DIR__ . '/header.php';
?>
<h1 class="portal-h1">Bonjour, <?= e($me['full_name']) ?></h1>
<?php if ($me['status'] === 'pending'): ?>
  <div class="flash flash-info">Votre compte est <strong>en attente de validation</strong> par l'administration. Votre page ne sera publique qu'après approbation.</div>
<?php endif; ?>

<div class="dash-grid">
  <!-- PROFIL -->
  <section class="dash-card">
    <h2 class="portal-h2"><i class="fas fa-id-card"></i> Mon profil</h2>
    <form method="post" enctype="multipart/form-data" class="portal-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_profile">

      <div class="photo-row">
        <div class="photo-preview">
          <?php if ($photoUrl): ?><img src="<?= e($photoUrl) ?>" alt="Photo"><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
        </div>
        <div class="form-group" style="flex:1">
          <label>Photo de profil (JPG/PNG/WebP, 4 Mo max)</label>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group"><label>Grade / Statut</label><input type="text" name="title" value="<?= e($p['title']??'') ?>" placeholder="Professeur, Maître de conférences, Doctorant…"></div>
        <div class="form-group"><label>Affiliation</label><input type="text" name="affiliation" value="<?= e($p['affiliation']??'') ?>" placeholder="ISGI, Université de Nouakchott…"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Discipline</label><input type="text" name="discipline" value="<?= e($p['discipline']??'') ?>"></div>
        <div class="form-group"><label>Axe de recherche</label>
          <select name="axis">
            <option value="">—</option>
            <?php foreach (axes() as $k=>$lbl): ?>
              <option value="<?= e($k) ?>" <?= (($p['axis']??'')===$k?'selected':'') ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Biographie</label><textarea name="bio" rows="5" placeholder="Parcours, thèmes de recherche, responsabilités…"><?= e($p['bio']??'') ?></textarea></div>

      <div class="form-row">
        <div class="form-group"><label>E-mail public</label><input type="email" name="public_email" value="<?= e($p['public_email']??'') ?>"></div>
        <div class="form-group"><label>Téléphone</label><input type="text" name="phone" value="<?= e($p['phone']??'') ?>"></div>
      </div>

      <h3 class="portal-h3">Identifiants & profils externes</h3>
      <div class="form-row">
        <div class="form-group"><label>ORCID</label><input type="text" name="orcid" value="<?= e($p['orcid']??'') ?>" placeholder="0000-0000-0000-0000"></div>
        <div class="form-group"><label>Google Scholar (URL)</label><input type="text" name="scholar_url" value="<?= e($p['scholar_url']??'') ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>ResearchGate (URL)</label><input type="text" name="researchgate_url" value="<?= e($p['researchgate_url']??'') ?>"></div>
        <div class="form-group"><label>LinkedIn (URL)</label><input type="text" name="linkedin_url" value="<?= e($p['linkedin_url']??'') ?>"></div>
      </div>
      <div class="form-group"><label>Site web personnel (URL)</label><input type="text" name="website_url" value="<?= e($p['website_url']??'') ?>"></div>

      <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Enregistrer le profil</button>
    </form>
  </section>

  <!-- PUBLICATIONS -->
  <section class="dash-card" id="pubs">
    <h2 class="portal-h2"><i class="fas fa-book"></i> Mes publications (<?= count($pubs) ?>)</h2>

    <div class="orcid-box">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_orcid">
        <label>Importer depuis ORCID</label>
        <div class="orcid-row">
          <input type="text" name="orcid" value="<?= e($p['orcid']??'') ?>" placeholder="0000-0000-0000-0000">
          <button class="btn btn-dark" type="submit"><i class="fas fa-cloud-arrow-down"></i> Importer</button>
        </div>
        <small>Récupère automatiquement vos publications déclarées sur ORCID.</small>
      </form>
    </div>

    <details class="add-pub">
      <summary><i class="fas fa-plus"></i> Ajouter une publication manuellement</summary>
      <form method="post" class="portal-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_pub">
        <div class="form-group"><label>Titre *</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Auteurs</label><input type="text" name="authors" placeholder="A. Ahmed, M. Benioug…"></div>
        <div class="form-row">
          <div class="form-group"><label>Revue / Journal</label><input type="text" name="journal"></div>
          <div class="form-group"><label>Année</label><input type="number" name="year" min="1950" max="<?= (int)date('Y')+1 ?>"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>DOI</label><input type="text" name="doi" placeholder="10.xxxx/xxxxx"></div>
          <div class="form-group"><label>Axe</label>
            <select name="axis"><option value="">—</option>
              <?php foreach (axes() as $k=>$lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Lien (URL)</label><input type="text" name="url"></div>
        <button class="btn btn-primary" type="submit">Ajouter</button>
      </form>
    </details>

    <ul class="dash-pub-list">
      <?php if (!$pubs): ?><li class="empty">Aucune publication pour l'instant.</li><?php endif; ?>
      <?php foreach ($pubs as $pub): ?>
        <li>
          <div class="dash-pub-main">
            <span class="dash-pub-title"><?= e($pub['title']) ?></span>
            <span class="dash-pub-meta"><?= e(trim(($pub['journal']?:'').' '.($pub['year']?'· '.$pub['year']:''))) ?>
              <?php if ($pub['source']==='orcid'): ?><span class="src-badge">ORCID</span><?php endif; ?></span>
          </div>
          <form method="post" onsubmit="return confirm('Supprimer cette publication ?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="del_pub">
            <input type="hidden" name="pub_id" value="<?= (int)$pub['id'] ?>">
            <button class="icon-btn" title="Supprimer"><i class="fas fa-trash"></i></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php require __DIR__ . '/footer.php'; ?>
