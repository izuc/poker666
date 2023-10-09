-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 09, 2023 at 12:50 PM
-- Server version: 8.0.34-0ubuntu0.20.04.1
-- PHP Version: 8.1.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `poker666`
--

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `card_id` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `card_number` int NOT NULL,
  `card_suit` char(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`card_id`, `card_number`, `card_suit`) VALUES
('2c', 2, 'c'),
('2d', 2, 'd'),
('2h', 2, 'h'),
('2s', 2, 's'),
('3c', 3, 'c'),
('3d', 3, 'd'),
('3h', 3, 'h'),
('3s', 3, 's'),
('4c', 4, 'c'),
('4d', 4, 'd'),
('4h', 4, 'h'),
('4s', 4, 's'),
('5c', 5, 'c'),
('5d', 5, 'd'),
('5h', 5, 'h'),
('5s', 5, 's'),
('6c', 6, 'c'),
('6d', 6, 'd'),
('6h', 6, 'h'),
('6s', 6, 's'),
('7c', 7, 'c'),
('7d', 7, 'd'),
('7h', 7, 'h'),
('7s', 7, 's'),
('8c', 8, 'c'),
('8d', 8, 'd'),
('8h', 8, 'h'),
('8s', 8, 's'),
('9c', 9, 'c'),
('9d', 9, 'd'),
('9h', 9, 'h'),
('9s', 9, 's'),
('Tc', 10, 'c'),
('Td', 10, 'd'),
('Th', 10, 'h'),
('Ts', 10, 's'),
('Jc', 11, 'c'),
('Jd', 11, 'd'),
('Jh', 11, 'h'),
('Js', 11, 's'),
('Qc', 12, 'c'),
('Qd', 12, 'd'),
('Qh', 12, 'h'),
('Qs', 12, 's'),
('Kc', 13, 'c'),
('Kd', 13, 'd'),
('Kh', 13, 'h'),
('Ks', 13, 's'),
('Ac', 14, 'c'),
('Ad', 14, 'd'),
('Ah', 14, 'h'),
('As', 14, 's');

-- --------------------------------------------------------

--
-- Table structure for table `deck`
--

CREATE TABLE `deck` (
  `game_id` int NOT NULL,
  `card_id` char(2) NOT NULL,
  `card_order` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int NOT NULL,
  `table_id` int NOT NULL,
  `move` int NOT NULL DEFAULT '0',
  `last_move` int NOT NULL DEFAULT '0',
  `turn_count` int NOT NULL DEFAULT '0',
  `current_stage` int NOT NULL DEFAULT '0',
  `dealer` int NOT NULL DEFAULT '0',
  `last_dealer` int NOT NULL,
  `small_blind` int DEFAULT NULL,
  `big_blind` int DEFAULT NULL,
  `pot` int NOT NULL DEFAULT '0',
  `card1` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `card2` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `card3` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `card4` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `card5` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `result` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `msg` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_running` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `hands`
--

CREATE TABLE `hands` (
  `player_id` int NOT NULL,
  `game_id` int NOT NULL,
  `card1` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `card2` char(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `pot` int NOT NULL DEFAULT '0',
  `bet` int NOT NULL DEFAULT '0',
  `result` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `ranking` int DEFAULT NULL,
  `fold` int NOT NULL DEFAULT '0',
  `all_in` int NOT NULL DEFAULT '0',
  `position` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int NOT NULL,
  `player_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `password` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `balance` int NOT NULL DEFAULT '0',
  `winpot` int NOT NULL,
  `gamesplayed` int NOT NULL,
  `tournamentsplayed` int NOT NULL,
  `tournamentswon` int NOT NULL,
  `handsplayed` int NOT NULL,
  `handswon` int NOT NULL,
  `bet` int NOT NULL,
  `checked` int NOT NULL,
  `called` int NOT NULL,
  `allin` int NOT NULL,
  `fold_pf` int NOT NULL,
  `fold_f` int NOT NULL,
  `fold_t` int NOT NULL,
  `fold_r` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`player_id`, `player_name`, `password`, `balance`, `winpot`, `gamesplayed`, `tournamentsplayed`, `tournamentswon`, `handsplayed`, `handswon`, `bet`, `checked`, `called`, `allin`, `fold_pf`, `fold_f`, `fold_t`, `fold_r`) VALUES
(1, 'player1', '5f4dcc3b5aa765d61d8327deb882cf99', 14575, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 'player2', '5f4dcc3b5aa765d61d8327deb882cf99', 29529, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3, 'player3', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(4, 'player4', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(5, 'player5', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(6, 'player6', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(7, 'player7', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(8, 'player8', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(9, 'player9', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(10, 'player10', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `player_actions`
--

CREATE TABLE `player_actions` (
  `id` int NOT NULL,
  `game_id` int DEFAULT NULL,
  `stage` enum('Pre-flop','Post-flop','Post-turn','Post-river','Showdown') DEFAULT NULL,
  `player_id` int DEFAULT NULL,
  `action` enum('small_blind','big_blind','bet','call','raise','check','fold') DEFAULT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

CREATE TABLE `seats` (
  `table_id` int NOT NULL,
  `player_id` int NOT NULL,
  `pot` int NOT NULL DEFAULT '0',
  `position` int NOT NULL,
  `active` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `table_id` int NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `table_seats` int NOT NULL,
  `table_limit` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`table_id`, `table_name`, `table_seats`, `table_limit`) VALUES
(1, 'testing', 10, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `card_number` (`card_number`,`card_suit`);

--
-- Indexes for table `deck`
--
ALTER TABLE `deck`
  ADD PRIMARY KEY (`game_id`,`card_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `hands`
--
ALTER TABLE `hands`
  ADD PRIMARY KEY (`player_id`,`game_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`);

--
-- Indexes for table `player_actions`
--
ALTER TABLE `player_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`table_id`,`player_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `player_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `player_actions`
--
ALTER TABLE `player_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
