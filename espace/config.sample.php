<?php
/* =====================================================
   UMR-AMES — Configuration de l'espace chercheur
   -----------------------------------------------------
   1. Copiez ce fichier sous le nom  config.php
   2. Renseignez les identifiants de votre base MySQL
      (créée dans cPanel → "MySQL Databases")
   3. NE versionnez JAMAIS config.php (déjà ignoré par .gitignore)
   ===================================================== */

return [
    // --- Base de données MySQL (cPanel) ---
    'db_host' => 'localhost',
    'db_name' => 'umrames_portal',     // ex: umrames_portal
    'db_user' => 'umrames_app',        // utilisateur MySQL
    'db_pass' => 'CHANGEZ_MOI',        // mot de passe MySQL

    // --- Sécurité ---
    // Générez une longue chaîne aléatoire (ex: https://passwordsgenerator.net/)
    'app_secret' => 'CHANGEZ_MOI_PAR_UNE_LONGUE_CHAINE_ALEATOIRE',

    // --- Compte administrateur initial ---
    // Le 1er compte créé avec cet e-mail devient automatiquement admin et approuvé.
    'admin_email' => 'contact@umr-ames.mr',

    // --- Site ---
    'site_url'   => 'https://umr-ames.mr',
    'uploads_dir' => __DIR__ . '/../uploads/chercheurs', // dossier physique des photos
    'uploads_url' => '/uploads/chercheurs',              // URL publique des photos
];
