-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 11 nov, 2010 at 04:29 PM
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
-- Struttura della tabella `z_calendario`
--

CREATE TABLE IF NOT EXISTS `z_calendario` (
  `id_partita` int(11) NOT NULL AUTO_INCREMENT,
  `serie` varchar(20) NOT NULL,
  `data` date NOT NULL,
  `ora_inizio` time NOT NULL,
  `casa` varchar(20) NOT NULL,
  `fuori` varchar(20) NOT NULL,
  `gol_casa` int(2) NOT NULL,
  `gol_fuori` int(2) NOT NULL,
  `giocata` int(1) NOT NULL,
  PRIMARY KEY (`id_partita`),
  KEY `data` (`data`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dump dei dati per la tabella `z_calendario`
--

