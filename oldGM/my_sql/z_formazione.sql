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
-- Struttura della tabella `z_formazione`
--

CREATE TABLE IF NOT EXISTS `z_formazione` (
  `id_partita` int(11) NOT NULL,
  `f_id_team` varchar(20) NOT NULL DEFAULT '',
  `f_1` int(8) NOT NULL DEFAULT '0',
  `f_2` int(8) NOT NULL DEFAULT '0',
  `f_3` int(8) NOT NULL DEFAULT '0',
  `f_4` int(8) NOT NULL DEFAULT '0',
  `f_5` int(8) NOT NULL DEFAULT '0',
  `f_6` int(8) NOT NULL DEFAULT '0',
  `f_7` int(8) NOT NULL DEFAULT '0',
  `f_8` int(8) NOT NULL DEFAULT '0',
  `f_9` int(8) NOT NULL DEFAULT '0',
  `f_10` int(8) NOT NULL DEFAULT '0',
  `f_11` int(8) NOT NULL DEFAULT '0',
  `f_12` int(8) NOT NULL DEFAULT '0',
  `f_13` int(8) NOT NULL DEFAULT '0',
  `f_14` int(8) NOT NULL DEFAULT '0',
  `f_15` int(8) NOT NULL DEFAULT '0',
  `f_16` int(8) NOT NULL DEFAULT '0',
  `f_17` int(8) NOT NULL DEFAULT '0',
  `f_18` int(8) NOT NULL DEFAULT '0',
  `f_19` int(8) NOT NULL DEFAULT '0',
  `f_20` int(8) NOT NULL DEFAULT '0',
  `f_21` int(8) NOT NULL DEFAULT '0',
  `f_22` int(8) NOT NULL DEFAULT '0',
  `f_23` int(8) NOT NULL DEFAULT '0',
  `f_24` int(8) NOT NULL DEFAULT '0',
  `f_25` int(8) NOT NULL DEFAULT '0',
  `f_26` int(8) NOT NULL DEFAULT '0',
  `f_27` int(8) NOT NULL DEFAULT '0',
  `f_28` int(8) NOT NULL DEFAULT '0',
  `f_29` int(8) NOT NULL DEFAULT '0',
  `f_30` int(8) NOT NULL DEFAULT '0',
  `f_31` int(8) NOT NULL DEFAULT '0',
  `f_32` int(8) NOT NULL DEFAULT '0',
  `f_33` int(8) NOT NULL DEFAULT '0',
  `f_34` int(8) NOT NULL DEFAULT '0',
  `f_35` int(8) NOT NULL DEFAULT '0',
  `f_36` int(8) NOT NULL DEFAULT '0',
  `f_37` int(8) NOT NULL DEFAULT '0',
  `f_38` int(8) NOT NULL DEFAULT '0',
  `f_39` int(8) NOT NULL DEFAULT '0',
  `f_40` int(8) NOT NULL DEFAULT '0',
  `f_41` int(8) NOT NULL DEFAULT '0',
  `f_42` int(8) NOT NULL DEFAULT '0',
  `f_43` int(8) NOT NULL DEFAULT '0',
  `f_44` int(8) NOT NULL DEFAULT '0',
  `f_45` int(8) NOT NULL DEFAULT '0',
  `f_46` int(8) NOT NULL DEFAULT '0',
  `f_47` int(8) NOT NULL DEFAULT '0',
  `f_48` int(8) NOT NULL DEFAULT '0',
  `f_49` int(8) NOT NULL DEFAULT '0',
  `f_50` int(8) NOT NULL DEFAULT '0',
  `f_51` int(8) NOT NULL DEFAULT '0',
  `f_52` int(8) NOT NULL DEFAULT '0',
  `f_53` int(8) NOT NULL DEFAULT '0',
  `f_54` int(8) NOT NULL DEFAULT '0',
  `f_55` int(8) NOT NULL DEFAULT '0',
  `f_56` int(8) NOT NULL DEFAULT '0',
  `f_57` int(8) NOT NULL DEFAULT '0',
  `f_58` int(8) NOT NULL DEFAULT '0',
  `f_59` int(8) NOT NULL DEFAULT '0',
  `f_60` int(8) NOT NULL DEFAULT '0',
  `f_61` int(8) NOT NULL DEFAULT '0',
  `f_62` int(8) NOT NULL DEFAULT '0',
  `f_63` int(8) NOT NULL DEFAULT '0',
  `f_64` int(8) NOT NULL DEFAULT '0',
  `f_65` int(8) NOT NULL DEFAULT '0',
  `f_66` int(8) NOT NULL DEFAULT '0',
  `f_67` int(8) NOT NULL DEFAULT '0',
  `f_68` int(8) NOT NULL DEFAULT '0',
  `f_69` int(8) NOT NULL DEFAULT '0',
  `f_70` int(8) NOT NULL DEFAULT '0',
  KEY `f_id_team` (`f_id_team`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dump dei dati per la tabella `z_formazione`
--

INSERT INTO `z_formazione` (`id_partita`, `f_id_team`, `f_1`, `f_2`, `f_3`, `f_4`, `f_5`, `f_6`, `f_7`, `f_8`, `f_9`, `f_10`, `f_11`, `f_12`, `f_13`, `f_14`, `f_15`, `f_16`, `f_17`, `f_18`, `f_19`, `f_20`, `f_21`, `f_22`, `f_23`, `f_24`, `f_25`, `f_26`, `f_27`, `f_28`, `f_29`, `f_30`, `f_31`, `f_32`, `f_33`, `f_34`, `f_35`, `f_36`, `f_37`, `f_38`, `f_39`, `f_40`, `f_41`, `f_42`, `f_43`, `f_44`, `f_45`, `f_46`, `f_47`, `f_48`, `f_49`, `f_50`, `f_51`, `f_52`, `f_53`, `f_54`, `f_55`, `f_56`, `f_57`, `f_58`, `f_59`, `f_60`, `f_61`, `f_62`, `f_63`, `f_64`, `f_65`, `f_66`, `f_67`, `f_68`, `f_69`, `f_70`) VALUES
(1, 'jammins', 0, 0, 0, 22, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15, 0, 23, 0, 0, 7, 0, 0, 10, 0, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17, 0, 77, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 9, 35, 33, 1, 20, 12, 0),
(0, 'jammins', 0, 0, 0, 0, 0, 0, 0, 0, 24, 0, 11, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 15, 0, 0, 0, 7, 0, 0, 10, 0, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17, 0, 77, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'jammins', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'jammins', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 77, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 12, 0),
(0, 'jammins', 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 11, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 7, 0, 0, 25, 0, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17, 0, 77, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'Real Libbiese', 0, 0, 0, 10, 0, 0, 0, 9, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 8, 0, 0, 7, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 5, 0, 0, 3, 16, 0, 12, 1, 0, 22, 0),
(0, 'Discepoli di Gabriel', 0, 0, 90, 9, 86, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 0, 0, 0, 11, 0, 0, 4, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 6, 0, 0, 0, 0, 0, 0, 95, 0, 0, 0, 18, 3, 97, 99, 2, 91, 0),
(0, 'LittleViolet', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'marlboro team', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 0, 0, 7, 0, 0, 0, 0, 0, 14, 0, 0, 0, 10, 0, 0, 0, 0, 0, 98, 0, 2, 0, 0, 3, 0, 0, 0, 0, 0, 18, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'F.C SENZANOME', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'F.C. Naproca', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'mestre utd', 0, 0, 0, 9, 0, 0, 0, 23, 0, 0, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 7, 0, 0, 0, 0, 0, 11, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 6, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 20, 17, 21, 2, 16, 12, 0),
(1, '11 pagliacci', 0, 0, 0, 22, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 6, 0, 0, 0, 11, 0, 0, 8, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 4, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 24, 18, 13, 1, 14, 12, 0),
(0, 'montelepre', 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 22, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 4, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 42, 0, 0, 24, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 18, 0, 0, 1, 3, 12, 0),
(0, '11 pagliacci', 0, 0, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 11, 0, 0, 8, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 5, 0, 3, 0, 0, 0, 0, 4, 0, 0, 0, 9, 7, 10, 1, 14, 12, 0),
(0, '11 pagliacci', 0, 0, 0, 0, 0, 0, 0, 0, 24, 0, 9, 0, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 8, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 0, 5, 0, 0, 0, 0, 0, 0, 4, 0, 3, 0, 22, 7, 6, 1, 2, 12, 0),
(0, 'tramai f.c.', 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 94, 0, 89, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 87, 0, 93, 6, 90, 0, 92, 0, 0, 0, 0, 0, 0, 0, 87, 96, 91, 1, 83, 98, 0),
(0, 'alemacusVFC', 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 29, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 21, 0, 0, 27, 0, 0, 8, 0, 0, 0, 17, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 4, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'alemacusVFC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Pessegatto', 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 85, 0, 91, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 0, 81, 0, 0, 89, 0, 0, 0, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 97, 0, 83, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0),
(0, 'Serendipi', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'giumpaofc', 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 6, 0, 0, 0, 0, 0, 9, 0, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 3, 0, 4, 5, 0, 0, 0, 0, 0, 0, 0, 0, 94, 23, 26, 1, 96, 12, 0),
(0, 'Neviano A.C.', 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 11, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 0, 0, 0, 0, 10, 0, 0, 7, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 5, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 20, 33, 6, 30, 4, 1, 0),
(0, 'Moijto FC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Galatticos', 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 28, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 25, 0, 0, 0, 21, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 3, 8, 4, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'brèsa 1911', 0, 0, 0, 0, 0, 0, 0, 0, 95, 0, 10, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 93, 0, 94, 0, 7, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 96, 0, 3, 0, 91, 0, 0, 0, 0, 0, 0, 0, 0, 9, 16, 99, 1, 89, 12, 0),
(0, 'brèsa 1911', 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 90, 0, 88, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 16, 0, 89, 0, 94, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 3, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 87, 0, 0, 0),
(0, 'brèsa 1911', 0, 0, 0, 0, 0, 0, 0, 0, 85, 0, 84, 0, 86, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 16, 0, 89, 0, 94, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 91, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 0, 0, 0),
(0, 'Mc Piece', 0, 0, 0, 0, 0, 0, 0, 0, 0, 95, 14, 0, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 0, 6, 0, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 4, 0, 98, 0, 3, 0, 0, 0, 0, 0, 0, 0, 9, 11, 81, 1, 92, 91, 0),
(0, 'AC Medea', 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 8, 0, 0, 4, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 2, 0, 0, 5, 0, 6, 0, 0, 91, 87, 90, 1, 86, 81, 0),
(0, 'El Milo FC', 0, 0, 10, 0, 91, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 85, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 4, 0, 0, 95, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 96, 0, 0, 5, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'caballero team', 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 10, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 0, 0, 7, 0, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 5, 0, 0, 3, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 17, 18, 1, 21, 12, 0),
(0, 'realkallo', 0, 0, 0, 22, 0, 0, 0, 0, 18, 0, 0, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 13, 0, 14, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 6, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 19, 12, 11, 3, 4, 1, 0),
(0, 'Silica', 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 87, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 0, 10, 0, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 13, 0, 89, 0, 90, 0, 0, 0, 0, 0, 0, 0, 24, 91, 22, 12, 94, 96, 0),
(0, 'Silica', 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 0, 0, 15, 0, 0, 10, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 0, 0, 0, 0, 14, 0, 0, 13, 0, 94, 0, 0, 0, 0, 0, 4, 0, 0, 0, 11, 6, 22, 12, 16, 1, 0),
(0, 'Silica', 0, 0, 0, 98, 0, 0, 0, 0, 0, 0, 24, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0, 95, 0, 0, 22, 0, 0, 91, 0, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 16, 0, 0, 93, 0, 0, 94, 0, 0, 0, 0, 0, 0, 0, 11, 15, 8, 96, 13, 12, 0),
(0, 'A.C STABIA', 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 99, 107, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 110, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 98, 0, 0, 0, 106, 0, 0, 0, 0, 0, 94, 0, 0, 4, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 11, 2, 3, 0, 0),
(0, 'alemacusVFC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Silica', 0, 0, 0, 11, 0, 0, 0, 0, 0, 9, 0, 24, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 0, 10, 0, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 13, 0, 89, 0, 90, 0, 0, 0, 0, 0, 0, 0, 98, 91, 22, 12, 94, 96, 0),
(0, 'Manchester Sicily', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Allegrotti', 0, 0, 0, 10, 0, 0, 91, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 87, 0, 0, 96, 0, 0, 93, 0, 0, 0, 0, 0, 0, 0, 22, 0, 0, 0, 0, 0, 93, 0, 0, 94, 0, 98, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 95, 0, 0, 0),
(0, 'TroubleBubbleTeam', 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 9, 0, 0, 27, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 53, 0, 0, 10, 0, 0, 19, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 57, 56, 2, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'iDiavolidellaNotte', 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 7, 0, 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 19, 0, 0, 8, 0, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 3, 0, 13, 2, 0, 0, 0, 0, 0, 0, 0, 0, 9, 80, 27, 12, 4, 1, 0),
(0, 'F.C. Ben Fica', 0, 0, 0, 10, 0, 0, 0, 0, 0, 93, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 0, 86, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 0, 0, 6, 0, 0, 94, 0, 0, 0, 5, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'TroubleBubbleTeam', 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 9, 0, 0, 27, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 55, 0, 0, 0, 53, 0, 0, 10, 0, 0, 19, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 57, 0, 2, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'TroubleBubbleTeam', 0, 0, 0, 0, 0, 0, 0, 27, 0, 0, 9, 0, 0, 19, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 53, 0, 0, 10, 0, 0, 58, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 57, 56, 2, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'TroubleBubbleTeam', 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 89, 0, 0, 50, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 54, 0, 0, 0, 87, 0, 0, 55, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 57, 0, 24, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'porchetts', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 0, 0, 0, 0, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 18, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Sparta Milano', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'the music', 0, 0, 0, 24, 0, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 13, 0, 0, 10, 0, 0, 0, 0, 0, 71, 0, 0, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0, 0, 5, 0, 0, 0, 0, 0, 0, 21, 0, 1, 0),
(0, 'alemacusVFC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'athorland', 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 17, 0, 0, 28, 0, 0, 8, 0, 0, 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 14, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'alemacusVFC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'TroubleBubbleTeam', 0, 0, 0, 0, 0, 0, 0, 27, 0, 0, 9, 0, 0, 19, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 53, 0, 0, 55, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 57, 56, 2, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'pertugini', 0, 0, 0, 25, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 0, 0, 0, 0, 0, 15, 0, 0, 0, 17, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 96, 0, 2, 97, 27, 0, 94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Silica', 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 87, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 0, 0, 0, 7, 0, 0, 10, 0, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 90, 0, 89, 0, 0, 0, 0, 0, 0, 0, 0, 24, 91, 22, 12, 93, 96, 0),
(0, 'FC Sixers', 0, 0, 0, 0, 9, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0, 96, 0, 0, 0, 0, 0, 95, 0, 0, 98, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 91, 0, 4, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'iDiavolidellaNotte', 0, 0, 0, 0, 0, 0, 0, 18, 0, 0, 7, 0, 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 51, 0, 0, 0, 19, 0, 0, 0, 0, 0, 22, 0, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 33, 5, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'porchetts', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 0, 10, 19, 13, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 18, 21, 0, 5, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0),
(0, 'BARI', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Sturm Truppen', 0, 0, 0, 90, 22, 0, 0, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 97, 0, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 91, 0, 89, 0, 98, 0, 0, 0, 0, 95, 0, 0, 0, 25, 87, 86, 92, 94, 84, 0),
(0, 'Hacuna Matata', 0, 0, 0, 9, 0, 0, 0, 13, 0, 0, 0, 0, 16, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 0, 0, 0, 17, 0, 0, 0, 0, 0, 18, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 3, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 24, 7, 5, 1, 6, 23, 0),
(0, 'Real CAMERI', 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 9, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 0, 0, 6, 0, 0, 5, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 4, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, '11 pagliacci', 0, 0, 0, 0, 0, 0, 0, 0, 24, 0, 22, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0, 11, 0, 0, 8, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 3, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 9, 7, 10, 1, 14, 12, 0),
(0, 'Gli Illuminati', 0, 0, 0, 7, 0, 0, 0, 16, 0, 0, 0, 0, 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 24, 0, 0, 10, 0, 0, 21, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 22, 0, 4, 0, 15, 0, 0, 0, 0, 0, 0, 0, 9, 8, 13, 1, 2, 12, 0),
(0, 'Gli Illuminati', 0, 0, 0, 25, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 16, 0, 0, 0, 0, 0, 21, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 10, 22, 4, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, '11 pagliacci', 0, 0, 0, 10, 0, 0, 0, 0, 9, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 0, 0, 0, 0, 0, 0, 0, 24, 0, 0, 6, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 0, 0, 0, 3, 0, 0, 0, 0, 4, 0, 0, 0, 8, 7, 16, 1, 5, 12, 0),
(0, 'Gigghis FC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'No Hay Tango', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'No Hay Tango', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Atletico Monumental', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'athorland', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'StradaroliVFC', 0, 0, 0, 95, 0, 0, 0, 0, 88, 0, 0, 0, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 90, 0, 0, 0, 0, 61, 0, 0, 65, 0, 0, 89, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 85, 0, 82, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 0),
(0, 'salvese', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Pessegatto', 0, 0, 0, 23, 0, 0, 0, 0, 0, 0, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 98, 0, 79, 0, 91, 0, 0, 0, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 93, 0, 0, 95, 0, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0),
(0, 'FC Real Cardi', 9, 0, 0, 10, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 14, 0, 0, 0, 7, 0, 0, 0, 0, 0, 5, 0, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 6, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 22, 32, 18, 12, 13, 1, 0),
(0, 'pelotas', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 0, 0, 0),
(0, 'Polisportiva Malafed', 0, 0, 0, 82, 0, 0, 0, 0, 0, 84, 0, 81, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 61, 0, 0, 59, 0, 0, 62, 0, 0, 0, 54, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 23, 26, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Mc Piece', 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 95, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 81, 0, 6, 0, 76, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 92, 0, 4, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 91, 0, 0, 0),
(0, 'sporting viola', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 83, 0, 86, 0),
(0, 'Gli Illuminati', 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 25, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 23, 0, 0, 24, 0, 0, 0, 0, 0, 8, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 22, 0, 0, 15, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'z''Makana', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 40, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 20, 0, 98, 0, 22, 0, 95, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 7, 0, 6, 4, 0, 0, 0, 0, 0, 0, 0, 0, 43, 30, 21, 2, 11, 1, 0),
(0, 'Pessegatto', 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 85, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 81, 0, 0, 0, 94, 0, 0, 86, 0, 0, 89, 0, 0, 0, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 93, 0, 83, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0),
(0, 'Pegaso FC', 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 97, 0, 0, 0, 0, 0, 8, 0, 0, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 4, 0, 2, 0, 3, 0, 0, 0, 0, 0, 0, 0, 94, 97, 0, 1, 12, 0, 0),
(0, 'Pegaso FC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 11, 0, 5, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 4, 0, 3, 0, 12, 0, 0, 0, 2, 0, 0, 0, 94, 7, 14, 1, 0, 0, 0),
(0, 'Dinamo Freisa', 0, 0, 0, 9, 0, 0, 0, 0, 0, 10, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 6, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 3, 0, 2, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Provesano United', 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 72, 0, 0, 32, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 22, 0, 0, 14, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 2, 0, 3, 0, 0, 0, 0, 4, 0, 0, 0, 17, 64, 8, 21, 2, 24, 0),
(0, 'mont real', 0, 0, 0, 49, 0, 0, 0, 0, 0, 9, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 0, 8, 0, 0, 17, 0, 0, 0, 16, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 3, 0, 4, 0, 0, 50, 7, 10, 1, 5, 22, 0),
(0, 'meoz united', 0, 0, 0, 14, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 0, 4, 20, 0, 7, 0, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 13, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 18, 19, 8, 16, 13, 1, 0),
(0, 'lando united', 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 89, 0, 0, 87, 0, 0, 0, 96, 0, 0, 0, 79, 0, 0, 0, 0, 0, 90, 0, 0, 81, 0, 80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0),
(0, 'konanmet', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'OlimpicMaz', 0, 0, 0, 10, 0, 0, 0, 0, 90, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 0, 0, 0, 6, 0, 0, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 3, 0, 4, 0, 5, 0, 0, 0, 1, 0, 0, 0),
(0, 'FC Juventus 1897*', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 20, 0, 0, 0, 7, 0, 0, 97, 0, 0, 0, 99, 0, 0, 0, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 29, 0, 0, 0, 0, 0, 6, 0, 0, 98, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 95, 8, 10, 94, 3, 0, 0),
(0, 'Pessegatto', 0, 0, 0, 0, 0, 0, 0, 0, 0, 85, 92, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 0, 0, 0, 0, 84, 0, 0, 0, 0, 0, 0, 90, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 80, 0, 96, 0, 95, 0, 88, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0),
(0, 'Outlandos Club', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Ponnusella', 0, 0, 0, 23, 0, 0, 0, 0, 7, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 16, 0, 0, 10, 0, 0, 15, 0, 0, 0, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 5, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Nomentum', 9, 0, 0, 10, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 7, 0, 0, 8, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 3, 0, 4, 0, 5, 0, 0, 0, 0, 0, 0, 0, 21, 20, 17, 1, 14, 12, 0),
(0, 's.s.turn&go', 0, 0, 0, 0, 0, 0, 0, 0, 81, 0, 98, 0, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 93, 0, 90, 0, 95, 0, 88, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 94, 0, 89, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0),
(0, 'Mado Utd', 0, 0, 0, 99, 0, 0, 88, 0, 0, 0, 78, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 84, 0, 0, 94, 0, 0, 86, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 0, 81, 0, 92, 0, 82, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 0, 0),
(0, 'prezzatese', 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 88, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 96, 0, 0, 83, 0, 0, 97, 0, 0, 0, 94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 95, 0, 90, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 82, 86, 12, 91, 4, 81, 0),
(0, 'F.C.DarkNight', 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 92, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 84, 0, 0, 0, 91, 0, 0, 0, 0, 0, 87, 0, 0, 85, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 95, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'MetalFC', 0, 0, 0, 0, 23, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 0, 0, 10, 0, 0, 11, 0, 26, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 4, 0, 5, 0, 22, 0, 0, 0, 0, 0, 0, 0, 24, 17, 27, 1, 6, 12, 0),
(0, 'tt', 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 0, 7, 0, 8, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 4, 0, 3, 0, 5, 0, 0, 0, 0, 0, 0, 0, 84, 96, 85, 1, 88, 83, 0),
(0, 'ucas', 0, 0, 0, 94, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 81, 0, 0, 0, 87, 0, 0, 10, 0, 0, 85, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 91, 0, 92, 0, 8, 0, 90, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 0, 0),
(0, 'tt', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 6, 0, 0, 8, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 3, 88, 4, 5, 0, 0, 0, 0, 0, 0, 0, 0, 10, 86, 85, 1, 97, 83, 0),
(0, 'Contedm', 0, 0, 0, 83, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 82, 0, 0, 99, 0, 0, 0, 84, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 4, 0, 5, 0, 87, 0, 0, 0, 0, 0, 0, 0, 93, 89, 10, 21, 88, 95, 0),
(0, 'Contedm', 0, 0, 0, 83, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 0, 0, 0, 99, 0, 0, 82, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 87, 0, 5, 0, 2, 0, 0, 0, 0, 0, 0, 0, 93, 89, 84, 21, 88, 95, 0),
(0, 'Contedm', 0, 0, 0, 0, 0, 0, 0, 0, 0, 83, 11, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 82, 0, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 0, 87, 0, 5, 0, 2, 0, 0, 0, 0, 0, 0, 0, 91, 89, 10, 21, 97, 95, 0),
(0, 'Contedm', 0, 0, 0, 91, 0, 0, 0, 0, 0, 0, 93, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0, 89, 0, 0, 84, 0, 0, 98, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 88, 0, 90, 0, 94, 0, 97, 0, 0, 0, 0, 0, 0, 0, 83, 96, 82, 95, 4, 21, 0),
(0, 'iDiavolidellaNotte', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Contedm', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 10, 82, 84, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 87, 0, 4, 0, 5, 0, 0, 0, 0, 0, 0, 0, 83, 89, 92, 21, 88, 95, 0),
(0, 'F.c. Toho', 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 0, 0, 0, 7, 0, 0, 0, 0, 0, 11, 0, 0, 8, 0, 89, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 4, 0, 14, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'thrasher', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 11, 0, 7, 0, 0, 8, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 3, 0, 6, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0),
(0, 'real cellina', 0, 0, 0, 97, 0, 0, 0, 0, 0, 9, 0, 10, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 17, 0, 8, 0, 20, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0, 0, 0, 5, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 18, 16, 6, 1, 98, 12, 0),
(0, 'lando united', 0, 0, 0, 95, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 96, 86, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 81, 80, 90, 0, 94, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0),
(0, 'lando united', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 0, 86, 0, 0, 82, 0, 0, 96, 0, 0, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 79, 0, 81, 0, 90, 0, 0, 0, 0, 80, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0),
(0, 'lando united', 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 95, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 86, 0, 0, 0, 82, 0, 0, 89, 0, 0, 87, 0, 0, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 80, 0, 81, 0, 90, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0),
(0, 'Fc Timoria Sport Clu', 0, 0, 0, 88, 0, 0, 0, 0, 0, 0, 95, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 81, 0, 0, 0, 0, 0, 90, 0, 0, 0, 75, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 84, 0, 78, 97, 96, 0, 82, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 94, 0, 0, 0),
(0, 'Inter Barzorf', 0, 0, 0, 0, 0, 0, 0, 0, 11, 0, 9, 0, 92, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 90, 0, 0, 10, 0, 0, 8, 0, 0, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 3, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 93, 92, 94, 1, 95, 97, 0),
(0, 'Atletico Landobuzza', 0, 0, 0, 0, 0, 0, 0, 0, 0, 25, 20, 26, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 22, 0, 0, 0, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0, 0, 0, 3, 0, 0, 2, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Atletico Landobuzza', 0, 0, 0, 0, 0, 0, 0, 0, 0, 25, 20, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0, 0, 0, 13, 0, 11, 0, 28, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 0, 2, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'ingrifati', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(0, 'Al.Sab. Calcio', 0, 0, 0, 78, 0, 0, 0, 0, 0, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0, 98, 0, 0, 0, 0, 0, 79, 0, 0, 0, 85, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 0, 95, 0, 88, 0, 96, 0, 0, 0, 0, 0, 0, 0, 83, 81, 105, 77, 95, 86, 0),
(0, 'Al.Sab. Calcio', 0, 0, 0, 0, 0, 0, 0, 0, 0, 78, 0, 99, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 82, 0, 0, 0, 98, 0, 0, 0, 0, 0, 79, 0, 0, 0, 85, 0, 0, 0, 89, 0, 0, 0, 0, 0, 91, 0, 0, 88, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 83, 81, 80, 86, 91, 1, 0),
(0, 'Pessegatto 2011', 0, 0, 0, 26, 0, 0, 0, 0, 0, 0, 25, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 96, 0, 11, 0, 99, 0, 0, 0, 13, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 2, 0, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 21, 0, 0, 0),
(0, 'lando united', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 97, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 96, 89, 86, 0, 0, 82, 0, 0, 0, 0, 0, 87, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 80, 0, 0, 0, 79, 0, 0, 81, 0, 0, 90, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 92, 0, 0, 0),
(0, 'clorpromazina', 0, 0, 0, 0, 0, 0, 0, 0, 0, 26, 20, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 0, 0, 27, 0, 0, 10, 0, 0, 28, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 2, 0, 3, 0, 0, 0, 0, 0, 0, 0, 0, 26, 25, 11, 1, 4, 21, 0),
(0, 'clorpromazina', 0, 0, 0, 0, 0, 0, 0, 0, 0, 27, 20, 26, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 13, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 0, 0, 0, 12, 0, 0, 0, 4, 0, 0, 0, 0, 0, 3, 0, 0, 5, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Al.Sab. Calcio', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 99, 0, 0, 81, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 81, 0, 0, 85, 0, 0, 79, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 95, 0, 0, 0, 0, 89, 0, 88, 0, 96, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 77, 0, 0, 0),
(0, 'Al.Sab. Calcio', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 83, 0, 0, 81, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 105, 0, 0, 0, 93, 0, 0, 0, 0, 0, 79, 0, 0, 0, 85, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 89, 0, 101, 0, 88, 0, 91, 0, 0, 0, 0, 0, 0, 0, 99, 98, 82, 86, 95, 77, 0),
(0, 'MetalFC', 0, 0, 17, 0, 23, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 0, 0, 0, 0, 0, 28, 0, 0, 28, 0, 26, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 33, 0, 5, 0, 22, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(0, 'Pessegatto 2011', 0, 0, 0, 0, 0, 0, 0, 0, 0, 98, 22, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 10, 0, 0, 0, 23, 0, 0, 13, 0, 0, 27, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 0, 2, 0, 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0);
