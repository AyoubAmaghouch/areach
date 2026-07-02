-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 03 juil. 2026 à 01:41
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
-- Base de données : `areach`
--

-- --------------------------------------------------------

--
-- Structure de la table `product_images`
--

CREATE TABLE `product_images` (
  `id_image` int(11) NOT NULL,
  `id_variant` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `product_images`
--

INSERT INTO `product_images` (`id_image`, `id_variant`, `image`, `is_primary`) VALUES
(4, 12, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-38-6a45934d2a2002.88052001.jpeg', 1),
(5, 12, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-39-1-6a45934d2a67e4.24521566.jpeg', 1),
(6, 12, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-39-6a45934d2aaa98.32741921.jpeg', 1),
(7, 15, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-38-6a45954b0aec35.62984509.jpeg', 1),
(8, 15, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-39-1-6a45954b0bab46.26318660.jpeg', 1),
(9, 15, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-39-6a45954b0c6d71.32981113.jpeg', 1),
(15, 27, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-41-1-6a459a5eb79468.28581389.jpeg', 1),
(16, 27, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-41-2-6a459a5eb7cf55.21846291.jpeg', 1),
(17, 27, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-41-3-6a459a5eb80315.82665881.jpeg', 1),
(18, 27, 'variant-gallery-whatsapp-image-2026-06-28-at-21-59-41-6a459a5eb831f6.55787121.jpeg', 1);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id_image`),
  ADD KEY `id_variant` (`id_variant`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id_image` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`id_variant`) REFERENCES `product_variants` (`id_variant`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
