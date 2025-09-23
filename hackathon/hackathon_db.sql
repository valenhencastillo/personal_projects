-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-09-2025 a las 23:10:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `hackathon_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `document_type` enum('cedula','pasaporte','cedula_escolar') NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `nationality` varchar(1) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('masculino','femenino') NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `state` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `education_level` enum('primaria','bachillerato','universidad') NOT NULL,
  `grade` varchar(100) DEFAULT NULL,
  `category` enum('Junior','Senior') NOT NULL,
  `microbit_experience` enum('ninguna','basica','intermedia','avanzada') NOT NULL,
  `expectations` text DEFAULT NULL,
  `shirt_size` enum('S','M','L','XL') NOT NULL,
  `document_photo_path` varchar(500) DEFAULT NULL,
  `is_minor` tinyint(1) DEFAULT 0,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_doc_type` varchar(50) DEFAULT NULL,
  `guardian_document` varchar(50) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `authorization_doc_path` varchar(500) DEFAULT NULL,
  `image_rights_accepted` tinyint(1) DEFAULT 1,
  `data_verified` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD KEY `idx_document_number` (`document_number`),
  ADD KEY `idx_registration_number` (`registration_number`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
