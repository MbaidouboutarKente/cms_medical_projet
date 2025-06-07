-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : ven. 30 mai 2025 à 18:31
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
-- Base de données : `campus_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `departements`
--

CREATE TABLE `departements` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `departements`
--

INSERT INTO `departements` (`id`, `nom`) VALUES
(2, 'Bio'),
(1, 'Info'),
(3, 'Physique');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
  `id` int(11) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `filiere` varchar(50) NOT NULL,
  `niveau` varchar(20) NOT NULL,
  `statut_etudiant` varchar(20) NOT NULL DEFAULT 'Ancien',
  `date_inscription` date NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'Actif',
  `date_naissance` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id`, `matricule`, `nom`, `email`, `prenom`, `filiere`, `niveau`, `statut_etudiant`, `date_inscription`, `statut`, `date_naissance`) VALUES
(1, '20A108FS', 'ABAKAR SIDDICK', 'abakarsiddick@gmail.com', 'MAHAMAT RAKHIS', 'Informatique', 'Licence 3', 'Ancien', '2020-09-15', 'Actif', '0000-00-00'),
(2, '20A110FS', 'ABBA DJIBIA', 'abbadjibia@gmail.com', 'KALLAH', 'Informatique', 'Licence 3', 'Ancien', '2020-10-03', 'Actif', '0000-00-00'),
(3, '22A676FS', 'ABBO', 'abbo@gmail.com', 'DAHIROU', 'Informatique', 'Licence 3', 'Ancien', '2022-09-25', 'Actif', '0000-00-00'),
(4, '20B159FS', 'ABDOUL AZIZ', 'abdoulaziz@gmail.com', 'NANA', 'Informatique', 'Licence 3', 'Ancien', '2020-10-10', 'Actif', '0000-00-00'),
(5, '22B399FS', 'ABDOUSSALAM', 'abdoussalam@gmail.com', 'SERNO', 'Informatique', 'Licence 3', 'Ancien', '2022-09-30', 'Actif', '0000-00-00'),
(6, '24A411FS', 'ABOLO BORIS', 'aboloboris@gmail.com', 'GRAIG', 'Informatique', 'Licence 3', 'Ancien', '2024-10-07', 'Actif', '0000-00-00'),
(7, '21B089FS', 'ABOUBAKAR', 'aboubakar@gmail.com', 'DEWA', 'Informatique', 'Licence 3', 'Ancien', '2021-09-17', 'Actif', '0000-00-00'),
(8, '21A318FS', 'ADAM MAHAMAT', 'adammahamat@gmail.com', 'ALI', 'Informatique', 'Licence 3', 'Ancien', '2021-10-02', 'Actif', '0000-00-00'),
(9, '21A399FS', 'ADOUM MAHAMAT', 'adoummahamat@gmail.com', 'AHMAT ADOUM', 'Informatique', 'Licence 3', 'Ancien', '2021-09-28', 'Actif', '0000-00-00'),
(10, '21B085FS', 'AHMADOU TIDJANI', 'ahmadoutidjani@gmail.com', NULL, 'Informatique', 'Licence 3', 'Ancien', '2021-09-20', 'Actif', '0000-00-00'),
(11, '21A241FS', 'AHMAT IDRISS', 'ahmatidriss@gmail.com', 'MAHAMAT', 'Informatique', 'Licence 3', 'Ancien', '2021-09-24', 'Actif', '0000-00-00'),
(12, '22A575FS', 'AHMAT TAHIR', 'ahmattahir@gmail.com', 'ABAKAR', 'Informatique', 'Licence 3', 'Ancien', '2022-10-01', 'Actif', '0000-00-00'),
(13, '22B256FS', 'ALI OUSMAN', 'aliousman@gmail.com', 'ALI', 'Informatique', 'Licence 3', 'Ancien', '2022-09-27', 'Actif', '0000-00-00'),
(14, '21A398FS', 'ALLARAMADJI', 'allaramadji@gmail.com', 'FREDERIC', 'Informatique', 'Licence 3', 'Ancien', '2021-09-22', 'Actif', '0000-00-00'),
(15, '22A811FS', 'ASBE', 'asbe@gmail.com', 'PATRICK DOG', 'Informatique', 'Licence 3', 'Ancien', '2022-10-05', 'Actif', '0000-00-00'),
(16, '24B512FS', 'ASNAN', 'asnan@gmail.com', 'ROLAND LE SAGE', 'Informatique', 'Licence 3', 'Ancien', '2024-09-29', 'Actif', '0000-00-00'),
(17, '19A216FS', 'ATLOVE NGNEMAVEN', 'atlovengnemaven@gmail.com', 'FLAV LOZERE', 'Informatique', 'Licence 3', 'Ancien', '2019-09-14', 'Actif', '0000-00-00'),
(18, '18B296FS', 'BABINWE', 'babinwe@gmail.com', NULL, 'Informatique', 'Licence 3', 'Ancien', '2018-09-18', 'Actif', '0000-00-00'),
(19, '21A324FS', 'BADANDI', 'badandi@gmail.com', 'DESIRE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-26', 'Actif', '0000-00-00'),
(20, '20B145FS', 'BAH NIGEL', 'bahnigel@gmail.com', 'BUNRI', 'Informatique', 'Licence 3', 'Ancien', '2020-10-09', 'Actif', '0000-00-00'),
(21, '22A654FS', 'BAKOWE', 'bakowe@gmail.com', 'JUSTIN', 'Informatique', 'Licence 3', 'Ancien', '2022-09-28', 'Actif', '0000-00-00'),
(22, '22B010FS', 'BECHIR MAHAMAT', 'bechirmahamat@gmail.com', 'YAYA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-02', 'Actif', '0000-00-00'),
(23, '21A184FS', 'BEDJI', 'bedji@gmail.com', 'MOITANGAR', 'Informatique', 'Licence 3', 'Ancien', '2021-09-19', 'Actif', '0000-00-00'),
(24, '22A002FS', 'BESSALA', 'bessala@gmail.com', 'JOSEPH ARMEL', 'Informatique', 'Licence 3', 'Ancien', '2022-09-22', 'Actif', '0000-00-00'),
(25, '21A471FS', 'BEUFERBE PALOUMA', 'beuferbepalouma@gmail.com', 'BESCHERELLE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-30', 'Actif', '0000-00-00'),
(26, '22B069FS', 'BRAHIM HIDINI', 'brahimhidini@gmail.com', 'DJOUMA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-04', 'Actif', '0000-00-00'),
(27, '22A196FS', 'CHAME KAMATE', 'chamekamate@gmail.com', 'GRACE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-26', 'Actif', '0000-00-00'),
(28, '21A393FS', 'CHEKANE', 'chekane@gmail.com', 'THEOPHILE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-25', 'Actif', '0000-00-00'),
(29, '22A456FS', 'DADA ASMAOU', 'dadaasmaou@gmail.com', 'MVOUGNA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-06', 'Actif', '0000-00-00'),
(30, '22A704FS', 'DAOUDA', 'daouda@gmail.com', 'HASSAN', 'Informatique', 'Licence 3', 'Ancien', '2022-09-29', 'Actif', '0000-00-00'),
(31, '21A467FS', 'DEUDIBE DOUKIKA', 'deudibedoukika@gmail.com', 'SEWORE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-27', 'Actif', '0000-00-00'),
(32, '22A249FS', 'DJACKBE FAWA', 'djackbefawa@gmail.com', 'SAMSON', 'Informatique', 'Licence 3', 'Ancien', '2022-10-03', 'Actif', '0000-00-00'),
(33, '21A394FS', 'DJAOBE BINGWE', 'djaobebingwe@gmail.com', 'FABRICE TAIBAM', 'Informatique', 'Licence 3', 'Ancien', '2021-10-01', 'Actif', '0000-00-00'),
(34, '22B345FS', 'DJAPON NKWIMI', 'djaponnkwimi@gmail.com', 'SONIA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-07', 'Actif', '0000-00-00'),
(35, '22A368FS', 'DJARMAILA', 'djarmaila@gmail.com', 'GODKREO', 'Informatique', 'Licence 3', 'Ancien', '2022-09-24', 'Actif', '0000-00-00'),
(36, '20A273FS', 'DJEKILLAMBER', 'djekillamber@gmail.com', 'BONHEUR', 'Informatique', 'Licence 3', 'Ancien', '2020-09-20', 'Actif', '0000-00-00'),
(37, '21A470FS', 'DJINFALBE CHINDANNE', 'djinfalbechindanne@gmail.com', 'NESTOR', 'Informatique', 'Licence 3', 'Ancien', '2021-09-23', 'Actif', '0000-00-00'),
(38, '21A289FS', 'DJOKI', 'djoki@gmail.com', 'HUGENE LIONEL', 'Informatique', 'Licence 3', 'Ancien', '2021-09-21', 'Actif', '0000-00-00'),
(39, '22B193FS', 'DJOUBA', 'djouba@gmail.com', 'BLAISE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-08', 'Actif', '0000-00-00'),
(40, '22A313FS', 'FANSA DANDE', 'fansadande@gmail.com', 'HERMANN', 'Informatique', 'Licence 3', 'Ancien', '2022-09-23', 'Actif', '0000-00-00'),
(41, '20B178FS', 'GANDEBE', 'gandebe@gmail.com', 'MARIUS', 'Informatique', 'Licence 3', 'Ancien', '2020-10-12', 'Actif', '0000-00-00'),
(42, '24A636FS', 'GUIBOLO HAKASSOU', 'guibolohakassou@gmail.com', 'YVAN AARON', 'Informatique', 'Licence 3', 'Ancien', '2024-10-01', 'Actif', '0000-00-00'),
(43, '20A673FS', 'HASSANE KOUNDJA', 'hassanekoundja@gmail.com', 'TAMBAYE', 'Informatique', 'Licence 3', 'Ancien', '2020-09-22', 'Actif', '0000-00-00'),
(44, '22A265FS', 'HAWA MAHAMAT', 'hawamahamat@gmail.com', 'KOKOY', 'Informatique', 'Licence 3', 'Ancien', '2022-10-09', 'Actif', '0000-00-00'),
(45, '20A111FS', 'HISSEINE', 'hisseine@gmail.com', 'ABDRAMANE', 'Informatique', 'Licence 3', 'Ancien', '2020-09-16', 'Actif', '0000-00-00'),
(46, '19A195FS', 'HOORE', 'hoore@gmail.com', 'DAVY', 'Informatique', 'Licence 3', 'Ancien', '2019-09-15', 'Actif', '0000-00-00'),
(47, '22A915FS', 'HOUAMBO MGBAM', 'houambomgbam@gmail.com', 'ABBE AUDE MARLYSE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-10', 'Actif', '0000-00-00'),
(48, '22A177FS', 'HOURO WARDOUGOU', 'hourowardougou@gmail.com', 'ABDALLAH', 'Informatique', 'Licence 3', 'Ancien', '2022-09-21', 'Actif', '0000-00-00'),
(49, '22A816FS', 'HOUSSEINI', 'housseini@gmail.com', 'ABDOURAHMAN', 'Informatique', 'Licence 3', 'Ancien', '2022-10-11', 'Actif', '0000-00-00'),
(50, '21A390FS', 'IBRAHIM HASSAN', 'ibrahimhassan@gmail.com', 'MAHAMAT', 'Informatique', 'Licence 3', 'Ancien', '2021-09-18', 'Actif', '0000-00-00'),
(51, '22B494FS', 'KOFANE', 'kofane@gmail.com', 'VIANNIE PATRICIA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-12', 'Actif', '0000-00-00'),
(52, '22A796FS', 'KOUBEUBE', 'koubeube@gmail.com', 'DESTIN', 'Informatique', 'Licence 3', 'Ancien', '2022-09-20', 'Actif', '0000-00-00'),
(53, '22A571FS', 'KOYOUE KAMDEM', 'koyouekamdem@gmail.com', 'STEPHANE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-13', 'Actif', '0000-00-00'),
(54, '22B008FS', 'KROU', 'krou@gmail.com', 'SERGE RAPHAEL', 'Informatique', 'Licence 3', 'Ancien', '2022-10-14', 'Actif', '0000-00-00'),
(55, '21A098FS', 'MADAHAR', 'madahar@gmail.com', 'JOSEPH', 'Informatique', 'Licence 3', 'Ancien', '2021-09-15', 'Actif', '0000-00-00'),
(56, '21A283FS', 'MAHAMAT ADOUM', 'mahamatadoum@gmail.com', 'SOULEYMANE ADOUM', 'Informatique', 'Licence 3', 'Ancien', '2021-09-16', 'Actif', '0000-00-00'),
(57, '22B076FS', 'MAHAMAT KALLY', 'mahamatkally@gmail.com', 'BOURMA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-15', 'Actif', '0000-00-00'),
(58, '22A382FS', 'MAHASSAB ABDERAHIM', 'mahassababderahim@gmail.com', 'DJIBRINE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-19', 'Actif', '0000-00-00'),
(59, '22A155FS', 'MAMOUDOU', 'mamoudou@gmail.com', 'ELWAHIS', 'Informatique', 'Licence 3', 'Ancien', '2022-10-16', 'Actif', '0000-00-00'),
(60, '21A331FS', 'MANA NGOUN', 'manangoun@gmail.com', 'JULES BRANDON KEVIN', 'Informatique', 'Licence 3', 'Ancien', '2021-09-17', 'Actif', '0000-00-00'),
(61, '21A242FS', 'MARIAM ADOUM', 'mariamadoum@gmail.com', 'IDRISS', 'Informatique', 'Licence 3', 'Ancien', '2021-09-29', 'Actif', '0000-00-00'),
(62, '22A146FS', 'MASLAW GARGA', 'maslawgarga@gmail.com', 'IBRAHIM', 'Informatique', 'Licence 3', 'Ancien', '2022-10-17', 'Actif', '0000-00-00'),
(63, '22A310FS', 'MBAIGANGNON', 'mbaigangnon@gmail.com', 'ESECHIEL', 'Informatique', 'Licence 3', 'Ancien', '2022-09-18', 'Actif', '0000-00-00'),
(64, '21A246FS', 'MBAIHOGAOU DJEDOUBOUM', 'mbaihogaoudjedouboum@gmail.com', 'JOSAPHAT', 'Informatique', 'Licence 3', 'Ancien', '2021-09-14', 'Actif', '0000-00-00'),
(65, '20A107FS', 'MBAINDIGUIM', 'mbaindiguim@gmail.com', 'ARISTOPHANE', 'Informatique', 'Licence 3', 'Ancien', '2020-09-17', 'Actif', '0000-00-00'),
(66, '21A245FS', 'MBAIRAMADJI', 'mbairamadjiclark@gmail.com', 'CLARK', 'Informatique', 'Licence 3', 'Ancien', '2021-09-13', 'Actif', '2000-02-21'),
(67, '21A763FS', 'MBAITESEM', 'mbaitesem@gmail.com', 'SOSTHENE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-12', 'Actif', '0000-00-00'),
(68, '22B145FS', 'MEFO TELLA', 'mefotella@gmail.com', 'JOELLE MIGUELLE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-18', 'Actif', '0000-00-00'),
(69, '22A144FS', 'METOUG MANDJOM', 'metougmandjom@gmail.com', 'PATRICK POLYCANOR', 'Informatique', 'Licence 3', 'Ancien', '2022-09-17', 'Actif', '0000-00-00'),
(70, '22B568FS', 'MILLA FANAVA', 'millafanava@gmail.com', 'MOISE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-19', 'Actif', '0000-00-00'),
(71, '22B172FS', 'MODOU VOGODA', 'modouvogoda@gmail.com', 'RODOLPHE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-16', 'Actif', '0000-00-00'),
(72, '21A286FS', 'MOHAMADOU', 'mohamadou@gmail.com', 'AWALOU', 'Informatique', 'Licence 3', 'Ancien', '2021-09-11', 'Actif', '0000-00-00'),
(73, '22A941FS', 'MOHAMADOU HOUSSEINI', 'mohamadouhousseini@gmail.com', 'DAOUDA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-20', 'Actif', '0000-00-00'),
(74, '22A702FS', 'MOUTOUANGA AROMNO', 'moutouangaaromno@gmail.com', 'JUNIOR', 'Informatique', 'Licence 3', 'Ancien', '2022-09-15', 'Actif', '0000-00-00'),
(75, '22B791FS', 'NABILATOU', 'nabilatou@gmail.com', NULL, 'Informatique', 'Licence 3', 'Ancien', '2022-10-21', 'Actif', '0000-00-00'),
(76, '18B532FS', 'NADJILEM KAMDO', 'nadjilemkamdo@gmail.com', 'LUDOVIC', 'Informatique', 'Licence 3', 'Ancien', '2018-09-19', 'Actif', '0000-00-00'),
(77, '22A838FS', 'NDAPPA', 'ndappa@gmail.com', 'KAMPETE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-14', 'Actif', '0000-00-00'),
(78, '22B427FS', 'NEKAR', 'nekar@gmail.com', 'JEAN CLAUDE NDJIMONG', 'Informatique', 'Licence 3', 'Ancien', '2022-10-22', 'Actif', '0000-00-00'),
(79, '22A563FS', 'NELDE TOUMENGAR', 'neldetoumengar@gmail.com', 'GERARD', 'Informatique', 'Licence 3', 'Ancien', '2022-09-13', 'Actif', '0000-00-00'),
(80, '22A383FS', 'NEUMBI KEMGANG', 'neumbikemgang@gmail.com', 'ZEUSSE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-23', 'Actif', '0000-00-00'),
(81, '22A170FS', 'NGASSAM LEADRA', 'ngassamleadra@gmail.com', 'NAOMI MELAINE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-12', 'Actif', '0000-00-00'),
(82, '22A270FS', 'NGATCHA DJEUTCHA', 'ngatchadjeutcha@gmail.com', 'HOANA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-24', 'Actif', '0000-00-00'),
(83, '22A162FS', 'NGOUANOM WABO', 'ngouanomwabo@gmail.com', 'BRAYAN', 'Informatique', 'Licence 3', 'Ancien', '2022-09-11', 'Actif', '0000-00-00'),
(84, '20A121FS', 'NGOYONG KOGNI', 'ngoyongkogni@gmail.com', 'DANIELLE', 'Informatique', 'Licence 3', 'Ancien', '2020-09-18', 'Actif', '0000-00-00'),
(85, '21A311FS', 'NODJIRAM ALLAHASRA', 'nodjiramallahastra@gmail.com', NULL, 'Informatique', 'Licence 3', 'Ancien', '2021-09-10', 'Actif', '0000-00-00'),
(86, '22B408FS', 'NOUMI MANGAMTI', 'noumimangamti@gmail.com', 'MIRYAM OLGA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-25', 'Actif', '0000-00-00'),
(87, '22A176FS', 'OUMAR TCHOU', 'oumartchou@gmail.com', 'HAMID', 'Informatique', 'Licence 3', 'Ancien', '2022-09-10', 'Actif', '0000-00-00'),
(88, '22A316FS', 'PESINIE TCHOFFO', 'pesinietchoffo@gmail.com', 'NOEL ROMARIC', 'Informatique', 'Licence 3', 'Ancien', '2022-10-26', 'Actif', '0000-00-00'),
(89, '20A978FS', 'ROI DAMNA', 'roidamna@gmail.com', 'TONY', 'Informatique', 'Licence 3', 'Ancien', '2020-09-19', 'Actif', '0000-00-00'),
(90, '22A562FS', 'SEIF AL NASSOUR', 'seifalnassour@gmail.com', 'MAHAMAT SOULEYMANE', 'Informatique', 'Licence 3', 'Ancien', '2022-09-09', 'Actif', '0000-00-00'),
(91, '22B267FS', 'SELATSA YIMZEU', 'selatsayimzeu@gmail.com', 'LOIC STEPHANE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-27', 'Actif', '0000-00-00'),
(92, '24B507FS', 'SOH CHENDJOU', 'sohchendjou@gmail.com', 'JUNIOR', 'Informatique', 'Licence 3', 'Ancien', '2024-09-28', 'Actif', '0000-00-00'),
(93, '18A070FS', 'SYLVESTRE OUMAR', 'sylvestreoumar@gmail.com', 'ADDAH', 'Informatique', 'Licence 3', 'Ancien', '2018-09-20', 'Actif', '0000-00-00'),
(94, '20B131FS', 'TCHOUANGUE KAMENI', 'tchouanguekameni@gmail.com', 'NIK KENZO', 'Informatique', 'Licence 3', 'Ancien', '2020-10-11', 'Actif', '0000-00-00'),
(95, '22B088FS', 'TEMGOUA CHERONE', 'temgouacherone@gmail.com', 'VARESA', 'Informatique', 'Licence 3', 'Ancien', '2022-10-28', 'Actif', '0000-00-00'),
(96, '22B352FS', 'TIOTSOP YOTEU', 'tiotsopyoteu@gmail.com', 'FRANCK PARFAIT', 'Informatique', 'Licence 3', 'Ancien', '2022-09-08', 'Actif', '0000-00-00'),
(97, '21A310FS', 'TOGODNE DOWARA', 'togodnedowara@gmail.com', 'SALATHIEL', 'Informatique', 'Licence 3', 'Ancien', '2021-09-09', 'Actif', '0000-00-00'),
(98, '21A244FS', 'TOGUEM', 'toguem@gmail.com', 'NARCISSE', 'Informatique', 'Licence 3', 'Ancien', '2021-09-08', 'Actif', '0000-00-00'),
(99, '22A063FS', 'TSAHUI NEMBOT', 'tsahuinembot@gmail.com', 'CHRIST SOCRATE', 'Informatique', 'Licence 3', 'Ancien', '2022-10-29', 'Actif', '0000-00-00'),
(100, '21A089FS', 'VATCHAO HANDINGBAA', 'vatchaohandingbaa@gmail.com', 'HYACINTHE SIDAOSSOU', 'Informatique', 'Licence 3', 'Ancien', '2021-09-07', 'Actif', '0000-00-00'),
(101, '20B125FS', 'WEDERSA', 'wedersa@gmail.com', 'MADEUH', 'Informatique', 'Licence 3', 'Ancien', '2020-10-13', 'Actif', '0000-00-00'),
(102, '22A327FS', 'YAMENI POUGOUE', 'yamenipougoue@gmail.com', 'GABIN WILFRIED', 'Informatique', 'Licence 3', 'Ancien', '2022-09-07', 'Actif', '0000-00-00'),
(103, '24A444FS', 'YANGANI ALFAT', 'yanganialfat@gmail.com', 'BENAJA', 'Informatique', 'Licence 3', 'Ancien', '2024-09-27', 'Actif', '0000-00-00'),
(104, '22B285FS', 'YAYA', 'yaya@gmail.com', 'ABOUBAKAR', 'Informatique', 'Licence 3', 'Ancien', '2022-10-30', 'Actif', '0000-00-00'),
(105, '21A387FS', 'YOGUERNAN', 'yoguernan@gmail.com', 'MERLIN', 'Informatique', 'Licence 3', 'Ancien', '2021-09-06', 'Actif', '0000-00-00'),
(106, '22A139FS', 'YOUSSOUF', 'youssouf@gmail.com', 'IDRISS TOM', 'Informatique', 'Licence 3', 'Ancien', '2022-09-06', 'Actif', '0000-00-00'),
(107, '22A457FS', 'YOUSSOUF', 'youssouf2@gmail.com', 'MAHAMAT', 'Informatique', 'Licence 3', 'Ancien', '2022-10-31', 'Actif', '0000-00-00'),
(108, '22B099FS', 'YOUSSOUFOU', 'youssoufou@gmail.com', 'YAYA', 'Informatique', 'Licence 3', 'Ancien', '2022-09-05', 'Actif', '0000-00-00'),
(109, '22A692FS', 'ADOUM OUMAR', 'adoumoumar@gmail.com', 'IDRISS', 'Informatique', 'Licence 3', 'Ancien', '2022-11-01', 'Actif', '0000-00-00'),
(110, '20A422FS', 'KAINBA OUIN', 'kainbaouin@gmail.com', 'JONAS', 'Informatique', 'Licence 3', 'Ancien', '2020-09-21', 'Actif', '0000-00-00'),
(111, '22A241FS', 'Etudiant2022', 'etudiant22@gmail.com', 'Etudiant22', 'informatique', 'L2', 'Ancien', '2024-10-04', 'actif', '0000-00-00'),
(112, '24A701FS', 'DJEKORNOM', 'jojo@gmail.com', 'Jonathan', 'Physique', 'Lience 1', 'Nouveau', '2024-09-20', 'Actif', '0000-00-00');

-- --------------------------------------------------------

--
-- Structure de la table `etudiant_departements`
--

CREATE TABLE `etudiant_departements` (
  `etudiant_id` int(11) NOT NULL,
  `departement_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `etudiant_departements`
--
ALTER TABLE `etudiant_departements`
  ADD PRIMARY KEY (`etudiant_id`,`departement_id`),
  ADD KEY `departement_id` (`departement_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `departements`
--
ALTER TABLE `departements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `etudiants`
--
ALTER TABLE `etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `etudiant_departements`
--
ALTER TABLE `etudiant_departements`
  ADD CONSTRAINT `etudiant_departements_ibfk_1` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `etudiant_departements_ibfk_2` FOREIGN KEY (`departement_id`) REFERENCES `departements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
