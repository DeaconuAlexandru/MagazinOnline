-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Gazdă: localhost:3306
-- Timp de generare: mart. 12, 2026 la 10:04 AM
-- Versiune server: 10.11.16-MariaDB-cll-lve
-- Versiune PHP: 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Bază de date: `magazi15_ShergeiCovoare`
--
CREATE DATABASE IF NOT EXISTS `magazi15_ShergeiCovoare` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `magazi15_ShergeiCovoare`;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `card_number` varchar(20) NOT NULL,
  `expiry_month` tinyint(4) NOT NULL,
  `expiry_year` smallint(6) NOT NULL,
  `cvv` varchar(4) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` mediumtext NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` mediumtext NOT NULL,
  `message` mediumtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'În curs de livrare'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `phone`, `email`, `message`, `created_at`, `status`) VALUES
(4, 'Daniel Müller', '+4915750102202', 'e.daniel.mueller@pm.me', 'Doresc sa Fac O comanda.', '2026-02-19 14:39:06', 'În curs de livrare'),
(6, 'Popa', '0723663100', 'biz.craiova@gmail.com', 'Test', '2026-03-06 20:08:05', 'În curs de livrare'),
(7, 'Deaconu Nicolae Alexandru', '+40771342916', 'deaconunicolaealexandru@gmail.com', 'Vreau sa cumpar un covor', '2026-03-09 09:37:18', 'În curs de livrare');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `easyboxes`
--

CREATE TABLE `easyboxes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `county` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` mediumtext NOT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Eliminarea datelor din tabel `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `message`, `created_at`) VALUES
(16, 15, 'Salutari', '2026-02-23 15:33:12'),
(18, 9, 'Salutare,aici puteti lasa Feedback,si sa ne vorbiti despre experienta oferita cumparand din acest website.Multumesc pentru intelegere.', '2026-02-23 17:00:12'),
(19, 16, 'Felicit?ri!', '2026-02-28 21:02:59'),
(20, 17, 'Doamne ajuta !', '2026-03-01 11:36:12'),
(21, 17, 'Am facut o comanda de un covor , astept sa fac plata.', '2026-03-01 12:07:19'),
(22, 17, 'Am ales un covor ! Cum fac plata ?', '2026-03-06 08:47:06');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `feedback_images`
--

CREATE TABLE `feedback_images` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `guest_addresses`
--

CREATE TABLE `guest_addresses` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `guest_addresses`
--

INSERT INTO `guest_addresses` (`id`, `guest_id`, `name`, `email`, `phone`, `address`, `city`, `country`, `created_at`, `updated_at`, `expires_at`) VALUES
(26, 0, 'Predoi Vasilica Loredan', '', '0762420041', 'Dolj, Craiova, Str. Henry Ford, bl. A, sc.1, et.1, ap.4', 'Craiova', 'Dolj', '2026-03-12 07:51:50', '2026-03-12 07:51:50', '2026-03-12 09:56:50');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `shipping_method` varchar(50) DEFAULT 'courier',
  `payment_method` varchar(50) DEFAULT 'online',
  `status` varchar(50) DEFAULT 'În curs de procesare',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `easybox_id` int(11) DEFAULT NULL,
  `shipping_name` varchar(255) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `payment_fee` decimal(10,2) DEFAULT 0.00,
  `easybox_city` varchar(100) DEFAULT NULL,
  `easybox_county` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `shipping_method`, `payment_method`, `status`, `created_at`, `updated_at`, `deleted_at`, `easybox_id`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_cost`, `payment_fee`, `easybox_city`, `easybox_county`) VALUES
(135, 17, 435.00, 'courier', 'delivery', 'processing', '2026-03-01 12:04:25', NULL, NULL, NULL, 'Adrian Pop', '0744 581688', 'Dolj, Craiova, Str. Severinului Bl 317 a,b', 25.00, 10.00, NULL, NULL),
(136, 11, 735.00, 'courier', 'delivery', 'processing', '2026-03-03 08:43:51', NULL, NULL, NULL, 'Mihaita Linca', '0764049235', 'Dolj, Mun. Craiova, Severinului bl. 317 ab', 25.00, 10.00, NULL, NULL),
(157, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-10 09:02:24', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL),
(179, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-11 08:23:30', NULL, NULL, NULL, 'Adrian Pop', '0744 581688', 'Dolj, Craiova, Calea Severinului, Bl 317 a,b, firma Pop Service Electronic HQ', 0.00, 0.00, NULL, NULL),
(197, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-11 13:47:32', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL),
(198, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-11 13:54:34', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL),
(199, 24, 10.00, 'courier', 'online', 'pending', '2026-03-11 13:55:36', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL),
(200, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-11 13:55:45', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL),
(201, 24, 10.00, 'courier', 'online', 'pending', '2026-03-11 14:02:10', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL),
(202, NULL, 45.00, 'courier', 'delivery', 'processing', '2026-03-11 14:15:20', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea, Bălcești, 39', 25.00, 10.00, NULL, NULL),
(203, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-11 14:19:09', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea, Bălcești, 39', 0.00, 0.00, NULL, NULL),
(204, NULL, 10.00, 'courier', 'online', 'paid', '2026-03-12 07:52:12', '2026-03-12 07:53:20', NULL, NULL, 'Predoi Vasilica Loredan', '0762420041', 'Dolj, Craiova, Str. Henry Ford, bl. A, sc.1, et.1, ap.4', 25.00, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`, `deleted_at`) VALUES
(136, 135, 71, 1, 400.00, '2026-03-01 12:04:25', NULL, NULL),
(137, 136, 33, 1, 350.00, '2026-03-03 08:43:51', NULL, NULL),
(138, 136, 32, 1, 350.00, '2026-03-03 08:43:51', NULL, NULL),
(159, 157, 93, 1, 10.00, '2026-03-10 09:02:24', NULL, NULL),
(181, 179, 91, 1, 10.00, '2026-03-11 08:23:30', NULL, NULL),
(199, 197, 93, 1, 10.00, '2026-03-11 13:47:32', NULL, NULL),
(200, 198, 93, 1, 10.00, '2026-03-11 13:54:34', NULL, NULL),
(201, 199, 93, 1, 10.00, '2026-03-11 13:55:36', NULL, NULL),
(202, 200, 93, 1, 10.00, '2026-03-11 13:55:45', NULL, NULL),
(203, 201, 92, 1, 10.00, '2026-03-11 14:02:10', NULL, NULL),
(204, 202, 92, 1, 10.00, '2026-03-11 14:15:20', NULL, NULL),
(205, 203, 93, 1, 10.00, '2026-03-11 14:19:09', NULL, NULL),
(206, 204, 92, 1, 10.00, '2026-03-12 07:52:12', NULL, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` text NOT NULL,
  `lungime` decimal(5,2) NOT NULL DEFAULT 1.60,
  `latime` decimal(5,2) NOT NULL DEFAULT 1.00,
  `img` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `lungime`, `latime`, `img`, `description`, `stock`, `created_at`, `active`, `updated_at`, `deleted_at`) VALUES
(31, 'Covor PsyGeometry Royal 1', '350.00', 1.60, 1.00, 'Image1.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(32, 'Covor PsyGeometry Royal 2', '350.00', 1.60, 1.00, 'Image2.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(33, 'Covor PsyGeometry Royal 3', '350.00', 1.60, 1.00, 'Image3.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(34, 'Covor PsyGeometry Royal 4', '350.00', 1.60, 1.00, 'Image4.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(35, 'Covor PsyGeometry Royal 5', '350.00', 1.60, 1.00, 'Image5.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(36, 'Covor PsyGeometry Royal 6', '350.00', 1.60, 1.00, 'Image6.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(37, 'Covor PsyGeometry Royal 7', '350.00', 1.60, 1.00, 'Image7.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(38, 'Covor PsyGeometry Royal 8', '350.00', 1.60, 1.00, 'Image8.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(39, 'Covor PsyGeometry Royal 9', '350.00', 1.60, 1.00, 'Image9.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(40, 'Covor PsyGeometry Royal 10', '350.00', 1.60, 1.00, 'Image10.png', 'Un covor PsyGeometry autentic lucrat automat cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL),
(41, 'Covor PsyGeometry Imperial 11', '350.00', 1.60, 1.00, 'Image11.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(42, 'Covor PsyGeometry Imperial 12', '350.00', 1.60, 1.00, 'Image12.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(43, 'Covor PsyGeometry Imperial 13', '350.00', 1.60, 1.00, 'Image13.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.\nCel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(44, 'Covor PsyGeometry Imperial 14', '350.00', 1.60, 1.00, 'Image14.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(45, 'Covor PsyGeometry Imperial 15', '350.00', 1.60, 1.00, 'Image15.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(46, 'Covor PsyGeometry Imperial 16', '350.00', 1.60, 1.00, 'Image16.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(47, 'Covor PsyGeometry Imperial 17', '350.00', 1.60, 1.00, 'Image17.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(48, 'Covor PsyGeometry Imperial 18', '350.00', 1.60, 1.00, 'Image18.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(49, 'Covor PsyGeometry Imperial 19', '350.00', 1.60, 1.00, 'Image19.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(50, 'Covor PsyGeometry Imperial 20', '350.00', 1.60, 1.00, 'Image20.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL),
(51, 'Covor PsyGeometry Abstract 21', '350.00', 1.60, 1.00, 'Image21.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(52, 'Covor PsyGeometry Abstract 22', '350.00', 1.60, 1.00, 'Image22.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(53, 'Covor PsyGeometry Abstract 23', '350.00', 1.60, 1.00, 'Image23.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(54, 'Covor PsyGeometry Abstract 24', '350.00', 1.60, 1.00, 'Image24.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(55, 'Covor PsyGeometry Abstract 25', '350.00', 1.60, 1.00, 'Image25.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(56, 'Covor Modern Abstract 26', '350.00', 1.60, 1.00, 'Image26.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(57, 'Covor Modern Abstract 27', '350.00', 1.60, 1.00, 'Image27.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(58, 'Covor Modern Abstract 28', '350.00', 1.60, 1.00, 'Image28.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(59, 'Covor Modern Abstract 29', '350.00', 1.60, 1.00, 'Image29.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(60, 'Covor Modern Abstract 30', '350.00', 1.60, 1.00, 'Image30.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL),
(61, 'Covor Artistic Premium 31', '350.00', 1.60, 1.00, 'Image31.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(62, 'Covor Artistic Premium 32', '350.00', 1.60, 1.00, 'Image32.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(63, 'Covor Artistic Premium 33', '350.00', 1.60, 1.00, 'Image33.jpg', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(64, 'Covor Artistic Premium 34', '350.00', 1.60, 1.00, 'Image34.jpg', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(65, 'Covor Artistic Premium 35', '350.00', 1.60, 1.00, 'Image35.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(66, 'Covor Artistic Premium 36', '350.00', 1.60, 1.00, 'Image36.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(67, 'Covor Artistic Premium 37', '350.00', 1.60, 1.00, 'Image37.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(68, 'Covor Artistic Premium 38', '350.00', 1.60, 1.00, 'Image38.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(69, 'Covor Artistic Premium 39', '350.00', 1.60, 1.00, 'Image39.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(70, 'Covor Artistic Premium 40', '350.00', 1.60, 1.00, 'Image40.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL),
(71, 'Covor Oriental Clasic 41', '350.00', 1.60, 1.00, 'Image41.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(72, 'Covor Oriental Clasic 42', '350.00', 1.60, 1.00, 'Image42.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(73, 'Covor Oriental Clasic 43', '350.00', 1.60, 1.00, 'Image43.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(74, 'Covor Oriental Clasic 44', '350.00', 1.60, 1.00, 'Image44.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(75, 'Covor Oriental Clasic 45', '350.00', 1.60, 1.00, 'Image45.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(76, 'Covor Oriental Clasic 46', '350.00', 1.60, 1.00, 'Image46.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(77, 'Covor Oriental Clasic 47', '350.00', 1.60, 1.00, 'Image47.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(78, 'Covor Oriental Clasic 48', '350.00', 1.60, 1.00, 'Image48.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(79, 'Covor Oriental Clasic 49', '350.00', 1.60, 1.00, 'Image49.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(80, 'Covor Oriental Clasic 50', '350.00', 1.60, 1.00, 'Image50.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL),
(81, 'Covor Traditional Moldovenesc 51', '350.00', 1.60, 1.00, 'Image51.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(82, 'Covor Traditional Moldovenesc 52', '350.00', 1.60, 1.00, 'Image52.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(83, 'Covor Traditional Moldovenesc 53', '350.00', 1.60, 1.00, 'Image53.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(84, 'Covor Traditional Moldovenesc 54', '350.00', 1.60, 1.00, 'Image54.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(85, 'Covor Traditional Moldovenesc 55', '350.00', 1.60, 1.00, 'Image55.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(86, 'Covor Traditional Moldovenesc 56', '350.00', 1.60, 1.00, 'Image56.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(87, 'Covor Traditional Moldovenesc 57', '350.00', 1.60, 1.00, 'Image57.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(88, 'Covor Traditional Moldovenesc 58', '350.00', 1.60, 1.00, 'Image58.jpg', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(89, 'Covor Traditional Moldovenesc 59', '350.00', 1.60, 1.00, 'Image59.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(90, 'Covor Traditional Moldovenesc 60', '350.00', 1.60, 1.00, 'Image60.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL),
(91, 'Ingeras 1', '10.00', 0.05, 0.02, 'Image61.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(92, 'Ingeras 2', '10.00', 0.05, 0.02, 'Image62.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(93, 'Ingeras 3', '10.00', 0.05, 0.02, 'Image63.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(94, 'Ingeras 4', '10.00', 0.05, 0.02, 'Image64.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(95, 'Ingeras 5', '10.00', 0.05, 0.02, 'Image65.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(96, 'Ingeras 6', '10.00', 0.05, 0.02, 'Image66.png', 'Un ingeras autentic cu o poveste divina in spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(100, 'Mandala 1', '5.00', 0.02, 0.02, 'Image71.png', 'O mandala superba care este o divinitate superba.', 10, '2026-02-23 11:54:20', 1, NULL, NULL),
(101, 'Mandala 2', '5.00', 0.02, 0.02, 'Image72.png', 'O mandala superba care este o divinitate superba.', 10, '2026-02-23 11:54:20', 1, NULL, NULL),
(111, 'Tablou 1', '50.00', 0.42, 0.30, 'Image81.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 11:54:20', 1, NULL, NULL),
(112, 'Tablou 2', '50.00', 0.42, 0.30, 'Image82.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 11:54:20', 1, NULL, NULL),
(113, 'Tablou 3', '50.00', 0.42, 0.30, 'Image83.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(114, 'Tablou 4', '50.00', 0.42, 0.30, 'Image84.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(115, 'Tablou 5', '50.00', 0.42, 0.30, 'Image85.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(116, 'Tablou 6', '50.00', 0.42, 0.30, 'Image86.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(117, 'Tablou 7', '50.00', 0.42, 0.30, 'Image87.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(118, 'Tablou 8', '50.00', 0.42, 0.30, 'Image88.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(119, 'Tablou 9', '50.00', 0.42, 0.30, 'Image89.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(120, 'Tablou 10', '50.00', 0.42, 0.30, 'Image90.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(121, 'Tablou 11', '50.00', 0.42, 0.30, 'Image91.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(122, 'Tablou 12', '50.00', 0.42, 0.30, 'Image92.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(123, 'Tablou 13', '50.00', 0.42, 0.30, 'Image93.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(124, 'Tablou 14', '50.00', 0.42, 0.30, 'Image94.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(125, 'Tablou 15', '50.00', 0.42, 0.30, 'Image95.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(126, 'Tablou 16', '50.00', 0.42, 0.30, 'Image96.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(127, 'Tablou 17', '50.00', 0.42, 0.30, 'Image97.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(128, 'Tablou 18', '50.00', 0.42, 0.30, 'Image98.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(129, 'Tablou 19', '50.00', 0.42, 0.30, 'Image99.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(130, 'Tablou 20', '50.00', 0.42, 0.30, 'Image100.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(131, 'Tablou 21', '50.00', 0.42, 0.30, 'Image101.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(132, 'Tablou 22', '50.00', 0.42, 0.30, 'Image102.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(133, 'Tablou 23', '50.00', 0.42, 0.30, 'Image103.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(134, 'Tablou 24', '50.00', 0.42, 0.30, 'Image104.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(135, 'Tablou 25', '50.00', 0.42, 0.30, 'Image105.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(136, 'Tablou 26', '50.00', 0.42, 0.30, 'Image106.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(137, 'Tablou 27', '50.00', 0.42, 0.30, 'Image107.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(138, 'Tablou 28', '50.00', 0.42, 0.30, 'Image108.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(139, 'Tablou 29', '50.00', 0.42, 0.30, 'Image109.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(140, 'Tablou 30', '50.00', 0.42, 0.30, 'Image110.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(141, 'Tablou 31', '50.00', 0.42, 0.30, 'Image111.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(142, 'Tablou 32', '50.00', 0.42, 0.30, 'Image112.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(143, 'Tablou 33', '50.00', 0.42, 0.30, 'Image113.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(144, 'Tablou 34', '50.00', 0.42, 0.30, 'Image114.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(145, 'Tablou 35', '50.00', 0.42, 0.30, 'Image115.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(146, 'Tablou 36', '50.00', 0.42, 0.30, 'Image116.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(147, 'Tablou 37', '50.00', 0.42, 0.30, 'Image117.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(148, 'Tablou 38', '50.00', 0.42, 0.30, 'Image118.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(149, 'Tablou 39', '50.00', 0.42, 0.30, 'Image119.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(150, 'Tablou 40', '50.00', 0.42, 0.30, 'Image120.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(151, 'Tablou 41', '50.00', 0.42, 0.30, 'Image121.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(152, 'Tablou 42', '50.00', 0.42, 0.30, 'Image122.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(153, 'Tablou 43', '50.00', 0.42, 0.30, 'Image123.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 09:54:20', 1, NULL, NULL),
(154, 'Tablou 44', '50.00', 0.42, 0.30, 'Image124.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 09:54:20', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `products_backup`
--

CREATE TABLE `products_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `img` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Eliminarea datelor din tabel `products_backup`
--

INSERT INTO `products_backup` (`id`, `name`, `price`, `img`, `description`, `stock`, `created_at`, `active`, `updated_at`, `deleted_at`) VALUES
(1, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 9, '2026-02-05 17:36:40', 1, NULL, NULL),
(2, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 5, '2026-02-05 17:36:40', 1, NULL, NULL),
(3, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-05 17:36:40', 1, NULL, NULL),
(4, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 3, '2026-02-05 17:36:40', 1, NULL, NULL),
(5, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 6, '2026-02-05 17:36:40', 1, NULL, NULL),
(6, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 12, '2026-02-05 17:36:40', 1, NULL, NULL),
(7, 'Covor Persan Royal 1', '350', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 10, '2026-02-05 17:36:40', 1, NULL, NULL),
(8, 'Covor Persan Imperial 2', '350', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 5, '2026-02-05 17:36:40', 1, NULL, NULL),
(9, 'Covor Modern Abstract 3', '350', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-05 17:36:40', 1, NULL, NULL),
(10, 'Covor Artistic Premium 4', '520', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 3, '2026-02-05 17:36:40', 1, NULL, NULL),
(11, 'Covor Oriental Clasic 5', '400', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 6, '2026-02-05 17:36:40', 1, NULL, NULL),
(12, 'Covor Traditional Moldovenesc 6', '300', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 12, '2026-02-05 17:36:40', 1, NULL, NULL),
(13, 'Covor Persan Royal 1', '350', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 10, '2026-02-05 17:36:40', 1, NULL, NULL),
(14, 'Covor Persan Imperial 2', '350', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 5, '2026-02-05 17:36:40', 1, NULL, NULL),
(15, 'Covor Modern Abstract 3', '350', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-05 17:36:40', 1, NULL, NULL),
(16, 'Covor Artistic Premium 4', '350', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 3, '2026-02-05 17:36:40', 1, NULL, NULL),
(17, 'Covor Oriental Clasic 5', '350', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 6, '2026-02-05 17:36:40', 1, NULL, NULL),
(18, 'Covor Traditional Moldovenesc 6', '350', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 12, '2026-02-05 17:36:40', 1, NULL, NULL),
(19, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 10, '2026-02-09 15:57:52', 1, NULL, NULL),
(20, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 5, '2026-02-09 15:57:52', 1, NULL, NULL),
(21, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-09 15:57:52', 1, NULL, NULL),
(22, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 3, '2026-02-09 15:57:52', 1, NULL, NULL),
(23, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 6, '2026-02-09 15:57:52', 1, NULL, NULL),
(24, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 12, '2026-02-09 15:57:52', 1, NULL, NULL),
(25, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(26, 'Covor Persan Royal 1', '350', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(27, 'Covor Persan Royal 1', '350', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(28, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(32, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(33, 'Covor Persan Imperial 2', '350', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(34, 'Covor Persan Imperial 2', '350', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(35, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(39, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(40, 'Covor Modern Abstract 3', '350', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(41, 'Covor Modern Abstract 3', '350', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(42, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(46, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(47, 'Covor Artistic Premium 4', '520', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(48, 'Covor Artistic Premium 4', '350', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(49, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(53, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(54, 'Covor Oriental Clasic 5', '400', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(55, 'Covor Oriental Clasic 5', '350', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(56, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(60, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(61, 'Covor Traditional Moldovenesc 6', '300', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(62, 'Covor Traditional Moldovenesc 6', '350', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(63, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:04:36', 1, NULL, NULL),
(67, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(68, 'Covor Persan Royal 1', '350', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(70, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(71, 'Covor Persan Imperial 2', '350', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(73, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(74, 'Covor Modern Abstract 3', '350', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(76, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(77, 'Covor Artistic Premium 4', '520', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(79, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(80, 'Covor Oriental Clasic 5', '400', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(82, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(83, 'Covor Traditional Moldovenesc 6', '300', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 1, '2026-02-09 16:09:49', 1, NULL, NULL),
(85, 'Covor Persan Royal 1', '350.00', 'Image1.png', 'Un covor persan autentic lucrat manual cu motive tradiționale elaborate. Fiecare fir este țesut cu măiestrie de meșteri cu experiență.', 10, '2026-02-09 16:12:07', 1, NULL, NULL),
(86, 'Covor Persan Imperial 2', '350.00', 'Image2.png', 'Cel mai elaborat covor persan cu motive complexe persane.', 5, '2026-02-09 16:12:07', 1, NULL, NULL),
(87, 'Covor Modern Abstract 3', '350.00', 'Image3.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-09 16:12:07', 1, NULL, NULL),
(88, 'Covor Artistic Premium 4', '520.00', 'Image4.png', 'O piesă de colecție cu design artistic unic.', 3, '2026-02-09 16:12:07', 1, NULL, NULL),
(89, 'Covor Oriental Clasic 5', '400.00', 'Image5.png', 'Eleganță orientală cu motive geometrice rafinate.', 6, '2026-02-09 16:12:07', 1, NULL, NULL),
(90, 'Covor Traditional Moldovenesc 6', '300.00', 'Image6.png', 'Inspirat din tradițiile moldovenești cu motive florale și geometrice.', 12, '2026-02-09 16:12:07', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` mediumtext DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `google_id` varchar(100) DEFAULT NULL,
  `facebook_id` varchar(100) DEFAULT NULL,
  `apple_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `username`, `password`, `name`, `google_id`, `facebook_id`, `apple_id`, `created_at`, `updated_at`, `deleted_at`, `balance`) VALUES
(11, 'mihaita_linca@yahoo.com', NULL, 'Mihaita', '$2y$10$wQyzGHwjNmQhGUllTeOUT.E5E5B4camhoUYN7jY8qtwGdWgDISPL2', NULL, NULL, NULL, NULL, '2026-02-18 07:42:51', NULL, NULL, 0.00),
(13, 'cmurgan@gmail.com', NULL, 'claudiu2026', '$2y$10$F4YQA9wjvSwemfvPykV6BOeUiKHZty4FI.rY9d14dtnubjeMV9W.u', NULL, NULL, NULL, NULL, '2026-02-20 15:28:26', NULL, NULL, 0.00),
(14, 'todaahava8@gmail.com', NULL, 'Mihaela', '$2y$10$vhhpj.2Hj/pMB4FyY7hwROqvOvQRuk/UajdFuYjwLH0SPgvPjtGHi', NULL, NULL, NULL, NULL, '2026-02-21 14:43:05', NULL, NULL, 0.00),
(15, 'e.daniel.mueller@pm.me', NULL, 'e.daniel.mueller@pm.me', '$2y$10$0ngMzPbbrD6sYXv4tQxkHuZMiaJym5TeBZy0z7eB4.YK0KJ3ct6mi', NULL, NULL, NULL, NULL, '2026-02-23 15:31:29', NULL, NULL, 0.00),
(16, 'carmen.moisoiu@yahoo.com', NULL, 'Yamuna', '$2y$10$XbIPU27Zfl3xU6cis.nIIuK5EQ8J2y32YTAFB2.y7ITmr.9Vm2URG', NULL, NULL, NULL, NULL, '2026-02-28 21:02:19', NULL, NULL, 0.00),
(17, 'adipop@popservice.ro', NULL, 'adipop', '$2y$10$fhxdzHq1sfKXPJ1NsaXtY.CiNCFwq6XTd9vuDKnZGHsvM..IU6eG2', NULL, NULL, NULL, NULL, '2026-03-01 11:35:07', NULL, NULL, 0.00),
(18, 'stelian.rosca@gmail.com', NULL, 'Stelian Art Design', '$2y$10$zGxcRd.G4sQzyFsHQ2SqgOmpYg/jOS1QTtBruh.ko4W3Kq2hsRDv2', NULL, NULL, NULL, NULL, '2026-03-04 20:02:54', NULL, NULL, 0.00),
(19, 'biz.craiova@gmail.com', NULL, NULL, '$2y$10$UFwXwbLm3vTzkv253k39ZeRCEhFH.mYyhVkn5SF913LeVioV6xcT2', 'Biz Admin', NULL, NULL, NULL, '2026-03-06 15:22:16', NULL, NULL, 0.00),
(20, 'office@magazinpsy.ro', NULL, NULL, '$2y$10$heWq9I5zZHTqEA9PvyJ7KewZWX.5X7EDWlBcy4TTJg68WHxxLy1lC', 'Biz Admin', NULL, NULL, NULL, '2026-03-06 17:39:33', NULL, NULL, 0.00),
(24, 'deaconunicolaealexandru@gmail.com', NULL, 'Alex', '$2y$10$s8Gk/GXt2ezGHA/nvHyWyeaQEv21PzT8OWNNJkXKhnBef/LpBeD6u', NULL, '100181747445491099665', NULL, NULL, '2026-03-10 08:32:40', NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('billing','shipping') NOT NULL DEFAULT 'billing',
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `county` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `address` mediumtext NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `type`, `name`, `phone`, `county`, `city`, `address_line`, `address`, `postal_code`, `session_id`, `created_at`) VALUES
(63, 11, 'shipping', 'Mihaita Linca', '0764049235', 'Dolj', 'Mun. Craiova', 'Severinului bl. 317 ab', 'Dolj, Mun. Craiova, Severinului bl. 317 ab', NULL, NULL, '2026-03-10 12:13:26'),
(64, 17, 'shipping', 'Adrian Pop', '0744 581688', 'Dolj', 'Craiova', 'Str. Severinului Bl 317 a,b', 'Dolj, Craiova, Str. Severinului Bl 317 a,b', NULL, NULL, '2026-03-10 12:13:26'),
(76, 24, 'shipping', 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea', 'Fauresti', '13', 'Valcea, Fauresti, 13', NULL, NULL, '2026-03-10 19:17:47'),
(77, 24, 'shipping', 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea', 'Bălcești', '39', 'Vâlcea, Bălcești, 39', NULL, '2c4ehbnf215tt683ghuai85d90', '2026-03-10 19:49:10');

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `easyboxes`
--
ALTER TABLE `easyboxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_easyboxes_city` (`city`);

--
-- Indexuri pentru tabele `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `feedback_images`
--
ALTER TABLE `feedback_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_feedback` (`feedback_id`);

--
-- Indexuri pentru tabele `guest_addresses`
--
ALTER TABLE `guest_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_idx` (`guest_id`);

--
-- Indexuri pentru tabele `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `idx_orders_user_id` (`user_id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_easybox` (`easybox_id`);

--
-- Indexuri pentru tabele `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id_idx` (`order_id`),
  ADD KEY `product_id_idx` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`),
  ADD KEY `idx_order_items_product` (`product_id`);

--
-- Indexuri pentru tabele `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexuri pentru tabele `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_users_email` (`email`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indexuri pentru tabele `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_type_address` (`user_id`,`type`,`address_line`),
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_user_or_session` (`user_id`,`session_id`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pentru tabele `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pentru tabele `easyboxes`
--
ALTER TABLE `easyboxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pentru tabele `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pentru tabele `feedback_images`
--
ALTER TABLE `feedback_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pentru tabele `guest_addresses`
--
ALTER TABLE `guest_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pentru tabele `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT pentru tabele `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT pentru tabele `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pentru tabele `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- Constrângeri pentru tabele eliminate
--

--
-- Constrângeri pentru tabele `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_easybox` FOREIGN KEY (`easybox_id`) REFERENCES `easyboxes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constrângeri pentru tabele `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constrângeri pentru tabele `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
