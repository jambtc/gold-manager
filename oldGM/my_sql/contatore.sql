-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 01 dic, 2010 at 03:06 PM
-- Versione MySQL: 5.1.37
-- Versione PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `my_goldenmanager`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `contatore`
--

CREATE TABLE IF NOT EXISTS `contatore` (
  `pagina` int(4) NOT NULL DEFAULT '0',
  `visite` int(11) NOT NULL DEFAULT '0',
  KEY `pagina` (`pagina`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `contatore`
--

INSERT INTO `contatore` (`pagina`, `visite`) VALUES
(1, 1978),
(10, 1830),
(20, 1724),
(30, 659),
(50, 1019),
(60, 738),
(40, 744),
(99, 942),
(456, 737),
(1970, 931),
(5699, 297),
(15482, 119),
(1980, 57),
(404, 11),
(401, 6),
(402, 13),
(90701, 378);
