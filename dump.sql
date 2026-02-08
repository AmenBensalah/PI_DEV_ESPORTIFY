SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

DROP TABLE IF EXISTS `candidature`;
DROP TABLE IF EXISTS `recrutement`;
DROP TABLE IF EXISTS `equipe`;
DROP TABLE IF EXISTS `manager_request`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `doctrine_migration_versions`;

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `role` varchar(255) NOT NULL,
  `pseudo` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `equipe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_equipe` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` longtext NOT NULL,
  `date_creation` datetime NOT NULL,
  `classement` varchar(50) NOT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `max_members` int(11) NOT NULL DEFAULT 5,
  `is_private` tinyint(4) NOT NULL DEFAULT 0,
  `manager_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_MANAGER_USER` (`manager_id`),
  CONSTRAINT `FK_2443196276C50E4A` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `candidature` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `niveau` varchar(50) NOT NULL,
  `motivation` longtext NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_candidature` datetime NOT NULL,
  `reason` varchar(255) NOT NULL,
  `play_style` varchar(100) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E33BD3B86D861B89` (`equipe_id`),
  CONSTRAINT `FK_E33BD3B86D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `recrutement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_rec` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `status` varchar(50) NOT NULL,
  `date_publication` datetime NOT NULL,
  `equipe_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_25EB23196D861B89` (`equipe_id`),
  CONSTRAINT `FK_25EB23196D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `manager_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `motivation` longtext NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `experience` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_855ABA89A76ED395` (`user_id`),
  CONSTRAINT `FK_855ABA89A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `equipe` (`id`, `nom_equipe`, `logo`, `description`, `date_creation`, `classement`, `tag`, `region`, `max_members`, `is_private`, `manager_id`) VALUES
(44, 'youssef team', '6983ecf5e0b1a.jpg', 'go', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Middle East', 5, 0, NULL),
(45, 'aysser', NULL, 'dsde', '2026-02-05 00:00:00', 'Challenger', 'ZZD', 'Middle East', 5, 0, NULL),
(46, 'ghaieth team', NULL, 'ggg', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Europe', 5, 0, NULL),
(48, 'team gloriuisaaa', NULL, 'aaaaa', '2026-02-05 00:00:00', 'Or', 'GLRX', 'Europe', 5, 0, NULL),
(50, 'ghaieth team', NULL, '2000', '2026-02-05 00:00:00', 'Argent', 'GBFGB', 'Europe', 5, 0, NULL),
(51, 'youssef team', NULL, 'hhhh', '2026-02-06 00:00:00', 'Bronze', 'GBFGB', 'South America', 5, 0, NULL),
(54, 'Ghaieth teams', '6985f8388e7ad.jpg', 'aa', '2026-02-06 00:00:00', 'Bronze', 'GG', 'North America', 50, 1, NULL),
(55, 'aaaaa', '6985fdc919855.png', 'aaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Or', 'AAAAA', 'Middle East', 5, 0, NULL),
(56, 'blender team', '69864cb5ec68a.png', 'olaolaola', '2026-02-06 21:19:01', 'Argent', 'BLD', 'North America', 50, 0, NULL),
(59, 'ggzz', '69865f4a2e8bc.jpg', 'go go go', '2026-02-06 00:00:00', 'Argent', 'BLD', 'Middle East', 50, 0, NULL),
(61, 'blender team', '6986649d1b169.jpg', 'allez allez', '2026-02-06 00:00:00', 'Or', 'GG', 'Europe', 50, 0, NULL),
(62, 'Ghaieth teams', '6986666ea49d8.png', 'aaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Argent', 'GG', 'Asia', 50, 0, NULL),
(63, 'sarra team', '6986735d8aa52.jpg', 'sarraaaaaa', '2026-02-06 00:00:00', 'Or', 'SARRA', 'Asia', 50, 0, NULL),
(64, 'sarroura team', '698675366d044.png', 'aaaaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Or', 'SARRA', 'Europe', 50, 0, NULL),
(65, 'esprit team', '6986787f42bcb.jpg', 'aaaaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Argent', 'GLRX', 'Middle East', 50, 0, 2),
(67, 'esprit team 1', '698766dfe3f8c.jpg', 'together we rise', '2026-02-07 00:00:00', 'Bronze', 'ESPRIT', 'Europe', 15, 0, 4);

INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`) VALUES
(1, 'admin@admin.com', '$2y$13$12YKbxno29l6cp/8u3GNFeWW9JTANx/0GO.dVxG4IXqEMONuP0mX2', 'Admin', 'ROLE_ADMIN', NULL),
(2, 'ghaiethbouamor23@gmail.com', '$2y$13$/HD5uZXi9theKh3UhHsWy.7oO3w6G53Ep9puU0CCw0DhBKLd/n.I6', 'ghaieth', 'ROLE_MANAGER', 'gaaloul'),
(3, 'ghaiethbouamor013@gmail.com', '$2y$13$2OjJLG7mu71vv2WQGqzLC.2N4mTCiPQEW2Jtpycgm8aA08wtxj8ZG', 'ghaieth', 'ROLE_JOUEUR', 'parker'),
(4, 'ghaiethbouamor773@gmail.com', '$2y$13$hNJXigjZBD439blj8LGKkOTVHx0Jsbey1VIyHYqoVvpexy1jajOLS', 'ghaieth', 'ROLE_MANAGER', '7riga');

INSERT INTO `candidature` (`id`, `niveau`, `motivation`, `statut`, `date_candidature`, `reason`, `play_style`, `equipe_id`, `user_id`) VALUES
(5, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-05 02:07:37', 'dcdc', 'dcdc', 44, 0),
(6, 'D?butant', 'Candidature spontan?e', 'Accept?', '2026-02-05 14:08:14', 'aa', 'aa', 45, 0),
(7, 'Interm?diaire', 'Candidature spontan?e', 'En attente', '2026-02-05 14:23:41', 'aa', 'aa', 46, 0),
(8, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-06 15:32:24', 'aaaaaaaaaaaaaaaaaaaaa', 'aaaaaa', 51, 0),
(9, 'Intermédiaire', 'Candidature spontanée', 'En attente', '2026-02-06 20:19:46', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaa', 55, 0),
(10, 'DÃ©butant', 'Candidature spontanÃ©e', 'En attente', '2026-02-06 23:04:34', 'je veux participer', 'gta fortnite', 61, 0),
(11, 'IntermÃ©diaire', 'Candidature spontanÃ©e', 'Accepté', '2026-02-06 23:10:23', 'aaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0),
(12, 'DÃ©butant', 'Candidature spontanÃ©e', 'En attente', '2026-02-06 23:18:20', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0),
(13, 'Confirmé', 'Candidature spontanée', 'En attente', '2026-02-07 00:01:10', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 62, NULL),
(14, 'Débutant', 'Candidature spontanée', 'En attente', '2026-02-07 00:02:41', 'aaaaaaaaaaaaaaaaaaa', 'azertyu', 62, 3),
(15, 'Débutant', 'Candidature spontanée', 'En attente', '2026-02-07 00:04:24', 'aaaaaaaaaaaaa', 'eryyaydydyyq', 63, 3),
(16, 'Intermédiaire', 'Candidature spontanée', 'En attente', '2026-02-07 00:10:35', 'aaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 55, 3),
(17, 'Intermédiaire', 'Candidature spontanée', 'En attente', '2026-02-07 00:12:23', 'aaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaa', 64, 3);

INSERT INTO `manager_request` (`id`, `motivation`, `status`, `created_at`, `user_id`, `nom`, `experience`) VALUES
(1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 16:16:23', 3, 'ghaieth', 'aaaaaaaaaaaaaaaaaaa'),
(2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 17:17:51', 4, 'ghaieth', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
