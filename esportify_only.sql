-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- HÃ´te : 127.0.0.1
-- GÃ©nÃ©rÃ© le : dim. 22 fÃ©v. 2026 Ã  15:11
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de donnÃ©es : `esportify`
--
CREATE DATABASE IF NOT EXISTS `esportify` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `esportify`;

-- --------------------------------------------------------

--
-- Structure de la table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `content` longtext DEFAULT NULL,
  `tag` varchar(60) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `media_type` varchar(255) NOT NULL,
  `media_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `tag`, `link`, `created_at`, `media_type`, `media_filename`) VALUES
(5, 'annonce', 'aaaaaaaaaaa', 'promo', NULL, '2026-02-10 09:50:43', 'image', 'd3224154bb54bc4f203d77fa.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `candidature`
--

CREATE TABLE `candidature` (
  `id` int(11) NOT NULL,
  `niveau` varchar(50) NOT NULL,
  `motivation` longtext NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_candidature` datetime NOT NULL,
  `reason` varchar(255) NOT NULL,
  `play_style` varchar(100) NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `disponibilite` varchar(100) DEFAULT NULL,
  `reason_ai_score` int(11) DEFAULT NULL,
  `reason_ai_label` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `candidature`
--

INSERT INTO `candidature` (`id`, `niveau`, `motivation`, `statut`, `date_candidature`, `reason`, `play_style`, `equipe_id`, `user_id`, `region`, `disponibilite`, `reason_ai_score`, `reason_ai_label`) VALUES
(5, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-05 02:07:37', 'dcdc', 'dcdc', 44, 0, NULL, NULL, NULL, NULL),
(6, 'D?butant', 'Candidature spontan?e', 'Accept?', '2026-02-05 14:08:14', 'aa', 'aa', 45, 0, NULL, NULL, NULL, NULL),
(7, 'Interm?diaire', 'Candidature spontan?e', 'En attente', '2026-02-05 14:23:41', 'aa', 'aa', 46, 0, NULL, NULL, NULL, NULL),
(8, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-06 15:32:24', 'aaaaaaaaaaaaaaaaaaaaa', 'aaaaaa', 51, 0, NULL, NULL, NULL, NULL),
(11, 'Interm????diaire', 'Candidature spontan????e', 'Accept??', '2026-02-06 23:10:23', 'aaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0, NULL, NULL, NULL, NULL),
(12, 'D????butant', 'Candidature spontan????e', 'En attente', '2026-02-06 23:18:20', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0, NULL, NULL, NULL, NULL),
(13, 'Confirm??', 'Candidature spontan??e', 'En attente', '2026-02-07 00:01:10', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 62, NULL, NULL, NULL, NULL, NULL),
(14, 'D??butant', 'Candidature spontan??e', 'En attente', '2026-02-07 00:02:41', 'aaaaaaaaaaaaaaaaaaa', 'azertyu', 62, 3, NULL, NULL, NULL, NULL),
(15, 'D??butant', 'Candidature spontan??e', 'Refus??', '2026-02-07 00:04:24', 'aaaaaaaaaaaaa', 'eryyaydydyyq', 63, 3, NULL, NULL, NULL, NULL),
(22, 'Confirm??', 'Candidature spontan??e', 'Accept??', '2026-02-15 17:55:39', 'oumouriiiiiiiiiiiiiii', 'awper', 63, 20, NULL, NULL, NULL, NULL),
(27, 'Interm??diaire', 'Candidature spontan??e', 'Accept??', '2026-02-17 11:21:27', 'aaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaa', 75, 23, NULL, NULL, NULL, NULL),
(28, 'Expert', 'Candidature spontan??e', 'RefusÃ©', '2026-02-17 11:22:24', 'zzzzzzzzzzzzzzzzzzzz', 'zzzzzzzzzzzzz', 75, 23, NULL, NULL, NULL, NULL),
(30, 'Expert', 'Candidature spontanÃ©e', 'RefusÃ©', '2026-02-22 02:18:51', 'Je souhaite rejoindre votre Ã©quipe pour progresser dans un cadre compÃ©titif. Mon objectif est dâ€™amÃ©liorer ma discipline dâ€™entraÃ®nement, ma communication en game et mon sens de la stratÃ©gie. Je suis motivÃ©, rÃ©gulier, et prÃªt Ã  mâ€™investir sur le long terme ', 'FPS/BATTLEROYAL', 75, 30, 'Asia', 'Moyenne', 78, 'pro'),
(33, 'ConfirmÃ©', 'Candidature spontanÃ©e', 'En attente', '2026-02-22 12:27:55', 'Je souhaite rejoindre votre Ã©quipe pour progresser dans un cadre compÃ©titif. Mon objectif est dâ€™amÃ©liorer ma discipline dâ€™entraÃ®nement, ma communication en game et mon sens de la stratÃ©gie. Je suis motivÃ©, rÃ©gulier, et prÃªt Ã  mâ€™investir sur le long terme ', 'FPS/BATTLE ROYALE', 76, 30, 'Europe', 'Ã‰levÃ©e', 78, 'pro'),
(35, 'Expert', 'Candidature spontanÃ©e', 'AcceptÃ©', '2026-02-22 14:13:37', 'Je souhaite rejoindre votre Ã©quipe car je partage votre vision compÃ©titive, votre professionnalisme et votre engagement envers la performance. Votre structure, votre organisation et votre ambition de progresser au plus haut niveau correspondent parfaiteme', 'Je suis un joueur compÃ©titif avec une excellente capacitÃ© dâ€™adaptation et un fort sens du travail dâ€™', 77, 31, 'Europe', 'Ã‰levÃ©e', 51, 'moyen');

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `categorie`
--

INSERT INTO `categorie` (`id`, `nom`) VALUES
(1, 'souris'),
(2, 'pc gamer'),
(3, 'clavier'),
(4, 'carte mre');

-- --------------------------------------------------------

--
-- Structure de la table `chat_message`
--

CREATE TABLE `chat_message` (
  `id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(4) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `equipe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `chat_message`
--

INSERT INTO `chat_message` (`id`, `message`, `created_at`, `is_read`, `user_id`, `equipe_id`) VALUES
(5, 'hi', '2026-02-17 11:25:26', 1, 23, 75),
(6, 'hi', '2026-02-22 02:32:01', 1, 12, 75),
(7, 'hi', '2026-02-22 02:32:08', 1, 12, 75),
(8, 'hi', '2026-02-22 02:39:53', 1, 12, 75),
(9, 'hi', '2026-02-22 02:48:51', 1, 12, 75),
(10, 'hi', '2026-02-22 04:00:53', 1, 30, 76),
(11, 'azzz', '2026-02-22 12:42:23', 1, 30, 75),
(12, 'hi', '2026-02-22 14:15:43', 1, 30, 77);

-- --------------------------------------------------------

--
-- Structure de la table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `body` longtext NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `call_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `recipient_id`, `body`, `type`, `call_url`, `is_read`, `created_at`) VALUES
(1, 12, 31, 'hi', 'text', NULL, 1, '2026-02-22 02:47:05'),
(2, 12, 32, 'Appel vocal', 'call_audio', 'https://meet.jit.si/esportify-call_audio-1771760950351', 0, '2026-02-22 12:49:10'),
(3, 31, 12, 'Appel vocal', 'call_audio', 'https://meet.jit.si/esportify-call_audio-1771767347336', 0, '2026-02-22 14:35:49');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `numtel` int(11) DEFAULT NULL,
  `statut` varchar(255) NOT NULL,
  `pays` varchar(255) DEFAULT NULL,
  `gouvernerat` varchar(255) DEFAULT NULL,
  `code_postal` varchar(20) DEFAULT NULL,
  `adresse_detail` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `identity_key` varchar(190) DEFAULT NULL,
  `ai_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `ai_risk_score` double DEFAULT NULL,
  `ai_block_reason` varchar(500) DEFAULT NULL,
  `ai_blocked_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `ai_block_until` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `commande`
--

INSERT INTO `commande` (`id`, `nom`, `prenom`, `adresse`, `quantite`, `numtel`, `statut`, `pays`, `gouvernerat`, `code_postal`, `adresse_detail`, `user_id`, `identity_key`, `ai_blocked`, `ai_risk_score`, `ai_block_reason`, `ai_blocked_at`, `ai_block_until`) VALUES
(65, 'zid', 'ilyes', 'buford  city', 1, 93987977, 'paid', 'Tunisie', 'hh', '30518', 'tunis', 6, 'zid|ilyes|93987977', 0, NULL, NULL, NULL, NULL),
(66, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '||0', 0, NULL, NULL, NULL, NULL),
(67, 'dhifallah', 'aysser', 'kelibia', 1, 12333333, 'paid', 'Tunisie', 'manouba', '2010', 'manouba hay said', 27, 'dhifallah|aysser|12333333', 0, NULL, NULL, NULL, NULL),
(68, 'ghaieth', 'aysser', 'kelibia', 1, 12345678, 'paid', 'Tunisie', 'manouba', '2010', 'manouba hay said', NULL, 'ghaieth|aysser|12345678', 0, NULL, NULL, NULL, NULL),
(69, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, NULL, '||0', 0, NULL, NULL, NULL, NULL),
(70, 'dhifallah', 'aysser', 'kelibia', 1, 11111111, 'paid', 'Tunisie', 'NABEUL', '8090', 'RUE MOUNIKER', 27, 'dhifallah|aysser|11111111', 0, NULL, NULL, NULL, NULL),
(72, 'dhifallah', 'aysser', 'kelibia', 1, 12345678, 'paid', 'Tunisie', 'aaaa', '8090', 'RUE MOUNIKER', 27, 'dhifallah|aysser|12345678', 0, NULL, NULL, NULL, NULL),
(73, 'dhifallah', 'aysser', 'kelibia', 1, 12345678, 'paid', 'Tunisie', 'aaaa', '8090', 'RUE MOUNIKER', 4, 'dhifallah|aysser|12345678', 0, NULL, NULL, NULL, NULL),
(74, 'dhifallah', 'aysser', 'kelibia', 4, 12345678, 'paid', 'Tunisie', 'aaaa', '8090', 'RUE MOUNIKER', 1, 'dhifallah|aysser|12345678', 0, NULL, NULL, NULL, NULL),
(75, 'dhifallah', 'aysser', NULL, 1, 12345678, 'draft', NULL, NULL, NULL, NULL, 4, 'dhifallah|aysser|12345678', 0, NULL, NULL, NULL, NULL),
(76, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, 4, '||0', 0, NULL, NULL, NULL, NULL),
(77, 'dhifallah', 'aysser', NULL, 1, 22222222, 'draft', NULL, NULL, NULL, NULL, 1, 'dhifallah|aysser|22222222', 0, NULL, NULL, NULL, NULL),
(78, 'aysser', 'dhifallah', 'kelibia', 1, 29787777, 'pending_payment', 'Tunisie', 'aaaa', '8090', 'RUE MOUNIKER', 28, 'aysser|dhifallah|29787777', 1, 93.93, 'Commande temporairement bloquee (score de risque: 94/100). Reessayez apres 21/02/2026 01:56.', '2026-02-21 00:40:22', '2026-02-21 01:56:22'),
(79, 'aysser', 'dhifallah', 'kelibia', 1, 29787777, 'pending_payment', 'Tunisie', 'aaaa', '8090', 'RUE MOUNIKER', 4, 'aysser|dhifallah|29787777', 1, 93.93, 'Commande temporairement bloquee (score de risque: 94/100). Reessayez apres 21/02/2026 01:56.', '2026-02-21 00:40:22', '2026-02-21 01:56:22'),
(80, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL, 31, '||0', 0, NULL, NULL, NULL, NULL),
(81, 'Bouamor', 'Mohamed ghaieth', 'hay said nahj beja', 1, 56276418, 'pending_payment', 'Tunisie', 'manouba', '2010', '47', 31, 'bouamor|mohamed ghaieth|56276418', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `author_id`, `post_id`, `content`, `created_at`) VALUES
(2, 11, 10, 'heellloooo', '2026-02-10 00:39:37'),
(3, 4, 12, 'hi', '2026-02-10 00:40:50'),
(4, 3, 21, 'Super id??e pour le tournoi !', '2026-02-10 01:18:32'),
(5, 4, 21, 'Je participe avec mon ??quipe.', '2026-02-10 01:18:32'),
(6, 2, 19, 'Vid??o test OK.', '2026-02-10 01:18:32'),
(7, 11, 18, 'Hello', '2026-02-10 01:55:52'),
(8, 4, 20, 'aaaa', '2026-02-10 02:39:31'),
(9, 4, 19, 'waw', '2026-02-10 10:02:29'),
(13, 22, 31, 'https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjExbDRxdjd0ZXl4YzY5dHd5c3hmMmptb2VjMjUxN2diZWt3cDA4ZnF2ZSZlcD12MV9naWZzX3NlYXJjaCZjdD1n/MdA16VIoXKKxNE8Stk/giphy.gif', '2026-02-16 20:39:01');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260127180417', '2026-02-18 14:15:54', 167),
('DoctrineMigrations\\Version20260127181104', '2026-02-18 14:15:54', 6),
('DoctrineMigrations\\Version20260131121000', '2026-02-18 14:15:54', 6),
('DoctrineMigrations\\Version20260131140000', '2026-02-18 14:15:54', 4),
('DoctrineMigrations\\Version20260131153000', '2026-02-18 14:15:54', 4),
('DoctrineMigrations\\Version20260204155237', '2026-02-18 14:15:54', 23),
('DoctrineMigrations\\Version20260204160000', '2026-02-18 14:15:54', 680),
('DoctrineMigrations\\Version20260206173814', '2026-02-18 14:15:55', 718),
('DoctrineMigrations\\Version20260206175008', '2026-02-18 14:15:55', 103),
('DoctrineMigrations\\Version20260206204412', '2026-02-18 14:15:56', 13),
('DoctrineMigrations\\Version20260207080454', '2026-02-18 14:15:56', 54),
('DoctrineMigrations\\Version20260207101449', '2026-02-18 14:15:56', 0),
('DoctrineMigrations\\Version20260207120000', '2026-02-18 14:15:56', 121),
('DoctrineMigrations\\Version20260207130000', '2026-02-18 14:15:56', 39),
('DoctrineMigrations\\Version20260207150000', '2026-02-18 14:15:56', 113),
('DoctrineMigrations\\Version20260207151000', '2026-02-18 14:15:56', 5),
('DoctrineMigrations\\Version20260207152000', '2026-02-18 14:15:56', 79),
('DoctrineMigrations\\Version20260207160000', '2026-02-18 14:15:56', 29),
('DoctrineMigrations\\Version20260207170000', '2026-02-18 14:15:56', 30),
('DoctrineMigrations\\Version20260207190000', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260207200000', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260207213000', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260208102432', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260208120000', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260208121000', '2026-02-18 14:20:19', 1),
('DoctrineMigrations\\Version20260210093947', '2026-02-18 14:20:29', 22),
('DoctrineMigrations\\Version20260211233000', '2026-02-18 14:20:29', 7),
('DoctrineMigrations\\Version20260212000500', '2026-02-18 14:20:29', 56),
('DoctrineMigrations\\Version20260212012000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260212153000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260213121500', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260213231500', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260215110000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260215114000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260216123000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260216220000', '2026-02-18 14:20:50', 1),
('DoctrineMigrations\\Version20260218100000', '2026-02-18 14:23:39', 100),
('DoctrineMigrations\\Version20260218110000', '2026-02-18 14:29:09', 8),
('DoctrineMigrations\\Version20260218120000', '2026-02-18 14:41:47', 61),
('DoctrineMigrations\\Version20260218134543', '2026-02-18 14:46:26', 128),
('DoctrineMigrations\\Version20260218134920', '2026-02-18 14:49:54', 47),
('DoctrineMigrations\\Version20260220153000', '2026-02-20 15:50:28', 407),
('DoctrineMigrations\\Version20260220161000', '2026-02-20 16:23:44', 33),
('DoctrineMigrations\\Version20260222110000', '2026-02-22 01:50:38', 127),
('DoctrineMigrations\\Version20260222113000', '2026-02-22 01:53:38', 29),
('DoctrineMigrations\\Version20260222130000', '2026-02-22 02:55:50', 36),
('DoctrineMigrations\\Version20260222170000', '2026-02-22 03:36:43', 13);

-- --------------------------------------------------------

--
-- Structure de la table `equipe`
--

CREATE TABLE `equipe` (
  `id` int(11) NOT NULL,
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
  `suspension_reason` longtext DEFAULT NULL,
  `suspended_until` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `suspension_duration_days` int(11) DEFAULT NULL,
  `discord_invite_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `equipe`
--

INSERT INTO `equipe` (`id`, `nom_equipe`, `logo`, `description`, `date_creation`, `classement`, `tag`, `region`, `max_members`, `is_private`, `is_active`, `manager_id`, `suspension_reason`, `suspended_until`, `suspension_duration_days`, `discord_invite_url`) VALUES
(44, 'youssef team', '6983ecf5e0b1a.jpg', 'go', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Middle East', 5, 0, 1, NULL, NULL, NULL, NULL, NULL),
(45, 'aysser', NULL, 'dsde', '2026-02-05 00:00:00', 'Challenger', 'ZZD', 'Middle East', 5, 0, 1, NULL, NULL, NULL, NULL, NULL),
(46, 'ghaieth team', NULL, 'ggg', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Europe', 5, 0, 1, NULL, NULL, NULL, NULL, NULL),
(50, 'ghaieth team', NULL, '2000', '2026-02-05 00:00:00', 'Argent', 'GBFGB', 'Europe', 5, 0, 1, NULL, NULL, NULL, NULL, NULL),
(51, 'youssef team', NULL, 'hhhh', '2026-02-06 00:00:00', 'Bronze', 'GBFGB', 'South America', 5, 0, 1, NULL, NULL, NULL, NULL, NULL),
(56, 'blender team', '69864cb5ec68a.png', 'olaolaola', '2026-02-06 21:19:01', 'Argent', 'BLD', 'North America', 50, 0, 1, NULL, NULL, NULL, NULL, NULL),
(62, 'Ghaieth teams', '6986666ea49d8.png', 'aaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Argent', 'GG', 'Asia', 50, 0, 1, NULL, NULL, NULL, NULL, NULL),
(63, 'sarra team', '6986735d8aa52.jpg', 'sarraaaaaa', '2026-02-06 00:00:00', 'Or', 'SARRA', 'Asia', 50, 0, 1, NULL, NULL, NULL, NULL, NULL),
(75, 'Alpha Team', '699a6eb3d6d3b.png', 'we are the top of gaming join us', '2026-02-17 00:00:00', 'Bronze', '#ALPHA', 'Asia', 5, 0, 1, 12, NULL, NULL, NULL, 'https://discord.gg/yGkd9kT9'),
(76, 'blender team', '699a704701841.jpg', 'together we domainate', '2026-02-22 00:00:00', 'Platine', '#BLD', 'Europe', 40, 0, 1, 32, NULL, NULL, NULL, 'https://discord.gg/yGkd9kT9'),
(77, 'Aura Team', '699aff74aef1a.jpg', 'Ã‰quipe eSport professionnelle dÃ©diÃ©e Ã  la performance et Ã  l\'excellence compÃ©titive. Nous rÃ©unissons des joueurs passionnÃ©s, engagÃ©s et stratÃ©giques avec pour objectif de reprÃ©senter nos couleurs au plus haut niveau et de contribuer activement Ã  la croissance de l\'eSport.', '2026-02-22 00:00:00', 'Diamant', '#AURA', 'Europe', 25, 1, 1, 30, NULL, NULL, NULL, 'https://discord.gg/MdnXUtN3');

-- --------------------------------------------------------

--
-- Structure de la table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `event_participants`
--

INSERT INTO `event_participants` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(1, 3, 21, '2026-02-10 01:18:52'),
(2, 4, 21, '2026-02-10 01:18:52');

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

CREATE TABLE `ligne_commande` (
  `id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `ligne_commande`
--

INSERT INTO `ligne_commande` (`id`, `quantite`, `prix`, `commande_id`, `produit_id`) VALUES
(4, 2, 19999, 4, 1),
(5, 1, 19999, 5, 1),
(6, 1, 19999, 6, 1),
(7, 1, 19999, 7, 1),
(8, 2, 19999, 8, 1),
(9, 2, 19999, 9, 1),
(10, 1, 19999, 10, 1),
(11, 1, 19999, 11, 1),
(12, 1, 19999, 12, 1),
(13, 1, 19999, 13, 1),
(14, 1, 19999, 14, 1),
(15, 3, 19999, 15, 1),
(16, 9, 19999, 16, 1),
(17, 1, 19999, 17, 1),
(18, 1, 19999, 18, 1),
(19, 1, 19999, 19, 1),
(20, 1, 19999, 20, 1),
(21, 1, 19999, 21, 1),
(22, 3, 19999, 22, 1),
(23, 4, 19999, 23, 1),
(24, 2, 19999, 24, 1),
(25, 1, 19999, 25, 1),
(26, 2, 19999, 26, 1),
(27, 1, 19999, 27, 1),
(28, 2, 19999, 28, 1),
(29, 1, 19999, 29, 1),
(30, 1, 19999, 30, 1),
(31, 1, 19999, 31, 1),
(32, 1, 19999, 32, 1),
(33, 1, 19999, 33, 1),
(34, 3, 19999, 34, 1),
(35, 1, 19999, 35, 1),
(36, 7, 19999, 36, 1),
(37, 2, 19999, 37, 1),
(38, 1, 19999, 38, 1),
(39, 5, 19999, 39, 1),
(40, 3, 19999, 40, 1),
(41, 2, 19999, 41, 1),
(42, 1, 19999, 42, 1),
(43, 2, 100, 43, 1),
(44, 1, 120, 44, 10),
(45, 2, 1405, 44, 5),
(46, 4, 100, 45, 1),
(47, 2, 140, 45, 3),
(48, 1, 100, 46, 1),
(49, 1, 140, 46, 3),
(50, 1, 140, 47, 3),
(51, 1, 15, 47, 12),
(52, 1, 140, 48, 3),
(53, 2, 140, 49, 3),
(54, 1, 100, 49, 1),
(55, 1, 1400, 49, 6),
(56, 1, 100, 50, 1),
(57, 1, 140, 50, 3),
(58, 1, 120, 50, 11),
(60, 1, 100, 51, 1),
(61, 1, 140, 51, 3),
(62, 1, 120, 51, 10),
(63, 1, 1400, 51, 6),
(64, 2, 120, 52, 10),
(66, 1, 100, 52, 1),
(67, 1, 100, 53, 1),
(68, 1, 100, 54, 1),
(69, 1, 140, 55, 3),
(71, 1, 100, 56, 1),
(72, 1, 100, 57, 1),
(73, 1, 100, 58, 1),
(74, 1, 1405, 59, 5),
(80, 40, 1405, 60, 5),
(84, 1, 12, 60, 14),
(85, 1, 140, 59, 3),
(86, 1, 12, 61, 14),
(87, 1, 100, 61, 1),
(88, 1, 100, 59, 1),
(89, 1, 12, 59, 14),
(90, 1, 100, 62, 1),
(91, 1, 100, 63, 1),
(92, 1, 100, 64, 1),
(104, 110, 120, 65, 10),
(105, 1, 100, 66, 1),
(106, 1, 100, 67, 1),
(107, 41, 1405, 68, 5),
(108, 2, 100, 68, 1),
(109, 1, 120, 68, 10),
(110, 2, 140, 68, 3),
(111, 1, 120, 69, 11),
(112, 1, 100, 70, 1),
(113, 2, 15, 70, 12),
(114, 2, 120, 70, 10),
(115, 1, 140, 70, 3),
(116, 1, 120, 70, 11),
(119, 1, 100, 72, 1),
(120, 5, 100, 73, 1),
(121, 1, 140, 73, 3),
(122, 2, 1405, 73, 5),
(123, 4, 120, 73, 11),
(124, 1, 16, 73, 13),
(125, 4, 140, 74, 3),
(127, 1, 100, 75, 1),
(128, 1, 100, 76, 1),
(129, 1, 100, 77, 1),
(130, 1, 100, 78, 1),
(131, 2, 100, 79, 1),
(132, 4, 140, 79, 3),
(133, 3, 1400, 79, 6),
(134, 1, 15, 79, 12),
(135, 3, 1405, 79, 5),
(136, 2, 120, 79, 11),
(137, 1, 16, 79, 13),
(138, 1, 100, 80, 1),
(139, 1, 140, 81, 3);

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(2, 11, 8, '2026-02-10 00:37:38'),
(3, 11, 10, '2026-02-10 00:39:18'),
(4, 4, 12, '2026-02-10 00:40:51'),
(5, 4, 10, '2026-02-10 00:40:55'),
(6, 4, 11, '2026-02-10 00:45:07'),
(7, 11, 15, '2026-02-10 01:01:24'),
(8, 11, 17, '2026-02-10 01:04:10'),
(9, 3, 21, '2026-02-10 01:18:39'),
(10, 4, 21, '2026-02-10 01:18:39'),
(11, 2, 19, '2026-02-10 01:18:39'),
(12, 11, 18, '2026-02-10 01:55:42'),
(13, 4, 20, '2026-02-10 02:39:35'),
(14, 4, 23, '2026-02-10 10:02:05'),
(16, 12, 27, '2026-02-10 11:45:52'),
(21, 22, 31, '2026-02-16 20:39:05'),
(22, 12, 32, '2026-02-17 11:42:31');

-- --------------------------------------------------------

--
-- Structure de la table `manager_request`
--

CREATE TABLE `manager_request` (
  `id` int(11) NOT NULL,
  `motivation` longtext NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(255) DEFAULT NULL,
  `experience` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `manager_request`
--

INSERT INTO `manager_request` (`id`, `motivation`, `status`, `created_at`, `user_id`, `nom`, `experience`) VALUES
(1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 16:16:23', 3, 'ghaieth', 'aaaaaaaaaaaaaaaaaaa'),
(2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 17:17:51', 4, 'ghaieth', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
(3, 'test', 'accepted', '2026-02-09 15:04:45', 11, 'Mohamed', 'Test'),
(4, 'test', 'accepted', '2026-02-09 15:06:10', 11, 'Mohamed bouzid', 'test test test etc'),
(5, 'sssssss', 'accepted', '2026-02-09 23:31:09', 11, 'aaa', 'zzz'),
(6, 'aaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-10 03:58:04', 4, 'aaaaa', 'aaaaaaaaaaaaaaa'),
(8, 'aaaaaaaaaaaaaaaaaaaaaaaaaa', 'pending', '2026-02-10 11:53:16', 12, 'Ben Salah', 'aaaaaaaaaaaaaaaaaaa'),
(9, 'aaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-22 03:53:13', 32, 'gaaloul', 'competitve'),
(10, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'pending', '2026-02-22 14:03:10', 30, 'ghaieth', 'aaaaaaaaaaaaaaaaaaaaaaaaaaa');

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `messenger_messages`
--

INSERT INTO `messenger_messages` (`id`, `body`, `headers`, `queue_name`, `created_at`, `available_at`, `delivered_at`) VALUES
(1, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:15:\\\"admin@admin.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(2, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:26:\\\"ghaiethbouamor23@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(3, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:27:\\\"ghaiethbouamor013@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(4, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:27:\\\"ghaiethbouamor773@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(5, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:18:\\\"ilyeszid@esprit.tn\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(6, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:19:\\\"ilyes.zid@esprit.tn\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(7, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:20:\\\"ilyeszid33@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(8, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:17:\\\"ilyes14@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(9, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:24:\\\"youssefchleghm@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL),
(10, 'O:36:\\\"Symfony\\\\Component\\\\Messenger\\\\Envelope\\\":2:{s:44:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0stamps\\\";a:1:{s:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\";a:1:{i:0;O:46:\\\"Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\\":1:{s:55:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Stamp\\\\BusNameStamp\\0busName\\\";s:21:\\\"messenger.bus.default\\\";}}}s:45:\\\"\\0Symfony\\\\Component\\\\Messenger\\\\Envelope\\0message\\\";O:51:\\\"Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\\":2:{s:60:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0message\\\";O:28:\\\"Symfony\\\\Component\\\\Mime\\\\Email\\\":6:{i:0;s:64:\\\"amnesia vient de cr??er un ??v??nement : aaaa (25/02/2026 01:01)\\\";i:1;s:5:\\\"utf-8\\\";i:2;N;i:3;N;i:4;a:0:{}i:5;a:2:{i:0;O:37:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\\":2:{s:46:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0headers\\\";a:3:{s:4:\\\"from\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:4:\\\"From\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:23:\\\"noreply@esportify.local\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}s:7:\\\"subject\\\";a:1:{i:0;O:48:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:7:\\\"Subject\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:55:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\UnstructuredHeader\\0value\\\";s:43:\\\"Nouvel ??v??nement dans le fil d\\\'actualit??\\\";}}s:2:\\\"to\\\";a:1:{i:0;O:47:\\\"Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\\":5:{s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0name\\\";s:2:\\\"To\\\";s:56:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lineLength\\\";i:76;s:50:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0lang\\\";N;s:53:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\AbstractHeader\\0charset\\\";s:5:\\\"utf-8\\\";s:58:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\MailboxListHeader\\0addresses\\\";a:1:{i:0;O:30:\\\"Symfony\\\\Component\\\\Mime\\\\Address\\\":2:{s:39:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0address\\\";s:14:\\\"amen@gmail.com\\\";s:36:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Address\\0name\\\";s:0:\\\"\\\";}}}}}s:49:\\\"\\0Symfony\\\\Component\\\\Mime\\\\Header\\\\Headers\\0lineLength\\\";i:76;}i:1;N;}}s:61:\\\"\\0Symfony\\\\Component\\\\Mailer\\\\Messenger\\\\SendEmailMessage\\0envelope\\\";N;}}', '[]', 'default', '2026-02-10 00:01:17', '2026-02-10 00:01:17', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `type` varchar(80) NOT NULL,
  `title` varchar(180) NOT NULL,
  `message` longtext NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-18 14:31:01'),
(2, 2, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-18 14:35:36'),
(3, 3, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-18 14:44:34'),
(4, 1, 'post', 'Nouvelle publication', 'ilyes a publi?? dans le fil d\'actualit??.', '/fil#post-1', 0, '2026-02-18 14:45:24'),
(5, 2, 'post', 'Nouvelle publication', 'ilyes a publi?? dans le fil d\'actualit??.', '/fil#post-1', 0, '2026-02-18 14:45:24'),
(6, 1, 'post', 'Nouvelle publication', 'ilyes a publi?? dans le fil d\'actualit??.', '/fil#post-2', 0, '2026-02-18 14:45:26'),
(7, 2, 'post', 'Nouvelle publication', 'ilyes a publi?? dans le fil d\'actualit??.', '/fil#post-2', 0, '2026-02-18 14:45:26'),
(59, 26, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-18 15:07:37'),
(60, 27, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-20 15:55:44'),
(61, 28, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a ??t?? cr???? avec succ??s. Commencez ?? explorer le fil d\'actualit??.', '/fil', 0, '2026-02-21 00:12:19'),
(62, 29, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s. Commencez Ã  explorer le fil d\'actualitÃ©.', '/fil', 0, '2026-02-21 13:30:28'),
(63, 30, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s. Commencez Ã  explorer le fil d\'actualitÃ©.', '/fil', 0, '2026-02-22 01:41:23'),
(64, 31, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s. Commencez Ã  explorer le fil d\'actualitÃ©.', '/fil', 0, '2026-02-22 02:27:49'),
(65, 32, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s. Commencez Ã  explorer le fil d\'actualitÃ©.', '/fil', 0, '2026-02-22 03:52:27'),
(66, 1, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (1 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:04:47'),
(67, 27, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (1 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:04:47'),
(68, 1, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (2 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:07:59'),
(69, 27, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (2 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:07:59'),
(70, 33, 'welcome', 'Bienvenue sur E-Sportify', 'Votre compte a Ã©tÃ© crÃ©Ã© avec succÃ¨s. Commencez Ã  explorer le fil d\'actualitÃ©.', '/fil', 0, '2026-02-22 04:09:33'),
(71, 1, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (3 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:09:56'),
(72, 27, 'team_report', 'Signalements Ã©quipe : blender team', 'Cette Ã©quipe a Ã©tÃ© signalÃ©e (3 signalement(s) rÃ©cents).', '/admin/equipes', 0, '2026-02-22 04:09:56');

-- --------------------------------------------------------

--
-- Structure de la table `participation`
--

CREATE TABLE `participation` (
  `tournoi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `participation`
--

INSERT INTO `participation` (`tournoi_id`, `user_id`) VALUES
(16, 4),
(16, 29),
(17, 4),
(17, 29),
(18, 4),
(18, 29),
(19, 4),
(19, 29),
(20, 4),
(20, 29),
(21, 4),
(21, 29),
(22, 4),
(22, 29),
(23, 4),
(23, 29);

-- --------------------------------------------------------

--
-- Structure de la table `participation_request`
--

CREATE TABLE `participation_request` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tournoi_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `message` longtext DEFAULT NULL,
  `player_level` varchar(20) DEFAULT NULL,
  `rules_accepted` tinyint(1) NOT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `applicant_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `participation_request`
--

INSERT INTO `participation_request` (`id`, `user_id`, `tournoi_id`, `status`, `message`, `player_level`, `rules_accepted`, `applicant_name`, `applicant_email`, `created_at`) VALUES
(22, 4, 17, 'approved', 'hahhahhahahahha', 'debutant', 1, NULL, NULL, '2026-02-21 13:28:53'),
(23, 4, 16, 'approved', 'oohhohihhiohiohhhoihio', 'amateur', 1, NULL, NULL, '2026-02-21 13:29:13'),
(26, 29, 16, 'approved', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'debutant', 1, NULL, NULL, '2026-02-21 13:31:53'),
(27, 29, 17, 'approved', 'ojojojoojojojojjojojj', 'pro', 1, NULL, NULL, '2026-02-21 13:32:13'),
(28, 4, 18, 'approved', 'hahahahhahahha', 'amateur', 1, NULL, NULL, '2026-02-21 18:07:36'),
(29, 29, 18, 'approved', 'kokokkokoko', 'amateur', 1, NULL, NULL, '2026-02-21 18:08:02'),
(30, 4, 19, 'approved', 'okokkokokokook', 'pro', 1, NULL, NULL, '2026-02-21 21:06:30'),
(31, 29, 19, 'approved', 'bddbbdbdbdbdbd', 'pro', 1, NULL, NULL, '2026-02-21 21:07:06'),
(32, 4, 20, 'approved', 'jzkzkenkjzjnekznj', 'debutant', 1, NULL, NULL, '2026-02-21 21:30:57'),
(33, 29, 20, 'approved', 'zjkejnkezjknjkezkj', 'pro', 1, NULL, NULL, '2026-02-21 21:31:26'),
(34, 4, 21, 'approved', 'zzzzzzzzzzzzz', 'amateur', 1, NULL, NULL, '2026-02-21 23:22:10'),
(35, 29, 21, 'approved', 'aaaaaaaaaaaaaaaaaa', 'pro', 1, NULL, NULL, '2026-02-21 23:22:48'),
(36, 4, 22, 'approved', 'aaaaaaaaaaaaaaaaa', 'pro', 1, NULL, NULL, '2026-02-21 23:41:10'),
(37, 29, 22, 'approved', 'aaaaaaaaaaaaaaaaaa', 'pro', 1, NULL, NULL, '2026-02-21 23:41:36'),
(38, 4, 23, 'approved', 'ffffffffffffff', 'pro', 1, NULL, NULL, '2026-02-22 00:19:48'),
(39, 29, 23, 'approved', 'zzzzzzzzzzzzzzzzz', 'pro', 1, NULL, NULL, '2026-02-22 00:20:37');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_codes`
--

CREATE TABLE `password_reset_codes` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `password_reset_codes`
--

INSERT INTO `password_reset_codes` (`id`, `email`, `code_hash`, `expires_at`, `created_at`) VALUES
(1, 'ilyeszid33@gmail.com', '$2y$10$EtWIsH4I9QsUZOU07I4alubT.SmZbu3CqgVQOcCsX50VD.8I2OxyO', '2026-02-18 15:02:18', '2026-02-18 14:52:18'),
(15, 'amenbensalah038@gmail.com', '$2y$10$dMZnK5ujnGM1/sievGyZN.1b1qkL5Lxnb7g5Zcnty76jJMILbjNSm', '2026-02-22 14:57:50', '2026-02-22 14:47:50');

-- --------------------------------------------------------

--
-- Structure de la table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `amount` double NOT NULL,
  `created_at` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `commande_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `payment`
--

INSERT INTO `payment` (`id`, `amount`, `created_at`, `status`, `commande_id`) VALUES
(5, 132, '2026-02-20 19:06:14', 'paid', 65),
(6, 1, '2026-02-20 19:06:15', 'paid', 67),
(7, 582.05, '2026-02-20 19:06:15', 'paid', 68),
(8, 6.3, '2026-02-20 19:06:15', 'paid', 70),
(9, 1, '2026-02-20 19:15:24', 'paid', 72),
(10, 5.6, '2026-02-20 23:56:03', 'paid', 74),
(11, 39.46, '2026-02-20 23:56:24', 'paid', 73);

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
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
  `author_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `posts`
--

INSERT INTO `posts` (`id`, `content`, `media_type`, `media_filename`, `created_at`, `image_path`, `video_url`, `is_event`, `event_title`, `event_date`, `event_location`, `max_participants`, `author_id`) VALUES
(1, 'sdfsq', 'text', NULL, '2026-02-18 14:45:24', NULL, NULL, 0, NULL, NULL, NULL, NULL, 3),
(2, NULL, 'text', NULL, '2026-02-18 14:45:26', 'b588aa03e8846f73cb9e0ab6.png', NULL, 0, NULL, NULL, NULL, NULL, 3),
(3, 'Openning', 'image', '455f9dccaf88f4fb5dbebc4f.png', '2026-02-08 13:56:58', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(7, 'test', 'text', NULL, '2026-02-08 19:19:36', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(8, 'hello', '', NULL, '2026-02-10 00:34:43', NULL, NULL, 0, NULL, NULL, NULL, NULL, 11),
(9, 'aaaa', '', NULL, '2026-02-10 00:38:59', 'b1969bed3a580db82e7dcaa6.png', NULL, 0, NULL, NULL, NULL, NULL, 1),
(10, 'aaaaaaa', '', NULL, '2026-02-10 00:39:11', '5a0b8eef4f59461c552157c7.png', NULL, 0, NULL, NULL, NULL, NULL, 1),
(11, NULL, '', NULL, '2026-02-10 00:39:45', 'e117407081da5f64ffe8fb96.png', NULL, 0, NULL, NULL, NULL, NULL, 11),
(12, 'okii', '', NULL, '2026-02-10 00:40:01', '0bb5b86563c3fa2813be6644.png', NULL, 0, NULL, NULL, NULL, NULL, 11),
(13, 'ok ok', '', NULL, '2026-02-10 01:00:05', 'b00468e1e39d559e9fc240bb.png', NULL, 0, NULL, NULL, NULL, NULL, 11),
(14, NULL, '', NULL, '2026-02-10 01:01:01', NULL, 'https://www.youtube.com/watch?v=diRiCP0r58A', 0, NULL, NULL, NULL, NULL, 11),
(15, NULL, '', NULL, '2026-02-10 01:01:14', NULL, NULL, 1, 'aaaa', '2026-02-25 01:01:00', 'sssss', 3, 11),
(16, 'ok', '', NULL, '2026-02-10 01:01:28', NULL, 'https://www.youtube.com/watch?v=diRiCP0r58A', 0, NULL, NULL, NULL, NULL, 11),
(17, NULL, '', NULL, '2026-02-10 01:04:03', NULL, 'https://www.youtube.com/watch?v=ZggspAgMsyE', 0, NULL, NULL, NULL, NULL, 11),
(18, 'Bienvenue sur E-Sportify ! Premier post de test.', 'text', NULL, '2026-02-10 01:18:24', NULL, NULL, 0, NULL, NULL, NULL, NULL, 3),
(19, 'Scrim du vendredi - lien vid??o int??gr??.', 'text', NULL, '2026-02-10 01:18:24', NULL, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 0, NULL, NULL, NULL, NULL, 4),
(20, 'Poster image test', 'text', NULL, '2026-02-10 01:18:24', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?q=80&w=1200&auto=format&fit=crop', NULL, 0, NULL, NULL, NULL, NULL, 3),
(21, 'Event test : Tournoi du week-end', 'text', NULL, '2026-02-10 01:18:24', NULL, NULL, 1, 'TOURNOI WEEK-END', '2026-02-13 01:18:24', 'Discord: https://discord.gg/esportify', 16, 2),
(22, NULL, '', NULL, '2026-02-10 02:00:52', '0fdb2f8d3f872d9a4fc8607c.png', NULL, 0, NULL, NULL, NULL, NULL, 11),
(23, 'fffffffffff', '', NULL, '2026-02-10 09:58:08', NULL, NULL, 0, NULL, NULL, NULL, NULL, 1),
(24, NULL, '', NULL, '2026-02-10 10:06:41', NULL, 'https://www.youtube.com/watch?v=7nQF5h-NioI&list=PLyKilQX_Qo-QE0XPiGEheGJ4_WWaV6LFL', 0, NULL, NULL, NULL, NULL, 4),
(25, 'E-sport world cup', '', NULL, '2026-02-10 10:07:31', NULL, 'https://www.youtube.com/watch?v=WGJR1ZYYgXc', 0, NULL, NULL, NULL, NULL, 4),
(27, 'hello', '', NULL, '2026-02-10 11:40:18', NULL, NULL, 0, NULL, NULL, NULL, NULL, 12),
(28, NULL, '', NULL, '2026-02-10 11:44:11', NULL, 'https://www.youtube.com/watch?v=N9bF8JfMcBA', 0, NULL, NULL, NULL, NULL, 1),
(29, 'test', '', NULL, '2026-02-10 11:46:08', NULL, NULL, 0, NULL, NULL, NULL, NULL, 12),
(30, 'sqd', '', NULL, '2026-02-14 19:36:54', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL),
(31, 'aaaaaaaaaa', '', NULL, '2026-02-16 20:23:44', NULL, NULL, 0, NULL, NULL, NULL, NULL, 22),
(32, 'aaaaaaaaaa', '', NULL, '2026-02-17 10:02:41', NULL, NULL, 0, NULL, NULL, NULL, NULL, 24);

-- --------------------------------------------------------

--
-- Structure de la table `post_media`
--

CREATE TABLE `post_media` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `path` varchar(255) NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `post_media`
--

INSERT INTO `post_media` (`id`, `post_id`, `type`, `path`, `position`) VALUES
(1, 2, 'image', 'b588aa03e8846f73cb9e0ab6.png', 0);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id` int(11) NOT NULL,
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
  `video_url` varchar(255) DEFAULT NULL,
  `technical_specs` text DEFAULT NULL,
  `install_difficulty` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `produit`
--

INSERT INTO `produit` (`id`, `nom`, `prix`, `stock`, `description`, `image`, `active`, `statut`, `owner_user_id`, `owner_equipe_id`, `categorie_id`, `video_url`, `technical_specs`, `install_difficulty`) VALUES
(1, 'carte mere hytts', 100, 140, 'La carte m??re est le circuit imprim?? principal d\'un ordinateur, agissant comme le syst??me nerveux central qui interconnecte le processeur, la m??moire (RAM), le stockage et les p??riph??riques. Elle g??re l\'alimentation et la communication entre ces co', 'uploads/images/Capture-d-ecran-2026-02-07-232806-6987c004318d1.png', 0, 'disponible', NULL, NULL, 4, NULL, NULL, NULL),
(3, 'carte mere ttht7410', 140, 21, 'La carte m??re est le circuit imprim?? principal d\'un ordinateur, agissant comme le syst??me nerveux central qui interconnecte le processeur, la m??moire (RAM), le stockage et les p??riph??riques. Elle g??re l\'alimentation et la communication entre ces co', 'uploads/images/Capture-d-ecran-2026-02-07-232800-6987bfea8b7f4.png', 0, 'disponible', NULL, NULL, 4, NULL, NULL, NULL),
(4, 'pc gamer mpla', 140, 0, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??r', 'uploads/images/Capture-d-ecran-2026-02-07-232638-6987bfb876b63.png', 0, 'disponible', NULL, NULL, 2, NULL, NULL, NULL),
(5, 'pc gamer', 1405, 40, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??r', 'uploads/images/Capture-d-ecran-2026-02-07-232644-6987bf9e010a5.png', 0, 'disponible', NULL, NULL, 2, NULL, NULL, NULL),
(6, 'pc gamer', 1400, 140, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??r', 'uploads/images/Capture-d-ecran-2026-02-07-232649-6987bf647206e.png', 0, 'disponible', NULL, NULL, 2, NULL, NULL, NULL),
(10, 'souris httys', 120, 110, 'La souris est un petit mammif??re rongeur (5-10 cm, 20-70 g) de la famille des murid??s, caract??ris?? par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232715-6987bf8533615.png', 0, 'disponible', NULL, NULL, 1, NULL, NULL, NULL),
(11, 'souris htx2012', 120, 140, 'La souris est un petit mammif??re rongeur (5-10 cm, 20-70 g) de la famille des murid??s, caract??ris?? par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232705-6987bf38f2071.png', 0, 'disponible', NULL, NULL, 1, NULL, NULL, NULL),
(12, 'clavier', 15, 140, 'Un clavier d\'ordinateur est un p??riph??rique d\'entr??e essentiel, compos?? d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un syst??me informatique. Issu des machines ?? ??crire, il se d??cline en versions filaires (USB', 'uploads/images/Capture-d-ecran-2026-02-07-232728-69885ea0f1647.png', 0, 'disponible', NULL, NULL, 3, NULL, NULL, NULL),
(13, 'clavier tt47', 16, 15, 'Un clavier d\'ordinateur est un p??riph??rique d\'entr??e essentiel, compos?? d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un syst??me informatique. Issu des machines ?? ??crire, il se d??cline en versions filaires (USB', 'uploads/images/Capture-d-ecran-2026-02-07-232732-69885eba30249.png', 0, 'disponible', NULL, NULL, 3, NULL, NULL, NULL),
(15, 'carte graphique', 154, 0, 'carte graphique', 'uploads/images/Capture-d-ecran-2026-02-07-232638-6995cecb2063a.png', 0, 'disponible', NULL, NULL, 2, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `recommendation`
--

CREATE TABLE `recommendation` (
  `id` int(11) NOT NULL,
  `score` double DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recrutement`
--

CREATE TABLE `recrutement` (
  `id` int(11) NOT NULL,
  `nom_rec` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `status` varchar(50) NOT NULL,
  `date_publication` datetime NOT NULL,
  `equipe_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `resultat_tournoi`
--

CREATE TABLE `resultat_tournoi` (
  `id_resultat` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `score` double NOT NULL,
  `id_tournoi` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `team_reports`
--

CREATE TABLE `team_reports` (
  `id` int(11) NOT NULL,
  `reason` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `equipe_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- DÃ©chargement des donnÃ©es de la table `team_reports`
--

INSERT INTO `team_reports` (`id`, `reason`, `created_at`, `equipe_id`, `reporter_id`) VALUES
(1, 'tricherie aaaaaaaaaaaaaaaa', '2026-02-22 04:04:47', 76, 30),
(2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-02-22 04:07:59', 76, 4),
(3, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-02-22 04:09:56', 76, 33);

-- --------------------------------------------------------

--
-- Structure de la table `tournoi`
--

CREATE TABLE `tournoi` (
  `id_tournoi` int(11) NOT NULL,
  `creator_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type_tournoi` varchar(50) NOT NULL,
  `type_game` varchar(50) NOT NULL,
  `game` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `prize_won` double NOT NULL,
  `max_places` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `tournoi`
--

INSERT INTO `tournoi` (`id_tournoi`, `creator_id`, `name`, `type_tournoi`, `type_game`, `game`, `start_date`, `end_date`, `status`, `prize_won`, `max_places`) VALUES
(16, 1, 'youssef 2025', 'solo', 'Sports', 'fifa 2026', '2026-02-27 13:27:00', '2026-02-28 13:27:00', 'planned', 500, 12),
(17, 1, 'Valorant Masters', 'solo', 'FPS', 'Valorant', '2026-02-27 13:27:00', '2026-02-28 13:28:00', 'planned', 600, 8),
(18, 1, 'chpaattt', 'solo', 'Sports', 'regby', '2026-02-27 18:06:00', '2026-02-28 18:06:00', 'planned', 6000, 6),
(19, 1, 'achreefff', 'solo', 'FPS', 'CS 2', '2026-02-27 21:05:00', '2026-02-28 21:05:00', 'planned', 600, 20),
(20, 1, 'balloutaaa', 'solo', 'Mind', 'chess', '2026-02-24 21:30:00', '2026-02-25 21:30:00', 'planned', 800, 6),
(21, 1, 'mohameedd', 'solo', 'Mind', 'chess', '2026-02-22 23:21:00', '2026-02-28 23:21:00', 'planned', 500, 4),
(22, 1, 'lasmeeerrr', 'solo', 'FPS', 'hahahah', '2026-02-25 23:36:00', '2026-02-28 23:36:00', 'planned', 10000, 4),
(23, 1, 'Valorant youssef', 'solo', 'Sports', 'fifa', '2026-02-23 00:12:00', '2026-02-27 00:12:00', 'planned', 50, 2);

-- --------------------------------------------------------

--
-- Structure de la table `tournoi_match`
--

CREATE TABLE `tournoi_match` (
  `id` int(11) NOT NULL,
  `tournoi_id` int(11) NOT NULL,
  `player_a_id` int(11) DEFAULT NULL,
  `player_b_id` int(11) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `score_a` int(11) DEFAULT NULL,
  `score_b` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `home_name` varchar(255) DEFAULT NULL,
  `away_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `tournoi_match`
--

INSERT INTO `tournoi_match` (`id`, `tournoi_id`, `player_a_id`, `player_b_id`, `scheduled_at`, `status`, `score_a`, `score_b`, `created_at`, `home_name`, `away_name`) VALUES
(31, 16, NULL, NULL, '2026-02-27 17:54:00', 'played', 3, 0, '2026-02-21 13:51:07', '7riga', 'chpat'),
(32, 17, NULL, NULL, '2026-02-27 13:51:00', 'played', 1, 1, '2026-02-21 13:51:50', '7riga', 'chpat'),
(33, 16, NULL, NULL, '2026-02-27 17:03:00', 'played', 3, 0, '2026-02-21 17:03:12', '7riga', 'chpat'),
(34, 18, NULL, NULL, '2026-02-27 18:58:00', 'played', 3, 0, '2026-02-21 18:58:08', '7riga', 'chpat'),
(35, 20, NULL, NULL, '2026-02-24 23:20:00', 'played', 3, 0, '2026-02-21 23:20:30', '7riga', 'chpat');

-- --------------------------------------------------------

--
-- Structure de la table `tournoi_match_participant_result`
--

CREATE TABLE `tournoi_match_participant_result` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `placement` varchar(20) NOT NULL,
  `points` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `tournoi_match_participant_result`
--

INSERT INTO `tournoi_match_participant_result` (`id`, `match_id`, `participant_id`, `placement`, `points`, `created_at`) VALUES
(1, 6, 4, 'first', 3, '2026-02-16 11:51:34'),
(2, 6, 20, 'third', 1, '2026-02-16 11:52:09'),
(3, 7, 4, 'first', 3, '2026-02-16 11:55:31'),
(4, 7, 20, 'third', 1, '2026-02-16 13:45:18'),
(5, 9, 22, 'second', 2, '2026-02-17 14:55:11'),
(6, 9, 23, 'first', 3, '2026-02-17 14:55:14'),
(7, 21, 22, 'first', 3, '2026-02-17 16:01:40'),
(8, 21, 23, 'second', 2, '2026-02-17 16:01:40'),
(9, 29, 22, 'first', 3, '2026-02-17 16:12:29'),
(10, 29, 23, 'third', 1, '2026-02-17 16:12:29'),
(11, 30, 22, 'second', 2, '2026-02-17 16:12:29'),
(12, 30, 23, 'third', 1, '2026-02-17 16:12:29'),
(13, 29, 4, 'first', 3, '2026-02-21 13:49:54'),
(14, 29, 29, 'third', 1, '2026-02-21 13:49:58');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `role` varchar(255) NOT NULL,
  `pseudo` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `face_descriptor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`face_descriptor`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`, `avatar`, `face_descriptor`) VALUES
(1, 'admin@admin.com', '$2y$13$zRr6I2.ZHl/UDBYYrSj2qu4JGJ/ahGAdsCbF2.bLKO1fWzl0gbTL6', 'Admin', 'ROLE_ADMIN', 'admin', NULL, NULL),
(2, 'ilyes@gmail.com', '$2y$13$9.Op00JdnHpcXUCAYETdI.QvhzMME7DiaQoeKkRgS.zoIz4fwfuKC', 'ilyes', 'ROLE_JOUEUR', 'ilyes', '800f2ce025aa9f2ebc7295f8.png', NULL),
(3, 'amen@gmail.com', '$2y$13$R5H/61tKongjGxKBlVv.8uGYv0qwtucek.OEHMnZ1KD16sTUyQGPG', 'ilyes', 'ROLE_JOUEUR', 'ilyes', NULL, NULL),
(4, 'ghaiethbouamor773@gmail.com', '$2y$13$jSxjw3XTopDlp8LVyeBhG.7275Zm1CS19OMkZBqLR3kybr/vl2XHO', 'ghaieth', 'ROLE_JOUEUR', '7riga', NULL, NULL),
(5, 'ilyeszid@esprit.tn', '$2y$13$o6YcNLpmzRJ.E5fiM4F8lOx.u/2wwF5QKTfOEhYIvR4KQcbZ.krSe', '', 'ROLE_JOUEUR', NULL, NULL, NULL),
(6, 'ilyes.zid@esprit.tn', '$2y$13$lvBoJoMoPHDDyOJyjwfRu.H9J1lHxzBOENFKXgyCsyq6Yun2aaE6a', 'ilyes zid', 'ROLE_JOUEUR', 'ilyes', NULL, NULL),
(8, 'ilyes14@gmail.com', '$2y$13$4wFj53Knc/.qnqIwvY3IpeV0uxaOqvMep4X2n945MYXzdWC2kUz2W', 'ilyes', 'ROLE_JOUEUR', 'ilyes', NULL, NULL),
(11, 'gmail@mohamed.nt', '$2y$13$DRVaJyN3.1zoP3A7Xb0CyeQNK6rKCO/ZDLqrHbNr3yFtBWJxobPoW', 'mohamedaaa', 'ROLE_MANAGER', 'amnesia', NULL, NULL),
(12, 'amen@amen.com', '$2y$13$PoClSjS/niOHDOflV3zfwul5eiTSJR6q.8ThIzWO1Js48v9oyPfLe', 'amen', 'ROLE_MANAGER', 'amen123', NULL, NULL),
(13, 'amenbensalah038@gmail.com', '$2y$13$K9QaQXTVDGPqHF/L.y0JPubBEuEMPQA3RvssZdhig6RkqMn.mgIE2', 'amen', 'ROLE_JOUEUR', 'amen1', NULL, NULL),
(14, 'mortadhaamaidi054@gmail.com', '$2y$13$yrXuyYHM.Ds7gqFbEkbjieU0u3BGkA8IoRDbo0g6835GM7SrdUZVe', 'mortadha', 'ROLE_JOUEUR', 'aasbalyoussef', NULL, NULL),
(26, 'ilyeszid@gmail.com', '$2y$13$sM1i63IcSUsLF6T1tuvx9e1mGkRt8wIp8.AKs4MedP98VLDNqR8tC', 'ilyes', 'ROLE_JOUEUR', 'ilyes', NULL, NULL),
(27, 'dhifallah17.aysser@gmail.com', '$2y$13$sWdbwXl1fg5fAPKv87GAtu2r0X9zFUY9.swZc8wB/TuG/M2faeWca', 'aysser dhifallah', 'ROLE_ADMIN', 'aysser25', NULL, NULL),
(28, 'dhifallah.aysser@gmail.com', '$2y$13$IYhYQ/yKcHwRDTSSBYoqG.bOqvYK.NHHZsvdGNRPVvx8aJkwh/bt2', 'aysser dhifallah', 'ROLE_JOUEUR', 'aysser25', NULL, NULL),
(29, 'youssef@gmail.com', '$2y$13$MDWBPq1Na4ZNzg.Msz8pReczxGFWyqdY4jQrx4OFAUZMzy9V8A/Nu', 'youssef', 'ROLE_JOUEUR', 'chpat', NULL, NULL),
(30, 'ghaiethbouamor1@gmail.com', '$2y$13$ahQKprfK4MfnyMNdwibV2uPKDQvbRetGNtW.CkVHlCR5ERnnzlQHC', 'ghaieth', 'ROLE_MANAGER', 'jellyfish', NULL, '[-0.07481438666582108,0.11277826875448227,0.02856130339205265,0.0021249919664114714,-0.10479679703712463,0.003022253978997469,-0.016452796757221222,-0.13426122069358826,0.2537117898464203,-0.17255277931690216,0.3135627508163452,0.028409138321876526,-0.23080827295780182,9.892391972243786e-5,-0.036662109196186066,0.10114617645740509,-0.11956202983856201,-0.12101265043020248,-0.00891154259443283,-0.015753518790006638,0.07401532679796219,0.07384451478719711,0.004206088371574879,0.05588122457265854,-0.13368846476078033,-0.31205683946609497,-0.0929543673992157,-0.10179990530014038,0.04960602521896362,-0.12275546789169312,-0.017574351280927658,0.023631762713193893,-0.1297469437122345,-0.02992129512131214,0.0035046394914388657,0.08917099237442017,-0.1183919683098793,-0.09979396313428879,0.23530016839504242,0.018149591982364655,-0.19270440936088562,-0.0744708776473999,-0.00856957957148552,0.2664701044559479,0.09920987486839294,-0.006047183182090521,0.06572789698839188,-0.1335708647966385,0.10112187266349792,-0.25546756386756897,0.07440837472677231,0.07861492037773132,0.14782026410102844,0.05613616481423378,0.09157713502645493,-0.21531426906585693,0.0050399526953697205,0.05632160231471062,-0.21995589137077332,0.06049538403749466,0.08059102296829224,-0.049541909247636795,-0.026578545570373535,-0.028459981083869934,0.18886999785900116,0.10646963864564896,-0.08622971177101135,-0.10973160713911057,0.18689528107643127,-0.20274506509304047,-0.08268488943576813,0.17051783204078674,-0.0338001511991024,-0.2743667960166931,-0.22698810696601868,-0.008573424071073532,0.5146229863166809,0.191153883934021,-0.12793326377868652,0.006571801379323006,-0.008255582302808762,-0.09806399047374725,0.0745154544711113,0.08726461231708527,-0.0546344518661499,0.03366580605506897,-0.1425391286611557,0.06558678299188614,0.22120977938175201,-0.002349885180592537,-0.06113043054938316,0.217161625623703,0.01857336238026619,0.05380472168326378,0.06189180165529251,0.0828455239534378,-0.08169618993997574,0.03131376579403877,-0.08728396147489548,-0.01767570525407791,0.015304944477975368,-0.06730706244707108,-0.022206459194421768,0.07873977720737457,-0.177780881524086,0.243636354804039,-0.012074428610503674,-0.05989020690321922,-0.02246686816215515,-0.028217071667313576,-0.11095982044935226,0.04868705943226814,0.17308391630649567,-0.3007976710796356,0.1198340356349945,0.08056408166885376,0.040464505553245544,0.23267441987991333,0.015403389930725098,0.051809024065732956,0.000658128410577774,-0.13077810406684875,-0.25495535135269165,-0.1099286824464798,-0.014343490824103355,-0.06566259264945984,0.09860727936029434,0.021414197981357574]'),
(31, 'ghaiethbouamor2@gmail.com', '$2y$13$XY4xvZRAcaOEp.Dw5cWzd.6M3D2Rve4B4LBzQaq6Qbh4YMWR2u1Oa', 'ghaieth', 'ROLE_JOUEUR', 'azizos', NULL, NULL),
(32, 'ghaiethbouamor3@gmail.com', '$2y$13$aI8rm90As7ggT9UzhFA4YeJnvojeNZJTLSHGltgffyzBjosQvo9/C', 'ghaieth', 'ROLE_MANAGER', 'gaaloul', NULL, NULL),
(33, 'ghaiethbouamor4@gmail.com', '$2y$13$dAJTz6R8lBqP/8xRspiqjOEwwN7RJj7mmAUyHrXue73Jvmfihc9ti', 'ahmed', 'ROLE_JOUEUR', 'qzzzez', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_saved_posts`
--

CREATE TABLE `user_saved_posts` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- DÃ©chargement des donnÃ©es de la table `user_saved_posts`
--

INSERT INTO `user_saved_posts` (`user_id`, `post_id`) VALUES
(3, 19),
(3, 21),
(4, 18),
(4, 20),
(4, 23);

--
-- Index pour les tables dÃ©chargÃ©es
--

--
-- Index pour la table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `candidature`
--
ALTER TABLE `candidature`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E33BD3B86D861B89` (`equipe_id`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FAB3FC16A76ED395` (`user_id`),
  ADD KEY `IDX_FAB3FC166D861B89` (`equipe_id`);

--
-- Index pour la table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_recipient_read` (`recipient_id`,`is_read`),
  ADD KEY `idx_chat_sender_recipient_created` (`sender_id`,`recipient_id`,`created_at`),
  ADD KEY `idx_chat_recipient_sender_created` (`recipient_id`,`sender_id`,`created_at`),
  ADD KEY `IDX_chat_sender` (`sender_id`),
  ADD KEY `IDX_chat_recipient` (`recipient_id`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_COMMANDE_USER` (`user_id`),
  ADD KEY `IDX_COMMANDE_IDENTITY_KEY` (`identity_key`),
  ADD KEY `IDX_COMMANDE_AI_BLOCKED` (`ai_blocked`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_commentaires_author` (`author_id`),
  ADD KEY `IDX_commentaires_post` (`post_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `equipe`
--
ALTER TABLE `equipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_2443196276C50E4A` (`manager_id`);

--
-- Index pour la table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_event_participant` (`user_id`,`post_id`),
  ADD KEY `IDX_event_participants_post` (`post_id`);

--
-- Index pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3170B74B82EA2E54` (`commande_id`),
  ADD KEY `IDX_3170B74BF347EFB` (`produit_id`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_like_user_post` (`user_id`,`post_id`),
  ADD KEY `IDX_likes_post` (`post_id`);

--
-- Index pour la table `manager_request`
--
ALTER TABLE `manager_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_855ABA89A76ED395` (`user_id`);

--
-- Index pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_created` (`recipient_id`,`created_at`),
  ADD KEY `idx_notifications_user_read` (`recipient_id`,`is_read`);

--
-- Index pour la table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`tournoi_id`,`user_id`),
  ADD KEY `IDX_PARTICIPATION_TOURNOI` (`tournoi_id`),
  ADD KEY `IDX_PARTICIPATION_USER` (`user_id`);

--
-- Index pour la table `participation_request`
--
ALTER TABLE `participation_request`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_PR_USER` (`user_id`),
  ADD KEY `IDX_PR_TOURNOI` (`tournoi_id`);

--
-- Index pour la table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_email` (`email`);

--
-- Index pour la table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6D28840D82EA2E54` (`commande_id`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_posts_author` (`author_id`);

--
-- Index pour la table `post_media`
--
ALTER TABLE `post_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_post_media_post` (`post_id`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `FK_PRODUIT_USER` (`owner_user_id`),
  ADD KEY `FK_PRODUIT_EQUIPE` (`owner_equipe_id`),
  ADD KEY `FK_PRODUIT_CATEGORIE` (`categorie_id`);

--
-- Index pour la table `recommendation`
--
ALTER TABLE `recommendation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_433224D2A76ED395` (`user_id`),
  ADD KEY `IDX_433224D2F347EFB` (`produit_id`);

--
-- Index pour la table `recrutement`
--
ALTER TABLE `recrutement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_25EB23196D861B89` (`equipe_id`);

--
-- Index pour la table `resultat_tournoi`
--
ALTER TABLE `resultat_tournoi`
  ADD PRIMARY KEY (`id_resultat`),
  ADD UNIQUE KEY `UNIQ_EC3E38FF7E0950D9` (`id_tournoi`);

--
-- Index pour la table `team_reports`
--
ALTER TABLE `team_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E66D6726D861B89` (`equipe_id`),
  ADD KEY `idx_team_reports_reporter` (`reporter_id`);

--
-- Index pour la table `tournoi`
--
ALTER TABLE `tournoi`
  ADD PRIMARY KEY (`id_tournoi`),
  ADD KEY `IDX_TOURNOI_CREATOR` (`creator_id`);

--
-- Index pour la table `tournoi_match`
--
ALTER TABLE `tournoi_match`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_TOURNOI_MATCH_TOURNOI` (`tournoi_id`),
  ADD KEY `IDX_TOURNOI_MATCH_PLAYER_A` (`player_a_id`),
  ADD KEY `IDX_TOURNOI_MATCH_PLAYER_B` (`player_b_id`);

--
-- Index pour la table `tournoi_match_participant_result`
--
ALTER TABLE `tournoi_match_participant_result`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_match_participant` (`match_id`,`participant_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- Index pour la table `user_saved_posts`
--
ALTER TABLE `user_saved_posts`
  ADD PRIMARY KEY (`user_id`,`post_id`),
  ADD KEY `FK_saved_post` (`post_id`);

--
-- AUTO_INCREMENT pour les tables dÃ©chargÃ©es
--

--
-- AUTO_INCREMENT pour la table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `candidature`
--
ALTER TABLE `candidature`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `chat_message`
--
ALTER TABLE `chat_message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `equipe`
--
ALTER TABLE `equipe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT pour la table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=140;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `manager_request`
--
ALTER TABLE `manager_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT pour la table `participation_request`
--
ALTER TABLE `participation_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT pour la table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT pour la table `post_media`
--
ALTER TABLE `post_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `recommendation`
--
ALTER TABLE `recommendation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `recrutement`
--
ALTER TABLE `recrutement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `resultat_tournoi`
--
ALTER TABLE `resultat_tournoi`
  MODIFY `id_resultat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `team_reports`
--
ALTER TABLE `team_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `tournoi`
--
ALTER TABLE `tournoi`
  MODIFY `id_tournoi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `tournoi_match`
--
ALTER TABLE `tournoi_match`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `tournoi_match_participant_result`
--
ALTER TABLE `tournoi_match_participant_result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- Contraintes pour les tables dÃ©chargÃ©es
--

--
-- Contraintes pour la table `candidature`
--
ALTER TABLE `candidature`
  ADD CONSTRAINT `FK_E33BD3B86D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`);

--
-- Contraintes pour la table `chat_message`
--
ALTER TABLE `chat_message`
  ADD CONSTRAINT `FK_FAB3FC166D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`),
  ADD CONSTRAINT `FK_FAB3FC16A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `FK_chat_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `FK_COMMANDE_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `FK_D9BEC0C44B89032C` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`),
  ADD CONSTRAINT `FK_D9BEC0C4F675F31B` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `equipe`
--
ALTER TABLE `equipe`
  ADD CONSTRAINT `FK_2443196276C50E4A` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_2C536C92783E3463` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `FK_event_participants_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_event_participants_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `FK_3170B74B82EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`),
  ADD CONSTRAINT `FK_3170B74BF347EFB` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`);

--
-- Contraintes pour la table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `FK_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_likes_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `manager_request`
--
ALTER TABLE `manager_request`
  ADD CONSTRAINT `FK_855ABA89A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `FK_6000B0D3E92F8F78` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_notifications_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `FK_PARTICIPATION_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PARTICIPATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `participation_request`
--
ALTER TABLE `participation_request`
  ADD CONSTRAINT `FK_PR_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`),
  ADD CONSTRAINT `FK_PR_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `FK_6D28840D82EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`);

--
-- Contraintes pour la table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `FK_posts_author` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `post_media`
--
ALTER TABLE `post_media`
  ADD CONSTRAINT `FK_FD372DE34B89032C` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `FK_PRODUIT_CATEGORIE` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_PRODUIT_EQUIPE` FOREIGN KEY (`owner_equipe_id`) REFERENCES `equipe` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_PRODUIT_USER` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `recommendation`
--
ALTER TABLE `recommendation`
  ADD CONSTRAINT `FK_433224D2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_433224D2F347EFB` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`);

--
-- Contraintes pour la table `recrutement`
--
ALTER TABLE `recrutement`
  ADD CONSTRAINT `FK_25EB23196D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`);

--
-- Contraintes pour la table `resultat_tournoi`
--
ALTER TABLE `resultat_tournoi`
  ADD CONSTRAINT `FK_EC3E38FF7E0950D9` FOREIGN KEY (`id_tournoi`) REFERENCES `tournoi` (`id_tournoi`);

--
-- Contraintes pour la table `team_reports`
--
ALTER TABLE `team_reports`
  ADD CONSTRAINT `FK_E66D6726D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_E66D672E1CFE6F5` FOREIGN KEY (`reporter_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tournoi`
--
ALTER TABLE `tournoi`
  ADD CONSTRAINT `FK_TOURNOI_CREATOR` FOREIGN KEY (`creator_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `tournoi_match`
--
ALTER TABLE `tournoi_match`
  ADD CONSTRAINT `FK_TOURNOI_MATCH_PLAYER_A` FOREIGN KEY (`player_a_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_TOURNOI_MATCH_PLAYER_B` FOREIGN KEY (`player_b_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_TOURNOI_MATCH_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_saved_posts`
--
ALTER TABLE `user_saved_posts`
  ADD CONSTRAINT `FK_saved_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_saved_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
--
-- Base de donnÃ©es : `esportify1`
--
