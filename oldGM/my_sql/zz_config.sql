-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 01 dic, 2010 at 03:04 PM
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
-- Struttura della tabella `zz_config`
--

CREATE TABLE IF NOT EXISTS `zz_config` (
  `id` int(1) NOT NULL,
  `data` date NOT NULL,
  `giorno` int(2) NOT NULL,
  `orario` time NOT NULL,
  `squadre` int(11) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `zz_config`
--

INSERT INTO `zz_config` (`id`, `data`, `giorno`, `orario`, `squadre`) VALUES
(1, '2011-01-07', 5, '17:00:00', 10);
