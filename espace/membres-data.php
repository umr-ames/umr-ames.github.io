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
        ['name'=>'Marbe Begnoug',                  'estab'=>'IUP',       'grade'=>'MC',   'spec'=>'Maths appliquées',      'phone'=>'44807010', 'email'=>'benioug@gmail.com',      'permanent'=>true,  'phd'=>false],
        ['name'=>'Zeinebou Zoubeir',               'estab'=>'IUP',       'grade'=>'MA',   'spec'=>'Informatique',          'phone'=>'26003996', 'email'=>'mzeinebou@gmail.com',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Aziza Ahmedou',                  'estab'=>'IUP',       'grade'=>'MC',   'spec'=>'Statistiques',          'phone'=>'36620383', 'email'=>'ahmedouaziza@yahoo.fr',  'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Saad Bouh Elemine Vall', 'estab'=>'IUP',       'grade'=>'MA',   'spec'=>'Maths appliquées',      'phone'=>'48154130', 'email'=>'saadbouh@iup.e-una.mr',  'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed El Hacen Dilla Bouna',   'estab'=>'IUP',       'grade'=>'PH',   'spec'=>'Informatique',          'phone'=>'43486485', 'email'=>'mohdyla@gmail.com',      'permanent'=>true,  'phd'=>false],
        ['name'=>'Yahya Mohamed',                  'estab'=>'FSJE',      'grade'=>'MA',   'spec'=>'Maths appliquées',      'phone'=>'38198138', 'email'=>'yahyajidou@yahoo.fr',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Bedin Mohamed Lemine Kerim',     'estab'=>'FSJE',      'grade'=>'MA',   'spec'=>'Statistiques',          'phone'=>'27578141', 'email'=>'bedine@Univ-nkc.mr',     'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Douh Begnoug',           'estab'=>'FST',       'grade'=>'PH',   'spec'=>'Mathématiques',         'phone'=>'27271057', 'email'=>'mdouh2001@yahoo.com',    'permanent'=>true,  'phd'=>false],
        ['name'=>'Mohamed Lemine Abdel Vettah',    'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'34263435', 'email'=>'Medlemineb6@gmail.com',  'permanent'=>true,  'phd'=>true],
        ['name'=>'Sidi Mohamed Ahmed Ramdhane',    'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'',         'email'=>'',                       'permanent'=>true,  'phd'=>true],
        ['name'=>'Hamza Mohmed Lemine',            'estab'=>'FST',       'grade'=>'DCR',  'spec'=>'Maths appliquées',      'phone'=>'',         'email'=>'',                       'permanent'=>true,  'phd'=>true],

        // ---- Membres associés : chercheurs ----
        ['name'=>'Mohamed Ahmed Sambe',            'estab'=>'ISGI',      'grade'=>'MA',   'spec'=>'Mathématiques Appliquées', 'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Jyda Moustapha',                 'estab'=>'FSEG / UN', 'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
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
        ['name'=>'Khadijetou El Heda',             'estab'=>'FSEG / UN', 'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
        ['name'=>'Hasna Hmoyed',                   'estab'=>'FSEG / UN', 'grade'=>'MA',   'spec'=>'Statistiques',             'phone'=>'', 'email'=>'', 'permanent'=>false, 'phd'=>false],
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

/** Membres permanents (ordre du document officiel). */
function membres_permanents(): array {
    return array_values(array_filter(membres_list(), fn($m) => $m['permanent']));
}

/** Membres associés (tous les autres chercheurs et doctorants). */
function membres_associes(): array {
    return array_values(array_filter(membres_list(), fn($m) => !$m['permanent']));
}

/** Légende des abréviations de grade. */
function membres_abbrev(): string {
    return 'PH — Professeur habilité ; MC — Maître de conférences ; MA — Maître-assistant(e) ; '
         . 'Cher — Chercheur/Chercheuse ; MTEC — Maître technologue ; Exp — Expert ; '
         . 'DCR — Doctorant(e) ; EM — Étudiant(e) de master.';
}
