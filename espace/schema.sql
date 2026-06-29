-- =====================================================
-- UMR-AMES — Espace chercheur : schéma de base de données
-- MySQL / MariaDB (cPanel)
-- À exécuter une seule fois via phpMyAdmin (onglet SQL).
-- =====================================================

SET NAMES utf8mb4;

-- Comptes chercheurs
CREATE TABLE IF NOT EXISTS researchers (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(150) NOT NULL,
  first_name     VARCHAR(80)  DEFAULT NULL,
  last_name      VARCHAR(80)  DEFAULT NULL,
  slug           VARCHAR(170) NOT NULL UNIQUE,
  role           ENUM('researcher','admin') NOT NULL DEFAULT 'researcher',
  status         ENUM('pending','approved','suspended') NOT NULL DEFAULT 'pending',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profils (1-1 avec researchers)
CREATE TABLE IF NOT EXISTS profiles (
  researcher_id    INT PRIMARY KEY,
  photo            VARCHAR(255) DEFAULT NULL,
  title            VARCHAR(120) DEFAULT NULL,   -- grade : Professeur, Maître de conf., Doctorant…
  affiliation      VARCHAR(160) DEFAULT NULL,   -- ISGI, Université de Nouakchott…
  discipline       VARCHAR(160) DEFAULT NULL,
  axis             VARCHAR(60)  DEFAULT NULL,    -- axe principal : env | sante | math | ia
  research_axes    TEXT,                          -- liste JSON des axes (unité + libres)
  name_clickable   TINYINT(1) NOT NULL DEFAULT 1, -- nom cliquable sur le site
  citations        INT          DEFAULT NULL,     -- bibliométrie (OpenAlex via ORCID)
  h_index          INT          DEFAULT NULL,
  i10_index        INT          DEFAULT NULL,
  metrics_manual   TINYINT(1) NOT NULL DEFAULT 0, -- 1 = indicateurs saisis à la main
  metrics_updated_at DATETIME   DEFAULT NULL,
  bio              TEXT,
  phone            VARCHAR(60)  DEFAULT NULL,
  public_email     VARCHAR(190) DEFAULT NULL,
  orcid            VARCHAR(40)  DEFAULT NULL,
  researchgate_url VARCHAR(255) DEFAULT NULL,
  scholar_url      VARCHAR(255) DEFAULT NULL,
  linkedin_url     VARCHAR(255) DEFAULT NULL,
  website_url      VARCHAR(255) DEFAULT NULL,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_researcher FOREIGN KEY (researcher_id)
    REFERENCES researchers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publications
CREATE TABLE IF NOT EXISTS publications (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  researcher_id INT NOT NULL,
  title         VARCHAR(500) NOT NULL,
  authors       VARCHAR(500) DEFAULT NULL,
  journal       VARCHAR(300) DEFAULT NULL,
  year          SMALLINT     DEFAULT NULL,
  doi           VARCHAR(120) DEFAULT NULL,
  url           VARCHAR(400) DEFAULT NULL,
  axis          VARCHAR(60)  DEFAULT NULL,     -- env | sante | math | ia
  source        ENUM('manual','orcid') NOT NULL DEFAULT 'manual',
  external_id   VARCHAR(120) DEFAULT NULL,     -- put-code ORCID (anti-doublon)
  ames_affiliation TINYINT(1) NULL,            -- NULL=à vérifier, 1=oui, 0=non
  ames_manual      TINYINT(1) NOT NULL DEFAULT 0,
  affiliation_raw  VARCHAR(500) NULL,
  ames_checked_at  DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pub_researcher FOREIGN KEY (researcher_id)
    REFERENCES researchers(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_orcid_work (researcher_id, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_pub_year ON publications (year);
CREATE INDEX idx_pub_axis ON publications (axis);

-- Réglages globaux (clé/valeur)
CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO settings (k, v) VALUES ('metrics_public', '1') ON DUPLICATE KEY UPDATE v = v;
INSERT INTO settings (k, v) VALUES ('publications_ames_only', '0') ON DUPLICATE KEY UPDATE v = v;
