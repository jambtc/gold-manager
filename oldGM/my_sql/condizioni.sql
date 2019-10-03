-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 26 gen, 2011 at 12:58 PM
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
-- Struttura della tabella `condizioni`
--

CREATE TABLE IF NOT EXISTS `condizioni` (
  `id` int(11) NOT NULL,
  `descrizione` varchar(50) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `condizioni`
--

INSERT INTO `condizioni` (`id`, `descrizione`) VALUES
(0, 'in qualsiasi situazione'),
(1, 'la partita è in parità'),
(2, 'in vantaggio'),
(3, 'in svantaggio'),
(4, 'in vantaggio di più di un gol'),
(5, 'in svantaggio di più di un gol'),
(6, 'un mio giocatore viene espulso'),
(7, 'un giocatore avversario viene espulso');
