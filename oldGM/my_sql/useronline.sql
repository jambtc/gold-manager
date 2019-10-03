-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 03 nov, 2010 at 01:33 PM
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
-- Struttura della tabella `useronline`
--

CREATE TABLE IF NOT EXISTS `useronline` (
  `timestamp` int(15) NOT NULL DEFAULT '0',
  `user` varchar(20) NOT NULL,
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `useronline`
--

INSERT INTO `useronline` (`timestamp`, `user`) VALUES
(1288786729, 'sexjam'),
(1288786727, 'sexjam'),
(1288786722, 'sexjam'),
(1288786725, 'sexjam');
