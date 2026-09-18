-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 18 sep. 2026 à 22:01
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
-- Base de données : `machaoui_ikhwa`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','editor') DEFAULT 'editor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(2, 'admin', '$2y$10$XHnBvfe1ES6L17KXUnMrCutxNsHCgj8iaO3qzZMKJY41Vzc2FVGTS', 'admin', '2026-06-13 22:45:19'),
(3, 'user', '$2y$10$3B6rXF0IuKuYhrg1HXEg9OMGeCXQev7P1wpSYNbZt1mDRdNYuDGQm', 'admin', '2026-06-14 12:48:43');

-- --------------------------------------------------------

--
-- Structure de la table `foods`
--

CREATE TABLE `foods` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `foods`
--

INSERT INTO `foods` (`id`, `name`, `description`, `price`, `image`, `category`, `created_at`, `updated_at`) VALUES
(1, 'فريت اوملات', 'طبق مميز', 350.00, '1781439269_6a2e9b25cf5af.png', 'مقبلات', '2026-06-14 13:14:29', '2026-06-14 13:14:44');

-- --------------------------------------------------------

--
-- Structure de la table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `discount` decimal(5,2) NOT NULL COMMENT 'نسبة الخصم من 1 إلى 100',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `site_name` varchar(255) DEFAULT 'مشاوي الإخوة',
  `whatsapp` varchar(50) DEFAULT '213000000000',
  `facebook` varchar(255) DEFAULT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `site_name`, `whatsapp`, `facebook`, `telegram`, `email`, `address`, `delivery_fee`, `created_at`, `updated_at`) VALUES
(1, 'مشاوي الإخوة', '+213670413993', 'https://facebook.com/machaoui', '+213670413993', 'mouhamedhoussem813@gmail.com', 'قالمة', 200.00, '2026-06-14 13:04:53', '2026-06-14 19:57:30');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_food_id` (`food_id`),
  ADD KEY `idx_discount` (`discount`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `foods`
--
ALTER TABLE `foods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `fk_offers_food_id` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
