-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 21, 2025 alle 14:15
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
-- Database: `banca`
--
DROP DATABASE IF EXISTS `banca`;
CREATE DATABASE IF NOT EXISTS `banca` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `banca`;

-- --------------------------------------------------------

--
-- Struttura della tabella `clienti`
--

DROP TABLE IF EXISTS `clienti`;
CREATE TABLE IF NOT EXISTS `clienti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `nome_utente` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `indirizzo` text NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `data_nascita` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_utente` (`nome_utente`),
  UNIQUE KEY `telefono` (`telefono`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `clienti`
--

INSERT INTO `clienti` (`id`, `nome`, `cognome`, `nome_utente`, `password`, `indirizzo`, `telefono`, `email`, `data_nascita`) VALUES
(1, 'rosario', 'villani', 'rosariovillani', '$2y$10$kKD0ymi9g0iBd8c480BGvuh8BWD473rDNAfj35MgUJvKTB2vzZ3Kq', 'prova', '3334455666', 'r@r.it', '1986-06-12'),
(5, 'p', 'p', 'p', '$2y$10$/o0PAZtqwhjaYQlVtp/pOe4oe59X784q02kdNhsMN3.QmunGMcmK.', 'p', '3324455666', 'p@p.it', '1991-10-10'),
(6, 'rosario', 'villani', 'administrator', '$2y$10$Y393yb1496bR8kjm7XwfqOQDkISo4nycB1zSQf8o4pdalhgP7BgK6', 'prova', '3455696987', 'admin@admin.it', '1986-06-12'),
(15, 'a', 'a', 'a', '$2y$10$chpWtu0oTkM1iZ4GUYMOG.nA/pKzcfA4k7yma9O72wo6SnGG6FaGu', 'a', '1112223333', 'a@gmail.com', '2025-05-18'),
(16, 'NextGear', 'Next', 'Gear', '$2y$10$kdQ8rhw23Ima5SLXGCFfUOSrLf6kZkZE147.BJS.4VHfvBIkOPlzy', 'boh', '1112223334', 'nextgear@gmail.com', '2025-05-19'),
(17, 'b', 'b', 'b', '$2y$10$inHaz9.5cCEms6wmVLnccePyJZUgX9T00qdeUcuWtg12lFc7PMGXS', 'b', '1112223344', 'b@gmail.com', '2025-05-21'),
(18, 'c', 'c', 'c', '$2y$10$xGptbJxrNHNyBawsfYMGKeE/BBzuRpncbO1xHmbecDpOTcKVq7q26', 'c', '1112223444', 'c@gmail.com', '2025-05-21');

-- --------------------------------------------------------

--
-- Struttura della tabella `conti`
--

DROP TABLE IF EXISTS `conti`;
CREATE TABLE IF NOT EXISTS `conti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `numero_conto` varchar(20) NOT NULL,
  `saldo` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipo_conto` enum('corrente','risparmio') NOT NULL,
  `data_creazione` timestamp NOT NULL DEFAULT current_timestamp(),
  `saldo_iniziale` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_conto` (`numero_conto`),
  KEY `id_cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `conti`
--

INSERT INTO `conti` (`id`, `id_cliente`, `numero_conto`, `saldo`, `tipo_conto`, `data_creazione`, `saldo_iniziale`) VALUES
(1, 1, '0001', 2212.00, 'corrente', '2025-02-03 09:07:28', 2212.00),
(2, 5, '0002', 3061760.00, 'corrente', '2025-02-04 07:49:31', 3061760.00),
(3, 5, '1000', 3156000.00, 'corrente', '2025-04-10 10:59:07', 3156000.00),
(4, 15, '3872', 496638906.00, 'corrente', '2025-05-18 15:07:58', 99999999.99),
(5, 16, '0013', 166000.00, 'corrente', '2025-05-19 17:30:52', 121000.00),
(6, 17, '9178', 2050.00, 'corrente', '2025-05-20 22:17:01', 2050.00),
(7, 18, '7905', 350.00, 'corrente', '2025-05-20 22:48:12', 300.00);

-- --------------------------------------------------------

--
-- Struttura della tabella `transazioni`
--

DROP TABLE IF EXISTS `transazioni`;
CREATE TABLE IF NOT EXISTS `transazioni` (
  `id` int(11) NOT NULL,
  `id_conto_mittente` int(11) NOT NULL,
  `id_conto_destinatario` int(11) DEFAULT NULL,
  `importo` decimal(15,2) NOT NULL,
  `data_transazione` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_operazione` enum('bonifico','deposito','prelievo') NOT NULL,
  `link_sito` varchar(255) DEFAULT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  KEY `transazioni_ibfk1` (`id_conto_mittente`),
  KEY `transazioni_ibfk2` (`id_conto_destinatario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `transazioni`
--

INSERT INTO `transazioni` (`id`, `id_conto_mittente`, `id_conto_destinatario`, `importo`, `data_transazione`, `tipo_operazione`, `link_sito`, `descrizione`) VALUES
(0, 2, NULL, 200.00, '2025-05-16 18:32:33', 'deposito', NULL, NULL),
(0, 2, NULL, 200.00, '2025-05-16 18:32:45', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:32:53', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:32:56', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:35:44', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:36:08', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:36:16', 'deposito', NULL, NULL),
(0, 2, NULL, 10.00, '2025-05-16 18:36:21', 'deposito', NULL, NULL),
(0, 2, NULL, 30.00, '2025-05-16 20:09:33', 'prelievo', NULL, NULL),
(0, 4, NULL, 4000.00, '2025-05-19 14:16:54', 'deposito', NULL, NULL),
(0, 4, NULL, 1.00, '2025-05-19 14:17:02', 'prelievo', NULL, NULL),
(0, 4, NULL, 20.00, '2025-05-19 15:17:39', 'deposito', NULL, NULL),
(0, 4, NULL, 1.00, '2025-05-19 15:17:57', 'deposito', NULL, NULL),
(0, 4, NULL, 1.00, '2025-05-19 15:18:00', 'prelievo', NULL, NULL),
(0, 4, NULL, 500000000.00, '2025-05-19 15:25:22', 'deposito', NULL, NULL),
(0, 4, NULL, 4.00, '2025-05-19 15:45:08', 'prelievo', NULL, NULL),
(0, 4, NULL, 4.00, '2025-05-19 15:45:21', 'prelievo', NULL, NULL),
(0, 4, NULL, 4.00, '2025-05-19 15:45:25', 'prelievo', NULL, NULL),
(0, 4, NULL, 200.00, '2025-05-19 16:43:45', 'deposito', NULL, 'Ricarica conto'),
(0, 4, NULL, 200000.00, '2025-05-19 16:58:53', 'prelievo', NULL, 'Prelievo conto'),
(0, 4, 1, 200.00, '2025-05-19 17:00:09', 'bonifico', NULL, 'gg'),
(0, 4, 5, 11000.00, '2025-05-19 19:10:45', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=3', NULL),
(0, 4, 1, 12.00, '2025-05-19 20:15:06', 'bonifico', NULL, 'gg'),
(0, 4, 5, 55000.00, '2025-05-19 20:15:44', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=1', NULL),
(0, 4, NULL, 3000000.00, '2025-05-19 21:21:28', 'prelievo', NULL, 'Prelievo conto'),
(0, 4, 5, 9000.00, '2025-05-20 15:21:44', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-20 15:29:17', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-20 15:29:23', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-20 15:47:04', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-20 16:02:58', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-20 16:27:22', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, NULL, 20.00, '2025-05-20 21:18:17', 'deposito', NULL, 'Ricarica conto'),
(0, 4, NULL, 10.00, '2025-05-20 21:18:27', 'prelievo', NULL, 'Prelievo conto'),
(0, 4, NULL, 50.00, '2025-05-20 21:18:53', 'prelievo', NULL, 'Prelievo conto'),
(0, 4, NULL, 50.00, '2025-05-20 21:19:52', 'prelievo', NULL, 'Prelievo conto'),
(0, 6, NULL, 50.00, '2025-05-20 22:17:44', 'deposito', NULL, 'Ricarica conto'),
(0, 7, NULL, 100.00, '2025-05-20 22:48:20', 'deposito', NULL, 'Ricarica conto'),
(0, 7, NULL, 50.00, '2025-05-20 22:48:27', 'prelievo', NULL, 'Prelievo conto'),
(0, 4, 5, 9000.00, '2025-05-21 12:04:48', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-21 12:04:53', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-21 12:10:26', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-21 12:13:40', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL),
(0, 4, 5, 9000.00, '2025-05-21 12:13:57', 'bonifico', 'http://localhost/e-commerce/dettaglio_moto.php?id=2', NULL);

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `conti`
--
ALTER TABLE `conti`
  ADD CONSTRAINT `conti_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clienti` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `transazioni`
--
ALTER TABLE `transazioni`
  ADD CONSTRAINT `transazioni_ibfk1` FOREIGN KEY (`id_conto_mittente`) REFERENCES `conti` (`id`),
  ADD CONSTRAINT `transazioni_ibfk2` FOREIGN KEY (`id_conto_destinatario`) REFERENCES `conti` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
