-- MySQL Dump
-- Database: esportify
-- Generated: 2026-02-18 15:00:35

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- ========================================
-- Table: announcements
-- ========================================

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `content` longtext DEFAULT NULL,
  `tag` varchar(60) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `media_type` varchar(255) NOT NULL,
  `media_filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: candidature
-- ========================================

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


-- ========================================
-- Table: categorie
-- ========================================

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: chat_message
-- ========================================

CREATE TABLE `chat_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(4) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `equipe_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_FAB3FC16A76ED395` (`user_id`),
  KEY `IDX_FAB3FC166D861B89` (`equipe_id`),
  CONSTRAINT `FK_FAB3FC166D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`),
  CONSTRAINT `FK_FAB3FC16A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: chat_messages
-- ========================================

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `body` longtext NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `call_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_chat_sender` (`sender_id`),
  KEY `IDX_chat_recipient` (`recipient_id`),
  KEY `idx_chat_sender_recipient_created` (`sender_id`,`recipient_id`,`created_at`),
  KEY `idx_chat_recipient_sender_created` (`recipient_id`,`sender_id`,`created_at`),
  KEY `idx_chat_recipient_read` (`recipient_id`,`is_read`),
  CONSTRAINT `FK_chat_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: commande
-- ========================================

CREATE TABLE `commande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `numtel` int(11) DEFAULT NULL,
  `statut` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: commentaires
-- ========================================

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_commentaires_author` (`author_id`),
  KEY `IDX_commentaires_post` (`post_id`),
  CONSTRAINT `FK_D9BEC0C44B89032C` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  CONSTRAINT `FK_D9BEC0C4F675F31B` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: doctrine_migration_versions
-- ========================================

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: doctrine_migration_versions
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260127180417', '2026-02-18 14:15:54', '167');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260127181104', '2026-02-18 14:15:54', '6');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260131121000', '2026-02-18 14:15:54', '6');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260131140000', '2026-02-18 14:15:54', '4');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260131153000', '2026-02-18 14:15:54', '4');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260204155237', '2026-02-18 14:15:54', '23');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260204160000', '2026-02-18 14:15:54', '680');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260206173814', '2026-02-18 14:15:55', '718');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260206175008', '2026-02-18 14:15:55', '103');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260206204412', '2026-02-18 14:15:56', '13');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207080454', '2026-02-18 14:15:56', '54');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207101449', '2026-02-18 14:15:56', '0');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207120000', '2026-02-18 14:15:56', '121');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207130000', '2026-02-18 14:15:56', '39');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207150000', '2026-02-18 14:15:56', '113');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207151000', '2026-02-18 14:15:56', '5');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207152000', '2026-02-18 14:15:56', '79');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207160000', '2026-02-18 14:15:56', '29');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207170000', '2026-02-18 14:15:56', '30');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207190000', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207200000', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260207213000', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260208102432', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260208120000', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260208121000', '2026-02-18 14:20:19', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260210093947', '2026-02-18 14:20:29', '22');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260211233000', '2026-02-18 14:20:29', '7');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260212000500', '2026-02-18 14:20:29', '56');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260212012000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260212153000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260213121500', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260213231500', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260215110000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260215114000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260216123000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260216220000', '2026-02-18 14:20:50', '1');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260218100000', '2026-02-18 14:23:39', '100');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260218110000', '2026-02-18 14:29:09', '8');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260218120000', '2026-02-18 14:41:47', '61');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260218134543', '2026-02-18 14:46:26', '128');
INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES ('DoctrineMigrations\\Version20260218134920', '2026-02-18 14:49:54', '47');


-- ========================================
-- Table: equipe
-- ========================================

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
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `manager_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_2C536C92783E3463` (`manager_id`),
  CONSTRAINT `FK_2C536C92783E3463` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: event_participants
-- ========================================

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event_participant` (`user_id`,`post_id`),
  KEY `IDX_event_participants_post` (`post_id`),
  CONSTRAINT `FK_event_participants_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_event_participants_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: ligne_commande
-- ========================================

CREATE TABLE `ligne_commande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quantite` int(11) NOT NULL,
  `prix` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3170B74B82EA2E54` (`commande_id`),
  KEY `IDX_3170B74BF347EFB` (`produit_id`),
  CONSTRAINT `FK_3170B74B82EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`),
  CONSTRAINT `FK_3170B74BF347EFB` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: likes
-- ========================================

CREATE TABLE `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_like_user_post` (`user_id`,`post_id`),
  KEY `IDX_likes_post` (`post_id`),
  CONSTRAINT `FK_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_likes_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: manager_request
-- ========================================

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


-- ========================================
-- Table: messenger_messages
-- ========================================

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: notifications
-- ========================================

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `type` varchar(80) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` longtext NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_created` (`recipient_id`,`created_at`),
  KEY `idx_notifications_user_read` (`recipient_id`,`is_read`),
  CONSTRAINT `FK_notifications_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table: notifications
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('1', '1', 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a été créé avec succès. Commencez à explorer le fil d\'actualité.', '/fil', '0', '2026-02-18 14:31:01');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('2', '2', 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a été créé avec succès. Commencez à explorer le fil d\'actualité.', '/fil', '0', '2026-02-18 14:35:36');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('3', '3', 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a été créé avec succès. Commencez à explorer le fil d\'actualité.', '/fil', '0', '2026-02-18 14:44:34');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('4', '1', 'post', 'Nouvelle publication', 'ilyes a publié dans le fil d\'actualité.', '/fil#post-1', '0', '2026-02-18 14:45:24');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('5', '2', 'post', 'Nouvelle publication', 'ilyes a publié dans le fil d\'actualité.', '/fil#post-1', '0', '2026-02-18 14:45:24');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('6', '1', 'post', 'Nouvelle publication', 'ilyes a publié dans le fil d\'actualité.', '/fil#post-2', '0', '2026-02-18 14:45:26');
INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES ('7', '2', 'post', 'Nouvelle publication', 'ilyes a publié dans le fil d\'actualité.', '/fil#post-2', '0', '2026-02-18 14:45:26');


-- ========================================
-- Table: participation
-- ========================================

CREATE TABLE `participation` (
  `tournoi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`tournoi_id`,`user_id`),
  KEY `IDX_PARTICIPATION_TOURNOI` (`tournoi_id`),
  KEY `IDX_PARTICIPATION_USER` (`user_id`),
  CONSTRAINT `FK_PARTICIPATION_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE,
  CONSTRAINT `FK_PARTICIPATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: participation_request
-- ========================================

CREATE TABLE `participation_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `message` longtext DEFAULT NULL,
  `player_level` varchar(20) DEFAULT NULL,
  `rules_accepted` tinyint(1) NOT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `applicant_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_PR_USER` (`user_id`),
  KEY `IDX_PR_TOURNOI` (`tournoi_id`),
  CONSTRAINT `FK_PR_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`),
  CONSTRAINT `FK_PR_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: password_reset_codes
-- ========================================

CREATE TABLE `password_reset_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: password_reset_codes
INSERT INTO `password_reset_codes` (`id`, `email`, `code_hash`, `expires_at`, `created_at`) VALUES ('1', 'ilyeszid33@gmail.com', '$2y$10$EtWIsH4I9QsUZOU07I4alubT.SmZbu3CqgVQOcCsX50VD.8I2OxyO', '2026-02-18 15:02:18', '2026-02-18 14:52:18');


-- ========================================
-- Table: payment
-- ========================================

CREATE TABLE `payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` double NOT NULL,
  `created_at` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `commande_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6D28840D82EA2E54` (`commande_id`),
  CONSTRAINT `FK_6D28840D82EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: post_media
-- ========================================

CREATE TABLE `post_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `path` varchar(255) NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_post_media_post` (`post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table: post_media
INSERT INTO `post_media` (`id`, `post_id`, `type`, `path`, `position`) VALUES ('1', '2', 'image', 'b588aa03e8846f73cb9e0ab6.png', '0');


-- ========================================
-- Table: posts
-- ========================================

CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` longtext DEFAULT NULL,
  `media_type` varchar(255) NOT NULL DEFAULT 'text',
  `media_filename` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `is_event` tinyint(1) NOT NULL DEFAULT 0,
  `event_title` varchar(180) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_posts_author` (`author_id`),
  CONSTRAINT `FK_posts_author` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table: posts
INSERT INTO `posts` (`id`, `content`, `media_type`, `media_filename`, `created_at`, `image_path`, `video_url`, `is_event`, `event_title`, `event_date`, `event_location`, `max_participants`, `author_id`) VALUES ('1', 'sdfsq', 'text', NULL, '2026-02-18 14:45:24', NULL, NULL, '0', NULL, NULL, NULL, NULL, '3');
INSERT INTO `posts` (`id`, `content`, `media_type`, `media_filename`, `created_at`, `image_path`, `video_url`, `is_event`, `event_title`, `event_date`, `event_location`, `max_participants`, `author_id`) VALUES ('2', NULL, 'text', NULL, '2026-02-18 14:45:26', 'b588aa03e8846f73cb9e0ab6.png', NULL, '0', NULL, NULL, NULL, NULL, '3');


-- ========================================
-- Table: produit
-- ========================================

CREATE TABLE `produit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prix` int(11) NOT NULL,
  `stock` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'disponible',
  `owner_user_id` int(11) DEFAULT NULL,
  `owner_equipe_id` int(11) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_PRODUIT_USER` (`owner_user_id`),
  KEY `FK_PRODUIT_EQUIPE` (`owner_equipe_id`),
  KEY `FK_PRODUIT_CATEGORIE` (`categorie_id`),
  CONSTRAINT `FK_PRODUIT_CATEGORIE` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_PRODUIT_EQUIPE` FOREIGN KEY (`owner_equipe_id`) REFERENCES `equipe` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_PRODUIT_USER` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: recommendation
-- ========================================

CREATE TABLE `recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `score` double DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_433224D2A76ED395` (`user_id`),
  KEY `IDX_433224D2F347EFB` (`produit_id`),
  CONSTRAINT `FK_433224D2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_433224D2F347EFB` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: recrutement
-- ========================================

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


-- ========================================
-- Table: resultat_tournoi
-- ========================================

CREATE TABLE `resultat_tournoi` (
  `id_resultat` int(11) NOT NULL AUTO_INCREMENT,
  `rank` int(11) NOT NULL,
  `score` double NOT NULL,
  `id_tournoi` int(11) NOT NULL,
  PRIMARY KEY (`id_resultat`),
  UNIQUE KEY `UNIQ_EC3E38FF7E0950D9` (`id_tournoi`),
  CONSTRAINT `FK_EC3E38FF7E0950D9` FOREIGN KEY (`id_tournoi`) REFERENCES `tournoi` (`id_tournoi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: team_reports
-- ========================================

CREATE TABLE `team_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E66D6726D861B89` (`equipe_id`),
  KEY `idx_team_reports_reporter` (`reporter_id`),
  CONSTRAINT `FK_E66D6726D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_E66D672E1CFE6F5` FOREIGN KEY (`reporter_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ========================================
-- Table: tournoi
-- ========================================

CREATE TABLE `tournoi` (
  `id_tournoi` int(11) NOT NULL AUTO_INCREMENT,
  `creator_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_tournoi` varchar(50) NOT NULL,
  `type_game` varchar(50) NOT NULL,
  `game` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `prize_won` double NOT NULL,
  `max_places` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_tournoi`),
  KEY `IDX_TOURNOI_CREATOR` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: tournoi_match
-- ========================================

CREATE TABLE `tournoi_match` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tournoi_id` int(11) NOT NULL,
  `player_a_id` int(11) DEFAULT NULL,
  `player_b_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `score_a` int(11) DEFAULT NULL,
  `score_b` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `home_name` varchar(255) DEFAULT NULL,
  `away_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_TOURNOI_MATCH_TOURNOI` (`tournoi_id`),
  KEY `IDX_TOURNOI_MATCH_PLAYER_A` (`player_a_id`),
  KEY `IDX_TOURNOI_MATCH_PLAYER_B` (`player_b_id`),
  CONSTRAINT `FK_TOURNOI_MATCH_PLAYER_A` FOREIGN KEY (`player_a_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_TOURNOI_MATCH_PLAYER_B` FOREIGN KEY (`player_b_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_TOURNOI_MATCH_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: tournoi_match_participant_result
-- ========================================

CREATE TABLE `tournoi_match_participant_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `match_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `placement` varchar(20) NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_match_participant` (`match_id`,`participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================
-- Table: user
-- ========================================

CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `role` varchar(255) NOT NULL,
  `pseudo` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`face_descriptor`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table: user
INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`, `avatar`, `face_descriptor`) VALUES ('1', 'ilyeszid33@gmail.com', '$2y$13$fCLnpiOal36wgkOHyqI1NeozVbE6QNWB/dDjYowR92RE3KikM1lsu', 'ilyes', 'ROLE_JOUEUR', 'ilyes', NULL, NULL);
INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`, `avatar`, `face_descriptor`) VALUES ('2', 'ilyes@gmail.com', '$2y$13$9.Op00JdnHpcXUCAYETdI.QvhzMME7DiaQoeKkRgS.zoIz4fwfuKC', 'ilyes', 'ROLE_JOUEUR', 'ilyes', '800f2ce025aa9f2ebc7295f8.png', NULL);
INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`, `avatar`, `face_descriptor`) VALUES ('3', 'amen@gmail.com', '$2y$13$R5H/61tKongjGxKBlVv.8uGYv0qwtucek.OEHMnZ1KD16sTUyQGPG', 'ilyes', 'ROLE_JOUEUR', 'ilyes', NULL, NULL);


-- ========================================
-- Table: user_saved_posts
-- ========================================

CREATE TABLE `user_saved_posts` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`post_id`),
  KEY `FK_saved_post` (`post_id`),
  CONSTRAINT `FK_saved_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_saved_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
