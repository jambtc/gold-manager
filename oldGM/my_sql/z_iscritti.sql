-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 11 nov, 2010 at 04:30 PM
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
-- Struttura della tabella `z_iscritti`
--

CREATE TABLE IF NOT EXISTS `z_iscritti` (
  `serie` varchar(20) NOT NULL,
  `squadra` varchar(20) NOT NULL,
  `tipogioco` int(2) NOT NULL,
  KEY `serie` (`serie`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `z_iscritti`
--

INSERT INTO `z_iscritti` (`serie`, `squadra`, `tipogioco`) VALUES
('6.449', 'jammins', 0),
('6.449', 'the music', 0),
('6.449', '11 pagliacci', 0),
('6.449', 'Real Libbiese', 0),
('6.449', 'Pessegatto', 0),
('6.449', 'Discepoli di Gabriel', 0);
