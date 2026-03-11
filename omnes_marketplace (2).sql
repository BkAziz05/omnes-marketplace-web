-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mer. 11 mars 2026 à 21:23
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `omnes_marketplace`
--

-- --------------------------------------------------------

--
-- Structure de la table `acheteur`
--

CREATE TABLE `acheteur` (
  `idAcheteur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `adresse` text,
  `NumTelephone` varchar(20) DEFAULT NULL,
  `clause_acceptee` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `acheteur`
--

INSERT INTO `acheteur` (`idAcheteur`, `nom`, `prenom`, `email`, `mdp`, `adresse`, `NumTelephone`, `clause_acceptee`, `created_at`) VALUES
(1, 'Saibi', 'Firas', 'FIRAS.SAIBI@email.fr', 'mdp', '12 Rue de la Paix, 75001 Paris', '0612345678', 1, '2026-03-11 03:44:08'),
(2, 'Benjilali', 'Iliasse', 'ilasse.benjilali@email.fr', 'mdp', '5 Avenue des Fleurs, 69001 Lyon', '0698765432', 1, '2026-03-11 03:44:08'),
(3, 'Aziz', 'Ben Khedher', 'Aziz.BenKhedher@email.fr', 'mdp123!', '8 Boulevard Haussmann, 75008 Paris', '0645678901', 0, '2026-03-11 03:44:08'),
(4, 'Saibi', 'Mohamed Firas', 'firassaibi@yahoo.com', '$2y$12$7joUx7psLWx2e4m.sHul..GHjW0aUhPDRUdxWkYMoL6lLccHetsKG', '32 Rue Daniel Fery', '0671071567', 1, '2026-03-11 07:28:20'),
(5, 'Saibi', 'Mohamed Firas', 'firassaibi25@yahoo.com', '$2y$12$KKcszozDLURleE3yfjDFx.xbO68/iwkjsFvaVdEvVnKrrDfuCuMbO', '32 Rue Daniel Fery', '0671071564', 1, '2026-03-11 07:33:07');

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

CREATE TABLE `administrateur` (
  `idAdmin` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `administrateur`
--

INSERT INTO `administrateur` (`idAdmin`, `nom`, `prenom`, `email`, `mot_de_passe`, `created_at`) VALUES
(1, 'Dupont', 'Jean', 'admin@omnes-marketplace.fr', 'mdp', '2026-03-11 03:44:08');

-- --------------------------------------------------------

--
-- Structure de la table `alerte`
--

CREATE TABLE `alerte` (
  `idAlerte` int(11) NOT NULL,
  `mots_cles` varchar(255) DEFAULT NULL,
  `prix_max` decimal(10,2) DEFAULT NULL,
  `mode_vente_souhaite` enum('immediat','negotiation','enchere','tous') DEFAULT 'tous',
  `active` tinyint(1) DEFAULT '1',
  `idAcheteur` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `alerte`
--

INSERT INTO `alerte` (`idAlerte`, `mots_cles`, `prix_max`, `mode_vente_souhaite`, `active`, `idAcheteur`, `created_at`) VALUES
(1, 'bijou or montre', '2000.00', 'enchere', 1, 1, '2026-03-11 03:44:08'),
(2, 'macbook apple', '2500.00', 'immediat', 1, 2, '2026-03-11 03:44:08'),
(3, 'sac luxe chanel', '5000.00', 'negotiation', 1, 3, '2026-03-11 03:44:08');

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE `article` (
  `idArticle` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description_qualite` text,
  `description_defaut` text,
  `prix_base` decimal(10,2) NOT NULL,
  `mode_vente` enum('immediat','negotiation','enchere') NOT NULL,
  `status` enum('disponible','vendu','en_cours','supprime') DEFAULT 'disponible',
  `date_publication` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_debut_enchere` datetime DEFAULT NULL,
  `date_fin_enchere` datetime DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `idVendeur` int(11) NOT NULL,
  `idCategorie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `article`
--

INSERT INTO `article` (`idArticle`, `nom`, `description_qualite`, `description_defaut`, `prix_base`, `mode_vente`, `status`, `date_publication`, `date_debut_enchere`, `date_fin_enchere`, `video_url`, `idVendeur`, `idCategorie`) VALUES
(1, 'Bague Cartier 18 carats or jaune 4.8g', 'Magnifique bague en or jaune 18 carats, poinçon visible, authentique', NULL, '1500.00', 'immediat', 'vendu', '2026-03-11 03:44:08', '2026-03-10 09:00:00', '2026-03-11 15:41:00', NULL, 1, 1),
(2, 'Montre Rolex Submariner', 'Montre de luxe en excellent état, boîte et papiers fournis', 'Légères traces d usage sur le bracelet', '8000.00', 'negotiation', 'disponible', '2026-03-11 03:44:08', NULL, NULL, NULL, 2, 1),
(3, 'Veste Hermès Vintage', 'Veste en tweed Hermès vintage, coupe parfaite', 'Légère décoloration sur la doublure', '450.00', 'negotiation', 'disponible', '2026-03-11 03:44:08', NULL, NULL, NULL, 2, 3),
(4, 'MacBook Pro 14 M3', 'MacBook Pro 14 pouces, puce M3, 16Go RAM, 512Go SSD', NULL, '1800.00', 'immediat', 'vendu', '2026-03-11 03:44:08', NULL, NULL, NULL, 3, 4),
(5, 'iPhone 15 Pro Max 256Go', 'iPhone 15 Pro Max couleur titane naturel, comme neuf', NULL, '950.00', 'immediat', 'vendu', '2026-03-11 03:44:08', NULL, NULL, NULL, 3, 4),
(6, 'Commode Louis XV Acajou', 'Commode d époque Louis XV en acajou massif, circa 1750', 'Petits manques de placage sur le côté gauche', '3200.00', 'enchere', 'supprime', '2026-03-11 03:44:08', '2026-03-12 10:00:00', '2026-03-19 18:00:00', NULL, 1, 8),
(7, 'Pack Livres HTML5 CSS3 JS', 'Lot de 4 livres de programmation web en très bon état', NULL, '45.00', 'immediat', 'vendu', '2026-03-11 03:44:08', NULL, NULL, NULL, 3, 5),
(8, 'Sac Chanel Classique Caviar', 'Sac Chanel classique en cuir caviar noir, quincaillerie dorée', 'Légère usure sur les coins', '4500.00', 'negotiation', 'disponible', '2026-03-11 03:44:08', NULL, NULL, NULL, 2, 3),
(9, 'Test', 'Test achat immediat', 'sdfdsf', '25.00', 'immediat', 'disponible', '2026-03-11 11:23:12', NULL, NULL, NULL, 1, 2),
(12, 'gooba', 'jj', 'grg', '25.00', 'immediat', 'disponible', '2026-03-11 15:28:46', NULL, NULL, NULL, 1, 7);

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `idCategorie` int(11) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `type_marchandise` enum('rare','haute_gamme','regulier') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`idCategorie`, `libelle`, `type_marchandise`) VALUES
(1, 'Bijoux & Montres', 'rare'),
(2, 'Art & Collections', 'rare'),
(3, 'Vêtements Luxe', 'haute_gamme'),
(4, 'Électronique', 'haute_gamme'),
(5, 'Livres & Papeterie', 'regulier'),
(6, 'Accessoires Mode', 'regulier'),
(7, 'Matériel Scolaire', 'regulier'),
(8, 'Mobilier Antique', 'rare');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `idCommande` int(11) NOT NULL,
  `date_commande` datetime DEFAULT CURRENT_TIMESTAMP,
  `montant_total` decimal(10,2) NOT NULL,
  `mode_validation` enum('immediat','negotiation','enchere') NOT NULL,
  `status_commande` enum('en_attente','validee','expediee','livree','annulee') DEFAULT 'en_attente',
  `adresse_livraison` text NOT NULL,
  `idAcheteur` int(11) NOT NULL,
  `idPanier` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`idCommande`, `date_commande`, `montant_total`, `mode_validation`, `status_commande`, `adresse_livraison`, `idAcheteur`, `idPanier`) VALUES
(1, '2026-03-11 05:25:12', '950.00', 'immediat', 'en_attente', 'Alice Dubois\n12 Rue de la Paix, 75001 Paris\n42585 Villejuif\nFrance\nTél: 0612345678', 1, 1),
(2, '2026-03-11 08:22:56', '1800.00', 'immediat', 'validee', 'Firas Saibi\n12 Rue de la Paix, 75001 Paris\n94800 Villejuif\nTéléphone : 0612345678', 1, 2),
(3, '2026-03-11 09:33:08', '45.00', 'immediat', 'validee', 'Mohamed Firas Saibi\n32 Rue Daniel Fery\n94800 Villejuif\nTéléphone : 0671071564', 5, 3),
(4, '2026-03-11 12:08:32', '25.00', 'immediat', 'validee', 'Firas Saibi\n12 Rue de la Paix, 75001 Paris\n94800 Villejuif\nTéléphone : 0612345678', 1, 5),
(5, '2026-03-11 15:45:34', '1500.00', 'immediat', 'validee', 'Firas Saibi\n12 Rue de la Paix, 75001 Paris\n94800 Villejuif\nTéléphone : 0612345678', 1, 6);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_panier`
--

CREATE TABLE `ligne_panier` (
  `idLigne` int(11) NOT NULL,
  `quantite` int(11) DEFAULT '1',
  `prix_snapshot` decimal(10,2) NOT NULL COMMENT 'Prix au moment de l ajout',
  `mode_acquisition` enum('immediat','negotiation','enchere') NOT NULL,
  `idPanier` int(11) NOT NULL,
  `idArticle` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `ligne_panier`
--

INSERT INTO `ligne_panier` (`idLigne`, `quantite`, `prix_snapshot`, `mode_acquisition`, `idPanier`, `idArticle`) VALUES
(1, 1, '950.00', 'immediat', 1, 5),
(3, 1, '1800.00', 'immediat', 2, 4),
(5, 1, '45.00', 'immediat', 3, 7),
(7, 1, '25.00', 'immediat', 5, 9),
(9, 1, '1500.00', 'immediat', 6, 1),
(10, 1, '25.00', 'immediat', 7, 12);

-- --------------------------------------------------------

--
-- Structure de la table `negociation`
--

CREATE TABLE `negociation` (
  `id_negociation` int(11) NOT NULL,
  `statut` enum('en_cours','acceptee','refusee','expiree') DEFAULT 'en_cours',
  `date_debut` datetime DEFAULT CURRENT_TIMESTAMP,
  `nb_tours` int(11) DEFAULT '0' COMMENT 'Nombre de propositions échangées (max 5)',
  `prix_final` decimal(10,2) DEFAULT NULL,
  `idArticle` int(11) NOT NULL,
  `idAcheteur` int(11) NOT NULL,
  `idVendeur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `negociation`
--

INSERT INTO `negociation` (`id_negociation`, `statut`, `date_debut`, `nb_tours`, `prix_final`, `idArticle`, `idAcheteur`, `idVendeur`) VALUES
(1, 'en_cours', '2026-03-11 03:44:08', 1, NULL, 2, 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `notification`
--

CREATE TABLE `notification` (
  `idNotification` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('alerte','negociation','enchere','commande','systeme') NOT NULL,
  `lue` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `idAcheteur` int(11) DEFAULT NULL,
  `idVendeur` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `notification`
--

INSERT INTO `notification` (`idNotification`, `message`, `type`, `lue`, `created_at`, `idAcheteur`, `idVendeur`) VALUES
(1, 'Votre commande #1 a bien été validée. Total : 950,00 €', 'commande', 0, '2026-03-11 04:25:12', 1, NULL),
(2, 'Votre commande pour l\'article \"MacBook Pro 14 M3\" a été validée.', 'commande', 0, '2026-03-11 07:22:56', 1, NULL),
(3, 'L\'article \"MacBook Pro 14 M3\" a été acheté et payé.', 'commande', 0, '2026-03-11 07:22:56', NULL, 3),
(4, 'Votre commande pour l\'article \"Pack Livres HTML5 CSS3 JS\" a été validée.', 'commande', 0, '2026-03-11 08:33:08', 5, NULL),
(5, 'L\'article \"Pack Livres HTML5 CSS3 JS\" a été acheté et payé.', 'commande', 0, '2026-03-11 08:33:08', NULL, 3),
(6, 'Votre commande pour l\'article \"zaeaz\" a été validée.', 'commande', 0, '2026-03-11 11:08:32', 1, NULL),
(7, 'L\'article \"zaeaz\" a été acheté et payé.', 'commande', 0, '2026-03-11 11:08:32', NULL, 1),
(8, 'Votre commande pour l\'article \"Bague Cartier 18 carats or jaune 4.8g\" a été validée.', 'commande', 0, '2026-03-11 14:45:34', 1, NULL),
(9, 'L\'article \"Bague Cartier 18 carats or jaune 4.8g\" a été acheté et payé.', 'commande', 0, '2026-03-11 14:45:34', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `offre_enchere`
--

CREATE TABLE `offre_enchere` (
  `idOffre` int(11) NOT NULL,
  `montant_max` decimal(10,2) NOT NULL COMMENT 'Montant maximum que l acheteur accepte',
  `montant_courant` decimal(10,2) NOT NULL COMMENT 'Offre effective placée par le système',
  `date_offre` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('active','gagnante','perdante','annulee') DEFAULT 'active',
  `idArticle` int(11) NOT NULL,
  `idAcheteur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `offre_enchere`
--

INSERT INTO `offre_enchere` (`idOffre`, `montant_max`, `montant_courant`, `date_offre`, `statut`, `idArticle`, `idAcheteur`) VALUES
(1, '99999999.00', '1505.00', '2026-03-11 05:27:52', 'active', 1, 1),
(2, '5000.00', '3201.00', '2026-03-11 03:44:08', 'active', 6, 2),
(3, '2550.00', '2550.00', '2026-03-11 09:31:22', 'active', 1, 5),
(4, '3255.00', '3255.00', '2026-03-11 09:31:31', 'active', 1, 5),
(5, '5000.00', '5000.00', '2026-03-11 11:58:45', 'active', 1, 1),
(6, '5500.00', '5500.00', '2026-03-11 12:05:36', 'active', 1, 1),
(7, '10000.00', '10000.00', '2026-03-11 15:37:17', 'active', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

CREATE TABLE `paiement` (
  `idPaiement` int(11) NOT NULL,
  `numero_masque` varchar(20) NOT NULL COMMENT 'Ex: **** **** **** 1234',
  `nom_carte` varchar(150) NOT NULL,
  `expiration` varchar(7) NOT NULL COMMENT 'Format MM/YYYY',
  `statut_paiement` enum('en_attente','approuve','refuse','rembourse') DEFAULT 'en_attente',
  `type_paiement` enum('visa','mastercard','amex','paypal') NOT NULL,
  `idCommande` int(11) NOT NULL,
  `idAcheteur` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `paiement`
--

INSERT INTO `paiement` (`idPaiement`, `numero_masque`, `nom_carte`, `expiration`, `statut_paiement`, `type_paiement`, `idCommande`, `idAcheteur`, `created_at`) VALUES
(1, '**** **** **** 7589', 'fras', '02/2028', 'approuve', 'visa', 1, 1, '2026-03-11 04:25:12'),
(2, '**** **** **** 7575', 'sfdsdf', '21/2025', 'approuve', 'visa', 2, 1, '2026-03-11 07:22:56'),
(3, '**** **** **** 2132', 'fras', '12/11', 'approuve', 'visa', 3, 5, '2026-03-11 08:33:08'),
(4, '**** **** **** 3651', 'fras', '01/25', 'approuve', 'visa', 4, 1, '2026-03-11 11:08:32'),
(5, '**** **** **** 9559', 'fras', '02/2026', 'approuve', 'visa', 5, 1, '2026-03-11 14:45:34');

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `idPanier` int(11) NOT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('ouvert','valide','abandonne') DEFAULT 'ouvert',
  `sous_total` decimal(10,2) DEFAULT '0.00',
  `idAcheteur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`idPanier`, `date_creation`, `statut`, `sous_total`, `idAcheteur`) VALUES
(1, '2026-03-11 03:44:08', 'valide', '950.00', 1),
(2, '2026-03-11 05:35:59', 'valide', '1800.00', 1),
(3, '2026-03-11 08:36:11', 'valide', '45.00', 5),
(4, '2026-03-11 09:33:17', 'ouvert', '0.00', 5),
(5, '2026-03-11 12:00:40', 'valide', '25.00', 1),
(6, '2026-03-11 15:42:05', 'valide', '1500.00', 1),
(7, '2026-03-11 15:53:05', 'ouvert', '25.00', 1);

-- --------------------------------------------------------

--
-- Structure de la table `photo_article`
--

CREATE TABLE `photo_article` (
  `idPhoto` int(11) NOT NULL,
  `url_photo` varchar(255) NOT NULL,
  `ordre` int(11) DEFAULT '1',
  `idArticle` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `photo_article`
--

INSERT INTO `photo_article` (`idPhoto`, `url_photo`, `ordre`, `idArticle`) VALUES
(1, 'uploads/articles/bague_cartier_1.jpg', 1, 1),
(2, 'uploads/articles/bague_cartier_2.jpg', 2, 1),
(3, 'uploads/articles/rolex_submariner_1.jpg', 1, 2),
(4, 'uploads/articles/veste_hermes_1.jpg', 1, 3),
(5, 'uploads/articles/macbook_pro_1.jpg', 1, 4),
(6, 'uploads/articles/iphone15_1.jpg', 1, 5),
(7, 'uploads/articles/commode_lxv_1.jpg', 1, 6),
(8, 'uploads/articles/livres_web_1.jpg', 1, 7),
(9, 'uploads/articles/sac_chanel_1.jpg', 1, 8);

-- --------------------------------------------------------

--
-- Structure de la table `proposition`
--

CREATE TABLE `proposition` (
  `idProposition` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_proposition` datetime DEFAULT CURRENT_TIMESTAMP,
  `emetteur` enum('acheteur','vendeur') NOT NULL,
  `statut` enum('en_attente','acceptee','refusee','contre_offre') DEFAULT 'en_attente',
  `id_negociation` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `proposition`
--

INSERT INTO `proposition` (`idProposition`, `montant`, `date_proposition`, `emetteur`, `statut`, `id_negociation`) VALUES
(1, '6500.00', '2026-03-11 03:44:08', 'acheteur', 'en_attente', 1);

-- --------------------------------------------------------

--
-- Structure de la table `vendeur`
--

CREATE TABLE `vendeur` (
  `idVendeur` int(11) NOT NULL,
  `pseudo` varchar(100) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `image_fond` varchar(255) DEFAULT NULL,
  `statut_compte` enum('actif','suspendu','supprime') DEFAULT 'actif',
  `idAdmin` int(11) NOT NULL COMMENT 'Vendeur géré par cet admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `vendeur`
--

INSERT INTO `vendeur` (`idVendeur`, `pseudo`, `nom`, `prenom`, `email`, `mot_de_passe`, `photo_profil`, `image_fond`, `statut_compte`, `idAdmin`, `created_at`) VALUES
(1, 'MarcAntiques', 'Martin', 'Marc', 'marc.martin@email.fr', 'mdp', NULL, NULL, 'actif', 1, '2026-03-11 03:44:08'),
(2, 'SophieLux', 'Leclerc', 'Sophie', 'sophie.leclerc@email.fr', '$2y$12$4uNztn9KPPmMDjEEjpV2AenU/n0dtIxIrja2JpZdVBgMwIX68TvhG', NULL, NULL, 'actif', 1, '2026-03-11 03:44:08'),
(3, 'ThomasShop', 'Bernard', 'Thomas', 'thomas.bernard@email.fr', '$2y$12$4uNztn9KPPmMDjEEjpV2AenU/n0dtIxIrja2JpZdVBgMwIX68TvhG', NULL, NULL, 'actif', 1, '2026-03-11 03:44:08'),
(4, 'azeaz', 'Saibi', 'Mohamed Firas', 'firassaibi@yemao.com', 'mdp', NULL, NULL, 'actif', 1, '2026-03-11 10:08:19'),
(5, 'nems', 'msakni', 'yousseef', 'nems@x.y', 'mdp', NULL, NULL, 'actif', 1, '2026-03-11 10:08:48'),
(6, 'yamigo', 'Saibi', 'Firas', 'firassaibi@FSG.com', 'mdp', NULL, NULL, 'actif', 1, '2026-03-11 11:10:49');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `acheteur`
--
ALTER TABLE `acheteur`
  ADD PRIMARY KEY (`idAcheteur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `administrateur`
--
ALTER TABLE `administrateur`
  ADD PRIMARY KEY (`idAdmin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `alerte`
--
ALTER TABLE `alerte`
  ADD PRIMARY KEY (`idAlerte`),
  ADD KEY `idAcheteur` (`idAcheteur`);

--
-- Index pour la table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`idArticle`),
  ADD KEY `idCategorie` (`idCategorie`),
  ADD KEY `idx_article_mode_vente` (`mode_vente`),
  ADD KEY `idx_article_status` (`status`),
  ADD KEY `idx_article_vendeur` (`idVendeur`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`idCommande`),
  ADD KEY `idAcheteur` (`idAcheteur`),
  ADD KEY `idPanier` (`idPanier`);

--
-- Index pour la table `ligne_panier`
--
ALTER TABLE `ligne_panier`
  ADD PRIMARY KEY (`idLigne`),
  ADD KEY `idPanier` (`idPanier`),
  ADD KEY `idArticle` (`idArticle`);

--
-- Index pour la table `negociation`
--
ALTER TABLE `negociation`
  ADD PRIMARY KEY (`id_negociation`),
  ADD KEY `idAcheteur` (`idAcheteur`),
  ADD KEY `idVendeur` (`idVendeur`),
  ADD KEY `idx_negociation_article` (`idArticle`);

--
-- Index pour la table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`idNotification`),
  ADD KEY `idVendeur` (`idVendeur`),
  ADD KEY `idx_notification_acheteur` (`idAcheteur`);

--
-- Index pour la table `offre_enchere`
--
ALTER TABLE `offre_enchere`
  ADD PRIMARY KEY (`idOffre`),
  ADD KEY `idAcheteur` (`idAcheteur`),
  ADD KEY `idx_offre_article` (`idArticle`);

--
-- Index pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD PRIMARY KEY (`idPaiement`),
  ADD KEY `idCommande` (`idCommande`),
  ADD KEY `idAcheteur` (`idAcheteur`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`idPanier`),
  ADD KEY `idAcheteur` (`idAcheteur`);

--
-- Index pour la table `photo_article`
--
ALTER TABLE `photo_article`
  ADD PRIMARY KEY (`idPhoto`),
  ADD KEY `idArticle` (`idArticle`);

--
-- Index pour la table `proposition`
--
ALTER TABLE `proposition`
  ADD PRIMARY KEY (`idProposition`),
  ADD KEY `id_negociation` (`id_negociation`);

--
-- Index pour la table `vendeur`
--
ALTER TABLE `vendeur`
  ADD PRIMARY KEY (`idVendeur`),
  ADD UNIQUE KEY `pseudo` (`pseudo`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idAdmin` (`idAdmin`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `acheteur`
--
ALTER TABLE `acheteur`
  MODIFY `idAcheteur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `administrateur`
--
ALTER TABLE `administrateur`
  MODIFY `idAdmin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `alerte`
--
ALTER TABLE `alerte`
  MODIFY `idAlerte` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `article`
--
ALTER TABLE `article`
  MODIFY `idArticle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCategorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `idCommande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `ligne_panier`
--
ALTER TABLE `ligne_panier`
  MODIFY `idLigne` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `negociation`
--
ALTER TABLE `negociation`
  MODIFY `id_negociation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `notification`
--
ALTER TABLE `notification`
  MODIFY `idNotification` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `offre_enchere`
--
ALTER TABLE `offre_enchere`
  MODIFY `idOffre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `paiement`
--
ALTER TABLE `paiement`
  MODIFY `idPaiement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `idPanier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `photo_article`
--
ALTER TABLE `photo_article`
  MODIFY `idPhoto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `proposition`
--
ALTER TABLE `proposition`
  MODIFY `idProposition` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `vendeur`
--
ALTER TABLE `vendeur`
  MODIFY `idVendeur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `alerte`
--
ALTER TABLE `alerte`
  ADD CONSTRAINT `alerte_ibfk_1` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`idVendeur`) REFERENCES `vendeur` (`idVendeur`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_ibfk_2` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`);

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`),
  ADD CONSTRAINT `commande_ibfk_2` FOREIGN KEY (`idPanier`) REFERENCES `panier` (`idPanier`);

--
-- Contraintes pour la table `ligne_panier`
--
ALTER TABLE `ligne_panier`
  ADD CONSTRAINT `ligne_panier_ibfk_1` FOREIGN KEY (`idPanier`) REFERENCES `panier` (`idPanier`) ON DELETE CASCADE,
  ADD CONSTRAINT `ligne_panier_ibfk_2` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`);

--
-- Contraintes pour la table `negociation`
--
ALTER TABLE `negociation`
  ADD CONSTRAINT `negociation_ibfk_1` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`),
  ADD CONSTRAINT `negociation_ibfk_2` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`),
  ADD CONSTRAINT `negociation_ibfk_3` FOREIGN KEY (`idVendeur`) REFERENCES `vendeur` (`idVendeur`);

--
-- Contraintes pour la table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`) ON DELETE CASCADE,
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`idVendeur`) REFERENCES `vendeur` (`idVendeur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `offre_enchere`
--
ALTER TABLE `offre_enchere`
  ADD CONSTRAINT `offre_enchere_ibfk_1` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`),
  ADD CONSTRAINT `offre_enchere_ibfk_2` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`);

--
-- Contraintes pour la table `paiement`
--
ALTER TABLE `paiement`
  ADD CONSTRAINT `paiement_ibfk_1` FOREIGN KEY (`idCommande`) REFERENCES `commande` (`idCommande`),
  ADD CONSTRAINT `paiement_ibfk_2` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`);

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`idAcheteur`) REFERENCES `acheteur` (`idAcheteur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `photo_article`
--
ALTER TABLE `photo_article`
  ADD CONSTRAINT `photo_article_ibfk_1` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE;

--
-- Contraintes pour la table `proposition`
--
ALTER TABLE `proposition`
  ADD CONSTRAINT `proposition_ibfk_1` FOREIGN KEY (`id_negociation`) REFERENCES `negociation` (`id_negociation`) ON DELETE CASCADE;

--
-- Contraintes pour la table `vendeur`
--
ALTER TABLE `vendeur`
  ADD CONSTRAINT `vendeur_ibfk_1` FOREIGN KEY (`idAdmin`) REFERENCES `administrateur` (`idAdmin`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
