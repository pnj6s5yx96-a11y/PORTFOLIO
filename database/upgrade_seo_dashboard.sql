-- SEO dashboard: migration non destructive.
-- Exécuter une fois dans phpMyAdmin si vous préférez créer la table avant d'ouvrir le dashboard.
USE `PORTFOLIO`;

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
