<?php
/* Internationalisation du portail (FR/EN) */

function portal_lang(): string {
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $allowed = ['fr', 'en'];
    if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
        $_SESSION['plang'] = $_GET['lang'];
        setcookie('plang', $_GET['lang'], time() + 31536000, '/');
        return $_GET['lang'];
    }
    if (!empty($_SESSION['plang']) && in_array($_SESSION['plang'], $allowed, true)) return $_SESSION['plang'];
    if (!empty($_COOKIE['plang']) && in_array($_COOKIE['plang'], $allowed, true)) return $_COOKIE['plang'];
    return 'fr';
}

function t(string $key): string {
    static $S = null;
    if ($S === null) $S = i18n_strings();
    $l = portal_lang();
    return $S[$l][$key] ?? ($S['fr'][$key] ?? $key);
}

/* Construit une URL en conservant la langue courante */
function lang_url(string $path): string {
    $l = portal_lang();
    $sep = (strpos($path, '?') !== false) ? '&' : '?';
    return $path . $sep . 'lang=' . $l;
}

function i18n_strings(): array {
    return [
    'fr' => [
        // Header / nav
        'dashboard' => 'Tableau de bord', 'admin' => 'Administration', 'my_page' => 'Ma page',
        'logout' => 'Déconnexion', 'login' => 'Connexion', 'create_account' => 'Créer un compte',
        'back_site' => 'Site', 'back_members' => 'Membres',
        'footer_line' => 'UMR-AMES — Espace chercheur', 'back_home' => 'Retour au site', 'home' => 'Accueil',
        // Connexion
        'login_title' => 'Connexion', 'login_sub' => 'Accédez à votre espace chercheur.',
        'email' => 'Adresse e-mail', 'password' => 'Mot de passe', 'login_btn' => 'Se connecter',
        'no_account' => 'Pas encore de compte ?', 'err_login' => 'E-mail ou mot de passe incorrect.',
        'err_suspended' => "Ce compte a été suspendu. Contactez l'administration.",
        // Inscription
        'reg_title' => 'Créer un compte chercheur',
        'reg_sub' => 'Réservé aux membres de l\'UMR-AMES (adresse @umr-ames.mr). Votre compte sera validé par l\'administration.',
        'full_name' => 'Nom complet', 'password8' => 'Mot de passe (8+ caractères)',
        'password_confirm' => 'Confirmer le mot de passe', 'reg_btn' => 'Créer mon compte',
        'already' => 'Déjà inscrit ?',
        'err_name' => 'Indiquez votre nom complet.', 'err_email' => 'Adresse e-mail invalide.',
        'err_domain' => "Inscription réservée aux adresses @umr-ames.mr. Contactez l'administration pour obtenir une adresse institutionnelle.",
        'err_pass8' => 'Le mot de passe doit faire au moins 8 caractères.',
        'err_pass_match' => 'Les deux mots de passe ne correspondent pas.',
        'err_exists' => 'Un compte existe déjà avec cette adresse.',
        'ok_pending' => "Compte créé ! Il doit être validé par l'administration avant d'apparaître publiquement. Vous pouvez déjà compléter votre profil.",
        'ok_admin' => 'Compte administrateur créé et activé.',
        // Dashboard
        'hello' => 'Bonjour', 'pending_notice' => "Votre compte est <strong>en attente de validation</strong> par l'administration. Votre page ne sera publique qu'après approbation.",
        'my_profile' => 'Mon profil', 'photo_label' => 'Photo de profil (JPG/PNG/WebP, 4 Mo max)',
        'grade' => 'Grade / Statut', 'affiliation' => 'Affiliation', 'discipline' => 'Discipline',
        'axis' => 'Axe de recherche', 'bio' => 'Biographie', 'public_email' => 'E-mail public', 'phone' => 'Téléphone',
        'axes_unit' => 'Axes de recherche de l\'unité', 'axes_other' => 'Autres axes de recherche',
        'axes_other_ph' => 'Séparez par des virgules : ex. Optimisation, Géostatistique',
        'err_db_migration' => 'Échec de l\'enregistrement. La base doit être mise à jour (colonne research_axes manquante). Contactez l\'administrateur.',
        'ext_ids' => 'Identifiants & profils externes', 'website' => 'Site web personnel (URL)',
        'save_profile' => 'Enregistrer le profil', 'my_pubs' => 'Mes publications',
        'orcid_import' => 'Importer depuis ORCID', 'import' => 'Importer',
        'orcid_help' => 'Récupère automatiquement vos publications déclarées sur ORCID.',
        'add_pub_manual' => 'Ajouter une publication manuellement', 'title' => 'Titre', 'authors' => 'Auteurs',
        'journal' => 'Revue / Journal', 'year' => 'Année', 'link' => 'Lien (URL)', 'add' => 'Ajouter',
        'no_pubs' => "Aucune publication pour l'instant.", 'confirm_del' => 'Supprimer cette publication ?',
        'delete' => 'Supprimer',
        'ok_profile' => 'Profil enregistré.', 'err_pub_email' => 'Adresse e-mail publique invalide.',
        'err_pub_title' => 'Le titre est obligatoire.', 'ok_pub_added' => 'Publication ajoutée.',
        'ok_pub_deleted' => 'Publication supprimée.',
        'err_photo_format' => 'La photo doit être au format JPG, PNG ou WebP.',
        'err_photo_size' => 'La photo ne doit pas dépasser 4 Mo.',
        'orcid_done' => 'Import ORCID terminé : %d publication(s) importée(s) sur %d.',
        'orcid_invalid' => 'Identifiant ORCID invalide (format attendu : 0000-0000-0000-0000).',
        'orcid_unreachable' => 'Impossible de contacter ORCID. Réessayez plus tard.',
        'orcid_none' => 'Aucune publication trouvée pour cet ORCID.',
        // Admin
        'admin_title' => 'Administration des comptes', 'admin_sub' => 'Validez les nouveaux comptes pour les rendre publics.',
        'col_name' => 'Nom', 'col_email' => 'E-mail', 'col_role' => 'Rôle', 'col_status' => 'Statut', 'col_actions' => 'Actions',
        'approve' => 'Approuver', 'suspend' => 'Suspendre', 'you' => 'vous', 'status_updated' => 'Statut mis à jour.',
        // Profil public
        'not_found' => 'Chercheur introuvable', 'not_found_txt' => "Cette page n'existe pas ou n'est pas encore publiée.",
        'see_members' => 'Voir les membres', 'private_preview' => "Aperçu privé — ce profil n'est pas encore validé publiquement.",
        'publications' => 'Publications', 'no_pubs_public' => 'Aucune publication renseignée.',
    ],
    'en' => [
        'dashboard' => 'Dashboard', 'admin' => 'Administration', 'my_page' => 'My page',
        'logout' => 'Log out', 'login' => 'Log in', 'create_account' => 'Create account',
        'back_site' => 'Site', 'back_members' => 'Members',
        'footer_line' => 'UMR-AMES — Researcher area', 'back_home' => 'Back to site', 'home' => 'Home',
        'login_title' => 'Log in', 'login_sub' => 'Access your researcher area.',
        'email' => 'Email address', 'password' => 'Password', 'login_btn' => 'Log in',
        'no_account' => 'No account yet?', 'err_login' => 'Incorrect email or password.',
        'err_suspended' => 'This account has been suspended. Please contact the administration.',
        'reg_title' => 'Create a researcher account',
        'reg_sub' => 'Reserved for UMR-AMES members (@umr-ames.mr address). Your account will be approved by the administration.',
        'full_name' => 'Full name', 'password8' => 'Password (8+ characters)',
        'password_confirm' => 'Confirm password', 'reg_btn' => 'Create my account',
        'already' => 'Already registered?',
        'err_name' => 'Please enter your full name.', 'err_email' => 'Invalid email address.',
        'err_domain' => 'Registration restricted to @umr-ames.mr addresses. Contact the administration to obtain an institutional address.',
        'err_pass8' => 'Password must be at least 8 characters.',
        'err_pass_match' => 'The two passwords do not match.',
        'err_exists' => 'An account already exists with this address.',
        'ok_pending' => 'Account created! It must be approved by the administration before appearing publicly. You can already complete your profile.',
        'ok_admin' => 'Administrator account created and activated.',
        'hello' => 'Hello', 'pending_notice' => 'Your account is <strong>pending approval</strong> by the administration. Your page will be public only after approval.',
        'my_profile' => 'My profile', 'photo_label' => 'Profile photo (JPG/PNG/WebP, 4 MB max)',
        'grade' => 'Position / Status', 'affiliation' => 'Affiliation', 'discipline' => 'Discipline',
        'axis' => 'Research axis', 'bio' => 'Biography', 'public_email' => 'Public email', 'phone' => 'Phone',
        'axes_unit' => 'Unit research axes', 'axes_other' => 'Other research axes',
        'axes_other_ph' => 'Comma-separated, e.g. Optimization, Geostatistics',
        'err_db_migration' => 'Save failed. The database needs updating (missing research_axes column). Please contact the administrator.',
        'ext_ids' => 'Identifiers & external profiles', 'website' => 'Personal website (URL)',
        'save_profile' => 'Save profile', 'my_pubs' => 'My publications',
        'orcid_import' => 'Import from ORCID', 'import' => 'Import',
        'orcid_help' => 'Automatically retrieves your publications listed on ORCID.',
        'add_pub_manual' => 'Add a publication manually', 'title' => 'Title', 'authors' => 'Authors',
        'journal' => 'Journal', 'year' => 'Year', 'link' => 'Link (URL)', 'add' => 'Add',
        'no_pubs' => 'No publications yet.', 'confirm_del' => 'Delete this publication?',
        'delete' => 'Delete',
        'ok_profile' => 'Profile saved.', 'err_pub_email' => 'Invalid public email address.',
        'err_pub_title' => 'Title is required.', 'ok_pub_added' => 'Publication added.',
        'ok_pub_deleted' => 'Publication deleted.',
        'err_photo_format' => 'The photo must be JPG, PNG or WebP.',
        'err_photo_size' => 'The photo must not exceed 4 MB.',
        'orcid_done' => 'ORCID import complete: %d publication(s) imported out of %d.',
        'orcid_invalid' => 'Invalid ORCID iD (expected format: 0000-0000-0000-0000).',
        'orcid_unreachable' => 'Could not reach ORCID. Please try again later.',
        'orcid_none' => 'No publications found for this ORCID.',
        'admin_title' => 'Account administration', 'admin_sub' => 'Approve new accounts to make them public.',
        'col_name' => 'Name', 'col_email' => 'Email', 'col_role' => 'Role', 'col_status' => 'Status', 'col_actions' => 'Actions',
        'approve' => 'Approve', 'suspend' => 'Suspend', 'you' => 'you', 'status_updated' => 'Status updated.',
        'not_found' => 'Researcher not found', 'not_found_txt' => 'This page does not exist or is not published yet.',
        'see_members' => 'See members', 'private_preview' => 'Private preview — this profile is not publicly approved yet.',
        'publications' => 'Publications', 'no_pubs_public' => 'No publications listed.',
    ],
    ];
}
