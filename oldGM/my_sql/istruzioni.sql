-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 28 gen, 2011 at 03:19 PM
-- Versione MySQL: 5.1.37
-- Versione PHP: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `m29621d1`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `istruzioni`
--

CREATE TABLE IF NOT EXISTS `istruzioni` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_team` varchar(20) NOT NULL,
  `tipologia` int(11) NOT NULL,
  `min` int(11) NOT NULL,
  `condizione` int(11) NOT NULL,
  `entra` int(11) NOT NULL,
  `esce` int(11) NOT NULL,
  `regola` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_team` (`id_team`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Dump dei dati per la tabella `istruzioni`
--

INSERT INTO `istruzioni` (`id`, `id_team`, `tipologia`, `min`, `condizione`, `entra`, `esce`, `regola`) VALUES
(3, 'jammins', 0, 26, 5, 16, 4, 0),
(9, 'jammins', 1, 76, 4, 0, 0, 1),
(11, 'jammins', 0, 75, 2, 3, 9, 0),
(13, 'jammins', 1, 28, 7, 0, 0, 14);
