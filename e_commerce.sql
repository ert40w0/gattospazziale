-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 21, 2025 alle 14:16
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_commerce`
--
DROP DATABASE IF EXISTS `e_commerce`;
CREATE DATABASE IF NOT EXISTS `e_commerce` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `e_commerce`;

-- --------------------------------------------------------

--
-- Struttura della tabella `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`) VALUES
(1, 'admin', 'admin@gmail.com', 'admin');

-- --------------------------------------------------------

--
-- Struttura della tabella `categoriemoto`
--

DROP TABLE IF EXISTS `categoriemoto`;
CREATE TABLE IF NOT EXISTS `categoriemoto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_categoria` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_categoria` (`nome_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `categoriemoto`
--

INSERT INTO `categoriemoto` (`id`, `nome_categoria`) VALUES
(2, 'Cruiser'),
(3, 'Naked'),
(4, 'Offroad'),
(5, 'Scrambler'),
(1, 'Sportive');

-- --------------------------------------------------------

--
-- Struttura della tabella `moto`
--

DROP TABLE IF EXISTS `moto`;
CREATE TABLE IF NOT EXISTS `moto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(100) NOT NULL,
  `modello` varchar(100) NOT NULL,
  `anno` int(11) NOT NULL,
  `prezzo` decimal(10,2) NOT NULL,
  `quantita` int(11) DEFAULT 0,
  `categoria_id` int(11) NOT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `descrizione` varchar(200) DEFAULT NULL,
  `cilindrata` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `moto`
--

INSERT INTO `moto` (`id`, `marca`, `modello`, `anno`, `prezzo`, `quantita`, `categoria_id`, `immagine`, `descrizione`, `cilindrata`) VALUES
(1, 'Ducati', 'StreetFighter', 2024, 55000.00, 6, 3, 'https://imgd.aeplcdn.com/664x374/n/bw/models/colors/ducati-select-model-ducati-red-1709901683687.jpeg?q=80', 'Moto del DAC', 1000),
(2, 'Triumph', 'Scrambler 900', 2023, 9000.00, 5, 5, 'https://img.stcrm.it/images/24720048/HOR_STD/800x/triumph-street-scrambler-2021-19.jpeg', 'Moto di forigo', 900),
(3, 'Aprilia', 'RS 660', 2020, 11000.00, 6, 1, 'https://img.stcrm.it/images/31697437/HOR_STD/800x/01-rs-660-jpg.jpeg', 'Moto Italiana', 660);

-- --------------------------------------------------------

--
-- Struttura della tabella `ordini`
--

DROP TABLE IF EXISTS `ordini`;
CREATE TABLE IF NOT EXISTS `ordini` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utente_id` int(11) NOT NULL,
  `moto_id` int(11) NOT NULL,
  `sede_id` int(11) NOT NULL,
  `data_ordine` datetime DEFAULT current_timestamp(),
  `data_prevista_ritiro` date NOT NULL,
  `importo_pagato` decimal(10,2) NOT NULL,
  `stato` enum('Completato','Annullato') DEFAULT 'Completato',
  PRIMARY KEY (`id`),
  KEY `utente_id` (`utente_id`),
  KEY `moto_id` (`moto_id`),
  KEY `sede_id` (`sede_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `ordini`
--

INSERT INTO `ordini` (`id`, `utente_id`, `moto_id`, `sede_id`, `data_ordine`, `data_prevista_ritiro`, `importo_pagato`, `stato`) VALUES
(1, 3, 3, 1, '2025-05-19 21:10:45', '2025-05-26', 11000.00, 'Completato'),
(2, 3, 1, 1, '2025-05-19 22:15:44', '2025-05-26', 55000.00, 'Completato'),
(3, 3, 2, 1, '2025-05-20 17:21:44', '2025-05-27', 9000.00, 'Completato'),
(4, 15, 2, 1, '2025-05-20 17:29:17', '2025-05-27', 9000.00, 'Completato'),
(5, 15, 2, 1, '2025-05-20 17:29:23', '2025-05-27', 9000.00, 'Completato'),
(6, 3, 2, 1, '2025-05-20 17:47:04', '2025-05-27', 9000.00, 'Completato'),
(7, 3, 2, 1, '2025-05-20 18:02:58', '2025-05-27', 9000.00, 'Completato'),
(8, 3, 2, 3, '2025-05-20 18:27:22', '2025-05-22', 9000.00, 'Completato'),
(9, 3, 2, 4, '2025-05-21 14:04:48', '2025-05-22', 9000.00, 'Completato'),
(10, 15, 2, 4, '2025-05-21 14:04:53', '2025-05-22', 9000.00, 'Completato'),
(11, 15, 2, 1, '2025-05-21 14:10:26', '2025-05-28', 9000.00, 'Completato'),
(12, 15, 2, 1, '2025-05-21 14:13:40', '2025-05-28', 9000.00, 'Completato'),
(13, 15, 2, 1, '2025-05-21 14:13:57', '2025-05-28', 9000.00, 'Completato');

-- --------------------------------------------------------

--
-- Struttura della tabella `sedi`
--

DROP TABLE IF EXISTS `sedi`;
CREATE TABLE IF NOT EXISTS `sedi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_sede` varchar(100) NOT NULL,
  `indirizzo` varchar(255) NOT NULL,
  `citta` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `sedi`
--

INSERT INTO `sedi` (`id`, `nome_sede`, `indirizzo`, `citta`) VALUES
(1, 'Sede Milano', 'Via Milano 100', 'Milano'),
(2, 'Sede Torino', 'Via Torino 200', 'Torino'),
(3, 'Sede Genova', 'Via Genova 300', 'Genova'),
(4, 'Sede Verona', 'Via Verona 400', 'Verona'),
(5, 'Sede Bergamo', 'Via Bergamo 500', 'Bergamo'),
(6, 'Sede Bologna', 'Via Bologna 600', 'Bologna'),
(7, 'Sede Trieste', 'Via Trieste 700', 'Trieste');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE IF NOT EXISTS `utenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `data_nascita` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `username`, `email`, `password`, `data_nascita`) VALUES
(1, 'cristian', 'cristian.forigo@gmail.com', '$2y$10$2qm5KR/oHbpXYDJ1W7AdH.on0QFUxUlYaGaWAvnrIp1ln8zzNUJPa', '2006-06-30'),
(2, 'Andrea', 'andrea.bonfante@gmail.com', '$2y$10$rk2R9woWxpFczRdwRPZC7OlEHSBaCDrDvuIWqfGXkFZElkgDMMZhm', '2006-06-30'),
(3, 'm', 'm@gmail.com', '$2y$10$H8wN.rn2UBkr6GtynlewzOJSqRMbvXpXFcv7GKIfBnFBCynY1xBlS', '2006-11-14');

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `moto`
--
ALTER TABLE `moto`
  ADD CONSTRAINT `moto_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categoriemoto` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
