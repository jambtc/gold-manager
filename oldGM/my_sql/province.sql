-- phpMyAdmin SQL Dump
-- version 3.2.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generato il: 24 gen, 2011 at 12:51 PM
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
-- Struttura della tabella `province`
--

CREATE TABLE IF NOT EXISTS `province` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `descrizione` (`descrizione`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=107 ;

--
-- Dump dei dati per la tabella `province`
--

INSERT INTO `province` (`id`, `descrizione`) VALUES
(1, 'Agrigento '),
(2, 'Alessandria '),
(3, 'Ancona '),
(4, 'Aosta '),
(5, 'Aquila '),
(6, 'Arezzo '),
(7, 'Ascoli-Piceno '),
(8, 'Asti '),
(9, 'Avellino '),
(10, 'Bari '),
(11, 'Barletta-Andria-Trani '),
(12, 'Belluno '),
(13, 'Benevento '),
(14, 'Bergamo '),
(15, 'Biella '),
(16, 'Bologna '),
(17, 'Bolzano '),
(18, 'Brescia '),
(19, 'Brindisi '),
(20, 'Cagliari '),
(21, 'Caltanissetta '),
(22, 'Campobasso '),
(23, 'Caserta '),
(24, 'Catania '),
(25, 'Catanzaro '),
(26, 'Chieti '),
(27, 'Como '),
(28, 'Cosenza '),
(29, 'Cremona '),
(30, 'Crotone '),
(31, 'Cuneo '),
(32, 'Enna '),
(33, 'Fermo '),
(34, 'Ferrara '),
(35, 'Firenze '),
(36, 'Foggia '),
(37, 'Forli-Cesena '),
(38, 'Frosinone '),
(39, 'Genova '),
(40, 'Gorizia '),
(41, 'Grosseto '),
(42, 'Imperia '),
(43, 'Isernia '),
(44, 'La-Spezia '),
(45, 'Latina '),
(46, 'Lecce '),
(47, 'Lecco '),
(48, 'Livorno '),
(49, 'Lodi '),
(50, 'Lucca '),
(51, 'Macerata '),
(52, 'Mantova '),
(53, 'Massa-Carrara '),
(54, 'Matera '),
(55, 'Messina '),
(56, 'Milano '),
(57, 'Modena '),
(58, 'Monza-Brianza '),
(59, 'Napoli '),
(60, 'Novara '),
(61, 'Nuoro '),
(62, 'Oristano '),
(63, 'Padova '),
(64, 'Palermo '),
(65, 'Parma '),
(66, 'Pavia '),
(67, 'Perugia '),
(68, 'Pesaro-Urbino '),
(69, 'Pescara '),
(70, 'Piacenza '),
(71, 'Pisa '),
(72, 'Pistoia '),
(73, 'Pordenone '),
(74, 'Potenza '),
(75, 'Prato '),
(76, 'Ragusa '),
(77, 'Ravenna '),
(78, 'Reggio-Calabria '),
(79, 'Reggio-Emilia '),
(80, 'Rieti '),
(81, 'Rimini '),
(82, 'Roma '),
(83, 'Rovigo '),
(84, 'Salerno '),
(85, 'Sassari '),
(86, 'Savona '),
(87, 'Siena '),
(88, 'Siracusa '),
(89, 'Sondrio '),
(90, 'Taranto '),
(91, 'Teramo '),
(92, 'Terni '),
(93, 'Torino '),
(94, 'Trapani '),
(95, 'Trento '),
(96, 'Treviso '),
(97, 'Trieste '),
(98, 'Udine '),
(99, 'Varese '),
(100, 'Venezia '),
(101, 'Verbania '),
(102, 'Vercelli '),
(103, 'Verona '),
(104, 'Vibo-Valentia '),
(105, 'Vicenza '),
(106, 'Viterbo ');
