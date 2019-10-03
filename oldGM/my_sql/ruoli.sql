-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 03 nov, 2010 at 01:22 PM
-- Versione MySQL: 5.1.37
-- Versione PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `my_guanalysis`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `ruoli`
--

CREATE TABLE IF NOT EXISTS `ruoli` (
  `ruolo_id` int(2) NOT NULL DEFAULT '0',
  `ruolo_desc` char(3) NOT NULL,
  `ruolo_order` int(2) NOT NULL DEFAULT '0',
  `tipogioco` int(2) NOT NULL,
  PRIMARY KEY (`ruolo_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `ruoli`
--

INSERT INTO `ruoli` (`ruolo_id`, `ruolo_desc`, `ruolo_order`, `tipogioco`) VALUES
(1, 'PO', 1, 0),
(2, 'DS', 2, 0),
(3, 'D', 3, 0),
(4, 'DD', 4, 0),
(5, 'CS', 5, 0),
(6, 'C', 6, 0),
(7, 'CD', 7, 0),
(8, 'AS', 8, 0),
(9, 'A', 9, 0),
(10, 'AD', 10, 0),
(11, 'XX', 11, 0),
(24, 'ALA', 24, 1),
(23, 'CEN', 23, 1),
(22, 'DIF', 22, 1),
(21, 'POR', 21, 1),
(25, 'ATT', 25, 1),
(26, 'XXX', 26, 1);
