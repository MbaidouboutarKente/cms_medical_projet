-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : jeu. 05 juin 2025 à 21:31
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `medical_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `analyses`
--

CREATE TABLE `analyses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `symptoms` text NOT NULL,
  `temperature` float NOT NULL,
  `oxygenLevel` int(11) NOT NULL,
  `eyeColor` varchar(20) NOT NULL,
  `urineColor` varchar(20) NOT NULL,
  `result` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `severity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `analyses`
--

INSERT INTO `analyses` (`id`, `user_id`, `age`, `symptoms`, `temperature`, `oxygenLevel`, `eyeColor`, `urineColor`, `result`, `created_at`, `severity`) VALUES
(1, 5, 25, 'koijkag', 12, 21, 'jaunâtre', 'jaune clair', 'Saturation critique, consultez immédiatement ! Yeux jaunes, problème hépatique possible. ', '2025-05-26 16:25:39', 0),
(2, 5, 25, 'fshdsgh', 39, 90, 'normal', 'jaune clair', '🚨 URGENCE : Consultez un médecin !\n🟥 Fièvre élevée ! Consultez un médecin rapidement.\n🟠 Oxygène légèrement faible. Surveillez.', '2025-05-26 18:32:41', 2),
(3, 5, 25, 'fshdsgh', 39, 90, 'normal', 'jaune clair', '🚨 URGENCE : Consultez un médecin !\n🟥 Fièvre élevée ! Consultez un médecin rapidement.\n🟠 Oxygène légèrement faible. Surveillez.', '2025-05-26 20:16:04', 2),
(4, 5, 25, 'fshdsgh', 37, 100, 'normal', 'jaune clair', '✅ Aucune anomalie détectée.', '2025-05-26 20:17:07', 0),
(5, 5, 37, 'Toux sèche ', 37, 95, 'normal', 'brunâtre', '🚨 URGENCE : Consultez un médecin !\n🟡 Infection respiratoire possible (grippe, bronchite).\n🟤 Urine très foncée : Problème rénal/hépatique.', '2025-05-26 20:24:41', 2),
(6, 5, 25, 'bbkj kjnlnl', 37, 70, 'normal', 'jaune clair', '🚨 URGENCE : Consultez un médecin !\n🔴 Saturation critique ! Consultation immédiate.', '2025-05-29 11:05:56', 2),
(7, 5, 25, 'fdhvbjds', 37, 70, 'normal', 'jaune clair', '🚨 URGENCE : Consultez un médecin !\n🔴 Saturation critique ! Consultation immédiate.', '2025-06-05 13:17:48', 2);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icone` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `icone`) VALUES
(1, 'Antidouleurs', 'Médicaments contre la douleur', 'fa-pills'),
(2, 'Antibiotiques', 'Traitement des infections bactériennes', 'fa-bacteria'),
(3, 'Vitamines', 'Compléments vitaminiques', 'fa-apple-alt'),
(4, 'Soins externes', 'Crèmes et pommades', 'fa-spray-can'),
(5, 'Autres', 'Autres types de médicaments', 'fa-plus-circle');

-- --------------------------------------------------------

--
-- Structure de la table `certificats`
--

CREATE TABLE `certificats` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medecin_id` int(11) NOT NULL,
  `type_certificat` varchar(50) NOT NULL DEFAULT 'standard',
  `contenu` text NOT NULL,
  `statut` enum('en attente','validé') DEFAULT 'en attente',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_validation` timestamp NULL DEFAULT NULL,
  `patient_nom` varchar(50) NOT NULL,
  `medecin_nom` varchar(50) NOT NULL,
  `commentaire_medecin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `certificats`
--

INSERT INTO `certificats` (`id`, `patient_id`, `medecin_id`, `type_certificat`, `contenu`, `statut`, `date_creation`, `date_validation`, `patient_nom`, `medecin_nom`, `commentaire_medecin`) VALUES
(6, 5, 6, 'standard', 'iarujorgwkrmgsf', 'en attente', '2025-05-28 11:59:00', NULL, 'Mbairamadji', 'medecin', NULL),
(7, 5, 6, 'urgence', 'b vjkmk ijioj;lm fh', 'en attente', '2025-05-28 11:59:12', NULL, 'Mbairamadji', 'medecin', NULL),
(8, 5, 6, 'standard', 'Teste de verification', 'en attente', '2025-05-28 12:05:21', NULL, 'Mbairamadji', 'medecin', NULL),
(9, 5, 6, 'aptitude', 'erygtouegh mnldfnm.b ff', 'en attente', '2025-05-28 13:22:50', NULL, 'Mbairamadji', 'medecin', NULL),
(10, 5, 6, 'standard', 'eaw', 'en attente', '2025-05-28 13:35:05', NULL, 'Mbairamadji', 'medecin', NULL),
(11, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:12', '2025-05-28 15:36:18', 'Mbairamadji', 'medecin', 'ca marche tres bien deja'),
(12, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:13', '2025-05-28 15:21:50', 'Mbairamadji', 'medecin', 'egijpptjhejgteh'),
(13, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:14', '2025-05-28 15:21:07', 'Mbairamadji', 'medecin', 'egijpptjhejgteh'),
(14, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:16', '2025-05-28 15:07:32', 'Mbairamadji', 'medecin', NULL),
(15, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:16', '2025-05-28 14:57:41', 'Mbairamadji', 'medecin', NULL),
(16, 5, 6, 'standard', 'eawjhs ajklsndan', 'validé', '2025-05-28 13:35:16', '2025-05-28 15:13:24', 'Mbairamadji', 'medecin', 'Aucun commentaire ajouté'),
(17, 5, 6, 'aptitude', 'wadafvgrfsabfabvfs', 'en attente', '2025-05-28 18:01:38', NULL, 'Mbairamadji', 'medecin', NULL),
(18, 5, 6, 'aptitude', 'Pour un toto le frere de titi et l&#39;enfant de tata', 'en attente', '2025-05-28 18:02:53', NULL, 'Mbairamadji', 'medecin', NULL),
(19, 5, 6, 'absence', 'rgfuhvfbj jhlefbn dv', 'en attente', '2025-05-28 18:34:43', NULL, 'Mbairamadji', 'medecin', NULL),
(20, 5, 6, 'aptitude', 'ascvnlknksvb [ mfela;bpkprlngpkl nohitgejfbvod', 'en attente', '2025-05-28 22:31:44', NULL, 'Mbairamadji', 'medecin', NULL),
(21, 5, 6, 'absence', 'Pour mon petit', 'en attente', '2025-05-29 00:22:56', NULL, 'Mbairamadji', 'medecin', NULL),
(22, 5, 6, 'urgence', 'gasgbhdfngdsndbgs', 'en attente', '2025-05-29 10:59:54', NULL, 'Mbairamadji', 'medecin', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commandes_pharma`
--

CREATE TABLE `commandes_pharma` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_commande` datetime NOT NULL DEFAULT current_timestamp(),
  `date_retrait` datetime DEFAULT NULL,
  `statut` enum('en_attente','prete','retiree','annulee') NOT NULL DEFAULT 'en_attente',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes_pharma`
--

INSERT INTO `commandes_pharma` (`id`, `user_id`, `date_commande`, `date_retrait`, `statut`, `notes`) VALUES
(4, 5, '2025-05-27 02:04:31', NULL, 'en_attente', NULL),
(5, 5, '2025-05-27 02:05:37', NULL, 'en_attente', NULL),
(6, 5, '2025-05-27 02:07:43', NULL, 'en_attente', NULL),
(7, 5, '2025-05-27 02:22:07', NULL, 'en_attente', NULL),
(8, 5, '2025-05-27 02:23:13', NULL, 'en_attente', NULL),
(9, 5, '2025-05-27 02:27:08', NULL, 'en_attente', NULL),
(10, 5, '2025-05-29 12:06:53', NULL, 'en_attente', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `consultations`
--

CREATE TABLE `consultations` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `professionnel_sante_id` int(11) NOT NULL,
  `rendez_vous_id` int(11) NOT NULL,
  `date_consultation` datetime NOT NULL,
  `motif` text NOT NULL,
  `diagnostic` text DEFAULT NULL,
  `traitement` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','archived') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mbairamadji clark', 'mbai@gmail.com', 'Verification', 'Le teste d\'ensemble', 'read', '2025-05-30 02:12:31', '2025-06-05 16:58:43'),
(2, 'Mbairamadji', 'mbai@gmail.com', 'Question générale', 'Vous faites quoi???', 'read', '2025-05-30 09:01:55', '2025-06-05 16:48:44');

-- --------------------------------------------------------

--
-- Structure de la table `demandes_analyses`
--

CREATE TABLE `demandes_analyses` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medecin_id` int(11) DEFAULT NULL,
  `date_demande` datetime DEFAULT current_timestamp(),
  `statut` enum('en attente','en cours','terminé','annulé') DEFAULT 'en attente',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `details_commande`
--

CREATE TABLE `details_commande` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `medicament_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dossiers_medicaux`
--

CREATE TABLE `dossiers_medicaux` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `groupe_sanguin` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `antécédents_medicaux` text DEFAULT NULL,
  `traitements` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dossiers_medicaux`
--

INSERT INTO `dossiers_medicaux` (`id`, `patient_id`, `groupe_sanguin`, `allergies`, `antécédents_medicaux`, `traitements`, `notes`, `created_at`) VALUES
(1, 5, 'O-', 'TPE', NULL, NULL, NULL, '2025-06-05 15:45:29');

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-25 18:23:10'),
(2, 1, 'Connexion réussie', 'Rôle: super_admin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-25 18:23:19'),
(3, 2, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 18:23:39'),
(4, NULL, 'Tentative connexion', 'Email inexistant: benadji@gmail.com', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 18:24:20'),
(5, 3, 'Connexion réussie', 'Rôle: admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 18:24:59'),
(6, 7, 'Connexion réussie', 'Rôle: infirmier', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-25 18:27:00'),
(7, 3, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 19:15:42'),
(8, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 19:15:52'),
(9, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-25 19:17:36'),
(10, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-25 19:31:04'),
(11, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-25 20:01:46'),
(12, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 00:21:54'),
(13, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 00:34:06'),
(14, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 00:34:28'),
(15, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 08:48:36'),
(16, 10, 'Inscription', 'Nouveau etudiant: etudiant@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 08:51:16'),
(17, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 08:52:54'),
(18, 1, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 09:00:00'),
(19, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 09:00:10'),
(20, 5, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 09:02:51'),
(21, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 09:03:13'),
(22, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 10:53:52'),
(23, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 11:00:12'),
(24, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 11:42:54'),
(26, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:00:42'),
(27, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:05:37'),
(28, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:06:50'),
(29, 5, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:15:21'),
(30, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:25:25'),
(31, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:25:30'),
(32, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 12:25:35'),
(33, 5, 'Déconnexion', 'Session terminée', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:18'),
(34, 5, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:26'),
(35, 5, 'Déconnexion', 'Session terminée', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:33'),
(36, 7, 'Connexion réussie', 'Rôle: infirmier', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:46'),
(37, 7, 'Déconnexion', 'Session terminée', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:53'),
(38, 1, 'Connexion réussie', 'Rôle: super_admin', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 12:50:58'),
(39, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 16:20:07'),
(40, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 16:20:28'),
(41, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 18:42:46'),
(42, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 18:44:00'),
(43, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 18:44:20'),
(44, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 18:44:59'),
(45, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 18:53:53'),
(46, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 19:27:02'),
(47, 5, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 20:21:27'),
(48, 5, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-26 20:51:34'),
(49, 10, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:19:08'),
(50, 11, 'Inscription', 'Nouveau etudiant: toguem@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:20:23'),
(51, 11, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:22:26'),
(52, 11, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:36:44'),
(53, 11, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:47:02'),
(54, 7, 'Connexion réussie', 'Rôle: infirmier', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:47:21'),
(55, 5, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 21:47:54'),
(56, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-26 21:48:04'),
(57, 7, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:50:24'),
(58, 2, 'Connexion réussie', 'Rôle: admin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 21:50:46'),
(59, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 22:01:38'),
(60, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 22:02:17'),
(61, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-26 23:15:02'),
(62, 5, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 00:05:18'),
(63, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 00:55:49'),
(64, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 01:15:53'),
(65, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 01:17:40'),
(66, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 01:21:35'),
(67, 2, 'Connexion réussie', 'Rôle: admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 01:48:54'),
(68, 2, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 01:49:35'),
(69, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 01:49:48'),
(70, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 02:56:06'),
(71, 1, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 02:56:51'),
(72, 1, 'Connexion réussie', 'Rôle: super_admin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 02:57:06'),
(73, 1, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 02:57:09'),
(74, 10, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 02:57:29'),
(75, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 03:00:57'),
(76, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 08:08:26'),
(77, 10, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 08:21:17'),
(78, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 08:21:29'),
(79, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 08:28:17'),
(80, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 09:30:08'),
(81, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 09:31:02'),
(82, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 09:34:21'),
(83, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 09:34:58'),
(84, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 09:35:08'),
(85, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 09:35:34'),
(86, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 09:37:08'),
(87, 5, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 09:46:59'),
(88, 12, 'Inscription', 'Nouveau etudiant: abbo@gmail.com', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 09:57:48'),
(89, 12, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 09:58:11'),
(90, 12, 'Déconnexion', 'Session terminée', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 09:58:21'),
(91, 6, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:00:17'),
(92, 10, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:00:27'),
(93, 13, 'Inscription', 'Nouveau etudiant: jojo@gmail.com', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 10:02:00'),
(94, 10, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:03:07'),
(95, 13, 'Tentative connexion', 'Mot de passe incorrect', '192.168.67.2', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 10:09:10'),
(96, 13, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.2', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 10:09:14'),
(97, 10, 'Connexion réussie', 'Rôle: etudiant', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:27:35'),
(98, 13, 'Déconnexion', 'Session terminée', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 10:28:37'),
(99, 13, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 10:28:45'),
(100, 13, 'Déconnexion', 'Session terminée', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 10:30:08'),
(101, 13, 'Connexion réussie', 'Rôle: etudiant', '192.168.67.2', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-27 10:30:13'),
(102, 10, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:34:42'),
(103, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 10:34:54'),
(104, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 12:09:16'),
(105, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 14:04:28'),
(106, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-27 14:09:03'),
(107, 6, 'Connexion réussie', 'Rôle: medecin', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-27 14:15:47'),
(108, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-28 10:23:39'),
(109, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 10:27:02'),
(110, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 11:10:57'),
(111, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-28 11:22:05'),
(112, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:48:26'),
(113, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:56:30'),
(114, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:56:57'),
(115, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 13:57:48'),
(116, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 16:37:49'),
(117, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 16:38:02'),
(118, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-28 16:40:36'),
(119, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 16:40:54'),
(120, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 16:41:38'),
(121, 6, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 17:54:28'),
(122, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 17:54:40'),
(123, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-28 23:48:35'),
(124, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:00:17'),
(125, 6, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:00:33'),
(126, 2, 'Connexion réussie', 'Rôle: admin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:00:53'),
(127, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:01:20'),
(128, 1, 'Connexion réussie', 'Rôle: super_admin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:01:31'),
(129, 1, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:02:26'),
(130, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:04:56'),
(131, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 11:12:42'),
(132, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 12:13:39'),
(133, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:00:31'),
(134, NULL, 'Tentative connexion', 'Email inexistant: ahmatidriss@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:02:08'),
(135, NULL, 'Tentative connexion', 'Email inexistant: ahmatidriss@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:03:32'),
(136, NULL, 'Tentative connexion', 'Email inexistant: ahmatidriss@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:07:44'),
(137, NULL, 'Tentative connexion', 'Email inexistant: ahmatidriss@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:08:06'),
(138, 14, 'Inscription', 'Nouveau etudiant: ahmatidriss@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 13:18:47'),
(139, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:44:24'),
(140, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:44:30'),
(141, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:44:55'),
(142, 5, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:45:04'),
(143, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:45:30'),
(144, 2, 'Connexion réussie', 'Rôle: admin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 14:45:45'),
(145, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:26:44'),
(146, 2, 'Connexion réussie', 'Rôle: admin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:27:15'),
(147, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:27:55'),
(148, 6, 'Connexion réussie', 'Rôle: medecin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:28:12'),
(149, 6, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:28:30'),
(150, 2, 'Connexion réussie', 'Rôle: admin', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-29 15:28:46'),
(151, 1, 'Connexion réussie', 'Rôle: super_admin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-29 18:49:49'),
(152, 1, 'Connexion réussie', 'Rôle: super_admin', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 00:38:27'),
(153, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:11:44'),
(154, NULL, 'Tentative connexion', 'Email inexistant: etudiant@gmail.com', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:12:09'),
(155, 10, 'Connexion réussie', 'Rôle: etudiant', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:12:24'),
(156, 10, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:23:42'),
(157, 10, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:23:48'),
(158, 10, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:26:37'),
(159, 10, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:26:43'),
(160, 10, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 03:27:19'),
(161, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 03:30:11'),
(162, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 09:14:06'),
(163, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 10:49:10'),
(164, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:03:23'),
(165, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:05:25'),
(166, 7, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 11:06:08'),
(167, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 11:06:17'),
(168, 14, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:08:08'),
(169, 14, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:09:15'),
(170, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:10:03'),
(171, 10, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 11:11:58'),
(172, 1, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 11:16:15'),
(173, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 11:16:22'),
(174, 5, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-30 13:17:23'),
(175, 5, 'Déconnexion', 'Session terminée', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-30 13:17:37'),
(176, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.216', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Mobile Safari/537.36', '2025-05-30 13:18:16'),
(178, 10, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 13:27:27'),
(179, 10, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 13:27:35'),
(180, 10, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 13:33:23'),
(181, 7, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 13:34:59'),
(182, 7, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 13:38:54'),
(183, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-30 13:41:36'),
(184, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 13:46:57'),
(185, 7, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 14:26:35'),
(186, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 14:27:04'),
(187, 6, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 14:37:52'),
(188, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 14:38:05'),
(189, 7, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 14:38:24'),
(190, 7, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 15:20:34'),
(191, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-30 15:20:52'),
(192, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 15:21:22'),
(193, 6, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-05-30 15:33:31'),
(194, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 12:26:18'),
(195, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:33:29'),
(196, 7, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:34:26'),
(197, 2, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:34:40'),
(198, 2, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:35:49'),
(199, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:36:04'),
(200, 1, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 12:39:54'),
(201, 6, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 13:02:27'),
(202, 6, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:25:38'),
(203, 14, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:26:57'),
(204, 15, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:30:47'),
(205, 15, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:31:20'),
(206, 14, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:32:48'),
(207, 14, 'Déconnexion', 'Session terminée', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:44:21'),
(208, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:44:37'),
(209, 1, 'Connexion réussie', 'Utilisateur connecté.', '192.168.67.153', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-05-31 13:58:26'),
(210, 6, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 14:48:47'),
(211, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 14:49:06'),
(212, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 15:25:17'),
(213, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 15:25:46'),
(214, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 15:27:53'),
(215, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 15:28:16'),
(216, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-05-31 15:46:00'),
(217, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 09:26:43'),
(218, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 10:57:41'),
(219, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 20:39:04'),
(220, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:46:28'),
(221, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 21:28:34'),
(222, 10, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 21:28:59'),
(223, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 22:57:57'),
(224, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 23:00:55'),
(225, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 23:01:16'),
(226, 10, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-01 23:01:21'),
(227, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-02 01:28:03'),
(228, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.43.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-06-03 13:18:06'),
(229, 6, 'Connexion réussie', 'Utilisateur connecté.', '192.168.43.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36', '2025-06-03 13:18:09'),
(230, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-03 13:27:40'),
(231, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-03 13:34:12'),
(232, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-03 19:12:13'),
(233, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-03 19:31:05'),
(234, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-04 10:38:23'),
(235, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-04 16:20:18'),
(236, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-04 17:11:49'),
(237, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-05 08:32:45'),
(238, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 13:49:59'),
(239, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:04:40'),
(240, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:27:57'),
(241, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:28:13'),
(242, 7, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:31:01'),
(243, 5, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:31:17'),
(244, 5, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:32:14'),
(245, 7, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 14:32:31'),
(246, 6, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-05 16:03:44'),
(247, 6, 'Déconnexion', 'Session terminée', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-05 16:03:48'),
(248, 2, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-05 16:04:18'),
(249, 2, 'Connexion réussie', 'Utilisateur connecté.', '::1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-05 16:09:28'),
(250, 7, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 16:20:49'),
(251, 1, 'Connexion réussie', 'Utilisateur connecté.', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 16:21:04'),
(252, 1, 'Déconnexion', 'Session terminée', '127.0.0.1', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0', '2025-06-05 16:59:12');

-- --------------------------------------------------------

--
-- Structure de la table `medicaments`
--

CREATE TABLE `medicaments` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `categorie_id` int(11) NOT NULL,
  `seuil_alerte` int(11) NOT NULL DEFAULT 5,
  `date_ajout` datetime NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `medicaments`
--

INSERT INTO `medicaments` (`id`, `nom`, `description`, `prix`, `stock`, `categorie_id`, `seuil_alerte`, `date_ajout`, `image_path`) VALUES
(6, 'Glibin', 'sfnlvsc', 700.00, 15, 2, 5, '2025-05-27 03:02:40', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `rendez_vous_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `rendez_vous_id`, `message`, `lu`, `created_at`) VALUES
(1, 6, 1, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-04 18:10:18'),
(2, 6, 2, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-05 08:30:39'),
(3, 6, 3, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-05 08:32:09'),
(4, 6, 4, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-05 12:19:36'),
(5, 6, 5, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-05 13:08:08'),
(6, 5, 1, 'Votre rendez-vous a été Confirmé', 0, '2025-06-05 14:05:17'),
(7, 6, 7, 'Nouveau rendez-vous demandé par un étudiant', 1, '2025-06-05 14:13:35'),
(8, 7, 8, 'Nouveau rendez-vous demandé par un étudiant', 0, '2025-06-05 14:31:55'),
(9, 5, 8, 'Votre rendez-vous a été Confirmé', 0, '2025-06-05 14:34:15');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `annee_scolaire` varchar(9) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `statut` enum('en attente','validé','payé') NOT NULL DEFAULT 'en attente',
  `date_paiement` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id`, `utilisateur_id`, `annee_scolaire`, `montant`, `statut`, `date_paiement`) VALUES
(1, 5, '2024-2025', 3000.00, 'payé', '2025-05-24'),
(3, 11, '2024-2025', 3000.00, 'payé', '2025-05-26'),
(6, 14, '2024-2025', 3000.00, 'payé', '2025-05-29'),
(9, 10, '2024-2025', 3000.00, 'payé', '2025-06-01');

-- --------------------------------------------------------

--
-- Structure de la table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Lien vers la table utilisateurs',
  `numero_dossier` varchar(20) DEFAULT NULL COMMENT 'Numéro unique du dossier médical',
  `sexe` enum('Homme','Femme','Autre') DEFAULT NULL COMMENT 'Sexe du patient',
  `groupe_sanguin` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL COMMENT 'Groupe sanguin',
  `allergies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Liste des allergies' CHECK (json_valid(`allergies`)),
  `antecedents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Antécédents médicaux' CHECK (json_valid(`antecedents`)),
  `traitements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Traitements en cours' CHECK (json_valid(`traitements`)),
  `commentaires_medicaux` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_maj` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `patients`
--

INSERT INTO `patients` (`id`, `user_id`, `numero_dossier`, `sexe`, `groupe_sanguin`, `allergies`, `antecedents`, `traitements`, `commentaires_medicaux`, `date_creation`, `date_maj`, `nom`) VALUES
(1, 10, 'PAT-202506-5519', 'Autre', NULL, '[]', '[]', '[]', NULL, '2025-06-01 23:52:01', '2025-06-01 23:52:01', 'Etudiant2022'),
(2, 5, 'PAT-202506-5293', 'Homme', NULL, '[]', '[]', '[]', NULL, '2025-06-02 00:01:01', '2025-06-05 13:25:43', 'Mbairamadji');

-- --------------------------------------------------------

--
-- Structure de la table `pharmacie`
--

CREATE TABLE `pharmacie` (
  `id` int(11) NOT NULL,
  `nom_medicament` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `prix` decimal(10,2) DEFAULT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `professionnel_sante_id` int(11) NOT NULL,
  `date_heure` datetime NOT NULL,
  `statut` enum('En attente','Accepté','Annulé','Confirmé') DEFAULT 'En attente',
  `notification_envoyee` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `motif` text NOT NULL,
  `orientation_medecin` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rendez_vous`
--

INSERT INTO `rendez_vous` (`id`, `patient_id`, `professionnel_sante_id`, `date_heure`, `statut`, `notification_envoyee`, `created_at`, `updated_at`, `motif`, `orientation_medecin`, `notes`) VALUES
(1, 5, 6, '2025-06-12 12:00:00', 'Confirmé', 1, '2025-06-04 18:10:18', '2025-06-05 14:27:44', 'lmmfdm m;bmpgbf', NULL, NULL),
(2, 5, 6, '2025-06-02 12:11:00', 'En attente', 0, '2025-06-05 08:30:39', '2025-06-05 08:30:39', 'fsdhjldskn', NULL, NULL),
(3, 5, 6, '2025-05-31 22:22:00', 'En attente', 0, '2025-06-05 08:32:09', '2025-06-05 08:32:09', 'ydyyfiuhvl', NULL, NULL),
(4, 5, 6, '2025-06-05 22:00:00', 'En attente', 0, '2025-06-05 12:19:36', '2025-06-05 12:19:36', 'djshviv s', NULL, NULL),
(5, 5, 6, '2025-06-21 12:12:00', 'En attente', 0, '2025-06-05 13:08:08', '2025-06-05 13:08:08', 'fgdvm', NULL, NULL),
(6, 2, 6, '2025-06-06 12:22:00', 'En attente', 0, '2025-06-05 13:57:37', '2025-06-05 13:57:37', 'Rien a dire', NULL, NULL),
(7, 5, 6, '2025-06-19 19:19:00', 'En attente', 0, '2025-06-05 14:13:35', '2025-06-05 14:13:35', 'fvhuyscgb', NULL, NULL),
(8, 5, 7, '2025-06-13 12:12:00', 'Confirmé', 0, '2025-06-05 14:31:55', '2025-06-05 14:34:15', 'dfaygoud', NULL, NULL);

--
-- Déclencheurs `rendez_vous`
--
DELIMITER $$
CREATE TRIGGER `after_rendez_vous_update` AFTER UPDATE ON `rendez_vous` FOR EACH ROW BEGIN
    IF NEW.statut != OLD.statut THEN
        INSERT INTO notifications (utilisateur_id, rendez_vous_id, message)
        VALUES (NEW.patient_id, NEW.id, CONCAT('Votre rendez-vous a été ', NEW.statut));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `structures`
--

CREATE TABLE `structures` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `adresse` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `structures`
--

INSERT INTO `structures` (`id`, `nom`, `adresse`, `created_at`, `updated_at`) VALUES
(1, 'wwww', 'w3w3', '2025-05-25 17:49:20', '2025-05-25 17:49:20'),
(2, 'fds', 'fads', '2025-05-25 17:49:43', '2025-05-25 17:49:43'),
(3, 'Structuure 1', 'FAce', '2025-05-29 19:02:31', '2025-05-29 19:02:31');

-- --------------------------------------------------------

--
-- Structure de la table `system_alerts`
--

CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('security','maintenance','new_user','error') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `system_alerts`
--

INSERT INTO `system_alerts` (`id`, `alert_type`, `title`, `message`, `is_read`, `related_id`, `created_at`) VALUES
(1, 'security', 'teste', 'Tester le systeme voir', 0, 5, '2025-05-30 02:14:22');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('etudiant','infirmier','medecin','admin','super_admin') NOT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `matricule` varchar(50) NOT NULL,
  `image` varchar(50) DEFAULT NULL,
  `numero_professionnel` varchar(20) DEFAULT NULL,
  `specialite` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `telephone` varchar(25) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `prenom` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `date_inscription`, `matricule`, `image`, `numero_professionnel`, `specialite`, `is_active`, `last_login`, `telephone`, `updated_at`, `prenom`) VALUES
(1, 'Superadmin', 'superadmin@gmail.com', '$2y$10$aANNigIj9DtpOwFXtDarQex9Cwp/fXEpYlawpEy29DTYz1./D9O0a', 'super_admin', '2025-05-24 12:21:54', 'SSAA', NULL, NULL, '', 1, '2025-06-05 17:21:04', '', '0000-00-00 00:00:00', ''),
(2, 'admin', 'admin@gmail.com', '$2y$10$YdhED6wuuSi2mavcUiVEx.maRQP5RE3P5r6Dl5jzv6pxT3Xu1klvO', 'admin', '2025-05-24 12:31:52', 'AA', NULL, NULL, '', 1, '2025-06-05 17:09:28', '', '0000-00-00 00:00:00', ''),
(3, 'admin2', 'admin2@gmail.com', '$2y$10$o9QqCJYaH06etu1Ra1afYO3bDZvmBxVREHiB9PPC8sN.GxqcgEf0a', 'admin', '2025-05-24 12:32:28', 'AA2', NULL, NULL, '', 1, NULL, '', '0000-00-00 00:00:00', ''),
(4, 'Superadmin2', 'superadmin2@gmail.com', '$2y$10$N7c2/wiRIPuBxMCZ0EDD1eOy04OBgCvzInAf9qmMTy8SwoaSjfJU.', 'super_admin', '2025-05-24 12:33:05', 'SSAA2', NULL, NULL, '', 1, NULL, '', '0000-00-00 00:00:00', ''),
(5, 'Mbairamadji', 'mbairamadjiclark@gmail.com', '$2y$10$XJbb5otz8BYsu56xTyJGKebUFSykcelw0QArcPKxMoqcqRCUyBJLq', 'etudiant', '2025-05-24 12:34:10', '21A245FS', 'mbair.jpg', NULL, '', 1, '2025-06-05 15:31:17', '', '0000-00-00 00:00:00', ''),
(6, 'medecin', 'medecin@gmail.com', '$2y$10$s.kk3B/A1GjgkQwkI/dDnuHlE4v4V.bAWqxLlfATKbnf/hd8pZaFi', 'medecin', '2025-05-24 19:03:26', 'MM1', NULL, 'MEDC1234', 'Generaliste', 1, '2025-06-05 17:03:44', '', '0000-00-00 00:00:00', ''),
(7, 'Infirmier', 'infirmier@gmail.com', '$2y$10$JmLDwvgDMvZX6zwb2ARxmufZHAN0pzOL4Z.zUA5HPpDnw2EQwCJy6', 'infirmier', '2025-05-24 19:06:04', 'INF', NULL, NULL, '', 1, '2025-06-05 15:32:31', '', '0000-00-00 00:00:00', ''),
(9, 'kente', 'kente@gmail.com', '$2y$10$NNiMpKfSQO3oVROc7.O7aegLMgiiGOMxge0LutgjPDIvQo4ZyHQJq', 'admin', '2025-05-25 17:31:13', 'ATEST', NULL, NULL, '', 1, NULL, '', '0000-00-00 00:00:00', ''),
(10, 'Etudiant2022', 'etudiant22@gmail.com', '$2y$10$wXr1IOxE3zWAhofSHBi/fuSblmbddSReM04bFLFKDShqUW2LX.dIi', 'etudiant', '2025-05-26 08:51:16', '22A241FS', 'etudi.jpg', NULL, '', 1, '2025-06-02 00:01:21', '5555555555', '0000-00-00 00:00:00', ''),
(11, 'TOGUEM', 'toguem@gmail.com', '$2y$10$MfBx0Y.0ZhCO/jvHEtEWyOpWBkuRzMMNm0HFctgR6HtvgcfK1.AK.', 'etudiant', '2025-05-26 21:20:23', '21A244FS', NULL, NULL, '', 1, NULL, '', '2025-05-31 18:08:45', ''),
(12, 'ABBO', 'abbo@gmail.com', '$2y$10$Q40h676.IUXLaGr./rHTHO7tg3RGHoaDom36l7nFZZwXSqqy.pZLq', 'infirmier', '2025-05-27 09:57:47', '22A676FS', NULL, NULL, '', 0, NULL, '', '2025-05-31 16:20:20', ''),
(13, 'DJEKORNOM', 'jojo@gmail.com', '$2y$10$gQbF/0ci3pv1KKgQ8O1Ng.Qwu9bmxUGxXlC6jCcnqIsOC0H734/s2', 'etudiant', '2025-05-27 10:01:59', '24A701FS', 'djeko.jpg', NULL, '', 1, NULL, '', '0000-00-00 00:00:00', ''),
(14, 'ahmat idriss', 'ahmatidriss@gmail.com', '$2y$10$20vXo2B28AXLBV3v8qonM.PTN9xiXVhc2zo7JnjKQngI6zCJQhUde', 'admin', '2025-05-29 13:18:47', '21A241FS', NULL, NULL, NULL, 1, '2025-05-31 14:32:48', '', '0000-00-00 00:00:00', ''),
(15, 'Adoum Oumar', 'adoumoumar@gmail.com', '$2y$10$FDm9a2.t2OjfspcuKqQM0OIPewgabxgVtXDLThhPJThNNGsbYbyhC', 'etudiant', '2025-05-30 13:22:03', '22A692FS', NULL, NULL, NULL, 1, '2025-05-31 14:30:47', '', '2025-05-31 18:08:27', '');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `analyses`
--
ALTER TABLE `analyses`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `certificats`
--
ALTER TABLE `certificats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medecin_id` (`medecin_id`),
  ADD KEY `certificats_ibfk_1` (`patient_id`);

--
-- Index pour la table `commandes_pharma`
--
ALTER TABLE `commandes_pharma`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `professionnel_sante_id` (`professionnel_sante_id`),
  ADD KEY `rendez_vous_id` (`rendez_vous_id`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `demandes_analyses`
--
ALTER TABLE `demandes_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `medecin_id` (`medecin_id`);

--
-- Index pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `details_commande_ibfk_2` (`medicament_id`);

--
-- Index pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Index pour la table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `medicaments`
--
ALTER TABLE `medicaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `rendez_vous_id` (`rendez_vous_id`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `numero_dossier` (`numero_dossier`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Index pour la table `pharmacie`
--
ALTER TABLE `pharmacie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `professionnel_sante_id` (`professionnel_sante_id`);

--
-- Index pour la table `structures`
--
ALTER TABLE `structures`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD UNIQUE KEY `id` (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `analyses`
--
ALTER TABLE `analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `certificats`
--
ALTER TABLE `certificats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `commandes_pharma`
--
ALTER TABLE `commandes_pharma`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `demandes_analyses`
--
ALTER TABLE `demandes_analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `details_commande`
--
ALTER TABLE `details_commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT pour la table `medicaments`
--
ALTER TABLE `medicaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `pharmacie`
--
ALTER TABLE `pharmacie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `structures`
--
ALTER TABLE `structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `system_alerts`
--
ALTER TABLE `system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `certificats`
--
ALTER TABLE `certificats`
  ADD CONSTRAINT `certificats_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificats_ibfk_2` FOREIGN KEY (`medecin_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commandes_pharma`
--
ALTER TABLE `commandes_pharma`
  ADD CONSTRAINT `commandes_pharma_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`professionnel_sante_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `consultations_ibfk_3` FOREIGN KEY (`rendez_vous_id`) REFERENCES `rendez_vous` (`id`);

--
-- Contraintes pour la table `demandes_analyses`
--
ALTER TABLE `demandes_analyses`
  ADD CONSTRAINT `demandes_analyses_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `demandes_analyses_ibfk_2` FOREIGN KEY (`medecin_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD CONSTRAINT `details_commande_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes_pharma` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `details_commande_ibfk_2` FOREIGN KEY (`medicament_id`) REFERENCES `medicaments` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dossiers_medicaux`
--
ALTER TABLE `dossiers_medicaux`
  ADD CONSTRAINT `dossiers_medicaux_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `medicaments`
--
ALTER TABLE `medicaments`
  ADD CONSTRAINT `medicaments_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`rendez_vous_id`) REFERENCES `rendez_vous` (`id`);

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`professionnel_sante_id`) REFERENCES `utilisateurs` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
