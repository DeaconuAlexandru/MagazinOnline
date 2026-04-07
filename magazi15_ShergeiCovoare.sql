-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Gazdă: localhost:3306
-- Timp de generare: apr. 07, 2026 la 03:24 PM
-- Versiune server: 10.11.16-MariaDB-cll-lve
-- Versiune PHP: 8.4.19

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
(6, 'Popa', '0723663100', 'biz.craiova@gmail.com', 'sada', '2026-03-06 20:08:05', 'În curs de livrare'),
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
  `message` mediumtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `message`, `created_at`) VALUES
(16, 15, 'Salutari', '2026-02-23 15:33:12'),
(24, 24, 'Salutare,aici puteti lasa Feedback,si sa ne vorbiti despre experienta oferita cumparand din acest website.Multumesc pentru intelegere.', '2026-03-18 09:34:19'),
(19, 16, 'Felicitări!', '2026-02-28 21:02:59'),
(20, 17, 'Doamne ajuta !', '2026-03-01 11:36:12'),
(21, 17, 'Am facut o comanda de un covor , astept sa fac plata.', '2026-03-01 12:07:19'),
(22, 17, 'Am ales un covor ! Cum fac plata ?', '2026-03-06 08:47:06'),
(25, 25, 'Mulțumesc pentru coloborare. Sunt și simt. Hari. Amin.', '2026-03-19 11:49:02'),
(27, 24, 'Vă salut pe toți,și vă mulțumesc din suflet.', '2026-03-19 12:11:05');

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `feedback_images`
--

CREATE TABLE `feedback_images` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `feedback_images`
--

INSERT INTO `feedback_images` (`id`, `feedback_id`, `image_path`) VALUES
(17, 25, 'uploads/feedback_1773920942367.jpeg');

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
(53, 0, 'Deaconu Nicolae Alexandru', '', '0771342916', 'Vâlcea, Fauresti, Strada Grigoresti 13', 'Fauresti', 'Vâlcea', '2026-03-27 13:58:41', '2026-03-27 13:58:41', '2026-03-27 16:03:41'),
(54, 0, 'Deaconu Nicolae Alexandru', '', '0771342916', 'Vâlcea, Murgasi, Strada Grigoresti 13', 'Murgasi', 'Vâlcea', '2026-03-27 13:59:56', '2026-03-27 13:59:56', '2026-03-27 16:04:56');

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
  `easybox_county` varchar(100) DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `shipping_method`, `payment_method`, `status`, `created_at`, `updated_at`, `deleted_at`, `easybox_id`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_cost`, `payment_fee`, `easybox_city`, `easybox_county`, `email_sent_at`) VALUES
(135, 17, 435.00, 'courier', 'delivery', 'processing', '2026-03-01 12:04:25', NULL, NULL, NULL, 'Adrian Pop', '0744 581688', 'Dolj, Craiova, Str. Severinului Bl 317 a,b', 25.00, 10.00, NULL, NULL, NULL),
(136, 11, 735.00, 'courier', 'delivery', 'processing', '2026-03-03 08:43:51', NULL, NULL, NULL, 'Mihaita Linca', '0764049235', 'Dolj, Mun. Craiova, Severinului bl. 317 ab', 25.00, 10.00, NULL, NULL, NULL),
(157, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-10 09:02:24', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL, NULL),
(179, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-11 08:23:30', NULL, NULL, NULL, 'Adrian Pop', '0744 581688', 'Dolj, Craiova, Calea Severinului, Bl 317 a,b, firma Pop Service Electronic HQ', 0.00, 0.00, NULL, NULL, NULL),
(197, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-11 13:47:32', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL, NULL),
(207, NULL, 45.00, 'courier', 'delivery', 'processing', '2026-03-23 20:27:50', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL, NULL),
(208, 24, 10.00, 'courier', 'online', 'paid', '2026-03-23 20:29:01', '2026-03-23 20:29:16', NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 0.00, NULL, NULL, '2026-03-23 22:29:17'),
(209, NULL, 45.00, 'courier', 'delivery', 'processing', '2026-03-23 20:32:14', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL, NULL),
(210, 24, 45.00, 'courier', 'delivery', 'processing', '2026-03-23 20:44:43', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 10.00, NULL, NULL, NULL),
(211, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-23 20:53:16', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL, NULL),
(212, NULL, 35.00, 'courier', 'online', 'pending', '2026-03-23 20:53:37', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 0.00, NULL, NULL, NULL),
(213, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-23 20:56:33', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL, NULL),
(214, 24, 10.00, 'courier', 'online', 'pending', '2026-03-23 20:57:57', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL, NULL),
(215, NULL, 10.00, 'courier', 'online', 'paid', '2026-03-23 21:09:54', '2026-03-23 21:10:37', NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 25.00, 0.00, NULL, NULL, '2026-03-23 23:10:37'),
(216, NULL, 10.00, 'courier', 'online', 'pending', '2026-03-23 21:12:20', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Valcea, Fauresti, 13', 0.00, 0.00, NULL, NULL, NULL),
(217, NULL, 385.00, 'courier', 'delivery', 'processing', '2026-03-27 10:36:11', NULL, NULL, NULL, 'nume', '0722222222', 'judet, localitate, adreasa', 25.00, 10.00, NULL, NULL, NULL),
(218, NULL, 45.00, 'courier', 'delivery', 'processing', '2026-03-27 13:58:44', NULL, NULL, NULL, 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea, Fauresti, Strada Grigoresti 13', 25.00, 10.00, NULL, NULL, NULL);

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
(181, 179, 91, 1, 10.00, '2026-03-11 08:23:30', NULL, NULL),
(209, 207, 92, 1, 10.00, '2026-03-23 20:27:50', NULL, NULL),
(210, 208, 92, 1, 10.00, '2026-03-23 20:29:01', NULL, NULL),
(211, 209, 92, 1, 10.00, '2026-03-23 20:32:14', NULL, NULL),
(212, 210, 92, 1, 10.00, '2026-03-23 20:44:43', NULL, NULL),
(213, 211, 92, 1, 10.00, '2026-03-23 20:53:16', NULL, NULL),
(214, 212, 92, 1, 10.00, '2026-03-23 20:53:37', NULL, NULL),
(215, 213, 93, 1, 10.00, '2026-03-23 20:56:33', NULL, NULL),
(216, 214, 92, 1, 10.00, '2026-03-23 20:57:57', NULL, NULL),
(217, 215, 92, 1, 10.00, '2026-03-23 21:09:54', NULL, NULL),
(218, 216, 92, 1, 10.00, '2026-03-23 21:12:20', NULL, NULL),
(219, 217, 161, 1, 350.00, '2026-03-27 10:36:11', NULL, NULL),
(220, 218, 92, 1, 10.00, '2026-03-27 13:58:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `products`
--

CREATE TABLE `products` (
  `sort_ordine` int(11) NOT NULL DEFAULT 0,
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,0) NOT NULL DEFAULT 0,
  `lungime_cm` decimal(7,2) DEFAULT NULL,
  `latime_cm` decimal(7,2) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `preview` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `diameter_cm` decimal(7,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `products`
--

INSERT INTO `products` (`sort_ordine`, `id`, `name`, `price`, `lungime_cm`, `latime_cm`, `img`, `description`, `preview`, `stock`, `created_at`, `active`, `updated_at`, `deleted_at`, `diameter_cm`) VALUES
(20, 31, 'Mocheta Dreptunghiulara 2', 525, 160.00, 100.00, 'Image1.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(40, 32, 'Mocheta Dreptunghiulara 4', 525, 160.00, 100.00, 'Image2.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(30, 33, 'Mocheta Dreptunghiulara 3', 525, 160.00, 100.00, 'Image3.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(50, 34, 'Mocheta Dreptunghiulara 5', 525, 160.00, 100.00, 'Image4.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(60, 35, 'Mocheta Dreptunghiulara 6', 525, 160.00, 100.00, 'Image5.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(70, 36, 'Mocheta Dreptunghiulara 7', 525, 160.00, 100.00, 'Image6.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate.O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(80, 37, 'Mocheta Dreptunghiulara 8', 525, 160.00, 100.00, 'Image7.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(90, 38, 'Mocheta Dreptunghiulara 9', 525, 160.00, 100.00, 'Image8.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(100, 39, 'Mocheta Dreptunghiulara 10', 525, 160.00, 100.00, 'Image9.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(110, 40, 'Mocheta Dreptunghiulara 11', 525, 160.00, 100.00, 'Image10.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate.O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(120, 41, 'Mocheta Dreptunghiulara 12', 525, 160.00, 100.00, 'Image11.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(130, 42, 'Mocheta Dreptunghiulara 13', 525, 160.00, 100.00, 'Image12.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(140, 43, 'Mocheta Dreptunghiulara 14', 525, 160.00, 100.00, 'Image13.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(150, 44, 'Mocheta Dreptunghiulara 15', 525, 160.00, 100.00, 'Image14.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(160, 45, 'Mocheta Dreptunghiulara 16', 525, 160.00, 100.00, 'Image15.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(170, 46, 'Mocheta Dreptunghiulara 17', 525, 160.00, 100.00, 'Image16.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(180, 47, 'Mocheta Dreptunghiulara 18', 525, 160.00, 100.00, 'Image17.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(190, 48, 'Mocheta Dreptunghiulara 19', 525, 160.00, 100.00, 'Image18.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(200, 49, 'Mocheta Dreptunghiulara 20', 525, 160.00, 100.00, 'Image19.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(210, 50, 'Mocheta Dreptunghiulara 21', 525, 160.00, 100.00, 'Image20.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(220, 51, 'Mocheta Dreptunghiulara 22', 525, 160.00, 100.00, 'Image21.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(230, 52, 'Mocheta Dreptunghiulara 23', 525, 160.00, 100.00, 'Image22.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(240, 53, 'Mocheta Dreptunghiulara 24', 525, 160.00, 100.00, 'Image23.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(250, 54, 'Mocheta Dreptunghiulara 25', 525, 160.00, 100.00, 'Image24.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(260, 55, 'Mocheta Dreptunghiulara 26', 525, 160.00, 100.00, 'Image25.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(270, 56, 'Mocheta Dreptunghiulara 27', 525, 160.00, 100.00, 'Image26.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(280, 57, 'Mocheta Dreptunghiulara 28', 525, 160.00, 100.00, 'Image27.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(290, 58, 'Mocheta Dreptunghiulara 29', 525, 160.00, 100.00, 'Image28.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(300, 59, 'Mocheta Dreptunghiulara 30', 525, 160.00, 100.00, 'Image29.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(310, 60, 'Mocheta Dreptunghiulara 31', 525, 160.00, 100.00, 'Image30.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(320, 61, 'Mocheta Dreptunghiulara 32', 525, 160.00, 100.00, 'Image31.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(330, 62, 'Mocheta Dreptunghiulara 33', 525, 160.00, 100.00, 'Image32.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(340, 63, 'Mocheta Dreptunghiulara 34', 525, 160.00, 100.00, 'Image33.jpg', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(350, 64, 'Mocheta Dreptunghiulara 35', 525, 160.00, 100.00, 'Image34.jpg', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(360, 65, 'Mocheta Dreptunghiulara 36', 525, 160.00, 100.00, 'Image35.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(370, 66, 'Mocheta Dreptunghiulara 37', 525, 160.00, 100.00, 'Image36.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(380, 67, 'Mocheta Dreptunghiulara 38', 525, 160.00, 100.00, 'Image37.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(390, 68, 'Mocheta Dreptunghiulara 39', 525, 160.00, 100.00, 'Image38.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(400, 69, 'Mocheta Dreptunghiulara 40', 525, 160.00, 100.00, 'Image39.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(10, 70, 'Mocheta Dreptunghiulara 1', 525, 160.00, 100.00, 'Image40.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(410, 71, 'Mocheta Dreptunghiulara 41', 525, 160.00, 100.00, 'Image41.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(420, 72, 'Mocheta Dreptunghiulara 42', 525, 160.00, 100.00, 'Image42.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(430, 73, 'Mocheta Dreptunghiulara 43', 525, 160.00, 100.00, 'Image43.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(440, 74, 'Mocheta Dreptunghiulara 44', 525, 160.00, 100.00, 'Image44.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(450, 75, 'Mocheta Dreptunghiulara 45', 525, 160.00, 100.00, 'Image45.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(460, 76, 'Mocheta Dreptunghiulara 46', 525, 160.00, 100.00, 'Image46.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(470, 77, 'Mocheta Dreptunghiulara 47', 525, 160.00, 100.00, 'Image47.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(480, 78, 'Mocheta Dreptunghiulara 48', 525, 160.00, 100.00, 'Image48.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(490, 79, 'Mocheta Dreptunghiulara 49', 525, 160.00, 100.00, 'Image49.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(500, 80, 'Mocheta Dreptunghiulara 50', 525, 160.00, 100.00, 'Image50.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(510, 81, 'Mocheta Dreptunghiulara 51', 525, 160.00, 100.00, 'Image51.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(520, 82, 'Mocheta Dreptunghiulara 52', 525, 160.00, 100.00, 'Image52.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(530, 83, 'Mocheta Dreptunghiulara 53', 525, 160.00, 100.00, 'Image53.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(540, 84, 'Mocheta Dreptunghiulara 54', 525, 160.00, 100.00, 'Image54.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(550, 85, 'Mocheta Dreptunghiulara 55', 525, 160.00, 100.00, 'Image55.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(560, 86, 'Mocheta Dreptunghiulara 56', 525, 160.00, 100.00, 'Image56.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(570, 87, 'Mocheta Dreptunghiulara 57', 525, 160.00, 100.00, 'Image57.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(580, 88, 'Mocheta Dreptunghiulara 58', 525, 160.00, 100.00, 'Image58.jpg', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(590, 89, 'Mocheta Dreptunghiulara 59', 525, 160.00, 100.00, 'Image59.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(600, 90, 'Mocheta Dreptunghiulara 60', 525, 160.00, 100.00, 'Image60.png', 'O mocheta autentica PsyGeometry printata\n\n\nEste o poartă vizuală către armonia interioară, unde simbolurile sacre și geometria divină se împletesc într-un câmp energetic de echilibru și conștiință. Centrul său radiant evocă unitatea dintre minte, spirit și univers, invitând privitorul la introspecție, claritate și reconectare cu esența profundă a ființei.', NULL, 0, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(0, 91, 'Ingeras 1', 10, 5.00, 2.00, 'Image61.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', NULL, 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 92, 'Ingeras 2', 10, 5.00, 2.00, 'Image62.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 93, 'Ingeras 3', 10, 5.00, 2.00, 'Image63.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 94, 'Ingeras 4', 10, 5.00, 2.00, 'Image64.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 95, 'Ingeras 5', 10, 5.00, 2.00, 'Image65.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 100, 'Mandala 1', 5, 2.00, 2.00, 'Image71.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 10, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(0, 101, 'Mandala 2', 5, 2.00, 2.00, 'Image72.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(0, 102, 'Mandala 3', 5, 2.00, 2.00, 'Image73.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 103, 'Mandala 4', 5, 2.00, 2.00, 'Image74.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 104, 'Mandala 5', 5, 2.00, 2.00, 'Image75.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00);
INSERT INTO `products` (`sort_ordine`, `id`, `name`, `price`, `lungime_cm`, `latime_cm`, `img`, `description`, `preview`, `stock`, `created_at`, `active`, `updated_at`, `deleted_at`, `diameter_cm`) VALUES
(0, 105, 'Mandala 6', 5, 2.00, 2.00, 'Image76.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 106, 'Mandala 7', 5, 2.00, 2.00, 'Image77.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 107, 'Mandala 8', 5, 2.00, 2.00, 'Image78.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 108, 'Mandala 9', 5, 2.00, 2.00, 'Image79.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 109, 'Mandala 10', 5, 2.00, 2.00, 'Image80.png', 'Această mandală este neobișnuită.\nAre propriul ei secret.\nLotusul cu unsprezece petale simbolizează renașterea, din cenușa focului Creatorului într-o floare frumoasă cu unsprezece petale. Ajută foarte mult la renașterea dintr-o formă rea într-una bună; dintr-o situație imposibilă, există o cale de ieșire spre siguranță și o soluție frumoasă.\nSub acest lotus, această mandală, se află o mandală cu opt lotusuri. Reprezintă cele opt puteri divine. Acestea se numesc siddhi. Ceea ce este imposibil devine posibil. Aceasta este puterea lotusului cu opt petale.\nDar ele sunt unite de lotusul cu trei petale, această mandală. Această mandală reprezintă Sfânta Treime.\nFrumoasa combinație a acestor trei mandale permite abordarea misterelor lumii.\n3 plus 8 plus 11 formează o mandală cu douăzeci de lotusuri. Nimeni nu a mai făcut asta până acum. Acesta este secretul meu.\nDar ce este 22? Aceasta este muzica cerească a sferelor cu toate notele cosmice. În muzică, avem 5 clape negre și 7 clape albe la pian. Dar orga are sunete care fie cresc, fie scad sunetul, vibrația. Orga mare de biserică a lui Johann Sebastian Bach are 22 de note. Cu o astfel de orgă cu 22 de note, compozitorul putea schimba vibrațiile din corpul uman, ceea ce s-a întâmplat și la concertul său de la Viena, Austria. Acest lucru l-a speriat foarte tare pe regele Austriei, și pe supușii săi, înțelegând că acest lucru ar putea schimba puterea organelor din corpul uman și l-ar putea manipula. Dar aceasta este muzică, nu o mandală. Mandala cu 22 de petale rezonează în noi atunci când privim această floare de lotus - o mandală. Acestea au rămas în notația muzicală până în ziua de azi. Se numesc note întregi, dar există un sunet fundamental, vibrația, ascuțit, bemol și natural. Un total de 22 de sunete de vibrație. Un ajutor în viața noastră este o astfel de mandală în casa ta. Un covor, un medalion cu magnet sau o pictură murală. Cumpără unul și descoperă-te pe tine însuți și puterea lotusului cu 22 de petale și a mandalei.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 111, 'Tablou 1', 50, 42.00, 30.00, 'Image81.png', 'Un tablou unic care te da pe spate', NULL, 0, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(0, 112, 'Tablou 2', 50, 42.00, 30.00, 'Image82.png', 'Un tablou unic care te da pe spate', NULL, 0, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(0, 113, 'Tablou 3', 50, 42.00, 30.00, 'Image83.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 114, 'Tablou 4', 50, 42.00, 30.00, 'Image84.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 115, 'Tablou 5', 50, 42.00, 30.00, 'Image85.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 116, 'Tablou 6', 50, 42.00, 30.00, 'Image86.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 117, 'Tablou 7', 50, 42.00, 30.00, 'Image87.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 118, 'Tablou 8', 50, 42.00, 30.00, 'Image88.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 119, 'Tablou 9', 50, 42.00, 30.00, 'Image89.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 120, 'Tablou 10', 50, 42.00, 30.00, 'Image90.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 121, 'Tablou 11', 50, 42.00, 30.00, 'Image91.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 122, 'Tablou 12', 50, 42.00, 30.00, 'Image92.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 123, 'Tablou 13', 50, 42.00, 30.00, 'Image93.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 124, 'Tablou 14', 50, 42.00, 30.00, 'Image94.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 125, 'Tablou 15', 50, 42.00, 30.00, 'Image95.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 126, 'Tablou 16', 50, 42.00, 30.00, 'Image96.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 127, 'Tablou 17', 50, 42.00, 30.00, 'Image97.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 128, 'Tablou 18', 50, 42.00, 30.00, 'Image98.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 129, 'Tablou 19', 50, 42.00, 30.00, 'Image99.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 130, 'Tablou 20', 50, 42.00, 30.00, 'Image100.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 131, 'Tablou 21', 50, 42.00, 30.00, 'Image101.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 132, 'Tablou 22', 50, 42.00, 30.00, 'Image102.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 133, 'Tablou 23', 50, 42.00, 30.00, 'Image103.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 134, 'Tablou 24', 50, 42.00, 30.00, 'Image104.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 135, 'Tablou 25', 50, 42.00, 30.00, 'Image105.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 136, 'Tablou 26', 50, 42.00, 30.00, 'Image106.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 137, 'Tablou 27', 50, 42.00, 30.00, 'Image107.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 138, 'Tablou 28', 50, 42.00, 30.00, 'Image108.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 139, 'Tablou 29', 50, 42.00, 30.00, 'Image109.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 140, 'Tablou 30', 50, 42.00, 30.00, 'Image110.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 141, 'Tablou 31', 50, 42.00, 30.00, 'Image111.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 142, 'Tablou 32', 50, 42.00, 30.00, 'Image112.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 143, 'Tablou 33', 50, 42.00, 30.00, 'Image113.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 144, 'Tablou 34', 50, 42.00, 30.00, 'Image114.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 145, 'Tablou 35', 50, 42.00, 30.00, 'Image115.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 146, 'Tablou 36', 50, 42.00, 30.00, 'Image116.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 147, 'Tablou 37', 50, 42.00, 30.00, 'Image117.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 148, 'Tablou 38', 50, 42.00, 30.00, 'Image118.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 149, 'Tablou 39', 50, 42.00, 30.00, 'Image119.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 150, 'Tablou 40', 50, 42.00, 30.00, 'Image120.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 151, 'Tablou 41', 50, 42.00, 30.00, 'Image121.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 152, 'Tablou 42', 50, 42.00, 30.00, 'Image122.png', 'Un tablou unic care te da pe spate.', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 153, 'Tablou 43', 50, 42.00, 30.00, 'Image123.png', 'Un tablou unic care te da pe spate', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 154, 'Tablou 44', 50, 42.00, 30.00, 'Image124.png', 'Un tablou unic care te da pe spate', NULL, 0, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(0, 155, 'Tablou 45', 50, 42.00, 30.00, 'Image125.png\r\n', 'Arhanghel Metatron și sigila-glif Moșek, sunt concepute pentru a ne oferi ajutor in viata. Energia acestui tablou Artefact schimba in bine campul energetic in casa, la birou si oriunde este asezat.', 'Arhanghel Metatron ajuta in tot ce facem', 0, '2026-02-23 07:54:20', 1, NULL, NULL, 0.00),
(0, 156, 'Tablou 46', 50, 42.00, 30.00, 'Image126.png', 'Un tablou unic care te da pe spate', NULL, 0, '2026-02-23 07:54:20', 1, NULL, NULL, 0.00),
(0, 161, 'Mocheta Rotunda PsyGeometry 1', 350, NULL, NULL, 'Image131.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 162, 'Mocheta Rotunda PsyGeometry 2', 350, NULL, NULL, 'Image132.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 163, 'Mocheta Rotunda PsyGeometry 3', 350, NULL, NULL, 'Image133.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 164, 'Mocheta Rotunda PsyGeometry 4', 350, NULL, NULL, 'Image134.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 165, 'Mocheta Rotunda PsyGeometry 5', 350, NULL, NULL, 'Image135.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 166, 'Mocheta Rotunda PsyGeometry 6', 350, NULL, NULL, 'Image136.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 167, 'Mocheta Rotunda PsyGeometry 7', 350, NULL, NULL, 'Image137.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 168, 'Mocheta Rotunda PsyGeometry 8', 350, NULL, NULL, 'Image138.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 169, 'Mocheta Rotunda PsyGeometry 9', 350, NULL, NULL, 'Image139.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 170, 'Mocheta Rotunda PsyGeometry 10', 350, NULL, NULL, 'Image140.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 171, 'Mocheta Rotunda PsyGeometry 11', 350, NULL, NULL, 'Image141.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 172, 'Mocheta Rotunda PsyGeometry 12', 350, NULL, NULL, 'Image142.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 173, 'Mocheta Rotunda PsyGeometry 13', 350, NULL, NULL, 'Image143.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 06:58:34', 1, NULL, NULL, 100.00),
(0, 174, 'Mocheta Rotunda PsyGeometry 14', 350, NULL, NULL, 'Image144.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 04:58:34', 1, NULL, NULL, 100.00),
(0, 175, 'Mocheta Rotunda PsyGeometry 15', 350, NULL, NULL, 'Image145.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 04:58:34', 1, NULL, NULL, 100.00),
(0, 176, 'Mocheta Rotunda PsyGeometry 16', 350, NULL, NULL, 'Image146.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 04:58:34', 1, NULL, NULL, 100.00),
(0, 177, 'Mocheta Rotunda PsyGeometry 17', 350, NULL, NULL, 'Image147.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', NULL, 0, '2026-02-18 04:58:34', 1, NULL, NULL, 100.00),
(0, 178, 'Mocheta Rotunda PsyGeometry 18', 350, NULL, NULL, 'Image148.png', 'Elemiah este al 4-lea înger Kabbalistic din cei 72 de Shem HaMephorash, aparținând corului Serafimilor. Cunoscut ca Îngerul Puterii Interioare, Succesului și Protecției, Elemiah îi guvernează pe cei născuți între 5 și 9 aprilie. Acest înger inspiră noi începuturi, ajută la depășirea crizelor profesionale și oferă liniște și protecție în timpul călătoriilor.\n\n\nAspecte cheie ale Îngerului Elemiah:\n\nSemnificație: „Dumnezeu Ascuns” sau „Dumnezeu este Ascuns”.\n\nAtribute: Reprezintă succesul, protecția, puterea interioară și spiritul antreprenorial. Este considerat un planificator și un împăciuitor.\n\nZodiac/Element: 16°–20° în Berbec, elementul Focului.\n\nDate de naștere: 5 aprilie – 9 aprilie.\n\nDate de influență: 23 martie, 4 iunie, 16 august, 28 octombrie, 9 ianuarie.\n\nSimbolism: Adesea legat de Citrin, culorile auriu/indigo și este folosit pentru a dobândi cunoștințe despre trădători sau pentru a evita eșecurile în afaceri.\n\nSe crede că Elemiah ajută indivizii să își atingă obiectivele prin inițiativă și ajută la transformarea situațiilor dificile în rezultate favorabile.', '', 0, '2026-02-18 04:58:34', 1, NULL, NULL, 100.00);

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
(24, 'deaconunicolaealexandru@gmail.com', NULL, 'Alex', '$2y$10$s8Gk/GXt2ezGHA/nvHyWyeaQEv21PzT8OWNNJkXKhnBef/LpBeD6u', NULL, '100181747445491099665', NULL, NULL, '2026-03-10 08:32:40', NULL, NULL, 0.00),
(25, 'maestrofortunato@gmail.com', NULL, 'Gon-Po Serghei', '$2y$10$UJeM51Jl.ynGzsm/MgVg3Og27ov/TMpdT2/44/oEEwcqhzxFLBQw.', NULL, NULL, NULL, NULL, '2026-03-19 11:45:59', NULL, NULL, 0.00),
(26, 'vasyromeo43@gmail.com', NULL, 'VasyRomeo', '$2y$10$Kg3m5Ce3rbenqS3iAvKA3uXWrZ2.OctrK8wKzk9.QXXkCfiXYZKp6', NULL, NULL, NULL, NULL, '2026-03-25 20:26:55', NULL, NULL, 0.00);

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
(77, 24, 'shipping', 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea', 'Bălcești', '39', 'Vâlcea, Bălcești, 39', NULL, '2c4ehbnf215tt683ghuai85d90', '2026-03-10 19:49:10'),
(78, 24, 'shipping', 'Deaconu Nicolae Alexandru', '0771342916', 'Vâlcea', 'Fauresti', 'Strada Grigoresti numarul 13', 'Vâlcea, Fauresti, Strada Grigoresti numarul 13', NULL, 'uv762ss3j9raprqgqvc0hoart9', '2026-03-27 14:41:02');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pentru tabele `feedback_images`
--
ALTER TABLE `feedback_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pentru tabele `guest_addresses`
--
ALTER TABLE `guest_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT pentru tabele `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT pentru tabele `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT pentru tabele `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

--
-- AUTO_INCREMENT pentru tabele `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pentru tabele `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

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
