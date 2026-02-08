-- Intégration Boutique dans la base esportify
-- À exécuter APRÈS avoir importé esportify.sql (ou sur la base esportify existante)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- Table catégorie (boutique)
CREATE TABLE IF NOT EXISTS `categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table produit (boutique)
CREATE TABLE IF NOT EXISTS `produit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categorie_id` int(11) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prix` double NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `statut` varchar(50) NOT NULL DEFAULT 'disponible',
  PRIMARY KEY (`id`),
  KEY `IDX_categorie` (`categorie_id`),
  CONSTRAINT `FK_produit_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exemple de données (optionnel)
-- INSERT INTO `categorie` (`nom`) VALUES ('Hardware'), ('Périphériques'), ('Merch');
-- INSERT INTO `produit` (`categorie_id`, `nom`, `prix`, `description`, `image`, `stock`, `statut`) VALUES
-- (1, 'Casque Pro', 129.99, 'Casque gaming 7.1', NULL, 50, 'disponible');
