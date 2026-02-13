-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 11 fév. 2026 à 21:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `esportify`
--

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
  `media_type` varchar(255) NOT NULL DEFAULT 'text',
  `media_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `announcements`
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
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `candidature`
--

INSERT INTO `candidature` (`id`, `niveau`, `motivation`, `statut`, `date_candidature`, `reason`, `play_style`, `equipe_id`, `user_id`) VALUES
(5, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-05 02:07:37', 'dcdc', 'dcdc', 44, 0),
(6, 'D?butant', 'Candidature spontan?e', 'Accept?', '2026-02-05 14:08:14', 'aa', 'aa', 45, 0),
(7, 'Interm?diaire', 'Candidature spontan?e', 'En attente', '2026-02-05 14:23:41', 'aa', 'aa', 46, 0),
(8, 'Interm?diaire', 'Candidature spontan?e', 'Accept?', '2026-02-06 15:32:24', 'aaaaaaaaaaaaaaaaaaaaa', 'aaaaaa', 51, 0),
(9, 'Interm??diaire', 'Candidature spontan??e', 'En attente', '2026-02-06 20:19:46', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaa', 55, 0),
(10, 'D????butant', 'Candidature spontan????e', 'En attente', '2026-02-06 23:04:34', 'je veux participer', 'gta fortnite', 61, 0),
(11, 'Interm????diaire', 'Candidature spontan????e', 'Accept??', '2026-02-06 23:10:23', 'aaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0),
(12, 'D????butant', 'Candidature spontan????e', 'En attente', '2026-02-06 23:18:20', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 62, 0),
(13, 'Confirm??', 'Candidature spontan??e', 'En attente', '2026-02-07 00:01:10', 'aaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 62, NULL),
(14, 'D??butant', 'Candidature spontan??e', 'En attente', '2026-02-07 00:02:41', 'aaaaaaaaaaaaaaaaaaa', 'azertyu', 62, 3),
(15, 'D??butant', 'Candidature spontan??e', 'En attente', '2026-02-07 00:04:24', 'aaaaaaaaaaaaa', 'eryyaydydyyq', 63, 3),
(16, 'Interm??diaire', 'Candidature spontan??e', 'En attente', '2026-02-07 00:10:35', 'aaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaa', 55, 3),
(17, 'Interm??diaire', 'Candidature spontan??e', 'En attente', '2026-02-07 00:12:23', 'aaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaa', 64, 3),
(19, 'Débutant', 'Candidature spontanée', 'En attente', '2026-02-10 10:10:02', 'hello kfdnksqdgnkmjgqsrzg', 'hellooo', 73, 4),
(21, 'Débutant', 'Candidature spontanée', 'Accepté', '2026-02-10 11:56:27', 'aaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaa', 74, 4);

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id`, `nom`) VALUES
(1, 'souris'),
(2, 'pc gamer'),
(3, 'clavier'),
(4, 'carte mre');

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
  `adresse_detail` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id`, `nom`, `prenom`, `adresse`, `quantite`, `numtel`, `statut`, `pays`, `gouvernerat`, `code_postal`, `adresse_detail`) VALUES
(4, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(5, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(6, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(7, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(8, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(9, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(10, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(11, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(12, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(13, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(14, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(15, 'aaaaaaaa', 'aaaaaaaaaa', 'aaaaaaaaaaa', 2, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(16, 'aaaaaaaa', 'aaaaaaaaaa', 'manouba', 3, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(17, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(18, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(19, 'dhifallah', 'aysser', NULL, 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(20, 'dhifallah', 'aysser', 'manouba', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(21, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(22, 'dhifallah', 'aaaaaaaa', 'manouba', 3, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(23, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(24, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(25, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(26, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(27, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(28, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(29, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(30, 'dhifallah', 'aaaaaaaa', NULL, 1, 29787777, 'cancelled', NULL, NULL, NULL, NULL),
(31, 'aaaaa', 'aaaaaaaa', NULL, 1, 566654455, 'cancelled', NULL, NULL, NULL, NULL),
(32, 'aaaaa', 'aaaaaaaa', NULL, 1, 111111111, 'cancelled', NULL, NULL, NULL, NULL),
(33, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(34, 'dhifallah', 'aaaaaaaa', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(35, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(36, 'dhifallah', 'aaaaaaaa', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(37, 'dhifallah', 'aysser', 'k?libia', 1, 29797950, 'cancelled', 'Tunisie', 'manouba', '2010', 'hay said beja'),
(38, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(39, 'dhifallah', 'aysser', 'k?libia', 1, 29797950, 'pending_payment', 'Tunisie', 'manouba', '2010', 'hay said beja'),
(40, 'dhifallah', 'aysser', 'kelibia', 2, 1232454333, 'pending_payment', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(41, 'dhifallah', 'aysser', 'kelibia', 2, 29787777, 'pending_payment', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(42, NULL, NULL, NULL, NULL, NULL, 'cancelled', NULL, NULL, NULL, NULL),
(43, 'dhifallah', 'aysser', 'kelibia', 2, 29787777, 'pending_payment', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(44, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(45, 'dhifallah', 'aysser', 'kelibia', 2, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(46, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(47, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'cancelled', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(48, 'dhifallah', 'aysser', 'kelibia', 1, 29787777, 'pending_payment', 'Tunisie', 'manouba', '2010', 'manouba hay said'),
(49, 'mohamed', 'bouzid', 'arii', 3, 22222222, 'pending_payment', 'tunisie', 'oo', '2000', 'opzkdncc'),
(50, 'Mohamed', 'Bouzid', 'ennasr', 3, 53359999, 'cancelled', 'tunisie', 'ariana', '2222', 'eeeeee'),
(51, 'aa', 'aa', 'azeae', 1, 22222222, 'pending_payment', 'Tunisia', 'azaze', '22', 'aaaa'),
(52, 'admama', 'sssss', 'aa', 3, 22222222, 'pending_payment', 'aaaa', 'aaaa', '2000', 'aaaaaaaaaaaa'),
(53, NULL, NULL, NULL, NULL, NULL, 'draft', NULL, NULL, NULL, NULL),
(54, 'Bensalah', 'Amen', 'hay ezzouhour 3', 1, 55555555, 'pending_payment', 'Tunisie', 'manouba', '2113', 'aaaaaaaaaaaaaaa'),
(55, 'Ben Salah', 'Amen', 'aaaaaaaaa', 1, 12345678, 'pending_payment', 'tunisie', 'aaaaa', '2010', 'aaaaaaaaaaaaaaaaa'),
(56, 'aysser', 'dhifallaah', 'aaaaaaaaa', 2, 12345678, 'pending_payment', 'tunisie', 'aaaaa', '2014', 'aaaaaaaa'),
(57, 'dhifallah', 'aysser', NULL, 1, 12345678, 'draft', NULL, NULL, NULL, NULL);

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
-- Déchargement des données de la table `commentaires`
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
(11, 13, 21, 'super', '2026-02-11 21:05:17');

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime,
  `execution_time` int(11)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260210093947', '2026-02-10 10:44:00', 26);

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
  `is_private` tinyint(4) NOT NULL DEFAULT 0,
  `manager_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `equipe`
--

INSERT INTO `equipe` (`id`, `nom_equipe`, `logo`, `description`, `date_creation`, `classement`, `tag`, `region`, `max_members`, `is_private`, `manager_id`) VALUES
(44, 'youssef team', '6983ecf5e0b1a.jpg', 'go', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Middle East', 5, 0, NULL),
(45, 'aysser', NULL, 'dsde', '2026-02-05 00:00:00', 'Challenger', 'ZZD', 'Middle East', 5, 0, NULL),
(46, 'ghaieth team', NULL, 'ggg', '2026-02-05 00:00:00', 'Argent', 'YSF', 'Europe', 5, 0, NULL),
(48, 'team gloriuisaaa', NULL, 'aaaaa', '2026-02-05 00:00:00', 'Or', 'GLRX', 'Europe', 5, 0, NULL),
(50, 'ghaieth team', NULL, '2000', '2026-02-05 00:00:00', 'Argent', 'GBFGB', 'Europe', 5, 0, NULL),
(51, 'youssef team', NULL, 'hhhh', '2026-02-06 00:00:00', 'Bronze', 'GBFGB', 'South America', 5, 0, NULL),
(54, 'Ghaieth teams', '6985f8388e7ad.jpg', 'aa', '2026-02-06 00:00:00', 'Bronze', 'GG', 'North America', 50, 1, NULL),
(55, 'aaaaa', '6985fdc919855.png', 'aaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Or', 'AAAAA', 'Middle East', 5, 0, NULL),
(56, 'blender team', '69864cb5ec68a.png', 'olaolaola', '2026-02-06 21:19:01', 'Argent', 'BLD', 'North America', 50, 0, NULL),
(61, 'blender team', '6986649d1b169.jpg', 'allez allez', '2026-02-06 00:00:00', 'Or', 'GG', 'Europe', 50, 0, NULL),
(62, 'Ghaieth teams', '6986666ea49d8.png', 'aaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Argent', 'GG', 'Asia', 50, 0, NULL),
(63, 'sarra team', '6986735d8aa52.jpg', 'sarraaaaaa', '2026-02-06 00:00:00', 'Or', 'SARRA', 'Asia', 50, 0, NULL),
(64, 'sarroura team', '698675366d044.png', 'aaaaaaaaaaaaaaaaaaaaaa', '2026-02-06 00:00:00', 'Or', 'SARRA', 'Europe', 50, 0, NULL),
(72, 'aysser123', NULL, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-02-10 00:00:00', 'Bronze', 'AAA', 'Europe', 5, 0, 2),
(73, 'ahmed', NULL, 'aaaaaaaaaaaaaa', '2026-02-10 00:00:00', 'Bronze', 'PHX', 'Europe', 5, 0, 1),
(74, 'test1', '698b0ea4d32c8.png', 'aaaaaaaaaaaaaaa', '2026-02-10 00:00:00', 'Or', 'PHX', 'Europe', 5, 0, 12);

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
-- Déchargement des données de la table `event_participants`
--

INSERT INTO `event_participants` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(1, 3, 21, '2026-02-10 01:18:52'),
(2, 4, 21, '2026-02-10 01:18:52'),
(4, 13, 21, '2026-02-11 21:05:08');

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
-- Déchargement des données de la table `ligne_commande`
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
(72, 1, 100, 57, 1);

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
-- Déchargement des données de la table `likes`
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
(18, 13, 21, '2026-02-11 21:05:11'),
(19, 13, 29, '2026-02-11 21:05:31'),
(20, 14, 29, '2026-02-11 21:26:07');

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
-- Déchargement des données de la table `manager_request`
--

INSERT INTO `manager_request` (`id`, `motivation`, `status`, `created_at`, `user_id`, `nom`, `experience`) VALUES
(1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 16:16:23', 3, 'ghaieth', 'aaaaaaaaaaaaaaaaaaa'),
(2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-07 17:17:51', 4, 'ghaieth', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaa'),
(3, 'test', 'accepted', '2026-02-09 15:04:45', 11, 'Mohamed', 'Test'),
(4, 'test', 'accepted', '2026-02-09 15:06:10', 11, 'Mohamed bouzid', 'test test test etc'),
(5, 'sssssss', 'accepted', '2026-02-09 23:31:09', 11, 'aaa', 'zzz'),
(6, 'aaaaaaaaaaaaaaaaaaaaaaaa', 'accepted', '2026-02-10 03:58:04', 4, 'aaaaa', 'aaaaaaaaaaaaaaa'),
(7, 'aaaaaaaaaaaaaaaaaaaaaaaa', 'pending', '2026-02-10 05:15:41', 4, 'aaaaaaa', 'aaaaaaaaaaaa'),
(8, 'aaaaaaaaaaaaaaaaaaaaaaaaaa', 'pending', '2026-02-10 11:53:16', 12, 'Ben Salah', 'aaaaaaaaaaaaaaaaaaa');

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
-- Déchargement des données de la table `messenger_messages`
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
-- Structure de la table `participation`
--

CREATE TABLE `participation` (
  `tournoi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `participation`
--

INSERT INTO `participation` (`tournoi_id`, `user_id`) VALUES
(1, 4),
(1, 11),
(3, 4);

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
-- Déchargement des données de la table `participation_request`
--

INSERT INTO `participation_request` (`id`, `user_id`, `tournoi_id`, `status`, `message`, `player_level`, `rules_accepted`, `applicant_name`, `applicant_email`, `created_at`) VALUES
(1, 4, 1, 'approved', 'aaaaaaaaaaaaaaaaaaaaaaaaaaa', 'amateur', 1, NULL, NULL, '2026-02-08 18:43:32'),
(3, 11, 1, 'approved', 'Aa', 'debutant', 1, NULL, NULL, '2026-02-08 22:17:31'),
(4, 4, 2, 'rejected', 'a', 'amateur', 1, NULL, NULL, '2026-02-10 05:06:57'),
(5, 4, 3, 'approved', 'hahahhahahahahahhhahha', 'debutant', 1, NULL, NULL, '2026-02-10 12:07:05');

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

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `content` longtext DEFAULT NULL,
  `media_type` varchar(255) NOT NULL,
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
-- Déchargement des données de la table `posts`
--

INSERT INTO `posts` (`id`, `content`, `media_type`, `media_filename`, `created_at`, `image_path`, `video_url`, `is_event`, `event_title`, `event_date`, `event_location`, `max_participants`, `author_id`) VALUES
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
(29, 'test', '', NULL, '2026-02-10 11:46:08', NULL, NULL, 0, NULL, NULL, NULL, NULL, 12);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id` int(11) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL,
  `owner_equipe_id` int(11) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prix` double NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `statut` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id`, `categorie_id`, `owner_user_id`, `owner_equipe_id`, `nom`, `prix`, `description`, `image`, `stock`, `statut`) VALUES
(1, 4, NULL, NULL, 'carte mere hytts', 100, 'La carte m??re est le circuit imprim?? principal d\'un ordinateur, agissant comme le syst??me nerveux central qui interconnecte le processeur, la m??moire (RAM), le stockage et les p??riph??riques. Elle g??re l\'alimentation et la communication entre ces composants via des bus, tout en d??finissant les capacit??s d\'??volution de la machine.', 'uploads/images/Capture-d-ecran-2026-02-07-232806-6987c004318d1.png', 140, 'disponible'),
(3, 4, NULL, NULL, 'carte mere ttht7410', 140, 'La carte m??re est le circuit imprim?? principal d\'un ordinateur, agissant comme le syst??me nerveux central qui interconnecte le processeur, la m??moire (RAM), le stockage et les p??riph??riques. Elle g??re l\'alimentation et la communication entre ces composants via des bus, tout en d??finissant les capacit??s d\'??volution de la machine.', 'uploads/images/Capture-d-ecran-2026-02-07-232800-6987bfea8b7f4.png', 21, 'disponible'),
(4, 2, NULL, NULL, 'pc gamer mpla', 140, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??riques (??cran, clavier, souris). Il fonctionne sous un syst??me d\'exploitation qui g??re le multit??che.', 'uploads/images/Capture-d-ecran-2026-02-07-232638-6987bfb876b63.png', 0, 'disponible'),
(5, 2, NULL, NULL, 'pc gamer', 1405, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??riques (??cran, clavier, souris). Il fonctionne sous un syst??me d\'exploitation qui g??re le multit??che.', 'uploads/images/Capture-d-ecran-2026-02-07-232644-6987bf9e010a5.png', 40, 'disponible'),
(6, 2, NULL, NULL, 'pc gamer', 1400, 'Un ordinateur personnel (PC) est un appareil num??rique polyvalent (travail, jeux, internet) compos?? d\'??l??ments mat??riels essentiels : processeur (cerveau), carte m??re (connexion), m??moire RAM (temporaire), stockage SSD/HDD (permanent) et p??riph??riques (??cran, clavier, souris). Il fonctionne sous un syst??me d\'exploitation qui g??re le multit??che.', 'uploads/images/Capture-d-ecran-2026-02-07-232649-6987bf647206e.png', 140, 'disponible'),
(10, 1, NULL, NULL, 'souris httys', 120, 'La souris est un petit mammif??re rongeur (5-10 cm, 20-70 g) de la famille des murid??s, caract??ris?? par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232715-6987bf8533615.png', 110, 'disponible'),
(11, 1, NULL, NULL, 'souris htx2012', 120, 'La souris est un petit mammif??re rongeur (5-10 cm, 20-70 g) de la famille des murid??s, caract??ris?? par un museau pointu, de grandes oreilles, une longue queue et un pelage souvent gris ou brun', 'uploads/images/Capture-d-ecran-2026-02-07-232705-6987bf38f2071.png', 140, 'disponible'),
(12, 3, NULL, NULL, 'clavier', 15, 'Un clavier d\'ordinateur est un p??riph??rique d\'entr??e essentiel, compos?? d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un syst??me informatique. Issu des machines ?? ??crire, il se d??cline en versions filaires (USB) ou sans fil, avec des dispositions sp??cifiques comme l\'AZERTY (francophone) ou le QWERTY (anglophone), et inclut souvent un pav?? num??rique.', 'uploads/images/Capture-d-ecran-2026-02-07-232728-69885ea0f1647.png', 140, 'disponible'),
(13, 3, NULL, NULL, 'clavier tt47', 16, 'Un clavier d\'ordinateur est un p??riph??rique d\'entr??e essentiel, compos?? d\'environ 100 touches, permettant de saisir du texte, des chiffres et de commander un syst??me informatique. Issu des machines ?? ??crire, il se d??cline en versions filaires (USB) ou sans fil, avec des dispositions sp??cifiques comme l\'AZERTY (francophone) ou le QWERTY (anglophone), et inclut souvent un pav?? num??rique.', 'uploads/images/Capture-d-ecran-2026-02-07-232732-69885eba30249.png', 15, 'disponible');

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
-- Déchargement des données de la table `tournoi`
--

INSERT INTO `tournoi` (`id_tournoi`, `creator_id`, `name`, `type_tournoi`, `type_game`, `game`, `start_date`, `end_date`, `status`, `prize_won`, `max_places`) VALUES
(1, 1, 'youssef', 'solo', 'Sports', 'khbjk', '2026-02-09 18:34:00', '2026-02-10 18:35:00', 'planned', 200202, 10),
(2, 1, 'OneDose', 'squad', 'FPS', 'cs 2', '2026-02-25 22:58:00', '2026-02-28 22:58:00', 'planned', 10, 16),
(3, 1, 'youssef 2025', 'solo', 'FPS', 'valorant', '2026-02-12 12:05:00', '2026-02-14 12:05:00', 'planned', 10000, 10);

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
  `pseudo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `nom`, `role`, `pseudo`) VALUES
(1, 'admin@admin.com', '$2y$13$o6YcNLpmzRJ.E5fiM4F8lOx.u/2wwF5QKTfOEhYIvR4KQcbZ.krSe', 'Admin', 'ROLE_ADMIN', NULL),
(2, 'ghaiethbouamor23@gmail.com', '$2y$13$/HD5uZXi9theKh3UhHsWy.7oO3w6G53Ep9puU0CCw0DhBKLd/n.I6', 'ali', 'ROLE_JOUEUR', 'gaaloul'),
(3, 'ghaiethbouamor013@gmail.com', '$2y$13$2OjJLG7mu71vv2WQGqzLC.2N4mTCiPQEW2Jtpycgm8aA08wtxj8ZG', 'ghaieth', 'ROLE_JOUEUR', 'parker'),
(4, 'ghaiethbouamor773@gmail.com', '$2y$13$jSxjw3XTopDlp8LVyeBhG.7275Zm1CS19OMkZBqLR3kybr/vl2XHO', 'ghaieth', 'ROLE_JOUEUR', '7riga'),
(5, 'ilyeszid@esprit.tn', 'azerty', '', 'ROLE_JOUEUR', NULL),
(6, 'ilyes.zid@esprit.tn', '$2y$13$lvBoJoMoPHDDyOJyjwfRu.H9J1lHxzBOENFKXgyCsyq6Yun2aaE6a', 'ilyes zid', 'ROLE_JOUEUR', 'ilyes'),
(8, 'ilyes14@gmail.com', '$2y$13$4wFj53Knc/.qnqIwvY3IpeV0uxaOqvMep4X2n945MYXzdWC2kUz2W', 'ilyes', 'ROLE_JOUEUR', 'ilyes'),
(11, 'gmail@mohamed.nt', '$2y$13$DRVaJyN3.1zoP3A7Xb0CyeQNK6rKCO/ZDLqrHbNr3yFtBWJxobPoW', 'mohamedaaa', 'ROLE_MANAGER', 'amnesia'),
(12, 'amen@amen.com', '$2y$13$PoClSjS/niOHDOflV3zfwul5eiTSJR6q.8ThIzWO1Js48v9oyPfLe', 'amen', 'ROLE_MANAGER', 'amen123'),
(13, 'amenbensalah038@gmail.com', '$2y$13$K9QaQXTVDGPqHF/L.y0JPubBEuEMPQA3RvssZdhig6RkqMn.mgIE2', 'amen', 'ROLE_JOUEUR', 'amen1'),
(14, 'mortadhaamaidi054@gmail.com', '$2y$13$yrXuyYHM.Ds7gqFbEkbjieU0u3BGkA8IoRDbo0g6835GM7SrdUZVe', 'mortadha', 'ROLE_JOUEUR', 'aasbalyoussef');

-- --------------------------------------------------------

--
-- Structure de la table `user_saved_posts`
--

CREATE TABLE `user_saved_posts` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_saved_posts`
--

INSERT INTO `user_saved_posts` (`user_id`, `post_id`) VALUES
(3, 19),
(3, 21),
(4, 18),
(4, 20),
(4, 23);

--
-- Index pour les tables déchargées
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
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `FK_MANAGER_USER` (`manager_id`);

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
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_PRODUIT_CATEGORIE` (`categorie_id`),
  ADD KEY `IDX_PRODUIT_USER` (`owner_user_id`),
  ADD KEY `IDX_PRODUIT_EQUIPE` (`owner_equipe_id`);

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
-- Index pour la table `tournoi`
--
ALTER TABLE `tournoi`
  ADD PRIMARY KEY (`id_tournoi`),
  ADD KEY `IDX_TOURNOI_CREATOR` (`creator_id`);

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
  ADD KEY `IDX_saved_user` (`user_id`),
  ADD KEY `IDX_saved_post` (`post_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `equipe`
--
ALTER TABLE `equipe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT pour la table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `manager_request`
--
ALTER TABLE `manager_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `participation_request`
--
ALTER TABLE `participation_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- AUTO_INCREMENT pour la table `tournoi`
--
ALTER TABLE `tournoi`
  MODIFY `id_tournoi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `candidature`
--
ALTER TABLE `candidature`
  ADD CONSTRAINT `FK_E33BD3B86D861B89` FOREIGN KEY (`equipe_id`) REFERENCES `equipe` (`id`);

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `FK_commentaires_author` FOREIGN KEY (`author_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_commentaires_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `equipe`
--
ALTER TABLE `equipe`
  ADD CONSTRAINT `FK_2443196276C50E4A` FOREIGN KEY (`manager_id`) REFERENCES `user` (`id`);

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
-- Contraintes pour la table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `FK_PARTICIPATION_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PARTICIPATION_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `participation_request`
--
ALTER TABLE `participation_request`
  ADD CONSTRAINT `FK_PR_TOURNOI` FOREIGN KEY (`tournoi_id`) REFERENCES `tournoi` (`id_tournoi`) ON DELETE CASCADE,
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
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `FK_PRODUIT_CATEGORIE` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_PRODUIT_EQUIPE` FOREIGN KEY (`owner_equipe_id`) REFERENCES `equipe` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_PRODUIT_USER` FOREIGN KEY (`owner_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

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
-- Contraintes pour la table `tournoi`
--
ALTER TABLE `tournoi`
  ADD CONSTRAINT `FK_TOURNOI_CREATOR` FOREIGN KEY (`creator_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `user_saved_posts`
--
ALTER TABLE `user_saved_posts`
  ADD CONSTRAINT `FK_saved_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_saved_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
