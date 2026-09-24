-- Mise à niveau non destructive d'une base PORTFOLIO déjà importée.
-- À exécuter une seule fois dans phpMyAdmin après l'import du dump initial.
USE `PORTFOLIO`;

CREATE TABLE IF NOT EXISTS `limite_formulaire` (
  `ip_anonymisee` varchar(45) NOT NULL,
  `last_submission` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_anonymisee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tentative_connexion` (
  `id_tentative` int NOT NULL AUTO_INCREMENT,
  `type_compte` enum('administrateur','client') NOT NULL,
  `email` varchar(150) NOT NULL,
  `ip_anonymisee` varchar(45) NOT NULL,
  `tentatives` tinyint unsigned NOT NULL DEFAULT '0',
  `dernier_echec` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verrouille_jusqu_a` datetime DEFAULT NULL,
  PRIMARY KEY (`id_tentative`),
  UNIQUE KEY `uq_tentative_compte_ip` (`type_compte`,`email`,`ip_anonymisee`),
  KEY `idx_tentative_verrouillage` (`verrouille_jusqu_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `projet` (`titre`, `description`, `stack_technique`, `ordre_affichage`)
SELECT 'Pressing Manager', 'Plateforme métier de gestion de pressing avec rôles différenciés propriétaire/gérant. Elle centralise les commandes, les paiements et les statuts afin de rendre le suivi quotidien plus fiable et plus rapide.', 'PHP natif, MySQL, HTML5, CSS3, JavaScript', 1
WHERE NOT EXISTS (SELECT 1 FROM `projet` WHERE `titre` = 'Pressing Manager');

INSERT INTO `projet` (`titre`, `description`, `stack_technique`, `ordre_affichage`)
SELECT 'Artisan Connect', 'Site vitrine orienté conversion conçu pour présenter une expertise artisanale, mettre en valeur les réalisations et faciliter les demandes de devis sur mobile.', 'HTML5, CSS3, JavaScript, SEO local', 2
WHERE NOT EXISTS (SELECT 1 FROM `projet` WHERE `titre` = 'Artisan Connect');

INSERT INTO `projet` (`titre`, `description`, `stack_technique`, `ordre_affichage`)
SELECT 'Market Flow', 'Prototype e-commerce mobile-first pensé pour rendre le catalogue lisible, rassurer les visiteurs et simplifier le passage de la découverte à la commande.', 'PHP natif, MySQL, JavaScript, UX', 3
WHERE NOT EXISTS (SELECT 1 FROM `projet` WHERE `titre` = 'Market Flow');


-- Gestion sûre des projets : brouillon, publication et archivage.
ALTER TABLE `projet`
  ADD COLUMN `statut_publication` ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'publie' AFTER `ordre_affichage`,
  ADD COLUMN `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
