<?php
/* =====================================================
   Liste de référence des membres de l'UMR-AMES.
   -----------------------------------------------------
   Source : la liste publiée sur le site (37 personnes).
   Le statut « permanent » provient du document officiel
   « Liste des membres permanents de l'UMR-AMES » : les
   11 personnes qui y figurent sont permanentes, toutes
   les autres sont associées.
   Pour ces 11 personnes, l'établissement, le grade, la
   spécialité et les coordonnées sont repris tels quels
   du document officiel ; pour les associés, ce sont les
   informations affichées sur le site.
   ===================================================== */

function membres_list(): array {
    return [
        // ---- Membres permanents (document officiel) ----
        ['name'=>'Marbe Begnoug',                  'estab'=>'ISGI',      'grade'=>'MC',   'spec'=>'Maths appliquées',      'phone'=>'44807010', 'email'=>'benioug@gmail.com',      'permanent'=>true,  'phd'=>false],
        ['name'=>'Zeinebou Zoubeir',               'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Informatique',          'phone'=>'26003996', 'email'=>'mzeinebou@gmail.com',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Aziza Ahmedou',                  'estab'=>'ISGI',      'grade'=>'MC',   'spec'=>'Statistiques',          'phone'=>'36620383', 'email'=>'ahmedouaziza@yahoo.fr',  'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Saad Bouh Elemine Vall', 'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Maths appliquées',      'phone'=>'48154130', 'email'=>'saadbouh@iup.e-una.mr',  'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed El Hacen Dilla Bouna',   'estab'=>'ISGI',      'grade'=>'PH',   'spec'=>'Informatique',          'phone'=>'43486485', 'email'=>'mohdyla@gmail.com',      'permanent'=>true,  'phd'=>false],
        ['name'=>'Yahya Mohamed',                  'estab'=>'FEG',       'grade'=>'MA',   'spec'=>'Maths appliquées',      'phone'=>'38198138', 'email'=>'yahyajidou@yahoo.fr',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Bedin Mohamed Lemine Kerim',     'estab'=>'FEG',       'grade'=>'MA',   'spec'=>'Statistiques',          'phone'=>'27578141', 'email'=>'bedine@Univ-nkc.mr',     'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Douh Begnoug',           'estab'=>'FST',       'grade'=>'PH',   'spec'=>'Mathématiques',         'phone'=>'27271057', 'email'=>'mdouh2001@yahoo.com',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Lemine Abdel Vettah',    'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'34263435', 'email'=>'Medlemineb6@gmail.com',  'permanent'=>true,  'phd'=>true],
        ['name'=>'Sidi Mohamed Ahmed Ramdhane',    'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'',         'email'=>'',                       'permanent'=>true,  'phd'=>true],
        ['name'=>'Hamza Mohmed Lemine',            'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'',         'email'=>'',                       'permanent'=>true,  'phd'=>true],

        // ---- Membres associés : chercheurs ----
        ['name'=>'Mohamed Ahmed Sambe',            'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Jyda Moustapha',                 'estab'=>'FEG / UN',  'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mohamed Hemmidy',                'estab'=>'FST / UN',  'grade'=>'MA',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'El Banany Mohamed Mahmoud',      'estab'=>'FST / UN',  'grade'=>'MA',   'spec'=>'Informatique',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mouna Hadrami Saleck',           'estab'=>'ENS',       'grade'=>'MA',   'spec'=>'Biologie',                 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Dia Mamadou',                    'estab'=>'IMROP',     'grade'=>'Cher', 'spec'=>'Environnement',            'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'El Hacen Mohamed El Hacen',      'estab'=>'PNBA',      'grade'=>'Cher', 'spec'=>'Environnement',            'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Marième Denna',                  'estab'=>'ONISPA',    'grade'=>'Cher', 'spec'=>'Environnement',            'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Ahmed Ahmed',                    'estab'=>'ISGI',      'grade'=>'Cher', 'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Ghoulam Mohamed Mahmoud',        'estab'=>'ISGI',      'grade'=>'MTEC', 'spec'=>'Informatique',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mohamed Elgheith Ledhem',        'estab'=>'ISGI',      'grade'=>'MTEC', 'spec'=>'Informatique',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Enne Benhmeida',                 'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Moustapha Saleck',               'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Informatique / IA',        'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mohamed Ahmed Sidi Cheikh',      'estab'=>'CDD',       'grade'=>'Exp',  'spec'=>'Géomatique',               'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Khadijetou El Heda',             'estab'=>'FEG / UN',  'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Hasna Hmoyed',                   'estab'=>'FEG / UN',  'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Ahmed Mohameden',                'estab'=>'FST / UN',  'grade'=>'MA',   'spec'=>'Informatique',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Abdoul Samba Ndongo',            'estab'=>'ISCAE',     'grade'=>'MA',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mariem Jidou Khayar',            'estab'=>'ESP',       'grade'=>'MA',   'spec'=>'—',                        'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Abdallahi Ahmedou Mohamed Lemine','estab'=>'FST / UN', 'grade'=>'EM',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Mohamed Lemine Mohamed',         'estab'=>'FST / UN',  'grade'=>'EM',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Lemhaba Yarba Ahmed Mahmoud',    'estab'=>'ENS',       'grade'=>'PH',   'spec'=>'Biologie',                 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],

        // ---- Membres associés : doctorants ----
        ['name'=>'Mariem M. A. Mohamed Sultane',   'estab'=>'FST / UN',  'grade'=>'DCR',  'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>true],
        ['name'=>'Ahmed Jidou Mohamed Lemine El Bechir','estab'=>'FST / UN','grade'=>'DCR','spec'=>'Mathématiques Appliquées','phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>true],
        ['name'=>'Zeineb Mohamed Mahmoud',         'estab'=>'FST / UN',  'grade'=>'DCR',  'spec'=>'Informatique',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>true],
        ['name'=>'Lalla Boulahe Chadade',          'estab'=>'FST / UN',  'grade'=>'DCR',  'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>true],
    ];
}

/* Normalisation d'un nom pour le rapprochement entre documents
   (accents, casse, espaces multiples). */
function membre_key(string $name): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
    return trim($s);
}

/**
 * Coordonnées relevées dans « Liste finale » (PDF annoté et .docx).
 * Clé = nom normalisé ; sert à renseigner les membres dont le téléphone
 * ou l'e-mail manquait. Les noms sont ceux de notre liste, y compris
 * lorsqu'ils s'écrivent différemment dans les documents source.
 */
function membres_contacts(): array {
    return [
        'marbe begnoug'                          => ['44807010', 'benioug@gmail.com'],
        'mohamed ahmed sambe'                    => ['30474221', 'bbaba2012@gmail.com'],
        'zeinebou zoubeir'                       => ['26003996', 'mzeinebou@gmail.com'],
        'aziza ahmedou'                          => ['36620383', 'ahmedouaziza@yahoo.fr'],
        'mohamed saad bouh elemine vall'         => ['48154130', 'saadbouh@iup.e-una.mr'],
        'mohamed elgheith ledhem'                => ['20559927', 'ovadel@gmail.com'],
        'enne benhmeida'                         => ['36299944', 'enne_sidaty@yahoo.fr'],
        'mohamed el hacen dilla bouna'           => ['43486485', 'mohdyla@gmail.com'],
        'lemhaba yarba ahmed mahmoud'            => ['4676048',  'ouldyarba@yahoo.fr'],
        'mohamed ahmed sidi cheikh'              => ['20081636', 'ouldsidicheikh@gmail.com'],
        'jyda moustapha'                         => ['31737945', 'jyda.mintmoustapha@gmail.com'],
        'khadijetou el heda'                     => ['26145513', 'khatouahmed@yahoo.fr'],
        'yahya mohamed'                          => ['38198138', 'yahyajidou@yahoo.fr'],
        'bedin mohamed lemine kerim'             => ['27578141', 'bedine@Univ-nkc.mr'],
        'hasna hmoyed'                           => ['49067003', 'hasnaahmedou@yahoo.fr'],
        'mohamed hemmidy'                        => ['42170101', 'mohamed.hemmidy@gmail.com'],
        'el banany mohamed mahmoud'              => ['49952342', 'medmhdbennannu@gmail.com'],
        'ahmed mohameden'                        => ['41666514', 'amed.mohameden@gmail.com'],
        'abdoul samba ndongo'                    => ['32675319', 'abdoul_ndongo@hotmail.com'],
        'mariem jidou khayar'                    => ['36252021', 'Mariem_jidou@hotmail.com'],
        'mariem m a mohamed sultane'             => ['32123214', 'rimsultan4@gmail.com'],
        'ahmed jidou mohamed lemine el bechir'   => ['48175967', 'ajidou35@gmail.com'],
        'abdallahi ahmedou mohamed lemine'       => ['20835363', 'daddahabdallahi@gmail.com'],
        'mohamed lemine mohamed'                 => ['34263435', 'medlemineb6@gmail.com'],
        'zeineb mohamed mahmoud'                 => ['20200019', 'zeynebouelmehdi@gmail.com'],
        'lalla boulahe chadade'                  => ['27177697', 'lalaboulahe@gmail.com'],
        'mohamed douh begnoug'                   => ['27271057', 'mdouh2001@yahoo.com'],
        'mohamed lemine abdel vettah'            => ['34263435', 'Medlemineb6@gmail.com'],
    ];
}

/**
 * Liste effective : celle de la base si la table existe (l'administration
 * peut y ajouter ou en retirer des membres), sinon la liste de référence
 * ci-dessus. Le repli évite une page blanche avant l'exécution de migrate.php.
 */
/** Complète téléphone et e-mail manquants depuis membres_contacts(). */
function membres_fill_contacts(array $list): array {
    $c = membres_contacts();
    foreach ($list as &$m) {
        $k = membre_key($m['name']);
        if (!isset($c[$k])) continue;
        if (empty($m['phone'])) $m['phone'] = $c[$k][0];
        if (empty($m['email'])) $m['email'] = $c[$k][1];
    }
    return $list;
}

function membres_all(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    // config() interrompt l'exécution si le fichier manque : on vérifie d'abord
    if (function_exists('db') && file_exists(__DIR__ . '/config.php')) {
        try {
            $rows = db()->query(
                'SELECT name, estab, grade, spec, phone, email, is_permanent, is_phd, id
                 FROM unit_members ORDER BY is_permanent DESC, sort_order, id'
            )->fetchAll();
            if ($rows) {
                $cache = array_map(fn($r) => [
                    'id'        => (int)$r['id'],
                    'name'      => $r['name'],
                    'estab'     => $r['estab'] ?? '',
                    'grade'     => $r['grade'] ?? '',
                    'spec'      => $r['spec'] ?? '',
                    'phone'     => $r['phone'] ?? '',
                    'email'     => $r['email'] ?? '',
                    'permanent' => (bool)$r['is_permanent'],
                    'phd'       => (bool)$r['is_phd'],
                ], $rows);
                return $cache;
            }
        } catch (Throwable $e) { /* table absente : repli sur la liste de référence */ }
    }
    $cache = membres_fill_contacts(membres_list());
    return $cache;
}

/** Membres permanents (ordre du document officiel). */
function membres_permanents(): array {
    return array_values(array_filter(membres_all(), fn($m) => $m['permanent']));
}

/** Membres associés (tous les autres chercheurs et doctorants). */
function membres_associes(): array {
    return array_values(array_filter(membres_all(), fn($m) => !$m['permanent']));
}

/** Légende des abréviations de grade. */
function membres_abbrev(): string {
    return 'PH — Professeur habilité ; MC — Maître de conférences ; MA — Maître-assistant(e) ; '
         . 'Cher — Chercheur/Chercheuse ; MTEC — Maître technologue ; Exp — Expert ; '
         . 'DCR — Doctorant(e) ; EM — Étudiant(e) de master.';
}
