-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : mar. 08 sep. 2026 à 09:35
-- Version du serveur : 8.0.44
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `PORTFOLIO`
--

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

CREATE TABLE `administrateur` (
  `id_admin` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `client_partenaire`
--

CREATE TABLE `client_partenaire` (
  `id_client` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `demande_contact`
--

CREATE TABLE `demande_contact` (
  `id_demande` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `type_projet` varchar(100) DEFAULT NULL,
  `budget` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `canal_origine` enum('formulaire','whatsapp') NOT NULL,
  `date_soumission` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('Non traité','En cours','Converti','Sans suite') NOT NULL DEFAULT 'Non traité',
  `id_client` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `export`
--

CREATE TABLE `export` (
  `id_export` int NOT NULL,
  `type` enum('contacts','statistiques') NOT NULL,
  `format` enum('CSV','PDF') NOT NULL,
  `date_generation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journal_securite`
--

CREATE TABLE `journal_securite` (
  `id_journal` int NOT NULL,
  `action` varchar(150) NOT NULL,
  `date_heure` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(45) DEFAULT NULL,
  `id_admin` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `id_notification` int NOT NULL,
  `canal` enum('email','whatsapp') NOT NULL,
  `date_envoi` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_envoi` enum('Envoyé','Échec') NOT NULL,
  `id_demande` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `page`
--

CREATE TABLE `page` (
  `id_page` int NOT NULL,
  `nom` varchar(100) NOT NULL,
  `type` enum('statique','detail-projet') NOT NULL,
  `id_projet` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `projet`
--

CREATE TABLE `projet` (
  `id_projet` int NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `stack_technique` varchar(255) NOT NULL,
  `lien_demo` varchar(255) DEFAULT NULL,
  `lien_repo` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `ordre_affichage` int NOT NULL DEFAULT '0',
  `statut_publication` enum('brouillon','publie','archive') NOT NULL DEFAULT 'publie',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `visite`
--

CREATE TABLE `visite` (
  `id_visite` int NOT NULL,
  `date_heure` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_anonymisee` varchar(45) DEFAULT NULL,
  `id_page` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Protection anti-spam du formulaire : une adresse IP pseudonymisée
-- ne peut pas soumettre plusieurs demandes dans un court délai.
--
CREATE TABLE `limite_formulaire` (
  `ip_anonymisee` varchar(45) NOT NULL,
  `last_submission` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_anonymisee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Protection contre les tentatives de connexion répétées.
-- Cette table évite de stocker des données de sécurité transitoires
-- dans les comptes administrateur et client.
--
CREATE TABLE `tentative_connexion` (
  `id_tentative` int NOT NULL,
  `type_compte` enum('administrateur','client') NOT NULL,
  `email` varchar(150) NOT NULL,
  `ip_anonymisee` varchar(45) NOT NULL,
  `tentatives` tinyint unsigned NOT NULL DEFAULT '0',
  `dernier_echec` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verrouille_jusqu_a` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `administrateur`
--
ALTER TABLE `administrateur`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `client_partenaire`
--
ALTER TABLE `client_partenaire`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `demande_contact`
--
ALTER TABLE `demande_contact`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `idx_demande_client` (`id_client`),
  ADD KEY `idx_demande_statut` (`statut`);

--
-- Index pour la table `export`
--
ALTER TABLE `export`
  ADD PRIMARY KEY (`id_export`),
  ADD KEY `idx_export_admin` (`id_admin`);

--
-- Index pour la table `journal_securite`
--
ALTER TABLE `journal_securite`
  ADD PRIMARY KEY (`id_journal`),
  ADD KEY `idx_journal_admin` (`id_admin`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `idx_notification_demande` (`id_demande`);

--
-- Index pour la table `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`id_page`),
  ADD KEY `idx_page_projet` (`id_projet`);

--
-- Index pour la table `projet`
--
ALTER TABLE `projet`
  ADD PRIMARY KEY (`id_projet`);

--
-- Index pour la table `visite`
--
ALTER TABLE `visite`
  ADD PRIMARY KEY (`id_visite`),
  ADD KEY `idx_visite_page` (`id_page`),
  ADD KEY `idx_visite_date` (`date_heure`);

--
-- Index pour la table `tentative_connexion`
--
ALTER TABLE `tentative_connexion`
  ADD PRIMARY KEY (`id_tentative`),
  ADD UNIQUE KEY `uq_tentative_compte_ip` (`type_compte`,`email`,`ip_anonymisee`),
  ADD KEY `idx_tentative_verrouillage` (`verrouille_jusqu_a`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `administrateur`
--
ALTER TABLE `administrateur`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `client_partenaire`
--
ALTER TABLE `client_partenaire`
  MODIFY `id_client` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demande_contact`
--
ALTER TABLE `demande_contact`
  MODIFY `id_demande` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `export`
--
ALTER TABLE `export`
  MODIFY `id_export` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_securite`
--
ALTER TABLE `journal_securite`
  MODIFY `id_journal` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `id_notification` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `page`
--
ALTER TABLE `page`
  MODIFY `id_page` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `projet`
--
ALTER TABLE `projet`
  MODIFY `id_projet` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `visite`
--
ALTER TABLE `visite`
  MODIFY `id_visite` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tentative_connexion`
--
ALTER TABLE `tentative_connexion`
  MODIFY `id_tentative` int NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `demande_contact`
--
ALTER TABLE `demande_contact`
  ADD CONSTRAINT `fk_demande_client` FOREIGN KEY (`id_client`) REFERENCES `client_partenaire` (`id_client`) ON DELETE SET NULL;

--
-- Contraintes pour la table `export`
--
ALTER TABLE `export`
  ADD CONSTRAINT `fk_export_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrateur` (`id_admin`) ON DELETE CASCADE;

--
-- Contraintes pour la table `journal_securite`
--
ALTER TABLE `journal_securite`
  ADD CONSTRAINT `fk_journal_admin` FOREIGN KEY (`id_admin`) REFERENCES `administrateur` (`id_admin`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande_contact` (`id_demande`) ON DELETE CASCADE;

--
-- Contraintes pour la table `page`
--
ALTER TABLE `page`
  ADD CONSTRAINT `fk_page_projet` FOREIGN KEY (`id_projet`) REFERENCES `projet` (`id_projet`) ON DELETE SET NULL;

--
-- Contraintes pour la table `visite`
--
ALTER TABLE `visite`
  ADD CONSTRAINT `fk_visite_page` FOREIGN KEY (`id_page`) REFERENCES `page` (`id_page`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Données initiales du portfolio
-- Ces enregistrements peuvent être modifiés depuis phpMyAdmin sans changer
-- le code du site. L'ordre d'affichage est utilisé sur l'accueil et la page
-- Projets.
-- --------------------------------------------------------

INSERT INTO `projet` (`titre`, `description`, `stack_technique`, `lien_demo`, `lien_repo`, `image_url`, `ordre_affichage`, `statut_publication`) VALUES
('Poissonnerie Saint-Michel', 'Site e-commerce pour une poissonnerie à Cotonou : catalogue produits, panier, suivi de commande côté client et espace équipe pour gérer les commandes au quotidien.', 'PHP natif, MySQL, HTML5, CSS3, JavaScript', NULL, 'https://github.com/pnj6s5yx96-a11y/poissonneriesaintmichel', 'assets/uploads/projects/85571b30abd64475e7dcba16ad1731e4.png', 1, 'publie'),
('Pressing Pro', 'Plateforme métier de gestion de pressing avec rôles différenciés propriétaire/gérant. Elle centralise les commandes, les paiements et les statuts afin de rendre le suivi quotidien plus fiable et plus rapide.', 'PHP natif, MySQL, HTML5, CSS3, JavaScript', NULL, 'https://github.com/pnj6s5yx96-a11y/PRESSING_PRO', NULL, 2, 'publie'),
('Saveurs du Bénin', 'Site vitrine pensé pour mettre en valeur la cuisine et les produits béninois : présentation soignée de l’offre et parcours de contact direct pour transformer la découverte en prise de contact.', 'HTML5, CSS3, JavaScript, SEO local', NULL, 'https://github.com/pnj6s5yx96-a11y/saveursdubenin', NULL, 3, 'publie');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Métadonnées SEO administrables depuis le dashboard.
CREATE TABLE IF NOT EXISTS `seo_meta` (
  `id_seo` int NOT NULL AUTO_INCREMENT,
  `page_key` varchar(100) NOT NULL,
  `language_code` char(2) NOT NULL,
  `seo_title` varchar(180) NOT NULL DEFAULT '',
  `meta_description` varchar(320) NOT NULL DEFAULT '',
  `focus_keyword` varchar(150) NOT NULL DEFAULT '',
  `canonical_url` varchar(500) NOT NULL DEFAULT '',
  `robots` varchar(80) NOT NULL DEFAULT 'index,follow',
  `date_modification` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_seo`),
  UNIQUE KEY `uq_seo_page_lang` (`page_key`,`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
