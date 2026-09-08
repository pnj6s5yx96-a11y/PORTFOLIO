-- ============================================================
-- Script SQL de création de la base de données
-- Projet : Site vitrine et portfolio professionnel
-- Porteur de projet : Michael AKAKPOSSE
-- Environnement cible : MySQL via MAMP
-- Basé sur le MCD/MLD du document "Modèle de Données v1.0"
-- ============================================================

-- 1. Création de la base
CREATE DATABASE IF NOT EXISTS site_vitrine_portfolio
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE site_vitrine_portfolio;

-- 2. Table ADMINISTRATEUR
CREATE TABLE administrateur (
  id_admin                    INT AUTO_INCREMENT PRIMARY KEY,
  nom                         VARCHAR(100) NOT NULL,
  email                       VARCHAR(150) NOT NULL UNIQUE,
  mot_de_passe_hash           VARCHAR(255) NOT NULL,
  tentatives_echouees         INT NOT NULL DEFAULT 0,
  verrouille_jusqu_a          DATETIME NULL
) ENGINE=InnoDB;

-- 3. Table CLIENT_PARTENAIRE
CREATE TABLE client_partenaire (
  id_client                   INT AUTO_INCREMENT PRIMARY KEY,
  nom                         VARCHAR(100) NOT NULL,
  email                       VARCHAR(150) NOT NULL UNIQUE,
  telephone                   VARCHAR(20),
  mot_de_passe_hash           VARCHAR(255) NOT NULL,
  date_creation               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tentatives_echouees         INT NOT NULL DEFAULT 0,
  verrouille_jusqu_a          DATETIME NULL
) ENGINE=InnoDB;

-- 4. Table PROJET
CREATE TABLE projet (
  id_projet         INT AUTO_INCREMENT PRIMARY KEY,
  titre             VARCHAR(150) NOT NULL,
  description       TEXT NOT NULL,
  stack_technique   VARCHAR(255) NOT NULL,
  lien_demo         VARCHAR(255),
  lien_repo         VARCHAR(255),
  image_url         VARCHAR(255),
  ordre_affichage   INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- 5. Table PAGE (référence PROJET pour les pages "détail-projet")
CREATE TABLE page (
  id_page    INT AUTO_INCREMENT PRIMARY KEY,
  nom        VARCHAR(100) NOT NULL,
  type       ENUM('statique', 'detail-projet') NOT NULL,
  id_projet  INT NULL,
  CONSTRAINT fk_page_projet FOREIGN KEY (id_projet)
    REFERENCES projet(id_projet) ON DELETE SET NULL,
  INDEX idx_page_projet (id_projet)
) ENGINE=InnoDB;

-- 6. Table VISITE (référence PAGE)
CREATE TABLE visite (
  id_visite       INT AUTO_INCREMENT PRIMARY KEY,
  date_heure      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_anonymisee   VARCHAR(45),
  id_page         INT NOT NULL,
  CONSTRAINT fk_visite_page FOREIGN KEY (id_page)
    REFERENCES page(id_page) ON DELETE CASCADE,
  INDEX idx_visite_page (id_page),
  INDEX idx_visite_date (date_heure)
) ENGINE=InnoDB;

-- 7. Table DEMANDE_CONTACT (référence CLIENT_PARTENAIRE une fois converti)
CREATE TABLE demande_contact (
  id_demande        INT AUTO_INCREMENT PRIMARY KEY,
  nom               VARCHAR(100) NOT NULL,
  email             VARCHAR(150) NOT NULL,
  telephone         VARCHAR(20),
  type_projet       VARCHAR(100),
  budget            ENUM('Moins de 500€', '500€ - 1000€', '1000€ - 3000€', '3000€ - 5000€', 'Plus de 5000€', 'Non précisé') DEFAULT 'Non précisé',
  message           TEXT NOT NULL,
  canal_origine     ENUM('formulaire', 'whatsapp') NOT NULL,
  date_soumission   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut            ENUM('Non traité', 'En cours', 'Converti', 'Sans suite') NOT NULL DEFAULT 'Non traité',
  id_client         INT NULL,
  CONSTRAINT fk_demande_client FOREIGN KEY (id_client)
    REFERENCES client_partenaire(id_client) ON DELETE SET NULL,
  INDEX idx_demande_client (id_client),
  INDEX idx_demande_statut (statut)
) ENGINE=InnoDB;

-- 8. Table NOTIFICATION (référence DEMANDE_CONTACT)
CREATE TABLE notification (
  id_notification  INT AUTO_INCREMENT PRIMARY KEY,
  canal            ENUM('email', 'whatsapp') NOT NULL,
  date_envoi       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut_envoi     ENUM('Envoyé', 'Échec') NOT NULL,
  id_demande       INT NOT NULL,
  CONSTRAINT fk_notification_demande FOREIGN KEY (id_demande)
    REFERENCES demande_contact(id_demande) ON DELETE CASCADE,
  INDEX idx_notification_demande (id_demande)
) ENGINE=InnoDB;

-- 9. Table EXPORT (référence ADMINISTRATEUR)
CREATE TABLE export (
  id_export         INT AUTO_INCREMENT PRIMARY KEY,
  type              ENUM('contacts', 'statistiques') NOT NULL,
  format            ENUM('CSV', 'PDF') NOT NULL,
  date_generation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_admin          INT NOT NULL,
  CONSTRAINT fk_export_admin FOREIGN KEY (id_admin)
    REFERENCES administrateur(id_admin) ON DELETE CASCADE,
  INDEX idx_export_admin (id_admin)
) ENGINE=InnoDB;

-- 10. Table JOURNAL_SECURITE (référence ADMINISTRATEUR, nullable pour évènement système)
CREATE TABLE journal_securite (
  id_journal   INT AUTO_INCREMENT PRIMARY KEY,
  action       VARCHAR(150) NOT NULL,
  date_heure   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip           VARCHAR(45),
  id_admin     INT NULL,
  CONSTRAINT fk_journal_admin FOREIGN KEY (id_admin)
    REFERENCES administrateur(id_admin) ON DELETE SET NULL,
  INDEX idx_journal_admin (id_admin)
) ENGINE=InnoDB;

-- 11. Table LIMITE_FORMULAIRE (anti-spam / limitation de fréquence par IP anonymisée)
CREATE TABLE limite_formulaire (
  ip_anonymisee   VARCHAR(45) PRIMARY KEY,
  last_submission DATETIME NOT NULL
) ENGINE=InnoDB;

-- 12. Table TENTATIVE_CONNEXION (protection anti-brute-force par compte et IP pseudonymisée)
CREATE TABLE tentative_connexion (
  id_tentative        INT AUTO_INCREMENT PRIMARY KEY,
  type_compte         ENUM('administrateur', 'client') NOT NULL,
  email               VARCHAR(150) NOT NULL,
  ip_anonymisee       VARCHAR(45) NOT NULL,
  tentatives          TINYINT UNSIGNED NOT NULL DEFAULT 0,
  dernier_echec       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verrouille_jusqu_a  DATETIME NULL,
  UNIQUE KEY uq_tentative_compte_ip (type_compte, email, ip_anonymisee),
  INDEX idx_tentative_verrouillage (verrouille_jusqu_a)
) ENGINE=InnoDB;

-- ============================================================
-- Remarques :
-- - InnoDB est utilisé pour le support des clés étrangères et des transactions.
-- - Les mots de passe ne sont JAMAIS stockés en clair : le hachage
--   (ex. password_hash() en PHP) doit être fait côté application avant insertion.
-- - Les champs ENUM (statut, canal, type, format, budget...) reflètent les
--   valeurs listées/décidées dans le dictionnaire de données. Adapter les
--   libellés si besoin avant l'implémentation définitive.
-- - Anti brute-force (RG-06) : tentatives_echouees s'incrémente à chaque
--   échec de connexion ; verrouille_jusqu_a se remplit quand le seuil est
--   atteint (ex. 5 tentatives). Côté application, bloquer la connexion tant
--   que NOW() < verrouille_jusqu_a, puis réinitialiser tentatives_echouees
--   à 0 après une connexion réussie ou l'expiration du verrou.
-- - Purge/archivage de VISITE : aucune contrainte de schéma ajoutée ici ;
--   à gérer via une tâche planifiée (ex. CRON MAMP/serveur) appliquant la
--   politique de rétention RGPD une fois définie avec le porteur de projet.
-- ============================================================
