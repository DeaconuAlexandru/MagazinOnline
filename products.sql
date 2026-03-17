-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Gazdă: localhost:3306
-- Timp de generare: mart. 17, 2026 la 11:51 AM
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

-- --------------------------------------------------------

--
-- Structură tabel pentru tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` text NOT NULL,
  `lungime` decimal(5,2) DEFAULT NULL,
  `latime` decimal(5,2) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `diameter_m` decimal(5,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Eliminarea datelor din tabel `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `lungime`, `latime`, `img`, `description`, `stock`, `created_at`, `active`, `updated_at`, `deleted_at`, `diameter_m`) VALUES
(31, 'Covor PsyGeometry Royal 1', '350.00', 1.60, 1.00, 'Image1.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(32, 'Covor PsyGeometry Royal 2', '350.00', 1.60, 1.00, 'Image2.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(33, 'Covor PsyGeometry Royal 3', '350.00', 1.60, 1.00, 'Image3.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(34, 'Covor PsyGeometry Royal 4', '350.00', 1.60, 1.00, 'Image4.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(35, 'Covor PsyGeometry Royal 5', '350.00', 1.60, 1.00, 'Image5.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(36, 'Covor PsyGeometry Royal 6', '350.00', 1.60, 1.00, 'Image6.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(37, 'Covor PsyGeometry Royal 7', '350.00', 1.60, 1.00, 'Image7.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(38, 'Covor PsyGeometry Royal 8', '350.00', 1.60, 1.00, 'Image8.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(39, 'Covor PsyGeometry Royal 9', '350.00', 1.60, 1.00, 'Image9.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(40, 'Covor PsyGeometry Royal 10', '350.00', 1.60, 1.00, 'Image10.png', 'Un covor PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(41, 'Covor PsyGeometry Imperial 11', '350.00', 1.60, 1.00, 'Image11.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(42, 'Covor PsyGeometry Imperial 12', '350.00', 1.60, 1.00, 'Image12.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(43, 'Covor PsyGeometry Imperial 13', '350.00', 1.60, 1.00, 'Image13.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.\nCel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(44, 'Covor PsyGeometry Imperial 14', '350.00', 1.60, 1.00, 'Image14.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(45, 'Covor PsyGeometry Imperial 15', '350.00', 1.60, 1.00, 'Image15.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(46, 'Covor PsyGeometry Imperial 16', '350.00', 1.60, 1.00, 'Image16.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(47, 'Covor PsyGeometry Imperial 17', '350.00', 1.60, 1.00, 'Image17.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(48, 'Covor PsyGeometry Imperial 18', '350.00', 1.60, 1.00, 'Image18.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(49, 'Covor PsyGeometry Imperial 19', '350.00', 1.60, 1.00, 'Image19.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(50, 'Covor PsyGeometry Imperial 20', '350.00', 1.60, 1.00, 'Image20.png', 'Cel mai elaborat covor PsyGeometry cu motive complexe psygeometry.', 5, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(51, 'Covor PsyGeometry Abstract 21', '350.00', 1.60, 1.00, 'Image21.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(52, 'Covor PsyGeometry Abstract 22', '350.00', 1.60, 1.00, 'Image22.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(53, 'Covor PsyGeometry Abstract 23', '350.00', 1.60, 1.00, 'Image23.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(54, 'Covor PsyGeometry Abstract 24', '350.00', 1.60, 1.00, 'Image24.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(55, 'Covor PsyGeometry Abstract 25', '350.00', 1.60, 1.00, 'Image25.png', 'Covor PsyGeometry contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(56, 'Covor Modern Abstract 26', '350.00', 1.60, 1.00, 'Image26.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(57, 'Covor Modern Abstract 27', '350.00', 1.60, 1.00, 'Image27.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(58, 'Covor Modern Abstract 28', '350.00', 1.60, 1.00, 'Image28.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(59, 'Covor Modern Abstract 29', '350.00', 1.60, 1.00, 'Image29.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(60, 'Covor Modern Abstract 30', '350.00', 1.60, 1.00, 'Image30.png', 'Design contemporan cu motive abstracte vibrante.', 7, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(61, 'Covor Artistic Premium 31', '350.00', 1.60, 1.00, 'Image31.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(62, 'Covor Artistic Premium 32', '350.00', 1.60, 1.00, 'Image32.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(63, 'Covor Artistic Premium 33', '350.00', 1.60, 1.00, 'Image33.jpg', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(64, 'Covor Artistic Premium 34', '350.00', 1.60, 1.00, 'Image34.jpg', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(65, 'Covor Artistic Premium 35', '350.00', 1.60, 1.00, 'Image35.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(66, 'Covor Artistic Premium 36', '350.00', 1.60, 1.00, 'Image36.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(67, 'Covor Artistic Premium 37', '350.00', 1.60, 1.00, 'Image37.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(68, 'Covor Artistic Premium 38', '350.00', 1.60, 1.00, 'Image38.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(69, 'Covor Artistic Premium 39', '350.00', 1.60, 1.00, 'Image39.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(70, 'Covor Artistic Premium 40', '350.00', 1.60, 1.00, 'Image40.png', 'O piesa de colectie cu design artistic unic.', 3, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(71, 'Covor Oriental Clasic 41', '350.00', 1.60, 1.00, 'Image41.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(72, 'Covor Oriental Clasic 42', '350.00', 1.60, 1.00, 'Image42.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(73, 'Covor Oriental Clasic 43', '350.00', 1.60, 1.00, 'Image43.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(74, 'Covor Oriental Clasic 44', '350.00', 1.60, 1.00, 'Image44.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(75, 'Covor Oriental Clasic 45', '350.00', 1.60, 1.00, 'Image45.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(76, 'Covor Oriental Clasic 46', '350.00', 1.60, 1.00, 'Image46.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(77, 'Covor Oriental Clasic 47', '350.00', 1.60, 1.00, 'Image47.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(78, 'Covor Oriental Clasic 48', '350.00', 1.60, 1.00, 'Image48.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(79, 'Covor Oriental Clasic 49', '350.00', 1.60, 1.00, 'Image49.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(80, 'Covor Oriental Clasic 50', '350.00', 1.60, 1.00, 'Image50.png', 'Eleganta orientala cu motive geometrice rafinate.', 6, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(81, 'Covor Traditional Moldovenesc 51', '350.00', 1.60, 1.00, 'Image51.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(82, 'Covor Traditional Moldovenesc 52', '350.00', 1.60, 1.00, 'Image52.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(83, 'Covor Traditional Moldovenesc 53', '350.00', 1.60, 1.00, 'Image53.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(84, 'Covor Traditional Moldovenesc 54', '350.00', 1.60, 1.00, 'Image54.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(85, 'Covor Traditional Moldovenesc 55', '350.00', 1.60, 1.00, 'Image55.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(86, 'Covor Traditional Moldovenesc 56', '350.00', 1.60, 1.00, 'Image56.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(87, 'Covor Traditional Moldovenesc 57', '350.00', 1.60, 1.00, 'Image57.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(88, 'Covor Traditional Moldovenesc 58', '350.00', 1.60, 1.00, 'Image58.jpg', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(89, 'Covor Traditional Moldovenesc 59', '350.00', 1.60, 1.00, 'Image59.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(90, 'Covor Traditional Moldovenesc 60', '350.00', 1.60, 1.00, 'Image60.png', 'Inspirat din traditiile moldovenesti cu motive florale si geometrice.', 12, '2026-02-18 08:58:34', 1, NULL, NULL, 0.00),
(91, 'Ingeras 1', '10.00', 0.05, 0.02, 'Image61.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(92, 'Ingeras 2', '10.00', 0.05, 0.02, 'Image62.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(93, 'Ingeras 3', '10.00', 0.05, 0.02, 'Image63.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(94, 'Ingeras 4', '10.00', 0.05, 0.02, 'Image64.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(95, 'Ingeras 5', '10.00', 0.05, 0.02, 'Image65.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(96, 'Ingeras 6', '10.00', 0.05, 0.02, 'Image66.png', 'Legenda Îngerilor Păcii\n\n\nSe spune că, în anul 1887, în liniștea adâncă a Sfântului Munte Athos, la Schitul românesc Prodromu, călugării se adunau în nopțile de priveghere pentru a se ruga Maicii Domnului pentru pacea lumii.\n\nÎn acea perioadă tulbure, oamenii erau despărțiți de ură, de războaie și de neînțelegeri. Iar monahii știau că singura putere care poate vindeca lumea este rugăciunea născută din iubire.\n\nÎntr-una dintre acele nopți, ei au rostit cu credință o rugăciune către Maica Domnului – Maica Păcii, cerând ca pacea să coboare peste omenire.\n\nRugăciunea Maicii Păcii\n\n\n„Doamnă prea milostivă, Regină a cerurilor, Maică a Păcii și dătătoare de iubire,\nla Tine ne îndreptăm cu tristețe și speranță.\nÎmpacă-i pe cei care se luptă, luminează-i pe cei care urăsc, stinge flăcările discordiei.\nRoagă-Te Fiului Tău, Domnului nostru Iisus Hristos, ca pacea să domnească în întreaga lume,\nca oamenii să trăiască în unitate și bucurie, slăvind numele Lui.\nFii mijlocitoarea și păzitoarea noastră în toate zilele vieții noastre.\n\nAmin.”\n\nMonahii spuneau că atunci când această rugăciune era rostită cu inimă curată, cerul răspundea.\n\nȘi că fiecare rugăciune pentru pace trimite în lume un înger al păcii.\n\nAcești îngeri nu sunt văzuți cu ochii trupului.\nDar ei sunt simțiți în sufletul oamenilor atunci când apare:\nliniștea,\nîmpăcarea,\nsperanța.\n\nAstfel s-a născut Legenda Îngerilor Păcii.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(100, 'Mandala 1', '5.00', 0.02, 0.02, 'Image71.png', 'O mandala superba care este o divinitate superba.', 10, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(101, 'Mandala 2', '5.00', 0.02, 0.02, 'Image72.png', 'O mandala superba care este o divinitate superba.', 10, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(111, 'Tablou 1', '50.00', 0.42, 0.30, 'Image81.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(112, 'Tablou 2', '50.00', 0.42, 0.30, 'Image82.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 11:54:20', 1, NULL, NULL, 0.00),
(113, 'Tablou 3', '50.00', 0.42, 0.30, 'Image83.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(114, 'Tablou 4', '50.00', 0.42, 0.30, 'Image84.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(115, 'Tablou 5', '50.00', 0.42, 0.30, 'Image85.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(116, 'Tablou 6', '50.00', 0.42, 0.30, 'Image86.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(117, 'Tablou 7', '50.00', 0.42, 0.30, 'Image87.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(118, 'Tablou 8', '50.00', 0.42, 0.30, 'Image88.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(119, 'Tablou 9', '50.00', 0.42, 0.30, 'Image89.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(120, 'Tablou 10', '50.00', 0.42, 0.30, 'Image90.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(121, 'Tablou 11', '50.00', 0.42, 0.30, 'Image91.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(122, 'Tablou 12', '50.00', 0.42, 0.30, 'Image92.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(123, 'Tablou 13', '50.00', 0.42, 0.30, 'Image93.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(124, 'Tablou 14', '50.00', 0.42, 0.30, 'Image94.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(125, 'Tablou 15', '50.00', 0.42, 0.30, 'Image95.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(126, 'Tablou 16', '50.00', 0.42, 0.30, 'Image96.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(127, 'Tablou 17', '50.00', 0.42, 0.30, 'Image97.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(128, 'Tablou 18', '50.00', 0.42, 0.30, 'Image98.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(129, 'Tablou 19', '50.00', 0.42, 0.30, 'Image99.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(130, 'Tablou 20', '50.00', 0.42, 0.30, 'Image100.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(131, 'Tablou 21', '50.00', 0.42, 0.30, 'Image101.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(132, 'Tablou 22', '50.00', 0.42, 0.30, 'Image102.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(133, 'Tablou 23', '50.00', 0.42, 0.30, 'Image103.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(134, 'Tablou 24', '50.00', 0.42, 0.30, 'Image104.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(135, 'Tablou 25', '50.00', 0.42, 0.30, 'Image105.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(136, 'Tablou 26', '50.00', 0.42, 0.30, 'Image106.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(137, 'Tablou 27', '50.00', 0.42, 0.30, 'Image107.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(138, 'Tablou 28', '50.00', 0.42, 0.30, 'Image108.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(139, 'Tablou 29', '50.00', 0.42, 0.30, 'Image109.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(140, 'Tablou 30', '50.00', 0.42, 0.30, 'Image110.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(141, 'Tablou 31', '50.00', 0.42, 0.30, 'Image111.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(142, 'Tablou 32', '50.00', 0.42, 0.30, 'Image112.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(143, 'Tablou 33', '50.00', 0.42, 0.30, 'Image113.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(144, 'Tablou 34', '50.00', 0.42, 0.30, 'Image114.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(145, 'Tablou 35', '50.00', 0.42, 0.30, 'Image115.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(146, 'Tablou 36', '50.00', 0.42, 0.30, 'Image116.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(147, 'Tablou 37', '50.00', 0.42, 0.30, 'Image117.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(148, 'Tablou 38', '50.00', 0.42, 0.30, 'Image118.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(149, 'Tablou 39', '50.00', 0.42, 0.30, 'Image119.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(150, 'Tablou 40', '50.00', 0.42, 0.30, 'Image120.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(151, 'Tablou 41', '50.00', 0.42, 0.30, 'Image121.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(152, 'Tablou 42', '50.00', 0.42, 0.30, 'Image122.png', 'Un tablou unic care te da pe spate.', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(153, 'Tablou 43', '50.00', 0.42, 0.30, 'Image123.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(154, 'Tablou 44', '50.00', 0.42, 0.30, 'Image124.png', 'Un tablou unic care te da pe spate', 10, '2026-02-23 09:54:20', 1, NULL, NULL, 0.00),
(161, 'Covor Rotund PsyGeometry 1', '350.00', NULL, NULL, 'Image131.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(162, 'Covor Rotund PsyGeometry 2', '350.00', NULL, NULL, 'Image132.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(163, 'Covor Rotund PsyGeometry 3', '350.00', NULL, NULL, 'Image133.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(164, 'Covor Rotund PsyGeometry 4', '350.00', NULL, NULL, 'Image134.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(165, 'Covor Rotund PsyGeometry 5', '350.00', NULL, NULL, 'Image135.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(166, 'Covor Rotund PsyGeometry 6', '350.00', NULL, NULL, 'Image136.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(167, 'Covor Rotund PsyGeometry 7', '350.00', NULL, NULL, 'Image137.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(168, 'Covor Rotund PsyGeometry 8', '350.00', NULL, NULL, 'Image138.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(169, 'Covor Rotund PsyGeometry 9', '350.00', NULL, NULL, 'Image139.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(170, 'Covor Rotund PsyGeometry 10', '350.00', NULL, NULL, 'Image140.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(171, 'Covor Rotund PsyGeometry 11', '350.00', NULL, NULL, 'Image141.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(172, 'Covor Rotund PsyGeometry 12', '350.00', NULL, NULL, 'Image142.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(173, 'Covor Rotund PsyGeometry 13', '350.00', NULL, NULL, 'Image143.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 06:58:34', 1, NULL, NULL, 1.00),
(174, 'Covor Rotund PsyGeometry 14', '350.00', NULL, NULL, 'Image144.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 04:58:34', 1, NULL, NULL, 1.00),
(175, 'Covor Rotund PsyGeometry 15', '350.00', NULL, NULL, 'Image145.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 04:58:34', 1, NULL, NULL, 1.00),
(176, 'Covor Rotund PsyGeometry 16', '350.00', NULL, NULL, 'Image146.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 04:58:34', 1, NULL, NULL, 1.00),
(177, 'Covor Rotund PsyGeometry 17', '350.00', NULL, NULL, 'Image147.png', 'Un covor rotund PsyGeometry autentic lucrat manual cu motive traditionale elaborate. Fiecare fir este tesut de mana.', 10, '2026-02-18 04:58:34', 1, NULL, NULL, 1.00);

--
-- Indexuri pentru tabele eliminate
--

--
-- Indexuri pentru tabele `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pentru tabele eliminate
--

--
-- AUTO_INCREMENT pentru tabele `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
