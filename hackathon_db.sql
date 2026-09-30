-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-09-2026 a las 23:11:42
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
-- Estructura de tabla para la tabla `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `challenges`
--

INSERT INTO `challenges` (`id`, `category_id`, `event_id`, `title`, `description`, `is_active`) VALUES
(23, 15, 1, 'Contador con Microbit', 'Contador con Microbit', 0),
(24, 15, 1, 'Medidor de luz', 'Medidor de luz', 0),
(25, 16, 1, 'Contador con Microbit', 'Contador con Microbit', 0),
(26, 16, 1, 'Medidor de luz', 'Medidor de luz', 0),
(27, 17, 2, 'Reto1', 'Enciende los led de la micro:bit de manera secuencial', 0),
(28, 18, 2, 'Reto1', 'Enciende los led de la micro:bit de manera secuencial', 0),
(39, 28, 3, 'Contador', 'Contador', 0),
(40, 28, 3, 'Sensor de luz', 'Sensor de luz', 0),
(41, 30, 4, 'Contador', 'Contador', 0),
(42, 30, 4, 'Sensor de luz', 'Sensor de luz', 0),
(43, 31, 5, 'Nivel de luz', 'Nivel de luz', 0),
(44, 31, 5, 'Segundo reto', 'Segundo reto', 0),
(51, 37, 6, 'Reto 1', 'Reto 1', 0),
(52, 37, 6, 'Reto 2', 'Reto 2', 0),
(53, 39, 7, 'reto 1', 'reto 1', 0),
(54, 39, 7, 'reto 2', 'reto 2', 0),
(55, 41, 8, 'Reto 1', 'Reto 1', 0),
(56, 41, 8, 'Reto 2', 'Reto 3', 1),
(57, 42, 8, 'Reto 1', 'Reto 1', 0),
(61, 48, 13, 'Reto 1', 'Reto 1', 1),
(62, 49, 13, 'Reto 2', 'Reto 2', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `challenge_items`
--

CREATE TABLE `challenge_items` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `challenge_items`
--

INSERT INTO `challenge_items` (`id`, `challenge_id`, `description`) VALUES
(45, 23, 'Al presionar el boton A incrementa el contador'),
(46, 23, 'Al presionar el boton B decrementa el contador'),
(47, 24, 'Cuando la luz es baja muestra icono de luna'),
(48, 24, 'Cuando la luz es alta muestra icono de sol'),
(49, 25, 'Al presionar el boton A incrementa el contador'),
(50, 25, 'Al presionar el boton A incrementa el contador'),
(51, 26, 'Cuando la luz es baja muestra icono de luna'),
(52, 26, 'Cuando la luz es alta muestra icono de sol'),
(53, 27, 'Enciende los LED?'),
(54, 27, 'Los LED encienden de manera secuencial?'),
(55, 28, 'Enciende los LED?'),
(56, 28, 'Los LED encienden de manera secuencial?'),
(77, 39, 'Al presionar el boton A sube el contador'),
(78, 39, 'Al presionar el boton B baja el contador'),
(79, 40, 'En la oscuridad muestra icono de luna'),
(80, 40, 'Con luz muestra icono de sol'),
(81, 41, 'Al presionar el boton A aumenta el contador'),
(82, 41, 'Al presionar el boton B disminuye el contador'),
(83, 42, 'Muestra icono de luna en luz ambiente'),
(84, 42, 'Muestra icono de sol al acercar una linterna'),
(85, 43, 'Item 1'),
(86, 43, 'Item 2'),
(87, 44, 'Item 1'),
(88, 44, 'Item 2'),
(101, 51, 'Item 1'),
(102, 51, 'Item 2'),
(103, 52, 'Item 1'),
(104, 52, 'Item 2'),
(105, 53, 'item 1'),
(106, 53, 'item 2'),
(107, 54, 'item 1'),
(108, 54, 'item 2'),
(109, 55, 'Item 1'),
(110, 55, 'Item 2'),
(111, 56, 'Item 1'),
(112, 57, 'Item 1'),
(113, 61, 'Item 1'),
(114, 61, 'Item 2'),
(115, 62, 'Item 1'),
(116, 62, 'Item 2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) DEFAULT 0,
  `event_type` enum('publico','colegio_interno') DEFAULT 'publico',
  `registration_start_date` datetime DEFAULT NULL,
  `registration_end_date` datetime DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `status`, `event_type`, `registration_start_date`, `registration_end_date`, `start_date`, `end_date`, `created_at`) VALUES
(1, 'Hackathon Microbit 2026', 'Hackathon Microbit 2026', 0, 'publico', '2026-08-25 10:00:00', '2026-08-31 10:00:00', '2026-08-25', '2026-08-25', '2026-08-25 14:06:05'),
(2, 'Hackathon 2026-1', 'Evento en el Buenaventura', 0, 'publico', '2026-09-01 00:01:00', '2026-11-07 00:00:00', '2026-11-07', '2026-10-03', '2026-08-26 12:54:59'),
(3, 'Hackathon 2 2026', 'Hackathon 2 2026', 0, 'publico', '2026-08-27 15:32:00', '2026-09-27 15:32:00', '2026-08-27', '2026-09-27', '2026-08-27 19:36:47'),
(4, 'Hackathon 4', 'Evento', 0, 'publico', '2026-08-27 17:43:00', '2026-09-03 17:43:00', '2026-08-27', '2026-09-27', '2026-08-27 21:51:30'),
(5, 'Hackathon 5', 'Evento 5', 0, 'publico', '2026-08-28 10:14:00', '2026-09-04 10:14:00', '2026-08-28', '2026-09-28', '2026-08-28 14:15:44'),
(6, 'Evento 6', 'Evento 5', 0, 'publico', '2026-08-28 10:18:00', '2026-09-04 10:18:00', '2026-08-28', '2026-09-28', '2026-08-28 14:19:45'),
(7, 'Evento 7', 'Evento 7', 0, 'publico', '2026-08-28 14:46:00', '2026-09-04 14:46:00', '2026-08-28', '2026-09-28', '2026-08-28 18:47:18'),
(8, 'Evento 8', 'Evento 8', 0, 'publico', '2026-08-28 15:09:00', '2026-09-04 15:09:00', '2026-08-28', '2026-09-28', '2026-08-28 19:10:46'),
(9, 'Test', '', 1, 'publico', '2026-08-28 17:48:00', '2026-08-31 17:48:00', '2026-10-01', '2026-09-02', '2026-08-28 21:49:07'),
(13, 'Hackathon Interno', 'Hackathon Interno', 1, 'colegio_interno', '2026-08-31 09:35:00', '2026-09-05 09:35:00', '2026-08-31', '2026-09-30', '2026-08-31 13:57:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `event_categories`
--

CREATE TABLE `event_categories` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL COMMENT 'NULL = cupos ilimitados',
  `registered_count` int(11) DEFAULT 0 COMMENT 'Contador de registrados'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `event_categories`
--

INSERT INTO `event_categories` (`id`, `event_id`, `name`, `description`, `capacity`, `registered_count`) VALUES
(15, 1, 'Junior', 'Junior', 1, 0),
(16, 1, 'Senior', 'Senior', 1, 0),
(17, 2, 'Junior', '8-12', 30, 0),
(18, 2, 'Senior', '13-16', 30, 1),
(28, 3, 'Junior', '', 5, 1),
(29, 3, 'Senior', 'No habilitada', 1, 0),
(30, 4, 'Junior', 'Categoria junior', 3, 0),
(31, 5, 'Junior', 'Categoria Junior', 3, 0),
(37, 6, 'Junior', '', 3, 1),
(38, 6, 'Senior', '', NULL, 0),
(39, 7, 'Junior', 'Junior', 3, 0),
(40, 7, 'Senior', '', NULL, 0),
(41, 8, 'Junior', '', 4, 3),
(42, 8, 'Senior', '', 2, 0),
(43, 9, 'Junior', '', NULL, 0),
(44, 9, 'Senior', '', NULL, 0),
(48, 13, 'Primer año', 'Pimer', NULL, 0),
(49, 13, 'Segundo año', 'Segundo', NULL, 0),
(50, 13, 'Tercer año', '', NULL, 0),
(51, 13, 'Cuarto año', '', NULL, 0),
(52, 13, 'Quinto año', '', NULL, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `judge_assignments`
--

CREATE TABLE `judge_assignments` (
  `id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `judge_evaluations`
--

CREATE TABLE `judge_evaluations` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `status` enum('superado','no_superado') NOT NULL,
  `total_time_seconds` int(11) NOT NULL,
  `judge_user_id` int(11) DEFAULT NULL,
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `judge_evaluations`
--

INSERT INTO `judge_evaluations` (`id`, `registration_id`, `challenge_id`, `status`, `total_time_seconds`, `judge_user_id`, `evaluated_at`) VALUES
(5, 4, 28, 'superado', 20, 2, '2026-08-27 15:53:57'),
(8, 12, 55, 'no_superado', 60, 2, '2026-08-28 21:37:50'),
(9, 13, 55, 'superado', 120, 2, '2026-08-28 21:42:24'),
(10, 12, 56, 'superado', 20, 2, '2026-08-28 21:42:55'),
(11, 13, 56, 'no_superado', 50, 2, '2026-08-28 21:43:07'),
(12, 25, 61, 'no_superado', 420, 3, '2026-08-31 19:54:38'),
(13, 32, 61, 'superado', 400, 2, '2026-08-31 20:03:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `judge_item_evaluations`
--

CREATE TABLE `judge_item_evaluations` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `challenge_item_id` int(11) NOT NULL,
  `status` enum('superado','no_superado') NOT NULL,
  `judge_user_id` int(11) DEFAULT NULL,
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `judge_item_evaluations`
--

INSERT INTO `judge_item_evaluations` (`id`, `registration_id`, `challenge_item_id`, `status`, `judge_user_id`, `evaluated_at`) VALUES
(15, 12, 109, 'superado', 2, '2026-08-28 21:37:50'),
(16, 12, 110, 'no_superado', 2, '2026-08-28 21:37:50'),
(17, 13, 109, 'superado', 2, '2026-08-28 21:42:24'),
(18, 13, 110, 'superado', 2, '2026-08-28 21:42:24'),
(19, 12, 111, 'superado', 2, '2026-08-28 21:42:55'),
(20, 13, 111, 'no_superado', 2, '2026-08-28 21:43:07'),
(21, 25, 113, 'superado', 3, '2026-08-31 19:54:38'),
(22, 25, 114, 'no_superado', 3, '2026-08-31 19:54:38'),
(23, 32, 113, 'superado', 2, '2026-08-31 20:03:04'),
(24, 32, 114, 'superado', 2, '2026-08-31 20:03:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `judge_student_assignments`
--

CREATE TABLE `judge_student_assignments` (
  `id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `judge_student_assignments`
--

INSERT INTO `judge_student_assignments` (`id`, `judge_id`, `registration_id`, `assigned_at`) VALUES
(5, 2, 12, '2026-08-28 20:51:12'),
(6, 2, 13, '2026-08-28 20:51:12'),
(7, 3, 25, '2026-08-31 19:53:26'),
(8, 3, 17, '2026-08-31 19:53:26'),
(9, 2, 32, '2026-08-31 19:53:37'),
(10, 2, 19, '2026-08-31 19:53:37'),
(11, 2, 18, '2026-08-31 19:53:37'),
(12, 2, 24, '2026-08-31 19:53:37'),
(13, 2, 26, '2026-08-31 19:53:37'),
(14, 2, 21, '2026-08-31 19:53:37'),
(15, 2, 20, '2026-08-31 19:53:37'),
(16, 2, 31, '2026-08-31 19:53:37'),
(17, 2, 15, '2026-08-31 19:53:37'),
(18, 2, 27, '2026-08-31 19:53:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partial_leads`
--

CREATE TABLE `partial_leads` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `doc_type` varchar(20) DEFAULT NULL,
  `nationality` varchar(5) DEFAULT NULL,
  `document_number` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'abandonado',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `partial_leads`
--

INSERT INTO `partial_leads` (`id`, `full_name`, `last_name`, `doc_type`, `nationality`, `document_number`, `birth_date`, `age`, `gender`, `email`, `phone`, `state`, `city`, `address`, `status`, `created_at`, `updated_at`) VALUES
(2, 'José', 'Marrufo', 'cedula', 'V', 'V-25697774', '2007-02-06', 19, 'masculino', 'j.l.marrufo@gmail.com', '+58 424-5928624', 'portuguesa', 'araure', 'Urb Tricentenaria\r\nPortuguesa', 'completado', '2026-02-06 20:02:52', '2026-08-26 13:00:54'),
(3, 'Alan Leonardo ', 'Herrera Rodríguez ', 'cedula', 'V', 'V-37471838', '2016-09-29', 9, 'masculino', 'aadrirb16@gmail.com', '+58 414-5672168', 'portuguesa', 'araure', 'Urb. Roca del Llano 2-08.', 'abandonado', '2026-02-06 23:51:32', '2026-02-06 23:51:32'),
(4, 'Giovanna Alejandra ', 'Perla Galvis ', 'cedula', 'V', 'V-33778455', '2011-04-18', 14, 'femenino', 'gioperla9@gmail.com', '+58 412-3433774', 'portuguesa', 'araure', 'Urbanización Valle Fresco II ', 'abandonado', '2026-02-07 18:14:44', '2026-02-07 18:59:12'),
(6, 'Carlos Jesus ', 'Peñaloza Contreras ', 'cedula', 'V', 'V-33349486', '2008-12-05', 17, 'masculino', 'carlosjpc7271@gmail.com', '+58 424-5636958', 'portuguesa', 'acarigua', 'Urbanización El Este,manzana 2, calle 2, casa número 13', 'abandonado', '2026-02-07 19:56:07', '2026-02-07 19:56:07'),
(7, 'Isabella', 'Contreras De Dea', 'cedula', 'V', 'V-36455726', '2014-09-19', 11, 'femenino', 'micontrema@gmail.com', '+58 414-1439493', 'portuguesa', 'acarigua', 'Urbanización Bosque de camoruco micro 8 casa 8-16', 'abandonado', '2026-02-07 21:03:14', '2026-02-07 21:08:04'),
(10, 'MATTHEW ZACH', 'BETANCOURT HERNANDEZ', 'cedula', 'V', 'V-34687380', '2012-10-21', 13, 'masculino', 'zach.betancourt21@gmail.com', '+58 424-5814430', 'portuguesa', 'acarigua', 'Urb. Durigua 3, vereda 26 nro. 18', 'abandonado', '2026-02-10 17:40:35', '2026-02-10 17:46:40'),
(12, 'Juan David', 'Ortega Aguirre', 'cedula', 'V', 'V-34534995', '2011-07-28', 14, 'masculino', 'juandavidortega28072011@gmail.com', '+58 412-6043860', 'portuguesa', 'acarigua', 'Urb Llano Alto Conjunto Azucena Casa 48', 'abandonado', '2026-02-11 16:44:30', '2026-02-11 16:44:30'),
(13, 'Mathias Alexi', 'Blanco Chávez', 'cedula', 'V', 'V-36993243', '2016-03-24', 9, 'masculino', 'mathiasblanco631@gmail.com', '+58 424-5432104', 'portuguesa', 'araure', 'Villa Araure 1 sector eucaliptos \"A\" casa #62-61', 'abandonado', '2026-02-12 16:43:27', '2026-02-12 16:43:27'),
(14, 'Tiana Isabella', 'Pérez Rivero', 'cedula', 'V', 'V-36858406', '2015-12-22', 10, 'femenino', 'tianaisabellape@gmail.com', '+58 412-7819206', 'portuguesa', 'araure', 'Urbanización altos de la galera conjunto roble 1 casa 56', 'abandonado', '2026-02-12 16:46:27', '2026-02-12 20:20:14'),
(16, 'Sophia Cristina ', 'Roa Urriola ', 'cedula', 'V', 'V-34683242', '2012-11-16', 13, 'femenino', 'johannacristinaurriola31@gmail.com', '+58 414-5549409', 'portuguesa', 'acarigua', 'Sector campo lindo calle 26 y 27 resd. Jacky apto. 2 piso 1 ', 'abandonado', '2026-02-12 18:39:35', '2026-02-12 18:39:35'),
(17, 'JESUS ALEJANDRO', 'SIFONTES ESPINOZA', 'cedula', 'V', 'V-33485592', '2010-04-23', 15, 'masculino', 'chusifontes2304@gmail.com', '+58 424-5101745', 'portuguesa', 'araure', 'URB LLANO ALTO CONJUNTO 12 CASA 49 ARAURE PORTUGUESA', 'abandonado', '2026-02-12 19:46:05', '2026-02-12 19:46:05'),
(19, 'Camila', 'Mavarez Borges', 'cedula', 'V', 'V-37173519', '2013-07-15', 12, 'femenino', 'mavarezborgescamila@gmail.com', '+58 412-1352171', 'portuguesa', 'acarigua', 'Acarigua 3301, Portuguesa', 'abandonado', '2026-02-13 12:25:35', '2026-02-13 12:25:35'),
(20, 'ryan', 'uzcategui', 'cedula', 'V', 'V-33084423', '2009-08-17', 16, 'masculino', 'ryansfinolu@gmail.com', '+58 416-439119', 'portuguesa', 'araure', 'Portuguesa ciudad araure carretera nacional vía San Carlos sector algodonal', 'abandonado', '2026-02-13 23:18:15', '2026-02-13 23:18:15'),
(21, 'Yibran David ', 'Contreras Zavarce ', 'cedula', 'V', 'V-34636858', '2012-10-27', 13, 'masculino', 'briggithzavarce@gmail.com', '+58 426-2472493', 'portuguesa', 'acarigua', 'Urbanización Ezequiel Zamora calle 1', 'abandonado', '2026-02-14 03:08:17', '2026-02-15 01:22:09'),
(22, 'FABIAN EDUARDO', 'SILVA FUENTES', 'cedula', 'V', 'V-36085222', '2014-06-12', 11, 'masculino', 'ffaby1206@gmail.com', '+58 416-2199068', 'portuguesa', 'araure', 'Urb Misia Amelia casa 68 Araure Portuguesa', 'abandonado', '2026-02-14 16:18:12', '2026-02-14 16:18:12'),
(27, 'MARCO AURELIO', 'RIVERO MARTINEZ', 'cedula', 'V', 'V-36610673', '2014-08-25', 11, 'masculino', 'marcourelioriveromartinez@gmail.com', '+58 414-5648586', 'portuguesa', 'araure', 'edificio la arboleda 2 piso, apto 3-9 araure ', 'abandonado', '2026-02-16 14:28:14', '2026-02-16 14:28:14'),
(28, 'Juan David ', 'Yepez Alvarez ', 'cedula', 'V', 'V-36078490', '2013-09-14', 12, 'masculino', 'juanyepeztecnocleveland@gmail.com', '+58 416-3998243', 'portuguesa', 'araure', 'Urb:Prados del sol\r\nSector: Mercantil ', 'abandonado', '2026-02-17 14:39:19', '2026-02-17 14:39:19'),
(29, 'Jorge luis Jeremias ', 'Castillo Romero ', 'cedula', 'V', 'V-36393982', '2014-02-18', 12, 'masculino', 'jorgeluisjeremiasc@gmail.com', '+58 414-5640850', 'portuguesa', 'araure', 'Urbanización los Robles 2 calle 12 casa 428 ', 'abandonado', '2026-02-18 12:32:18', '2026-02-18 12:32:18'),
(30, 'Gabriel alejandro', 'Pulido Alvarado ', 'cedula', 'V', 'V-36740650', '2015-08-13', 10, 'masculino', 'ynescaalvarado@gmail.com', '+58 414-9545992', 'portuguesa', 'araure', 'Urbanización molinos IV casa Nro 21', 'abandonado', '2026-02-18 15:11:57', '2026-02-18 15:17:45'),
(32, 'Oriannys Zamantha ', 'Alvarez dueñez ', 'cedula', 'V', 'V-34458410', '2012-09-12', 13, 'femenino', 'd.yelimar@gmail.com', '+58 412-5577058', 'portuguesa', 'acarigua', 'Barrio él algarrobo calle 30b casa 17-89 ', 'abandonado', '2026-02-18 15:50:56', '2026-02-19 18:53:43'),
(33, 'JORIANNYS VALENTINA', 'MARIN BARRIOS', 'cedula', 'V', 'V-34276377', '2010-02-14', 16, 'femenino', 'andreabarrios1482@gmail.com', '+58 426-2073221', 'portuguesa', 'acarigua', 'AV 2 ENTRE CALLES 11 Y 12 PAYARA', 'abandonado', '2026-02-18 18:46:42', '2026-02-18 18:46:42'),
(34, 'Matteo Santiago', 'Vinciguerra Marecos', 'cedula', 'V', 'V-33778571', '2011-05-30', 14, 'masculino', 'matteovinciguerra05@gmail.com', '+58 424-5752654', 'portuguesa', 'araure', 'Urbanización las palmas, segunda etapa, calle 4, casa 424', 'abandonado', '2026-02-18 19:03:03', '2026-02-18 19:03:03'),
(35, 'Anderson Josué', 'Chacón Rojas', 'cedula', 'V', 'V-32386940', '2007-04-03', 18, 'masculino', 'rojasanni808@gmail.com', '+58 412-6793999', 'portuguesa', 'acarigua', 'Acarigua', 'abandonado', '2026-02-18 19:32:16', '2026-02-18 19:32:16'),
(36, 'Maria Antonieta', 'Calado Colmenarez', 'cedula', 'V', 'V-35029901', '2012-06-28', 13, 'femenino', 'yusmarucolmenarez1@gmail.com', '+58 424-5749183', 'portuguesa', 'araure', 'Rio Acarigua', 'abandonado', '2026-02-18 20:17:20', '2026-02-18 20:17:20'),
(38, 'Sofia Victoria ', 'Antunez Alvarado ', 'cedula', 'V', 'V-34535422', '2012-09-19', 13, 'femenino', 'sofiavictoriaantunezalvarado@gmail.com', '+58 412-9565316', 'portuguesa', 'araure', 'Urbanización Misia Amelia Calle 12 casa 162', 'abandonado', '2026-02-18 21:44:24', '2026-02-18 21:44:24'),
(39, 'Miguel Angel', 'Navas Hernandez', 'cedula', 'V', 'V-34773589', '2012-11-26', 13, 'masculino', 'luismarchernandez@gmail.com', '+58 412-7604626', 'portuguesa', 'araure', 'Urb villas del pilar 2da etapas calle 2 numero 905D', 'abandonado', '2026-02-18 22:55:40', '2026-02-18 22:55:40'),
(40, 'Matias', 'Goyo', 'cedula', 'V', 'V-36194581', '2014-08-01', 11, 'masculino', 'matiasgoyof@gmail.com', '+58 414-5563835', 'portuguesa', 'acarigua', 'urbanizacion altos de la galera, conjunto el saman casa S82', 'abandonado', '2026-02-18 23:53:58', '2026-02-18 23:53:58'),
(41, 'Arantza Sofía ', 'Meléndez Torcates ', 'cedula', 'V', 'V-34275569', '2011-08-10', 14, 'masculino', 'melendezarantzasofia@gmail.com', '+58 412-1505643', 'portuguesa', 'acarigua', 'Urbanización la virginia calle 5 tercera etapa casa 43A ', 'abandonado', '2026-02-19 00:03:35', '2026-02-19 00:03:35'),
(42, 'Luis Ignacio ', 'Querales Barrio', 'cedula', 'V', 'V-33348142', '2010-04-19', 15, 'masculino', 'lqueralesbarrio@gmail.com', '+58 414-1572104', 'portuguesa', 'araure', 'urb el pilar calle los apamates casa107-1', 'abandonado', '2026-02-19 00:25:51', '2026-02-19 00:25:51'),
(43, 'Santiago Josué ', 'Carrizo Vidal', 'cedula', 'V', 'V-34169440', '2011-10-13', 14, 'masculino', 'sjcvidal21@gmail.com', '+58 424-5384998', 'portuguesa', 'acarigua', 'Parroquia payara municipio paez ', 'abandonado', '2026-02-19 00:27:29', '2026-02-19 00:37:49'),
(46, 'Miguel Francisco ', 'Laguna Valera ', 'cedula', 'V', 'V-37023237', '2015-10-21', 10, 'masculino', 'daiciriscvalerac@gmail.com', '+58 414-5080738', 'portuguesa', 'otra_-_(especificar)', 'San Rafael de Onoto ', 'abandonado', '2026-02-19 17:11:13', '2026-02-19 17:11:13'),
(47, 'Susej Marrero ', 'Marrero Arambulet ', 'cedula', 'V', 'V-34169642', '2011-12-07', 14, 'femenino', 'marrerosusejgabriela@gmail.com', '+58 424-5182382', 'portuguesa', 'acarigua', 'Final Av 40 calle sin salida casa 11 Barrio Paez ', 'abandonado', '2026-02-19 17:14:24', '2026-02-19 18:19:14'),
(51, 'Marcos Jesus', 'Oliveros Ruiz', 'cedula', 'V', 'V-34534499', '2012-07-11', 13, 'masculino', 'marcosjesus1107@gmail.com', '+58 412-0578194', 'portuguesa', 'araure', 'Urbanización Llano Alto conjunto araguaney casa 39 ', 'abandonado', '2026-02-19 18:27:02', '2026-02-19 18:27:02'),
(53, 'Agatha', 'Stracquadaini Espinoza', 'cedula', 'V', 'V-33497042', '2010-08-29', 15, 'femenino', 'mbespinoza81@gmail.com', '+58 414-5226051', 'lara', 'barquisimeto', 'CALLE 28 ENTRE AV. 20 Y CARR 21 Edif Guamacire Piso 3 apart 11', 'abandonado', '2026-02-19 19:08:28', '2026-02-19 19:08:28'),
(54, 'Gustavo', 'Giraldo', 'cedula', 'V', 'V-33719058', '2008-11-25', 17, 'masculino', 'ggiraldorojas@gmail.com', '+58 412-2922511', 'portuguesa', 'araure', 'Urbanización 24 de Julio \r\nSector 1 calle 5 casa 17 ', 'abandonado', '2026-02-19 21:28:41', '2026-02-20 17:22:07'),
(55, 'Daniela Valentina ', 'Rojas Ruiz ', 'cedula', 'V', 'V-34169207', '2011-02-25', 14, 'femenino', 'albaniruiz90@gmail.com', '+58 424-5872027', 'portuguesa', 'araure', 'Urbanización villas del pilar calle 7 de los tetras segunda entrada', 'abandonado', '2026-02-19 21:31:14', '2026-02-19 21:31:14'),
(56, 'Orlando ', 'Márquez ', 'cedula', 'V', 'V-20392821', '2010-02-03', 16, 'masculino', 'orlandom_04_30@hotmail.com', '+58 414-973120', 'lara', 'otra_-_(especificar)', 'Duaca', 'abandonado', '2026-02-19 21:35:07', '2026-02-19 21:35:07'),
(57, 'Ivanna Veruska', 'Bompart Martinez', 'cedula', 'V', 'V-34388662', '2010-12-17', 15, 'femenino', 'ibompartmarinez@gmail.com', '+58 412-1505566', 'portuguesa', 'araure', 'Conjunto Residencial Parque Cedral', 'abandonado', '2026-02-19 21:38:53', '2026-02-19 21:38:53'),
(58, 'Naimarllys Yhoxana', 'Palma Nadal', 'cedula', 'V', 'V-33554366', '2009-12-30', 16, 'femenino', 'naimarllysp.33554366@gmail.com', '+58 412-0934214', 'lara', 'barquisimeto', 'Urbanización Reinaldo bravo Duaca ', 'abandonado', '2026-02-19 21:47:44', '2026-02-19 21:47:44'),
(59, 'Gaby Anthonella', 'Alvarado Durán', 'cedula', 'V', 'V-33613155', '2010-09-14', 15, 'femenino', 'gabyalvaradoduran@gmail.com', '+58 414-9731320', 'lara', 'otra_-_(especificar)', 'Duaca. ', 'abandonado', '2026-02-19 21:49:13', '2026-02-19 21:49:13'),
(60, 'Duvieliz Valentina ', 'Calderón Torres ', 'cedula', 'V', 'V-33948381', '2011-07-15', 14, 'femenino', 'duvielizcalderon@gmail.com', '+58 412-7248196', 'portuguesa', 'araure', 'Urb llano alto, conjunto Merecure, casa número 62 ', 'abandonado', '2026-02-19 21:51:31', '2026-02-19 22:03:13'),
(62, 'Samuel Isaac', 'Hernández Ledezma ', 'cedula', 'V', 'V-36560019', '2014-06-10', 11, 'masculino', 'ledezmamarielby5@gmail.com', '+58 412-5519288', 'portuguesa', 'otra_-_(especificar)', 'San Rafael de onoto ', 'abandonado', '2026-02-19 22:04:02', '2026-02-20 00:35:07'),
(63, 'Victoria Valentina', 'Rodríguez Castillo ', 'cedula', 'V', 'V-34974475', '2012-08-31', 13, 'femenino', 'josmabierr@gmail.com', '+58 414-9731320', 'lara', 'otra_-_(especificar)', 'Urbanizacion brisas del eneal duaca', 'abandonado', '2026-02-19 22:16:05', '2026-02-19 22:16:05'),
(64, 'Fabián Josue ', 'Giménez Urquiola ', 'cedula', 'V', 'V-34910211', '2012-07-23', 13, 'masculino', 'fabiangimenez535@gmail.com', '+58 414-9731320', 'lara', 'barquisimeto', 'Duaca', 'abandonado', '2026-02-19 22:29:21', '2026-02-19 23:47:39'),
(65, 'Luis David ', 'Pérez Calles ', 'cedula', 'V', 'V-33336268', '2010-02-06', 16, 'masculino', 'luisp.33336268crc@gmail.com', '+58 412-9304760', 'lara', 'barquisimeto', 'Carretera Barquisimeto_Duaca km 27 entrada carrizal Perarapa sector estadio ', 'abandonado', '2026-02-19 22:58:41', '2026-02-19 22:58:41'),
(66, 'Luis Gustavo', 'Castillo Leal', 'cedula', 'V', 'V-34321456', '2012-07-19', 13, 'masculino', 'castillor28672570@gmail.com', '+58 412-4397625', 'lara', 'otra_-_(especificar)', 'Duaca, Carrera 12 entre 10 y 11', 'abandonado', '2026-02-19 23:37:11', '2026-02-19 23:37:11'),
(68, 'Gabrielys ', 'Lobatón ', 'cedula', 'V', 'V-33447940', '2009-03-23', 16, 'femenino', 'GabrielysL33447940c.r.c@gmail.com', '+58 416-1674092', 'lara', 'barquisimeto', 'Carretera Barquisimeto-Duaca km 27 entrada carrizal caserio Perarapa sector el estadio ', 'abandonado', '2026-02-20 00:06:54', '2026-02-20 00:06:54'),
(69, 'Jesús Sebastián', 'Colmenárez Sandoval', 'cedula', 'V', 'V-32830505', '2009-04-01', 16, 'masculino', 'jesusc.32830505crc@gmail.com', '+58 412-6233078', 'lara', 'otra_-_(especificar)', 'Calle 4 entre carreras 9 y 10. sector Rey Dormido. Duaca-Lara', 'abandonado', '2026-02-20 00:18:49', '2026-02-20 00:18:49'),
(70, 'Oriana Paola ', 'Gallardo Torrealba', 'cedula', 'V', 'V-33749974', '2010-12-25', 15, 'femenino', 'orianag.33749974crc@gmail.com', '+58 424-5547187', 'lara', 'barquisimeto', 'Calle 20 entre carreras 5 y 6. Sector la morita, Duaca Edo Lara ', 'abandonado', '2026-02-20 00:21:36', '2026-02-20 00:39:19'),
(73, 'NABI JESUS', 'SILVA GOMEZ', 'cedula', 'V', 'V-33051263', '2009-09-14', 16, 'masculino', 'Nabisilva287@gmail.com', '+58 412-5111864', 'lara', 'barquisimeto', 'Calle 11 entre carreras 8 y 9', 'abandonado', '2026-02-20 01:39:05', '2026-02-20 01:52:18'),
(76, 'Valeria Sofia', 'Cordero Lopez', 'cedula', 'V', 'V-36770713', '2014-04-30', 11, 'femenino', 'daiciriscvalerac@gmail.com', '+58 424-5282051', 'portuguesa', 'otra_-_(especificar)', 'San Rafael de Onoto ', 'abandonado', '2026-02-20 11:59:46', '2026-02-20 11:59:46'),
(80, 'María Preciosa', 'Vazquez Garcia', 'cedula', 'V', 'V-34169523', '2012-02-06', 14, 'femenino', 'garciajulielsy1@gmail.com', '+58 424-5311595', 'portuguesa', 'otra_-_(especificar)', 'San Rafael de Onoto ', 'abandonado', '2026-02-20 17:31:44', '2026-02-20 17:31:44'),
(81, 'Karina del Carmen Bermudez Matos', 'Matos', 'cedula', 'V', 'V-36818161', '2014-10-18', 11, 'masculino', 'mundoimagen2012@gmail.com', '+58 042-4544353', 'portuguesa', 'acarigua', 'Calle 30 entre avenidas 36 y 37, centro ', 'abandonado', '2026-02-20 17:56:55', '2026-02-20 17:56:55'),
(82, 'ENMANUEL DAVID', 'NIEVES LOZADA', 'cedula', 'V', 'V-33709383', '2010-03-30', 15, 'masculino', '1enmanuelnieves@gmail.com', '+58 422-0108399', 'portuguesa', 'acarigua', 'Urb. Bosques de Camoruco Av. Principal Conjunto 6 casa 16', 'abandonado', '2026-02-20 20:42:17', '2026-02-20 20:42:17'),
(83, 'José Daniel ', 'Lozada Correa ', 'cedula', 'V', 'V-33169503', '2008-11-14', 17, 'masculino', 'whiterock432@gmail.com', '+58 424-5541917', 'portuguesa', 'acarigua', 'bosques de camoruco conjunto 6 casa 6-16', 'abandonado', '2026-02-20 21:29:51', '2026-02-20 21:29:51'),
(84, 'Santiago Javier ', 'Sosa Hernández ', 'cedula', 'V', 'V-33879742', '2010-04-12', 15, 'masculino', 'mirlajosehernandez@gmail.com', '+58 424-5888744', 'portuguesa', 'araure', 'Urb desarrolló camburito calle 8 casa 24', 'abandonado', '2026-02-20 22:00:48', '2026-02-20 22:00:48'),
(85, 'Isavó Karmir ', 'Uzcátegui Pérez ', 'cedula', 'V', 'V-32512333', '2008-03-17', 17, 'femenino', 'isavokarmir@gmail.com', '+58 424-5110414', 'portuguesa', 'acarigua', 'San José 2,Av. 1 casa número 46 ', 'abandonado', '2026-02-20 23:21:49', '2026-02-20 23:21:49'),
(86, 'Miguel Alfonso ', 'Guedez Camacho ', 'cedula', 'V', 'V-32442894', '2006-10-08', 19, 'masculino', 'alfonzoguedezzz@gmail.com', '+58 424-5066888', 'portuguesa', 'araure', 'Hacienda San José 2 calle 6 #40', 'abandonado', '2026-02-20 23:41:58', '2026-02-20 23:41:58'),
(87, 'Maria Felicia', 'Castillo Aguilar', 'cedula', 'V', 'V-36897689', '2010-11-21', 15, 'femenino', 'daiciriscvalerac@gmail.com', '+58 424-5006780', 'portuguesa', 'otra_-_(especificar)', 'San Rafael de Onoto ', 'abandonado', '2026-02-21 00:18:37', '2026-02-21 00:18:37'),
(88, 'Aylet de los Ángeles ', 'Álvarez Torres', 'cedula', 'V', 'V-32809875', '2009-01-09', 17, 'femenino', 'ayletdelosangelesalvareztorres@gmail.com', '+58 424-5636789', 'portuguesa', 'araure', 'Urbanizacion Lomas de Santa Sofia, Conjunto 12, Casa 09, Araure. ', 'abandonado', '2026-02-21 12:56:09', '2026-02-21 12:56:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
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
  `microbit_experience` enum('ninguna','basica','intermedia','avanzada') NOT NULL,
  `document_photo_path` varchar(500) DEFAULT NULL,
  `is_minor` tinyint(1) DEFAULT 0,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_doc_type` varchar(50) DEFAULT NULL,
  `guardian_document` varchar(50) DEFAULT NULL,
  `guardian_email` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `participants`
--

INSERT INTO `participants` (`id`, `full_name`, `last_name`, `document_type`, `document_number`, `nationality`, `birth_date`, `age`, `gender`, `email`, `phone`, `address`, `state`, `city`, `institution`, `education_level`, `grade`, `microbit_experience`, `document_photo_path`, `is_minor`, `guardian_name`, `guardian_doc_type`, `guardian_document`, `guardian_email`, `guardian_phone`, `created_at`, `updated_at`) VALUES
(1, 'Miguel', 'Rojas Rivera', 'cedula', '83117602', 'V', '2010-04-03', 16, 'femenino', 'miguel.rojas955@gmail.com', '0424-8078605', 'Av. Principal, Casa #139', 'Carabobo', 'Araure', 'U.E. Instituto Técnico Jesús Obrero', 'bachillerato', '4to año', 'basica', NULL, 1, 'Andrés Vargas', 'V', '61461424', 'guardian489@gmail.com', '0412-1344798', '2026-08-27 22:00:29', '2026-08-27 22:00:29'),
(2, 'Alejandro', 'Pérez Ramírez', 'cedula', '12085387', 'V', '2016-04-15', 10, 'femenino', 'correo@gmail.com', '0426-1898837', 'Av. Principal, Casa #142', 'Miranda', 'Barquisimeto', 'Colegio Nacional Bolivariano', 'primaria', '4to grado', 'intermedia', NULL, 1, 'Pedro García', 'V', '28560112', 'guardian682@gmail.com', '0426-8769201', '2026-08-27 22:03:28', '2026-08-27 22:03:28'),
(3, 'Juan', 'Rivera Vargas', 'cedula', '67186923', 'V', '2016-02-01', 10, 'masculino', 'juan.rivera768@gmail.com', '0424-5849872', 'Av. Principal, Casa #59', 'Lara', 'Caracas', 'U.E. Colegio Simón Bolívar', 'primaria', '4to grado', 'ninguna', NULL, 1, 'Daniel Romero', 'V', '78235647', 'guardian861@gmail.com', '0426-3747141', '2026-08-28 14:23:16', '2026-08-28 14:23:16'),
(4, 'Ricardo', 'García Ortiz', 'cedula', '48910181', 'V', '2009-01-28', 17, 'femenino', 'ricardo.garcia92@gmail.com', '0412-5957970', 'Av. Principal, Casa #169', 'Carabobo', 'Maracaibo', 'Colegio Nacional Bolivariano', 'bachillerato', '5to año', 'avanzada', NULL, 1, 'Alejandro López', 'V', '81801755', 'guardian840@gmail.com', '0424-6614363', '2026-08-28 15:18:10', '2026-08-28 15:18:10'),
(5, 'José', 'Ortiz Medina', 'cedula', '65047163', 'V', '2012-04-17', 14, 'masculino', 'jose.ortiz473@gmail.com', '0416-1125687', 'Av. Principal, Casa #29', 'Mérida', 'San Cristóbal', 'Colegio Nacional Bolivariano', 'primaria', '8to grado', 'intermedia', NULL, 1, 'Javier Hernández', 'V', '43119648', 'guardian489@gmail.com', '0414-9216838', '2026-08-28 15:20:30', '2026-08-28 15:20:30'),
(6, 'Luis', 'Rojas Martínez', 'cedula', '69615214', 'V', '2009-04-27', 17, 'masculino', 'luis.rojas317@gmail.com', '0426-5323281', 'Av. Principal, Casa #85', 'Táchira', 'Caracas', 'Liceo Bolivariano Rafael Urdaneta', 'bachillerato', '5to año', 'avanzada', NULL, 1, 'Diego Martínez', 'V', '68857185', 'guardian442@gmail.com', '0416-2968465', '2026-08-28 15:38:15', '2026-08-28 15:38:15'),
(7, 'Andrés', 'Ortiz Pérez', 'cedula', '17351959', 'V', '2015-03-01', 11, 'femenino', 'andres.ortiz@gmail.com', '0424-3501249', 'Av. Principal, Casa #34', 'Mérida', 'Acarigua', 'U.E. Privada Los Andes', 'primaria', '5to grado', 'basica', NULL, 1, 'Rafael Torres', 'V', '16428832', 'guardian32@gmail.com', '0412-4590085', '2026-08-28 15:39:33', '2026-08-28 15:39:33'),
(8, 'Ricardo', 'Martínez González', 'cedula', '55788018', 'V', '2006-11-17', 19, 'masculino', 'ricardo.martinez345@gmail.com', '0424-2349442', 'Av. Principal, Casa #189', 'Zulia', 'Mérida', 'U.E. Instituto Técnico Jesús Obrero', 'universidad', '1er semestre', 'basica', NULL, 0, '', 'V', '', '', '', '2026-08-28 15:47:03', '2026-08-28 15:47:03'),
(9, 'dasd', 'dasdsa', 'cedula', '54587895', 'V', '2015-06-28', 11, 'masculino', 'dasdsa@gmail.com', '4265485755', 'Acarigua', 'Portuguesa', 'Acarigua', 'Angel', 'primaria', '4to', 'basica', NULL, 1, 'dasdasd', 'V', '5487544', 'dasdas@gmail.com', '4265487555', '2026-08-28 15:49:13', '2026-08-28 15:49:13'),
(10, 'Andrés', 'García Rojas', 'cedula', '43800338', 'V', '2008-05-13', 18, 'masculino', 'andres.garcia787@gmail.com', '0414-2117989', 'Av. Principal, Casa #49', 'Táchira', 'Valencia', 'U.E. Colegio Simón Bolívar', 'bachillerato', '6to año', 'avanzada', NULL, 0, '', 'V', '', '', '', '2026-08-28 15:58:36', '2026-08-28 15:58:36'),
(11, 'Jesús', 'Martínez Rivera', 'cedula', '30710134', 'V', '2018-04-21', 8, 'masculino', 'jesus.martinez875@gmail.com', '0426-4658393', 'Av. Principal, Casa #124', 'Zulia', 'Valencia', 'Colegio Nuestra Señora de Coromoto', 'primaria', '2to grado', 'avanzada', NULL, 1, 'Carlos Ramírez', 'V', '47981762', 'guardian164@gmail.com', '0416-1493590', '2026-08-28 18:10:35', '2026-08-28 18:10:35'),
(12, 'Ricardo', 'Hernández Ramírez', 'cedula', '58019832', 'V', '2006-09-15', 19, 'femenino', 'ricardo.hernandez836@gmail.com', '0416-3258546', 'Av. Principal, Casa #52', 'Táchira', 'San Cristóbal', 'Liceo Bolivariano Rafael Urdaneta', 'universidad', '1er semestre', 'intermedia', NULL, 0, '', 'V', '', '', '', '2026-08-28 18:49:45', '2026-08-28 18:49:45'),
(13, 'Miguel', 'López Pérez', 'cedula', '62375324', 'V', '2016-08-02', 10, 'femenino', 'miguel.lopez3132@gmail.com', '0424-5469286', 'Av. Principal, Casa #29', 'Lara', 'Acarigua', 'U.E. Nacional Creación', 'primaria', '4to grado', 'basica', NULL, 1, 'Pedro Hernández', 'V', '63636803', 'guardian39@gmail.com', '0412-9214571', '2026-08-28 19:12:49', '2026-08-28 19:12:49'),
(14, 'Diego', 'Vargas Vargas', 'cedula', '16234526', 'V', '2011-06-26', 15, 'masculino', 'dada.dasdas@gmail.com', '0414-3851309', 'Av. Principal, Casa #127', 'Yaracuy', 'Coro', 'Liceo Bolivariano Rafael Urdaneta', 'bachillerato', '3to año', 'ninguna', NULL, 1, 'Javier Medina', 'V', '49423815', 'guardian388@gmail.com', '0426-4521814', '2026-08-28 19:14:25', '2026-08-28 19:14:25'),
(15, 'Carlos', 'Sánchez Torres', 'cedula', '19648654', 'V', '2017-03-23', 9, 'masculino', 'carlos.daasda@gmail.com', '0412-1226550', 'Av. Principal, Casa #69', 'Falcón', 'Maracay', 'U.E. Fe y Alegría', 'primaria', '3to grado', 'ninguna', NULL, 1, 'Javier Castro', 'V', '63898769', 'guardian279@gmail.com', '0426-7498755', '2026-08-28 19:16:01', '2026-08-28 19:16:01'),
(16, 'Carlos', 'García Hernández', 'cedula', '21226786', 'V', '2007-02-05', 19, 'femenino', 'carlos.garcias2312@gmail.com', '0412-7659430', 'Av. Principal, Casa #42', 'Miranda', 'Mérida', 'Colegio San José', 'universidad', '1er semestre', 'avanzada', NULL, 0, '', 'V', '', '', '', '2026-08-28 19:16:50', '2026-08-28 19:16:50'),
(17, 'dasdsa', 'dasdasd', 'cedula', '24587455', 'V', '2015-06-28', 11, 'masculino', 'dasdasdasd@gmail.com', '584265487551', 'dasdas', 'portuguesa', 'acarigua', 'dasdasdsa', 'bachillerato', '2do_año', 'basica', 'uploads/documents/HC8-17879483783111_document.jpg', 1, 'dasdasdasd', 'cedula', 'V-5845744', 'dasdasdsa@gmail.com', '584262548755', '2026-08-28 20:19:39', '2026-08-28 20:19:39'),
(18, 'María', 'Pérez', 'cedula_escolar', '12345678', 'V', '2010-05-15', 16, 'femenino', 'maria.perez@example.com', '0412-1234567', '', '', '', 'Colegio Nacional Bolivariano', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(19, 'Juan', 'Rodríguez', 'cedula_escolar', '87654321', 'V', '2009-11-02', 16, 'masculino', 'juan.rodriguez@example.com', '0424-7654321', '', '', '', 'U.E. Privada Los Andes', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(20, 'Valentina', 'García', 'cedula_escolar', '23456789', 'V', '2011-02-14', 15, 'femenino', 'valentina.garcia@example.com', '0416-5551234', '', '', '', 'U.E. Colegio Simón Bolívar', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(21, 'Diego', 'Martínez', 'cedula_escolar', '34567890', 'V', '2010-08-21', 16, 'masculino', 'diego.martinez@example.com', '0414-9876543', '', '', '', 'Liceo Bolivariano Rafael Urdaneta', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(22, 'Camila', 'Hernández', 'cedula_escolar', '45678901', 'V', '2012-03-30', 14, 'femenino', 'camila.hernandez@example.com', '0426-3214567', '', '', '', 'U.E. Instituto Técnico Jesús Obrero', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(23, 'Andrés', 'López', 'cedula_escolar', '56789012', 'V', '2009-12-12', 16, 'masculino', 'andres.lopez@example.com', '0412-6547890', '', '', '', 'Colegio San José', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(24, 'Isabella', 'Ramírez', 'cedula_escolar', '67890123', 'V', '2011-06-18', 15, 'femenino', 'isabella.ramirez@example.com', '0424-1112223', '', '', '', 'U.E. Fe y Alegría', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(25, 'Gabriel', 'Torres', 'cedula_escolar', '78901234', 'V', '2010-09-05', 15, 'masculino', 'gabriel.torres@example.com', '0416-3334445', '', '', '', 'U.E. Instituto Montessori', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(26, 'Sofía', 'Flores', 'cedula_escolar', '89012345', 'V', '2013-01-25', 13, 'femenino', 'sofia.flores@example.com', '0414-5556667', '', '', '', 'Colegio Nuestra Señora de Coromoto', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(27, 'Samuel', 'Rivera', 'cedula_escolar', '90123456', 'V', '2012-07-09', 14, 'masculino', 'samuel.rivera@example.com', '0426-7778889', '', '', '', 'U.E. Nacional Creación', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(28, 'Daniela', 'Morales', 'cedula_escolar', '11223344', 'V', '2010-04-17', 16, 'femenino', 'daniela.morales@example.com', '0412-9990001', '', '', '', 'U.E. Colegio Simón Bolívar', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(29, 'Carlos', 'Ortiz', 'cedula_escolar', '22334455', 'V', '2009-10-03', 16, 'masculino', 'carlos.ortiz@example.com', '0414-8887776', '', '', '', 'Liceo Bolivariano Rafael Urdaneta', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(30, 'Fernanda', 'Castro', 'cedula_escolar', '33445566', 'V', '2011-08-11', 15, 'femenino', 'fernanda.castro@example.com', '0416-2223334', '', '', '', 'U.E. Privada Los Andes', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(31, 'Luis', 'Medina', 'cedula_escolar', '44556677', 'V', '2010-02-28', 16, 'masculino', 'luis.medina@example.com', '0424-4445556', '', '', '', 'Colegio Nacional Bolivariano', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(32, 'Valeria', 'Vargas', 'cedula_escolar', '55667788', 'V', '2012-12-05', 13, 'femenino', 'valeria.vargas@example.com', '0426-6667778', '', '', '', 'U.E. Instituto Técnico Jesús Obrero', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(33, 'Miguel', 'Rojas', 'cedula_escolar', '66778899', 'V', '2009-05-22', 17, 'masculino', 'miguel.rojas@example.com', '0412-3334445', '', '', '', 'Colegio San José', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(34, 'Paula', 'Cruz', 'cedula_escolar', '77889900', 'V', '2011-09-14', 14, 'femenino', 'paula.cruz@example.com', '0414-2223334', '', '', '', 'U.E. Fe y Alegría', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(35, 'Jesús', 'Gómez', 'cedula_escolar', '88990011', 'V', '2010-06-07', 16, 'masculino', 'jesus.gomez@example.com', '0424-1112223', '', '', '', 'U.E. Instituto Montessori', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(36, 'Andrea', 'Reyes', 'cedula_escolar', '99887766', 'V', '2013-03-19', 13, 'femenino', 'andrea.reyes@example.com', '0416-5556667', '', '', '', 'Colegio Nuestra Señora de Coromoto', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02'),
(37, 'Ricardo', 'Navarro', 'cedula_escolar', '10293847', 'V', '2009-11-30', 16, 'masculino', 'ricardo.navarro@example.com', '0426-9998887', '', '', '', 'U.E. Nacional Creación', 'bachillerato', 'Primer año', 'ninguna', NULL, 1, NULL, 'cedula', NULL, NULL, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `payment_method` enum('pago_movil','efectivo') NOT NULL,
  `payment_phone` varchar(20) DEFAULT NULL,
  `payment_bank` varchar(10) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(50) DEFAULT NULL,
  `payment_proof_path` varchar(500) DEFAULT NULL,
  `payment_amount_bs` decimal(10,2) NOT NULL,
  `bcv_rate` decimal(10,2) NOT NULL,
  `status` enum('pendiente','verificado','rechazado') NOT NULL DEFAULT 'pendiente',
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `payments`
--

INSERT INTO `payments` (`id`, `registration_id`, `payment_method`, `payment_phone`, `payment_bank`, `payment_date`, `payment_reference`, `payment_proof_path`, `payment_amount_bs`, `bcv_rate`, `status`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(9, 1, 'pago_movil', '0412-1863959', 'Mercantil', '2026-08-27', '6964', 'uploads/receipts/HC3-17878680299513_payment.jpg', 32.10, 36.50, 'pendiente', NULL, NULL, '2026-08-27 22:00:29', '2026-08-27 22:00:29'),
(10, 2, 'pago_movil', '0416-8867706', 'Banco Naci', '2026-08-27', '6920', 'uploads/receipts/HC3-17878682089459_payment.jpg', 47.49, 36.50, 'pendiente', NULL, NULL, '2026-08-27 22:03:28', '2026-08-27 22:03:28'),
(15, 9, 'efectivo', '', '', '2026-08-24', '4587', 'uploads/receipts/9_payment.jpg', 16000.00, 850.00, 'pendiente', NULL, NULL, '2026-08-28 18:49:45', '2026-08-28 18:49:45'),
(16, 10, 'pago_movil', '0412-8719571', 'BBVA Provi', '2026-08-25', '4091', 'uploads/receipts/10_payment.jpg', 16000.00, 850.00, 'verificado', 'admin', '2026-08-28 16:49:33', '2026-08-28 19:12:50', '2026-08-28 20:49:33'),
(17, 11, 'efectivo', '', '', '2026-08-27', '5551', 'uploads/receipts/11_payment.jpg', 16000.00, 850.00, 'verificado', 'admin', '2026-08-28 16:49:27', '2026-08-28 19:14:25', '2026-08-28 20:49:27'),
(18, 12, 'pago_movil', '0414-2034588', 'Banesco', '2026-08-25', '2174', 'uploads/receipts/12_payment.jpg', 16000.00, 850.00, 'rechazado', 'admin', '2026-08-28 16:49:21', '2026-08-28 19:16:01', '2026-08-28 20:49:21'),
(19, 13, 'efectivo', '', '', '0000-00-00', '', NULL, 0.00, 0.00, 'verificado', 'admin', '2026-08-28 16:49:12', '2026-08-28 20:19:39', '2026-08-28 20:49:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `shirt_size` enum('S','M','L','XL') NOT NULL,
  `expectations` text DEFAULT NULL,
  `authorization_doc_path` varchar(500) DEFAULT NULL,
  `status` enum('pendiente','confirmado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `image_rights_accepted` tinyint(1) DEFAULT 1,
  `data_verified` tinyint(1) DEFAULT 1,
  `verified_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attendance_status` tinyint(1) DEFAULT 0,
  `attendance_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registrations`
--

INSERT INTO `registrations` (`id`, `registration_number`, `participant_id`, `event_id`, `category_id`, `shirt_size`, `expectations`, `authorization_doc_path`, `status`, `image_rights_accepted`, `data_verified`, `verified_by`, `created_at`, `updated_at`, `attendance_status`, `attendance_time`) VALUES
(1, 'HC3-17878680299513', 1, 3, 29, 'M', 'Quiero aprender y divertirme en el hackathon.', NULL, 'pendiente', 1, 1, NULL, '2026-08-27 22:00:29', '2026-08-27 22:00:29', 0, NULL),
(2, 'HC3-17878682089459', 2, 3, 28, 'L', 'Quiero aprender y divertirme en el hackathon.', NULL, 'pendiente', 1, 1, NULL, '2026-08-27 22:03:28', '2026-08-27 22:03:28', 0, NULL),
(9, 'HC6-17879429853799', 12, 6, 37, 'L', 'Quiero aprender y divertirme en el hackathon.', NULL, 'pendiente', 1, 1, NULL, '2026-08-28 18:49:45', '2026-08-28 18:49:45', 0, NULL),
(10, 'HC8-17879443706712', 13, 8, 41, 'S', 'Quiero aprender y divertirme en el hackathon.', NULL, 'confirmado', 1, 1, 'admin', '2026-08-28 19:12:50', '2026-08-28 20:49:33', 0, NULL),
(11, 'HC8-17879444658459', 14, 8, 41, 'L', 'Quiero aprender y divertirme en el hackathon.', NULL, 'confirmado', 1, 1, 'admin', '2026-08-28 19:14:25', '2026-08-28 20:49:27', 0, NULL),
(12, 'HC8-17879445618975', 15, 8, 41, 'M', 'Quiero aprender y divertirme en el hackathon.', NULL, 'cancelado', 1, 1, 'admin', '2026-08-28 19:16:01', '2026-08-28 20:49:21', 0, NULL),
(13, 'HC8-17879483783111', 17, 8, 41, 'M', 'dasdasdasdas', NULL, 'confirmado', 1, 1, 'admin', '2026-08-28 20:19:39', '2026-08-28 20:49:12', 0, NULL),
(14, 'HC13-17882012426276', 18, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(15, 'HC13-17882012425829', 19, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(16, 'HC13-17882012425134', 20, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(17, 'HC13-17882012426870', 21, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(18, 'HC13-17882012424883', 22, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(19, 'HC13-17882012427415', 23, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(20, 'HC13-17882012421897', 24, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(21, 'HC13-17882012424481', 25, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(22, 'HC13-17882012426371', 26, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(23, 'HC13-17882012427294', 27, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(24, 'HC13-17882012428603', 28, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(25, 'HC13-17882012421645', 29, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(26, 'HC13-17882012424568', 30, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(27, 'HC13-17882012424144', 31, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(28, 'HC13-17882012425794', 32, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(29, 'HC13-17882012428273', 33, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(30, 'HC13-17882012425432', 34, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(31, 'HC13-17882012424595', 35, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(32, 'HC13-17882012424638', 36, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL),
(33, 'HC13-17882012423035', 37, 13, 48, 'M', NULL, NULL, 'confirmado', 1, 1, NULL, '2026-08-31 18:34:02', '2026-08-31 18:34:02', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registrations_old`
--

CREATE TABLE `registrations_old` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
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
  `payment_method` enum('pago_movil','efectivo') DEFAULT NULL,
  `payment_phone` varchar(20) DEFAULT NULL,
  `payment_bank` varchar(10) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_reference` varchar(4) DEFAULT NULL,
  `payment_proof_path` varchar(500) DEFAULT NULL,
  `payment_amount_bs` decimal(10,2) DEFAULT NULL,
  `bcv_rate` decimal(10,2) DEFAULT NULL,
  `payment_verified` tinyint(4) NOT NULL DEFAULT 0,
  `image_rights_accepted` tinyint(1) DEFAULT 1,
  `data_verified` tinyint(1) DEFAULT 1,
  `verified_by` varchar(255) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `event_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registrations_old`
--

INSERT INTO `registrations_old` (`id`, `registration_number`, `full_name`, `last_name`, `document_type`, `document_number`, `nationality`, `birth_date`, `age`, `gender`, `email`, `phone`, `address`, `state`, `city`, `institution`, `education_level`, `grade`, `category`, `microbit_experience`, `expectations`, `shirt_size`, `document_photo_path`, `is_minor`, `guardian_name`, `guardian_doc_type`, `guardian_document`, `guardian_email`, `guardian_phone`, `authorization_doc_path`, `payment_method`, `payment_phone`, `payment_bank`, `payment_date`, `payment_reference`, `payment_proof_path`, `payment_amount_bs`, `bcv_rate`, `payment_verified`, `image_rights_accepted`, `data_verified`, `verified_by`, `status`, `created_at`, `updated_at`, `event_id`, `category_id`) VALUES
(23, 'HC2025-17594359334118', 'henry', 'Castillo', 'cedula', 'V-26378984', 'V', '2012-02-29', 13, 'masculino', 'henryvalentincastillo@gmail.com', '+58 414-5687923', 'ejemplo', 'portuguesa', 'acarigua', 'iutepi', 'primaria', '3er_grado', 'Junior', 'basica', '', 'S', 'uploads/documents/HC2025-17594359334118_document.png', 1, 'henry', 'cedula', 'V-27367498', 'valenhencastillo@gmail.com', '+58 414-5989209', '', 'pago_movil', '+58 414-5898278', '0105', '2025-03-23', '3902', 'uploads/receipts/HC2025-17594359334118_payment.png', 3626.07, 181.30, 1, 1, 1, NULL, 0, '2025-10-02 20:12:13', '2025-11-27 15:25:32', NULL, NULL),
(24, 'HC2025-17594369586811', 'Henry', 'Castillo', 'cedula', 'V-26379845', 'V', '2012-02-09', 13, 'masculino', 'henryvalentincastillo@gmail.com', '+58 414-5687923', 'ejemplo', 'nueva_esparta', 'juan_griego', 'iutepi', 'primaria', '4to_grado', 'Junior', 'basica', '', 'M', 'uploads/documents/HC2025-17594369586811_document.png', 1, 'Henry', 'cedula', 'V-29300199', 'valenhencastillo@gmail.com', '+58 414-5989209', '', 'efectivo', '', '', '2025-10-02', '', '', NULL, NULL, 0, 1, 1, NULL, 0, '2025-10-02 20:29:18', '2025-10-07 20:02:30', NULL, NULL),
(25, 'HC2025-17594374266678', 'sadsa', 'asd', 'cedula', 'V-25697777', 'V', '2015-01-02', 10, 'masculino', 'j.l.marrufo23@gmail.com', '+58 424-5928624', 'asdsadsad', 'portuguesa', 'acarigua', 'Tecnocleveland', 'primaria', '1er_grado', 'Junior', 'avanzada', 'asdsadsadsa', 'S', 'uploads/documents/HC2025-17594374266678_document.png', 1, 'asdasdsa', 'cedula', 'V-12334455', 'as@gmail.com', '+58 131-2312312', '', 'pago_movil', '+58 424-5928624', '0102', '2025-10-02', '1234', 'uploads/receipts/HC2025-17594374266678_payment.png', 3626.07, 181.30, 1, 1, 1, NULL, 0, '2025-10-02 20:37:06', '2025-10-07 20:02:18', NULL, NULL),
(26, 'HC2025-17594923347361', 'Simon', 'Leal', 'cedula', 'V-31313131', 'V', '2014-01-28', 11, 'masculino', 'a@g.com', '+58 424-5667291', 'oooo', 'portuguesa', 'guanare', 'Angel de la Guarda', 'primaria', '6to_grado', 'Junior', 'basica', 'quiero ganar', 'S', 'uploads/documents/HC2025-17594923347361_document.jpg', 1, 'Jehova Leal', 'cedula', 'V-17797941', 'jehova20@gmail.com', '+58 412-3067291', '', 'pago_movil', '+58 412-3067291', '0102', '2025-10-03', '8789', 'uploads/receipts/HC2025-17594923347361_payment.jpg', 3662.74, 183.14, 0, 1, 1, NULL, 0, '2025-10-03 11:52:14', '2026-02-10 15:03:28', NULL, NULL),
(27, 'HC2025-17598677436682', 'asdsadsa', 'sadasdasd', 'cedula', 'V-25697776', 'V', '2015-01-01', 10, 'masculino', 'j.l.marrufo213@gmail.com', '+58 424-5928624', 'asdsad', 'portuguesa', 'araure', 'Tecnocleveland', 'primaria', '1er_grado', 'Junior', 'ninguna', 'asd', 'M', 'uploads/documents/HC2025-17598677436682_document.png', 1, 'asdsadas', 'cedula', 'V-12321312', 'as@gmail.com', '+58 131-2312312', '', 'pago_movil', '+58 424-5928624', '0169', '2025-10-07', '1234', 'uploads/receipts/HC2025-17598677436682_payment.png', 3745.79, 187.29, 1, 1, 1, NULL, 0, '2025-10-07 20:09:03', '2025-11-27 15:25:34', NULL, NULL),
(28, 'HC2025-17601353168616', 'Juan Pablo ', 'Andrade escalona ', 'cedula', 'V-34388546', 'V', '2010-08-28', 15, 'masculino', 'juanmx89012@gmail.com', '+58 412-2006393', 'Residencias doña encarnación piso 3 número 33 ', 'portuguesa', 'araure', 'Gran mariscal de Ayacucho ', 'bachillerato', '4to_año', 'Senior', 'basica', 'Saber más de la parte robótica en cuanto a programación y desarrollar mucho más mi logica', 'S', 'uploads/documents/HC2025-17601353168616_document.png', 1, 'Rohini escalona ', 'cedula', 'V-20812869', 'rmdasesores@gmail.com', '+58 412-1581100', '', 'pago_movil', '+58 412-1581100', '0134', '2025-10-10', '4106', 'uploads/receipts/HC2025-17601353168616_payment.png', 3904.98, 195.25, 1, 1, 1, NULL, 1, '2025-10-10 22:28:36', '2026-02-20 14:10:29', NULL, NULL),
(29, 'HC2025-17618611536935', 'Scarleth Gabriela ', 'Medina Hernández ', 'cedula', 'V-34223545', 'V', '2011-03-21', 14, 'femenino', 'scarleth.gabriela1120@gmail.com', '+58 414-3734161', 'Pimpinela, sector Micheli, casa  26-322', 'portuguesa', 'acarigua', 'Fray Miguel de Olivares ', 'bachillerato', '3er_año', 'Junior', 'basica', '', 'M', 'uploads/documents/HC2025-17618611536935_document.jpg', 1, 'Yexsi Hernández ', 'cedula', 'V-16596404', 'yexsimarieglis@gmail.com', '+58 424-5061321', '', 'pago_movil', '+58 424-5061321', '0114', '2025-10-30', '3600', 'uploads/receipts/HC2025-17618611536935_payment.jpg', 4472.92, 223.65, 1, 1, 1, NULL, 1, '2025-10-30 21:52:33', '2026-02-20 14:10:52', NULL, NULL),
(30, 'HC2025-17649398812603', 'MERLUIS ENRIQUE', 'USEA ESCALONA', 'cedula', 'V-25035132', 'V', '2010-05-15', 15, 'masculino', 'merluisenriqueusea@gmail.com', '+58 412-6781036', 'Acarigua', 'portuguesa', 'acarigua', 'Colegio', 'primaria', '6to_grado', 'Senior', 'ninguna', '', 'M', 'uploads/documents/HC2025-17649398812603_document.jpg', 1, 'MERLUIS USEA', 'cedula', 'V-25035132', 'merluisenriqueusea@gmail.com', '+58 412-6781036', '', 'pago_movil', '+58 412-6781036', '0102', '2025-12-05', '1234', 'uploads/receipts/HC2025-17649398812603_payment.jpg', 5097.41, 254.87, 0, 1, 1, NULL, 0, '2025-12-05 13:04:41', '2025-12-05 13:21:09', NULL, NULL),
(31, 'HC2025-17655801761619', 'Sebastian Noel', 'Segueri Ramos', 'cedula', 'V-36472885', 'V', '2013-09-18', 12, 'masculino', 'marianoels182113@gmail.com', '+58 412-7671809', 'Urb El Pilar calle los Jabillos casa 111', 'portuguesa', 'acarigua', 'Colegio Ángel de la Guarda', 'bachillerato', '1er_año', 'Junior', 'intermedia', 'Espero lograr un puesto y también acumular experiencia ', 'S', 'uploads/documents/HC2025-17655801761619_document.jpeg', 1, 'María Ramos ', 'cedula', 'V-20273925', 'marianoels182113@gmail.com', '+58 412-7671809', '', 'pago_movil', '+58 414-7671809', '0134', '2025-12-12', '0688', 'uploads/receipts/HC2025-17655801761619_payment.png', 5415.79, 270.79, 1, 1, 1, NULL, 1, '2025-12-12 22:56:16', '2026-02-20 14:11:12', NULL, NULL),
(32, 'HC2025-17697138873135', 'Marco Antonio', 'Perez Dreka', 'cedula', 'V-37284086', 'V', '2014-09-01', 11, 'masculino', 'meperezcolocolo@gmail.com', '+58 412-3024240', 'Urb 5 Diciembre Ave Ppal Casa Nro 25-60', 'portuguesa', 'araure', 'U.E.C.P Alejandro Humboldt', 'primaria', '6to_grado', 'Junior', 'intermedia', 'ganar una laptop o cualquiera de esos premios', 'L', 'uploads/documents/HC2025-17697138873135_document.pdf', 1, '', '', '', '', '', '', 'pago_movil', '+58 412-3024240', '0102', '2026-01-29', '8382', 'uploads/receipts/HC2025-17697138873135_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-01-29 19:11:27', '2026-02-20 14:11:27', NULL, NULL),
(33, 'HC2025-17698195432399', 'Xavier Alejandro ', 'Castañeda Jaimes', 'cedula', 'V-36623131', 'V', '2014-07-31', 11, 'masculino', 'xaviercasta3107@gmail.com', '+58 424-5003445', 'Roca del llano araure', 'portuguesa', 'araure', 'U.E Colegio Alejandro Humboltd ', 'primaria', '6to_grado', 'Junior', 'basica', '', 'M', 'uploads/documents/HC2025-17698195432399_document.jpg', 1, '', '', '', '', '', '', 'pago_movil', '+58 414-3524358', '0105', '2026-01-30', '8631', 'uploads/receipts/HC2025-17698195432399_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-01-31 00:32:23', '2026-02-20 14:11:42', NULL, NULL),
(34, 'HC2025-17699804043188', 'Juan Diego', 'Mantilla Perozo ', 'cedula', 'V-34322353', 'V', '2011-06-24', 14, 'masculino', 'juandiegomantillaperozo@gmail.com', '+58 412-0543439', 'Calle 52 con Carrera 21A residencia Casares ', 'lara', 'barquisimeto', 'Colegio ilustre americano Barquisimeto', 'bachillerato', '3er_año', 'Junior', 'intermedia', 'Me gustaría poder compartir mi pasión por la programación con personas similares en un evento cómo este', 'S', 'uploads/documents/HC2025-17699804043188_document.jpg', 1, '', '', '', '', '', '', 'pago_movil', '+58 414-2698301', '0104', '2026-02-01', '9855', 'uploads/receipts/HC2025-17699804043188_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-01 21:13:24', '2026-02-20 14:11:58', NULL, NULL),
(35, 'HC2025-17700440861710', 'Dulma', 'Riva', 'cedula', 'V-25644444', 'V', '2010-02-01', 16, 'masculino', 'josemarrufo.tecno@gmail.com', '+58 424-5928624', 'acarigua', 'portuguesa', 'acarigua', 'Tecno Cleveland', 'universidad', 'licenciatura___8vo_semestre', 'Senior', 'basica', 'Ganar', 'L', 'uploads/documents/HC2025-17700440861710_document.png', 1, '', '', '', '', '', '', 'pago_movil', '+58 424-9528624', '0134', '2026-02-02', '9876', 'uploads/receipts/HC2025-17700440861710_payment.png', 7405.09, 370.25, 0, 1, 1, NULL, 0, '2026-02-02 14:54:46', '2026-02-02 14:57:07', NULL, NULL),
(36, 'HC2025-17703040964184', 'alexis', 'caceres', 'cedula', 'V-27693822', 'V', '2016-06-23', 9, 'masculino', 'mastercaceresxt12@gmail.com', '+58 426-3545200', 'dasdsadsad', 'portuguesa', 'acarigua', 'dsadsad', 'primaria', '5to_grado', 'Junior', 'basica', 'dasdasdasdsad', 'M', 'uploads/documents/HC2025-17703040964184_document.png', 1, 'dasdsad', 'cedula', 'V-27693822', 'dasdsad@gmail.com', '+58 426-3545200', '', 'efectivo', NULL, NULL, NULL, NULL, '', NULL, NULL, 0, 1, 1, NULL, 0, '2026-02-05 15:08:16', '2026-02-05 18:01:37', NULL, NULL),
(37, 'HC2025-17703139079503', 'Juan David', 'Cordero Izquierdo ', 'cedula', 'V-34683677', 'V', '2012-05-03', 13, 'masculino', 'juandcordero2012@gmail.com', '+58 424-5560757', 'Acarigua', 'portuguesa', 'acarigua', 'Alejandro Humboldt', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Ganar', 'M', 'uploads/documents/HC2025-17703139079503_document.jpeg', 1, 'Buzeina Izquierdo ', 'cedula', 'V-17600118', 'rosmeizquierdo85@gmail.com', '+58 424-5560757', '', 'pago_movil', NULL, '0105', '2026-02-12', '6503', 'uploads/receipts/juan_cordero_payment.jpg', 3933.00, NULL, 1, 1, 1, NULL, 1, '2026-02-05 17:51:47', '2026-02-20 14:12:05', NULL, NULL),
(38, 'HC2025-17703406708391', 'Angel Gabriel ', 'Dos santos farinha ', 'cedula', 'V-37306005', 'V', '2014-06-09', 11, 'masculino', 'anamariafarinha75@hotmail.com', '+58 424-5981889', 'Urb el pilar calle los Chaguaramos casa 79 ', 'portuguesa', 'araure', 'Alejandro Humboldt ', 'primaria', '6to_grado', 'Junior', 'basica', '', 'M', 'uploads/documents/HC2025-17703406708391_document.jpg', 1, 'Ana farinha ', 'cedula', 'V-15092916', 'anamariafarinha75@hotmail.com', '+58 424-5981889', '', 'pago_movil', '+58 424-5981889', '0171', '2026-02-06', '7293', 'uploads/receipts/HC2025-17703406708391_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-06 01:17:50', '2026-02-20 14:15:34', NULL, NULL),
(39, 'HC2025-17703422408079', 'Aarón Moisés', 'Galindez Galindez Silva', 'cedula', 'V-33922655', 'V', '2011-12-14', 14, 'masculino', 'aaronmoisesgalindez.silva@gmail.com', '+58 412-4870533', 'Urbanización Ruezga Norte Sector2 vereda9 casa 8', 'lara', 'barquisimeto', 'U. E. Colegio Ilustre Americano', 'bachillerato', '3er_año', 'Junior', 'basica', 'Adquirir conocimientos y experiencia sobre la micro:bit', 'S', 'uploads/documents/HC2025-17703422408079_document.jpg', 1, 'Eduing Galindez', 'cedula', 'V-16112909', 'eduingalin@gmail.com', '+58 412-0580479', '', 'pago_movil', '+58 412-0580479', '0115', '2026-02-06', '0004', 'uploads/receipts/HC2025-17703422408079_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-06 01:44:00', '2026-02-20 14:16:23', NULL, NULL),
(40, 'HC2025-17703888607460', 'Henry', 'Castillo', 'cedula', 'V-26379854', 'V', '2010-10-10', 15, 'masculino', 'henryvalentincastillo@gmail.com', '+58 414-5687923', 'PRUEBA', 'portuguesa', 'acarigua', 'PRUEBA', 'primaria', '2do_grado', 'Senior', 'intermedia', '', 'M', 'uploads/documents/HC2025-17703888607460_document.png', 1, 'ESTO ES UNA PRUEBA', 'cedula', 'V-5687923', 'valenhencastillo@gmail.com', '+58 414-5789514', '', 'pago_movil', '+58 414-5687923', '0157', '2026-02-06', '1234', 'uploads/receipts/HC2025-17703888607460_payment.png', 7622.20, 381.11, 0, 1, 1, NULL, 0, '2026-02-06 14:41:00', '2026-02-06 15:15:29', NULL, NULL),
(41, 'HC2025-17703942419500', 'ESTO ES', 'UNA PRUEBA', 'cedula', 'V-12345678', 'V', '2006-01-01', 20, 'masculino', 'henryvalentincastillo@gmail.com', '+58 414-5687923', 'ESTO ES UNA PRUEBA', 'merida', 'el_vigía', 'PRUEBA', 'bachillerato', '1er_año', 'Senior', 'ninguna', 'PRUEBA', 'S', 'uploads/documents/HC2025-17703942419500_document.png', 0, '', '', '', '', '', '', 'pago_movil', '+58 123-4567895', '0168', '2026-02-06', '1234', 'uploads/receipts/HC2025-17703942419500_payment.png', 7622.20, 381.11, 0, 1, 1, NULL, 0, '2026-02-06 16:10:41', '2026-02-06 19:45:46', NULL, NULL),
(42, 'HC2025-17704032089719', 'Jesus David ', 'Colmenares Oberto ', 'cedula', 'V-33209841', 'V', '2010-02-19', 15, 'masculino', 'colmerdavid295@gmail.com', '+58 412-7744234', 'Urbanización villa antigua ARAURE ', 'portuguesa', 'araure', 'Colegio gran mariscal de Ayacucho ', 'bachillerato', '4to_año', 'Senior', 'intermedia', 'Ganar ', 'M', 'uploads/documents/HC2025-17704032089719_document.jpg', 1, 'Haydee Oberto ', 'cedula', 'V-9567470', 'haydeeoberto2@gmail.com', '+58 412-6723523', '', 'pago_movil', '+58 412-6723523', '0102', '2026-02-06', '8502', 'uploads/receipts/HC2025-17704032089719_payment.jpg', 7622.20, 381.11, 1, 1, 1, NULL, 1, '2026-02-06 18:40:08', '2026-02-20 14:16:42', NULL, NULL),
(43, 'HC2025-17704231117870', 'Alan Leonardo ', 'Herrera Rodríguez ', 'cedula', 'V-37471838', 'V', '2016-09-29', 9, 'masculino', 'aadrirb16@gmail.com', '+58 414-5672168', 'Urb. Roca del Llano 2-08.', 'portuguesa', 'araure', 'Colegio Alejandro Humboldt ', 'primaria', '4to_grado', 'Junior', 'ninguna', 'Aprender más sobre la programación de Micro:bit', 'S', 'uploads/documents/HC2025-17704231117870_document.pdf', 1, 'Adriana Rodríguez ', 'cedula', 'V-17092846', 'aadrirb16@gmail.com', '+58 414-5672168', '', 'pago_movil', '+58 042-4541769', '0114', '2026-02-06', '7618', 'uploads/receipts/HC2025-17704231117870_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-07 00:11:51', '2026-02-20 14:17:14', NULL, NULL),
(44, 'HC2025-17704946793732', 'Carlos Jesus ', 'Peñaloza Contreras ', 'cedula', 'V-33349486', 'V', '2008-12-05', 17, 'masculino', 'carlosjpc7271@gmail.com', '+58 424-5636958', 'Urbanización El Este,manzana 2, calle 2, casa número 13', 'portuguesa', 'acarigua', 'Liceo Eduardo Chollet Boada', 'bachillerato', '5to_año', 'Senior', 'intermedia', 'Experiencia, mejorar mis habilidades usando microbit', 'M', 'uploads/documents/HC2025-17704946793732_document.jpg', 1, 'Luzmary Alexandra Contreras Contreras ', 'cedula', 'V-12486918', 'luzmarylacc@gmail.com', '+58 414-0804458', '', 'pago_movil', '+58 414-0804458', '0102', '2026-02-07', '8727', 'uploads/receipts/HC2025-17704946793732_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-07 20:04:39', '2026-02-20 14:18:02', NULL, NULL),
(45, 'HC2025-17704988501184', 'Isabella', 'Contreras De Dea', 'cedula', 'V-36455726', 'V', '2014-09-19', 11, 'femenino', 'micontrema@gmail.com', '+58 414-1439493', 'Urbanización Bosque de camoruco micro 8 casa 8-16', 'portuguesa', 'acarigua', 'Alejandro Humboldt ', 'primaria', '6to_grado', 'Junior', 'basica', 'Competir, aprender y divertirme ', 'M', 'uploads/documents/HC2025-17704988501184_document.jpg', 1, 'Miguel Contreras ', 'cedula', 'V-16447925', 'micontrema@gmail.com', '+58 414-1439493', '', 'pago_movil', '+58 414-1439493', '0134', '2026-02-07', '6063', 'uploads/receipts/HC2025-17704988501184_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-07 21:14:10', '2026-02-20 14:18:48', NULL, NULL),
(46, 'HC2025-17706837001538', 'Giovanna Alejandra ', 'Perla Galvis ', 'cedula', 'V-33778455', 'V', '2011-04-18', 14, 'femenino', 'gioperla9@gmail.com', '+58 412-3433774', 'Urbanización Valle Fresco II ', 'portuguesa', 'araure', 'U.E.P Colegio Alejandro Humboldt ', 'bachillerato', '3er_año', 'Junior', 'basica', '', 'L', 'uploads/documents/HC2025-17706837001538_document.jpg', 1, 'Giovanna Galvis ', 'cedula', 'V-15924361', 'giovannagalvis05@gmail.com', '+58 412-0568392', '', 'pago_movil', '+58 412-0568392', '0134', '2026-02-09', '7153', 'uploads/receipts/HC2025-17706837001538_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-10 00:35:00', '2026-02-20 14:19:21', NULL, NULL),
(47, 'HC2025-17707456753791', 'MATTHEW ZACH', 'BETANCOURT HERNANDEZ', 'cedula', 'V-34687380', 'V', '2012-10-21', 13, 'masculino', 'zach.betancourt21@gmail.com', '+58 424-5814430', 'Urb. Durigua 3, vereda 26 nro. 18', 'portuguesa', 'acarigua', 'Dr. Daniel Camejo Acosta', 'bachillerato', '2do_año', 'Junior', 'basica', 'Aprender y compartir con otros participantes', 'M', 'uploads/documents/HC2025-17707456753791_document.jpeg', 1, 'Zulimar Hernández', 'cedula', 'V-13703672', 'zulimarhernandez@hotmail.com', '+58 414-2602797', '', 'efectivo', NULL, NULL, '2026-02-10', NULL, '', NULL, NULL, 1, 1, 1, NULL, 1, '2026-02-10 17:47:55', '2026-02-20 15:06:09', NULL, NULL),
(48, 'HC2025-17708290154585', 'Juan David', 'Ortega Aguirre', 'cedula', 'V-34534995', 'V', '2011-07-28', 14, 'masculino', 'juandavidortega28072011@gmail.com', '+58 412-6043860', 'Urb Llano Alto Conjunto Azucena Casa 48', 'portuguesa', 'acarigua', 'UECP Alejandro Humboldt ', 'bachillerato', '3er_año', 'Junior', 'basica', '', 'M', 'uploads/documents/HC2025-17708290154585_document.jpeg', 1, 'Jhenny Aguirre ', 'cedula', 'V-11546404', 'jhennyaguirre14@gmail.com', '+58 424-5626775', '', 'pago_movil', '+58 424-5626775', '0105', '2026-02-11', '3451', 'uploads/receipts/HC2025-17708290154585_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-11 16:56:55', '2026-02-20 14:20:44', NULL, NULL),
(49, 'HC2025-17709161813479', 'Mathias Alexi', 'Blanco Chávez', 'cedula', 'V-36993243', 'V', '2016-03-24', 9, 'masculino', 'mathiasblanco631@gmail.com', '+58 424-5432104', 'Villa Araure 1 sector eucaliptos \"A\" casa #62-61', 'portuguesa', 'araure', 'Escuela parroquial Jesús Horizonte y Camino ', 'primaria', '5to_grado', 'Junior', 'intermedia', 'Que será un súper evento de mucho aprendizaje ', 'S', 'uploads/documents/HC2025-17709161813479_document.jpg', 1, 'María Laura chavez ', 'cedula', 'V-24588672', 'lauramariaprincipe3@gmail.com', '+58 424-5432104', '', 'pago_movil', '+58 424-5432104', '0102', '2026-02-12', '9440', 'uploads/receipts/HC2025-17709161813479_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-12 17:09:41', '2026-02-20 14:21:36', NULL, NULL),
(50, 'HC2025-17709219503494', 'Sophia Cristina ', 'Roa Urriola ', 'cedula', 'V-34683242', 'V', '2012-11-16', 13, 'femenino', 'johannacristinaurriola31@gmail.com', '+58 414-5549409', 'Sector campo lindo calle 26 y 27 resd. Jacky apto. 2 piso 1 ', 'portuguesa', 'acarigua', 'Unidad educativa privada Colegio Alejandro Humboldt ', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Me gustaría aprender de esta experiencia y ganar la competencia ', 'M', 'uploads/documents/HC2025-17709219503494_document.jpg', 1, 'Johanna Cristina Urriola ', 'cedula', 'V-17944628', 'johannacristinaurriola31@gmail.com', '+58 414-5549409', '', 'pago_movil', '+58 414-5549409', '0105', '2026-02-12', '7972', 'uploads/receipts/HC2025-17709219503494_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-12 18:45:50', '2026-02-20 14:22:42', NULL, NULL),
(51, 'HC2025-17709259865801', 'JESUS ALEJANDRO', 'SIFONTES ESPINOZA', 'cedula', 'V-33485592', 'V', '2010-04-23', 15, 'masculino', 'chusifontes2304@gmail.com', '+58 424-5101745', 'URB LLANO ALTO CONJUNTO 12 CASA 49 ARAURE PORTUGUESA', 'portuguesa', 'araure', 'uecp Alejandro Humboldt', 'bachillerato', '4to_año', 'Senior', 'basica', 'mejorar mi habilidad con la programacion y realizar un buen desempeño y ganar un premio de ser posible ', 'L', 'uploads/documents/HC2025-17709259865801_document.jpeg', 1, 'MIGNIDHY CAROLINA ESPINOZA VELAZCO', 'cedula', 'V-14981112', 'MIGNIDHY33@GMAIL.com', '+58 414-5577961', '', 'pago_movil', '+58 414-5577961', '0134', '2026-02-12', '2040', 'uploads/receipts/HC2025-17709259865801_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-12 19:53:06', '2026-02-20 14:23:14', NULL, NULL),
(52, 'HC2025-17709278028382', 'Tiana Isabella', 'Pérez Rivero', 'cedula', 'V-36858406', 'V', '2015-12-22', 10, 'femenino', 'tianaisabellape@gmail.com', '+58 412-7819206', 'Urbanización altos de la galera conjunto roble 1 casa 56', 'portuguesa', 'araure', 'Colegio Alejadro Humboldt', 'primaria', '5to_grado', 'Junior', 'ninguna', '', 'M', 'uploads/documents/HC2025-17709278028382_document.jpeg', 1, 'Roxanny Rivero', 'cedula', 'V-25347385', 'roxannyrivero9@gmail.com', '+58 412-7819206', '', 'pago_movil', '+58 412-7819206', '0134', '2026-02-12', '9897', 'uploads/receipts/HC2025-17709278028382_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-12 20:23:22', '2026-02-20 14:23:48', NULL, NULL),
(53, 'HC2025-17709984237666', 'Camila', 'Mavarez Borges', 'cedula', 'V-37173519', 'V', '2013-07-15', 12, 'femenino', 'mavarezborgescamila@gmail.com', '+58 412-1352171', 'Acarigua 3301, Portuguesa', 'portuguesa', 'acarigua', 'Alejandro Humboldt ', 'bachillerato', '1er_año', 'Junior', 'intermedia', 'Espero divertirme mucho y ser la ganadora', 'S', 'uploads/documents/HC2025-17709984237666_document.jpg', 1, 'Ariana Vanessa Borges Perdomo', 'cedula', 'V-20809386', 'arianavanessab@gmail.com', '+58 424-5061168', '', 'pago_movil', '+58 412-5224459', '0172', '2026-02-13', '1981', 'uploads/receipts/HC2025-17709984237666_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-13 16:00:23', '2026-02-20 14:24:24', NULL, NULL),
(54, 'HC2025-17710861137861', 'FABIAN EDUARDO', 'SILVA FUENTES', 'cedula', 'V-36085222', 'V', '2014-06-12', 11, 'masculino', 'ffaby1206@gmail.com', '+58 416-2199068', 'Urb Misia Amelia casa 68 Araure Portuguesa', 'portuguesa', 'araure', 'Colegio Alejandro Humboldt', 'primaria', '6to_grado', 'Junior', 'basica', 'Experiencia ', 'M', 'uploads/documents/HC2025-17710861137861_document.jpeg', 1, 'Efigenia Fuentes ', 'cedula', 'V-14677837', 'efibasti@gmail.com', '+58 414-5574164', '', 'pago_movil', '+58 414-5574164', '0108', '2026-02-14', '7973', 'uploads/receipts/HC2025-17710861137861_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-14 16:21:53', '2026-02-20 14:25:08', NULL, NULL),
(55, 'HC2025-17711186607802', 'Yibran David ', 'Contreras Zavarce ', 'cedula', 'V-34636858', 'V', '2012-10-27', 13, 'masculino', 'briggithzavarce@gmail.com', '+58 426-2472493', 'Urbanización Ezequiel Zamora calle 1', 'portuguesa', 'acarigua', 'Liceo Aristóbulo isturiz ', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Vivir la experiencia y aprender mucho mas ', 'M', 'uploads/documents/HC2025-17711186607802_document.jpg', 1, 'Briggith Zavarce ', 'cedula', 'V-21394887', 'briggithzavarce@gmail.com', '+58 426-2472493', '', 'pago_movil', '+58 416-4120164', '0102', '2026-02-14', '8329', 'uploads/receipts/HC2025-17711186607802_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-15 01:24:20', '2026-02-20 14:25:52', NULL, NULL),
(56, 'HC2025-17712524083608', 'MARCO AURELIO', 'RIVERO MARTINEZ', 'cedula', 'V-36610673', 'V', '2014-08-25', 11, 'masculino', 'marcourelioriveromartinez@gmail.com', '+58 414-5648586', 'edificio la arboleda 2 piso, apto 3-9 araure ', 'portuguesa', 'araure', 'colegio angel de la guarda', 'primaria', '6to_grado', 'Junior', 'basica', 'participar', 'S', 'uploads/documents/HC2025-17712524083608_document.jpeg', 1, 'CARMEN YOHANA MARTINEZ', 'cedula', 'V-15693502', 'caryomar@gmail.com', '+58 416-1363662', '', 'pago_movil', '+58 416-1363662', '0102', '2026-02-16', '9998', 'uploads/receipts/HC2025-17712524083608_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-16 14:33:28', '2026-02-20 14:26:20', NULL, NULL),
(57, 'HC2025-17713397306080', 'Juan David ', 'Yepez Alvarez ', 'cedula', 'V-36078490', 'V', '2013-09-14', 12, 'masculino', 'juanyepeztecnocleveland@gmail.com', '+58 416-3998243', 'Urb:Prados del sol\r\nSector: Mercantil ', 'portuguesa', 'araure', 'U.E.P. Colegio Ezequiel Zamora ', 'bachillerato', '1er_año', 'Junior', 'intermedia', 'Espero concretar mis conocimientos aprendidos.', 'M', 'uploads/documents/HC2025-17713397306080_document.jpg', 1, 'Yesenia Alvarez', 'cedula', 'V-16294090', 'Yesealverez211@gmail', '+58 416-6516060', '', 'pago_movil', '+58 416-6516060', '0102', '2026-02-17', '6034', 'uploads/receipts/HC2025-17713397306080_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-17 14:48:50', '2026-02-20 14:26:49', NULL, NULL),
(58, 'HC2025-17714181676398', 'Jorge luis Jeremias ', 'Castillo Romero ', 'cedula', 'V-36393982', 'V', '2014-02-18', 12, 'masculino', 'jorgeluisjeremiasc@gmail.com', '+58 414-5640850', 'Urbanización los Robles 2 calle 12 casa 428 ', 'portuguesa', 'araure', 'Unidad Educativa Privada Alejandro Humboldt', 'primaria', '6to_grado', 'Junior', 'basica', 'Mejorar y aprender ', 'S', 'uploads/documents/HC2025-17714181676398_document.jpg', 1, 'Liliana romero', 'cedula', 'V-19542751', 'arieslin890@hotmail.com', '+58 424-5934668', '', 'pago_movil', '+58 426-5529673', '0134', '2026-02-18', '4350', 'uploads/receipts/HC2025-17714181676398_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 12:36:07', '2026-02-20 14:27:19', NULL, NULL),
(59, 'HC2025-17714283594072', 'Gabriel alejandro', 'Pulido Alvarado ', 'cedula', 'V-36740650', 'V', '2015-08-13', 10, 'masculino', 'ynescaalvarado@gmail.com', '+58 414-9545992', 'Urbanización molinos IV casa Nro 21', 'portuguesa', 'araure', 'Colegio alejandro Humboldt ', 'primaria', '5to_grado', 'Junior', 'ninguna', '', 'M', 'uploads/documents/HC2025-17714283594072_document.jpg', 1, 'Ynes carolina Alvarado contreras ', 'cedula', 'V-13226913', 'ynescaalvarado@gmail.com', '+58 414-9545992', '', 'pago_movil', '+58 414-9545992', '0102', '2026-02-18', '5856', 'uploads/receipts/HC2025-17714283594072_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 0, '2026-02-18 15:25:59', '2026-02-20 23:14:45', NULL, NULL),
(60, 'HC2025-17714426776780', 'Matteo Santiago', 'Vinciguerra Marecos', 'cedula', 'V-33778571', 'V', '2011-05-30', 14, 'masculino', 'matteovinciguerra05@gmail.com', '+58 424-5752654', 'Urbanización las palmas, segunda etapa, calle 4, casa 424', 'portuguesa', 'araure', 'Unidad Educativa Colegio Doctor Daniel Camejo Acosta', 'bachillerato', '3er_año', 'Junior', 'intermedia', 'Tener mayor conocimiento y experiencia ', 'M', 'uploads/documents/HC2025-17714426776780_document.jpg', 1, 'Jenny Gisela Marecos', 'cedula', 'V-15547176', 'jgmarecos33@gmail.com', '+58 424-5957790', '', 'pago_movil', '+58 424-5752654', '0115', '2026-02-18', '0789', 'uploads/receipts/HC2025-17714426776780_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 19:24:37', '2026-02-20 14:30:49', NULL, NULL),
(61, 'HC2025-17714436146584', 'Anderson Josué', 'Chacón Rojas', 'cedula', 'V-32386940', 'V', '2007-04-03', 18, 'masculino', 'rojasanni808@gmail.com', '+58 412-6793999', 'Acarigua', 'portuguesa', 'acarigua', 'Tecno Cleveland', 'primaria', '6to_grado', 'Senior', 'basica', 'Ganar', 'M', 'uploads/documents/HC2025-17714436146584_document.jpeg', 0, '', '', '', '', '', '', 'pago_movil', '+58 041-2679399', '0134', '2026-02-18', '8264', 'uploads/receipts/HC2025-17714436146584_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 19:40:14', '2026-02-20 14:31:17', NULL, NULL),
(62, 'HC2025-17714462376704', 'Maria Antonieta', 'Calado Colmenarez', 'cedula', 'V-35029901', 'V', '2012-06-28', 13, 'femenino', 'yusmarucolmenarez1@gmail.com', '+58 424-5749183', 'Rio Acarigua', 'portuguesa', 'araure', 'U.E.I. El Parque S.C.', 'bachillerato', '2do_año', 'Junior', 'basica', 'Me gustaria participar y aprender', 'S', 'uploads/documents/HC2025-17714462376704_document.jpg', 1, 'Yusmary Colmenarez', 'cedula', 'V-15071953', 'yusmarucolmenarez1@gmail.com', '+58 424-5749183', '', 'pago_movil', '+58 424', '0102', '2026-02-18', '4419', 'uploads/receipts/HC2025-17714462376704_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 20:23:57', '2026-02-20 14:31:56', NULL, NULL),
(63, 'HC2025-17714470782714', 'JORIANNYS VALENTINA', 'MARIN BARRIOS', 'cedula', 'V-34276377', 'V', '2010-02-14', 16, 'femenino', 'andreabarrios1482@gmail.com', '+58 426-2073221', 'AV 2 ENTRE CALLES 11 Y 12 PAYARA', 'portuguesa', 'acarigua', 'UNIDAD EDUCARTIVA COLEGIO DR. DANIEL CAMEJO ACOSTA', 'bachillerato', '4to_año', 'Senior', 'basica', 'Espero aprender muchas cosas más sobre la robótica y tener mucha más experiencia a la hora de elaborar trabajos muchas más avanzados, tengo expectativas de ver cosas nuevas y aprender de ello', 'S', 'uploads/documents/HC2025-17714470782714_document.jpeg', 1, 'ANDREINA BARRIOS', 'cedula', 'V-20642116', 'andreabarrios1482@gmail.com', '+58 426-2073221', '', 'pago_movil', '+58 422-5510144', '0102', '2026-02-18', '4081', 'uploads/receipts/HC2025-17714470782714_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 20:37:58', '2026-02-20 14:32:27', NULL, NULL),
(64, 'HC2025-17714515169471', 'Sofia Victoria ', 'Antunez Alvarado ', 'cedula', 'V-34535422', 'V', '2012-09-19', 13, 'femenino', 'sofiavictoriaantunezalvarado@gmail.com', '+58 412-9565316', 'Urbanización Misia Amelia Calle 12 casa 162', 'portuguesa', 'araure', 'Alejandro Humboldt ', 'bachillerato', '1er_año', 'Junior', 'basica', 'Aprendizaje ', 'S', 'uploads/documents/HC2025-17714515169471_document.jpg', 1, 'Anelin Alvarado ', 'cedula', 'V-14177584', 'anelinalvarado@gmail.com', '+58 414-3538063', '', 'pago_movil', '+58 414-3538063', '0102', '2026-02-18', '9707', 'uploads/receipts/HC2025-17714515169471_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 21:51:56', '2026-02-20 14:32:51', NULL, NULL),
(65, 'HC2025-17714558098004', 'Miguel Angel', 'Navas Hernandez', 'cedula', 'V-34773589', 'V', '2012-11-26', 13, 'masculino', 'luismarchernandez@gmail.com', '+58 412-7604626', 'Urb villas del pilar 2da etapas calle 2 numero 905D', 'portuguesa', 'araure', 'UE Colegio Dr Daniel Camejo Acosta', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Nuevas programaciones y nuevos retos que me ayuden a crecer con mas aprendizaje en el tema', 'S', 'uploads/documents/HC2025-17714558098004_document.jpg', 1, 'Luismar Hernandez', 'cedula', 'V-15215796', 'luismarchernandez@gmail.com', '+58 412-6681810', '', 'pago_movil', '+58 412-6681810', '0134', '2026-02-18', '5769', 'uploads/receipts/HC2025-17714558098004_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-18 23:03:29', '2026-02-20 14:33:35', NULL, NULL),
(66, 'HC2025-17714596909333', 'Matias', 'Goyo', 'cedula', 'V-36194581', 'V', '2014-08-01', 11, 'masculino', 'matiasgoyof@gmail.com', '+58 414-5563835', 'urbanizacion altos de la galera, conjunto el saman casa S82', 'portuguesa', 'acarigua', 'Unidad Educativa Colegio Privado \"Ángel de la Guarda\"', 'primaria', '6to_grado', 'Junior', 'basica', '', 'S', 'uploads/documents/HC2025-17714596909333_document.jpg', 1, 'Jesus Goyo', 'cedula', 'V-16964739', 'chuchogoyo@hotmail.com', '+58 414-5563835', '', 'pago_movil', '+58 414-5563835', '0134', '2026-02-18', '9725', 'uploads/receipts/HC2025-17714596909333_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 00:08:10', '2026-02-20 14:34:00', NULL, NULL),
(67, 'HC2025-17714618548656', 'Santiago Josué ', 'Carrizo Vidal', 'cedula', 'V-34169440', 'V', '2011-10-13', 14, 'masculino', 'sjcvidal21@gmail.com', '+58 424-5384998', 'Parroquia payara municipio paez ', 'portuguesa', 'acarigua', 'Colegio Dr. Daniel Camejo Acosta ', 'bachillerato', '3er_año', 'Junior', 'intermedia', 'Mejorar mis conocimientos y habilidades en está competencia.', 'M', 'uploads/documents/HC2025-17714618548656_document.jpg', 1, 'Vicker Estefanía Vidal ', 'cedula', 'V-21394990', '1709ev@gmail.com', '+58 424-5258131', '', 'pago_movil', '+58 412-0565547', '0134', '2026-02-18', '9599', 'uploads/receipts/HC2025-17714618548656_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 00:44:14', '2026-02-20 14:34:27', NULL, NULL),
(68, 'HC2025-17715143493796', 'Arantza Sofía ', 'Meléndez Torcates ', 'cedula', 'V-34275569', 'V', '2011-08-10', 14, 'femenino', 'melendezarantzasofia@gmail.com', '+58 412-1505643', 'Urbanización la virginia calle 5 tercera etapa casa 43A ', 'portuguesa', 'acarigua', 'Colegio Dr Daniel Camejo Acosta ', 'bachillerato', '3er_año', 'Junior', 'basica', 'Optener más aprendizaje ', 'S', 'uploads/documents/HC2025-17715143493796_document.jpg', 1, 'Eidelyn Torcates ', 'cedula', 'V-21059997', 'ventasmaxi2017@gmail.com', '+58 424-5503365', '', 'pago_movil', '+58 424-5503365', '0102', '2026-02-19', '0098', 'uploads/receipts/HC2025-17715143493796_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 15:19:09', '2026-02-20 14:34:55', NULL, NULL),
(69, 'HC2025-17715235883926', 'Miguel Francisco ', 'Laguna Valera ', 'cedula', 'V-37023237', 'V', '2015-10-21', 10, 'masculino', 'daiciriscvalerac@gmail.com', '+58 414-5080738', 'San Rafael de Onoto ', 'portuguesa', 'otra_-_(especificar)', 'UEC Fray Miguel de Olivares ', 'primaria', '5to_grado', 'Junior', 'basica', 'Ampliar mi experiencia en programación ', 'S', 'uploads/documents/HC2025-17715235883926_document.jpg', 1, 'Daiciris Valera ', 'cedula', 'V-18295365', 'daiciriscvalerac@gmail.com', '+58 414-5080738', '', 'pago_movil', '+58 414-5080738', '0175', '2026-02-19', '3334', 'uploads/receipts/HC2025-17715235883926_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 17:53:08', '2026-02-20 14:35:27', NULL, NULL),
(70, 'HC2025-17715240359414', 'Luis Ignacio ', 'Querales Barrio', 'cedula', 'V-33348142', 'V', '2010-04-19', 15, 'masculino', 'lqueralesbarrio@gmail.com', '+58 414-1572104', 'urb el pilar calle los apamates casa107-1', 'portuguesa', 'araure', 'U.E.C.P. Alejandro Humboldt', 'bachillerato', '4to_año', 'Senior', 'basica', 'que sea de lo mejor que tenga cosas faciles y que tenga buena aatencion\r\n', 'XL', 'uploads/documents/HC2025-17715240359414_document.jpg', 1, 'Karla Querales', 'cedula', 'V-7597662', 'macodemakecode@gmail.com', '+58 414-5550870', '', 'pago_movil', '+58 414-5550870', '0102', '2026-02-19', '6670', 'uploads/receipts/HC2025-17715240359414_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 18:00:35', '2026-02-20 14:36:03', NULL, NULL),
(71, 'HC2025-17715252791785', 'Susej Marrero ', 'Marrero Arambulet ', 'cedula', 'V-34169642', 'V', '2011-12-07', 14, 'femenino', 'marrerosusejgabriela@gmail.com', '+58 424-5182382', 'Final Av 40 calle sin salida casa 11 Barrio Paez ', 'portuguesa', 'acarigua', 'Daniel Camejo Acosta ', 'bachillerato', '3er_año', 'Junior', 'basica', 'Trabajo en equipo disciplina constancia adquirir conocimientos ', 'M', 'uploads/documents/HC2025-17715252791785_document.jpg', 1, 'Gleibys Arambulet ', 'cedula', 'V-17944450', 'sybielg25@gmail.com', '+58 424-5244746', '', 'pago_movil', '+58 424-5182382', '0134', '2026-02-19', '4213', 'uploads/receipts/HC2025-17715252791785_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 18:21:19', '2026-02-20 14:37:12', NULL, NULL),
(72, 'HC2025-17715267304349', 'Marcos Jesus', 'Oliveros Ruiz', 'cedula', 'V-34534499', 'V', '2012-07-11', 13, 'masculino', 'marcosjesus1107@gmail.com', '+58 412-0578194', 'Urbanización Llano Alto conjunto araguaney casa 39 ', 'portuguesa', 'araure', 'Colegio Daniel Camejo Acosta', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Espero participar ,aprender y disfrutar junto a mis compañeros', 'M', 'uploads/documents/HC2025-17715267304349_document.jpg', 1, ' Carleny Ruiz Marin', 'cedula', 'V-14773440', 'carlenycecilia@gmail.com', '+58 412-2399602', '', 'pago_movil', '+58 412-2399602', '0134', '2026-02-19', '3991', 'uploads/receipts/HC2025-17715267304349_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 18:45:30', '2026-02-20 14:37:33', NULL, NULL),
(73, 'HC2025-17715275501826', 'Oriannys Zamantha ', 'Alvarez dueñez ', 'cedula', 'V-34458410', 'V', '2012-09-12', 13, 'femenino', 'd.yelimar@gmail.com', '+58 412-5577058', 'Barrio él algarrobo calle 30b casa 17-89 ', 'portuguesa', 'acarigua', 'Unidad educativa colegio Dr Daniel Camejo Acosta', 'bachillerato', '2do_año', 'Junior', 'basica', 'Aprender y vivir esta nueva experiencia ', 'M', 'uploads/documents/HC2025-17715275501826_document.jpg', 1, 'Yelimar dueñez ', 'cedula', 'V-18843576', 'd.yelimar@gmail.com', '+58 414-5754534', '', 'pago_movil', '+58 414-5754534', '0138', '2026-02-19', '3972', 'uploads/receipts/HC2025-17715275501826_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 18:59:10', '2026-02-20 14:38:10', NULL, NULL),
(74, 'HC2025-17715287486131', 'Agatha', 'Stracquadaini Espinoza', 'cedula', 'V-33497042', 'V', '2010-08-29', 15, 'femenino', 'mbespinoza81@gmail.com', '+58 414-5226051', 'CALLE 28 ENTRE AV. 20 Y CARR 21 Edif Guamacire Piso 3 apart 11', 'lara', 'barquisimeto', 'Colegio Ilustre Americano', 'bachillerato', '3er_año', 'Senior', 'ninguna', '', 'S', 'uploads/documents/HC2025-17715287486131_document.jpeg', 1, 'Monica Espinoza', 'cedula', 'V-15305934', 'mbespinoza81@gmail.com', '+58 414-5226051', '', 'pago_movil', '+58 414-5226051', '0134', '2026-02-19', '8274', 'uploads/receipts/HC2025-17715287486131_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 19:19:08', '2026-02-20 14:38:47', NULL, NULL),
(75, 'HC2025-17715374785306', 'Ivanna Veruska', 'Bompart Martinez', 'cedula', 'V-34388662', 'V', '2010-12-17', 15, 'femenino', 'ibompartmarinez@gmail.com', '+58 412-1505566', 'Conjunto Residencial Parque Cedral', 'portuguesa', 'araure', 'Colegio Alejandro Humboldt ', 'bachillerato', '3er_año', 'Senior', 'basica', '', 'S', 'uploads/documents/HC2025-17715374785306_document.jpeg', 1, 'Ivonne Martinez', 'cedula', 'V-15071698', 'ivonnetvelandiav@gmail.com', '+58 424-5564659', '', 'pago_movil', '+58 424-5221920', '0134', '2026-02-19', '4019', 'uploads/receipts/HC2025-17715374785306_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 21:44:38', '2026-02-20 14:39:13', NULL, NULL),
(76, 'HC2025-17715379563102', 'Naimarllys Yhoxana', 'Palma Nadal', 'cedula', 'V-33554366', 'V', '2009-12-30', 16, 'femenino', 'NaimarllysP33554366@gmail.com', '+58 412-0934214', 'Urbanización Reinaldo bravo Duaca ', 'lara', 'barquisimeto', 'Colegio Rafael castillo ', 'bachillerato', '5to_año', 'Senior', 'basica', 'Cada conocimiento adquirido en todas las áreas que nos puedan ayudar en un futuro son bienvenidos, y por su puesto dar lo mejor de mí para ganar y reforzar cada día mis capacidades y conocimientos!', 'M', 'uploads/documents/HC2025-17715379563102_document.jpg', 1, 'Nauris Nadal ', 'cedula', 'V-20393126', 'naurisnadal23.nn@gmail.com', '+58 424-5973284', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715379563102_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 21:52:36', '2026-02-20 15:00:50', NULL, NULL),
(77, 'HC2025-17715383076252', 'Gaby Anthonella', 'Alvarado Durán', 'cedula', 'V-33613155', 'V', '2010-09-14', 15, 'femenino', 'gabyalvaradoduran@gmail.com', '+58 414-9731320', 'Duaca. ', 'lara', 'otra_-_(especificar)', 'Colegio Rafael Castillo', 'bachillerato', '4to_año', 'Senior', 'intermedia', '', 'S', 'uploads/documents/HC2025-17715383076252_document.jpg', 1, 'Neydi Alejandra Durán Fonseca', 'cedula', 'V-17011098', 'neydiporsiempre@gmail.com', '+58 414-9731320', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715383076252_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 21:58:27', '2026-02-20 15:01:00', NULL, NULL),
(78, 'HC2025-17715386905822', 'Duvieliz Valentina ', 'Calderón Torres ', 'cedula', 'V-33948381', 'V', '2011-07-15', 14, 'femenino', 'duvielizcalderon@gmail.com', '+58 412-7248196', 'Urb llano alto, conjunto Merecure, casa número 62 ', 'portuguesa', 'araure', 'Alejandro Humboldt ', 'bachillerato', '3er_año', 'Junior', 'intermedia', 'Poder demostrar mis habilidades con la micro;bit', 'S', 'uploads/documents/HC2025-17715386905822_document.jpg', 1, 'Duvalier Calderón ', 'cedula', 'V-13584474', 'duvalierx@gmail.com', '+58 412-1539351', '', 'pago_movil', '+58 412-1539351', '0102', '2026-02-19', '3493', 'uploads/receipts/HC2025-17715386905822_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 22:04:50', '2026-02-20 14:44:25', NULL, NULL),
(79, 'HC2025-17715397815264', 'Victoria Valentina', 'Rodríguez Castillo ', 'cedula', 'V-34974475', 'V', '2012-08-31', 13, 'femenino', 'josmabierr@gmail.com', '+58 414-9731320', 'Urbanizacion brisas del eneal duaca', 'lara', 'otra_-_(especificar)', 'Colegio Rafael Castillo', 'bachillerato', '2do_año', 'Junior', 'ninguna', '', 'S', 'uploads/documents/HC2025-17715397815264_document.jpg', 1, 'Angelica Castillo ', 'cedula', 'V-16292450', 'josmabierr@gmail.com', '+58 424-5911086', '', 'pago_movil', '+58 414-9731320', '0102', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715397815264_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 22:23:01', '2026-02-20 15:01:08', NULL, NULL),
(80, 'HC2025-17715425189827', 'Luis David ', 'Pérez Calles ', 'cedula', 'V-33336268', 'V', '2010-02-06', 16, 'masculino', 'luisp.33336268crc@gmail.com', '+58 412-9304760', 'Carretera Barquisimeto_Duaca km 27 entrada carrizal Perarapa sector estadio ', 'lara', 'barquisimeto', 'U.E Rafael Castillo ', 'bachillerato', '5to_año', 'Senior', 'intermedia', 'Adquirir nuevos conocimientos sobre la materia ', 'M', 'uploads/documents/HC2025-17715425189827_document.jpg', 1, 'Haydee Calles Cadevilla ', 'cedula', 'V-12025078', 'haideecalles@gmail.com', '+58 424-5289880', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715425189827_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 23:08:38', '2026-02-20 15:01:15', NULL, NULL),
(81, 'HC2025-17715446259899', 'Luis Gustavo', 'Castillo Leal', 'cedula', 'V-34321456', 'V', '2012-07-19', 13, 'masculino', 'castillor28672570@gmail.com', '+58 412-4397625', 'Duaca, Carrera 12 entre 10 y 11', 'lara', 'otra_-_(especificar)', 'U.E Colegio Rafael Castillo', 'bachillerato', '2do_año', 'Junior', 'intermedia', '', 'S', 'uploads/documents/HC2025-17715446259899_document.jpg', 1, 'Delia Leal', 'cedula', 'V-15599909', 'eduardocl.32861910.crc@gmail.com', '+58 424-5206115', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715446259899_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 23:43:45', '2026-02-20 15:01:25', NULL, NULL),
(82, 'HC2025-17715455708039', 'Fabián Josue ', 'Giménez Urquiola ', 'cedula', 'V-34910211', 'V', '2012-07-23', 13, 'masculino', 'fabiangimenez535@gmail.com', '+58 414-9731320', 'Duaca', 'lara', 'barquisimeto', 'Colegio Rafael Castillo ', 'bachillerato', '2do_año', 'Junior', 'intermedia', 'Dar todo lo que he aprendido de la robótica en este concurso ', 'S', 'uploads/documents/HC2025-17715455708039_document.jpg', 1, 'Marianny Urquiola', 'cedula', 'V-19424150', 'urquiolamarianny49@gmail.com', '+58 416-3155843', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715455708039_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-19 23:59:30', '2026-02-20 15:01:36', NULL, NULL),
(83, 'HC2025-17715465288400', 'Gabrielys ', 'Lobatón ', 'cedula', 'V-33447940', 'V', '2009-03-23', 16, 'femenino', 'GabrielysL33447940c.r.c@gmail.com', '+58 416-1674092', 'Carretera Barquisimeto-Duaca km 27 entrada carrizal caserio Perarapa sector el estadio ', 'lara', 'barquisimeto', 'Colegio \"Rafael Castillo\"', 'bachillerato', '5to_año', 'Senior', 'basica', 'Espero obtener la oportunidad de una nueva experiencia.', 'S', 'uploads/documents/HC2025-17715465288400_document.jpg', 1, 'Migdalia Fernández ', 'cedula', 'V-17020801', 'midagliafernandez@gmail.com', '+58 416-2628514', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715465288400_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 00:15:28', '2026-02-20 15:02:00', NULL, NULL),
(84, 'HC2025-17715469447502', 'Jesús Sebastián', 'Colmenárez Sandoval', 'cedula', 'V-32830505', 'V', '2009-04-01', 16, 'masculino', 'jesusc.32830505crc@gmail.com', '+58 412-6233078', 'Calle 4 entre carreras 9 y 10. sector Rey Dormido. Duaca-Lara', 'lara', 'otra_-_(especificar)', 'UE Colegio Rafael Castillo', 'bachillerato', '5to_año', 'Senior', 'intermedia', 'Ganar experiencia en el área de robótica y programación', 'M', 'uploads/documents/HC2025-17715469447502_document.jpeg', 1, 'Nhorymar Sandoval', 'cedula', 'V-17149652', 'nhorymarsandoval@gmail.com', '+58 412-3647652', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-19', '1234', 'uploads/receipts/HC2025-17715469447502_payment.jpeg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 00:22:24', '2026-02-20 15:01:45', NULL, NULL),
(85, 'HC2025-17715478684994', 'Samuel Isaac', 'Hernández Ledezma ', 'cedula', 'V-36560019', 'V', '2014-06-10', 11, 'masculino', 'ledezmamarielby5@gmail.com', '+58 412-5519288', 'San Rafael de onoto ', 'portuguesa', 'otra_-_(especificar)', 'UEC Fray Miguel de Olivares ', 'primaria', '6to_grado', 'Junior', 'basica', 'Mostrar mis conocimientos y aprender aún más ', 'S', 'uploads/documents/HC2025-17715478684994_document.jpg', 1, 'Marielby Ledezma ', 'cedula', 'V-14391479', 'ledezmamarielby5@gmail.com', '+58 412-5519288', '', 'pago_movil', '+58 412-5519288', '0175', '2026-02-20', '5103', 'uploads/receipts/HC2025-17715478684994_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 00:37:48', '2026-02-20 14:47:03', NULL, NULL),
(86, 'HC2025-17715480794579', 'Oriana Paola ', 'Gallardo Torrealba', 'cedula', 'V-33749974', 'V', '2010-12-25', 15, 'femenino', 'orianag.33749974crc@gmail.com', '+58 424-5547187', 'Calle 20 entre carreras 5 y 6. Sector la morita, Duaca Edo Lara ', 'lara', 'barquisimeto', 'U E Colegio Rafael Castillo ', 'bachillerato', '4to_año', 'Senior', 'basica', 'Vivir una experiencia innovadora ', 'L', 'uploads/documents/HC2025-17715480794579_document.jpg', 1, 'María Daniela Torrealba ollarves', 'cedula', 'V-20718479', 'mariadanielatorrealbaollarves@gmail.com', '+58 412-7643245', '', 'pago_movil', '+58 414-9731320', '0108', '2026-02-20', '1234', 'uploads/receipts/HC2025-17715480794579_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 00:41:19', '2026-02-20 15:01:51', NULL, NULL),
(87, 'HC2025-17715529396975', 'NABI JESUS', 'SILVA GOMEZ', 'cedula', 'V-33051263', 'V', '2009-09-14', 16, 'masculino', 'Nabisilva287@gmail.com', '+58 412-5111864', 'Calle 11 entre carreras 8 y 9', 'lara', 'barquisimeto', 'Calegio ilustre americano', 'bachillerato', '5to_año', 'Senior', 'ninguna', 'Aprender mas acerca del tema y conocer cosas nuevas ', 'M', 'uploads/documents/HC2025-17715529396975_document.jpg', 1, 'Jesus silva', 'cedula', 'V-16532508', 'automotrizrosi@gmail.com', '+58 414-5025684', '', 'pago_movil', '+58 426-7588225', '0134', '2026-02-20', '0620', 'uploads/receipts/HC2025-17715529396975_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 02:02:19', '2026-02-20 14:48:01', NULL, NULL),
(88, 'HC2025-17715889929570', 'Valeria Sofia', 'Cordero Lopez', 'cedula', 'V-36770713', 'V', '2014-04-30', 11, 'femenino', 'daiciriscvalerac@gmail.com', '+58 424-5282051', 'San Rafael de Onoto ', 'portuguesa', 'otra_-_(especificar)', 'UEC Fray Miguel de Olivares ', 'primaria', '6to_grado', 'Junior', 'basica', 'Tener nuevas experiencias ', 'M', 'uploads/documents/HC2025-17715889929570_document.jpg', 1, 'Genesis Lopez', 'cedula', 'V-19889973', 'daiciriscvalerac@gmail.com', '+58 424-5282051', '', 'pago_movil', '+58 042-4528205', '0102', '2026-02-20', '4305', 'uploads/receipts/HC2025-17715889929570_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 12:03:12', '2026-02-20 14:48:25', NULL, NULL),
(89, 'HC2025-17715933042495', 'Daniela Valentina ', 'Rojas Ruiz ', 'cedula', 'V-34169207', 'V', '2011-02-25', 14, 'femenino', 'albaniruiz90@gmail.com', '+58 424-5872027', 'Urbanización villas del pilar calle 7 de los tetras segunda entrada', 'portuguesa', 'araure', 'UEC Arturo Michelena ', 'bachillerato', '3er_año', 'Junior', 'basica', 'Obtener más conocimiento ', 'L', 'uploads/documents/HC2025-17715933042495_document.jpg', 1, 'Albanis ', 'cedula', 'V-20809221', 'albaniruiz90@gmail.com', '+58 424-5872027', '', 'pago_movil', '+58 424-5872027', '0102', '2026-02-20', '9565', 'uploads/receipts/HC2025-17715933042495_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 13:15:04', '2026-02-20 14:49:05', NULL, NULL),
(90, 'HC2025-17716082432071', 'Gustavo', 'Giraldo', 'cedula', 'V-33719058', 'V', '2008-11-25', 17, 'masculino', 'ggiraldorojas@gmail.com', '+58 412-2922511', 'Urbanización 24 de Julio \r\nSector 1 calle 5 casa 17 ', 'portuguesa', 'araure', 'U.E.C Arturo Michelena ', 'bachillerato', '4to_año', 'Senior', 'basica', 'Aprender más de programación ', 'M', 'uploads/documents/HC2025-17716082432071_document.pdf', 1, 'Gresmady Rojas ', 'cedula', 'V-17796682', 'gresma07.gr@gmail.com', '+58 414-5256455', '', 'pago_movil', '+58 414-5256455', '0102', '2026-02-20', '0908', 'uploads/receipts/HC2025-17716082432071_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 17:24:03', '2026-02-24 14:38:07', NULL, NULL);
INSERT INTO `registrations_old` (`id`, `registration_number`, `full_name`, `last_name`, `document_type`, `document_number`, `nationality`, `birth_date`, `age`, `gender`, `email`, `phone`, `address`, `state`, `city`, `institution`, `education_level`, `grade`, `category`, `microbit_experience`, `expectations`, `shirt_size`, `document_photo_path`, `is_minor`, `guardian_name`, `guardian_doc_type`, `guardian_document`, `guardian_email`, `guardian_phone`, `authorization_doc_path`, `payment_method`, `payment_phone`, `payment_bank`, `payment_date`, `payment_reference`, `payment_proof_path`, `payment_amount_bs`, `bcv_rate`, `payment_verified`, `image_rights_accepted`, `data_verified`, `verified_by`, `status`, `created_at`, `updated_at`, `event_id`, `category_id`) VALUES
(91, 'HC2025-17716208776045', 'ENMANUEL DAVID', 'NIEVES LOZADA', 'cedula', 'V-33709383', 'V', '2010-03-30', 15, 'masculino', '1enmanuelnieves@gmail.com', '+58 422-0108399', 'Urb. Bosques de Camoruco Av. Principal Conjunto 6 casa 16', 'portuguesa', 'acarigua', 'UEPC ANGEL DE LA GUARDA', 'bachillerato', '4to_año', 'Senior', 'basica', 'Primero aprender y ampliar mis conocimientos en en programación y robótica. Aprender de esta experiencia con otras personas que están en igual nivel o más avanzados. Que quiero lograr, ganar y ya con participar he ganado el hecho de estar en este primer evento que hará historia ', 'M', 'uploads/documents/HC2025-17716208776045_document.jpg', 1, 'Arelis Lozada ', 'cedula', 'V-13485232', 'milaidalozadafernandez3877@gmail.com', '+58 042-4516604', '', 'efectivo', NULL, NULL, '2026-02-20', NULL, '', NULL, NULL, 0, 1, 1, NULL, 1, '2026-02-20 20:54:37', '2026-02-20 20:54:37', NULL, NULL),
(92, 'HC2025-17716235338601', 'José Daniel ', 'Lozada Correa ', 'cedula', 'V-33169503', 'V', '2008-11-14', 17, 'masculino', 'whiterock432@gmail.com', '+58 424-5541917', 'bosques de camoruco conjunto 6 casa 6-16', 'portuguesa', 'acarigua', 'UPTP J.J montilla', 'universidad', 'ingeniería___1er_semestre', 'Senior', 'basica', 'Espero aprender más sobre las micro:bit y programación. Que quiero lograr llegar al primer lugar y gracias por esta experiencia ', 'M', 'uploads/documents/HC2025-17716235338601_document.jpg', 1, 'Arelis Lozada ', 'cedula', 'V-13485232', 'milaidalozadafernandez3877@gmail.com', '+58 424-5166040', '', 'pago_movil', '+58 424-5166040', '0102', '2026-02-21', '1234', 'uploads/receipts/HC2025-17716235338601_payment.jpg', 0.00, 0.00, 0, 1, 1, NULL, 1, '2026-02-20 21:38:53', '2026-02-20 21:38:53', NULL, NULL),
(93, 'HC2025-17716250533548', 'Santiago Javier ', 'Sosa Hernández ', 'cedula', 'V-33879742', 'V', '2010-04-12', 15, 'masculino', 'mirlajosehernandez@gmail.com', '+58 424-5888744', 'Urb desarrolló camburito calle 8 casa 24', 'portuguesa', 'araure', 'Daniel camejo acosta ', 'bachillerato', '4to_año', 'Senior', 'basica', 'Muy interesante ', 'M', 'uploads/documents/HC2025-17716250533548_document.jpeg', 1, 'Francisco sosa ', 'cedula', 'V-19172887', 'franciscososa1889@gmail.com', '+58 416-7585603', '', 'pago_movil', '+58 424-5888744', '0102', '2026-02-20', '7179', 'uploads/receipts/HC2025-17716250533548_payment.png', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-20 22:04:13', '2026-02-24 14:40:12', NULL, NULL),
(94, 'HC2025-17716298641124', 'Isavó Karmir ', 'Uzcátegui Pérez ', 'cedula', 'V-32512333', 'V', '2008-03-17', 17, 'femenino', 'isavokarmir@gmail.com', '+58 424-5110414', 'San José 2,Av. 1 casa número 46 ', 'portuguesa', 'acarigua', 'Además Vásquez Cháves ', 'bachillerato', '5to_año', 'Senior', 'basica', '', 'M', 'uploads/documents/HC2025-17716298641124_document.jpg', 1, 'Mirla Pérez ', 'cedula', 'V-11076770', 'isakarl2009@gmail.com', '+58 414-5549022', '', 'pago_movil', '+58 424-5110414', '0134', '2026-02-20', '1234', 'uploads/receipts/HC2025-17716298641124_payment.jpg', 0.00, 0.00, 0, 1, 1, NULL, 1, '2026-02-20 23:24:24', '2026-02-20 23:24:24', NULL, NULL),
(95, 'HC2025-17716317976502', 'Miguel Alfonso ', 'Guedez Camacho ', 'cedula', 'V-32442894', 'V', '2006-10-08', 19, 'masculino', 'alfonzoguedezzz@gmail.com', '+58 424-5066888', 'Hacienda San José 2 calle 6 #40', 'portuguesa', 'araure', 'U.P.T.P \"JJ Montilla\"', 'universidad', 'ingeniería___5to_semestre', 'Senior', 'basica', 'Mejorar mis habilidades de programación y el análisis lógico para la resolución de problemas ', 'M', 'uploads/documents/HC2025-17716317976502_document.jpg', 0, '', '', '', '', '', '', 'pago_movil', '+58 424-5066888', '0102', '2026-02-20', '1234', 'uploads/receipts/HC2025-17716317976502_payment.jpg', 0.00, 0.00, 0, 1, 1, NULL, 1, '2026-02-20 23:56:37', '2026-02-20 23:56:37', NULL, NULL),
(96, 'HC2025-17716332959225', 'Maria Felicia', 'Castillo Aguilar', 'cedula', 'V-36897689', 'V', '2014-11-21', 14, 'femenino', 'daiciriscvalerac@gmail.com', '+58 424-5006780', 'San Rafael de Onoto ', 'portuguesa', 'otra_-_(especificar)', 'UEC Fray Miguel de Olivares ', 'primaria', '6to_grado', 'Junior', 'basica', 'Experiencias innovadoras', 'S', 'uploads/documents/HC2025-17716332959225_document.jpg', 1, 'Roxana Aguilar', 'cedula', 'V-15341601', 'roxanaaguilar232@gmail.com', '+58 424-5006780', '', 'pago_movil', '+58 424-5006780', '0102', '2026-02-21', '1043', 'uploads/receipts/HC2025-17716332959225_payment.jpg', 0.00, 0.00, 1, 1, 1, NULL, 1, '2026-02-21 00:21:35', '2026-02-24 14:40:57', NULL, NULL),
(97, 'HC2025-1771638609 ', 'Maria Preciosa', 'Vazquez García', 'cedula_escolar', '', 'V', '2012-02-06', 14, 'femenino', 'garciajulielsy1@gmail.com', '04245311595', 'San Rafael de Onoto', 'portuguesa', '', 'UEC Fray Miguel de Olivares', 'primaria', '2to_año', 'Junior', 'basica', NULL, 'S', 'uploads/documents/cedula_ana_vazquez.jpeg', 1, 'Julielsy García', 'cedula', 'V-12237280', NULL, '04245311595', NULL, 'pago_movil', NULL, '0134', '2026-02-20', '5182', 'uploads/receipts/pago_movil_ana_vazquez.jpeg', 4023.30, NULL, 1, 1, 1, NULL, 1, '2026-02-21 02:28:52', '2026-02-24 14:41:45', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Juez');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `schools`
--

INSERT INTO `schools` (`id`, `name`, `address`, `city`, `state`, `phone`, `email`, `created_at`, `updated_at`) VALUES
(1, 'Angel de la Guarda', 'Acarigua', 'Acarigua', 'Portuguesa', '5804245006787', 'angel@gmail.com', '2026-08-31 16:03:10', '2026-08-31 16:03:10'),
(2, 'Rafael Castillo', 'Acarigu', 'Acarigu', 'Portuguesa', '0245845785', 'rafael@gmail.com', '2026-08-31 18:24:51', '2026-08-31 18:26:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `student_groups`
--

CREATE TABLE `student_groups` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role_id`, `created_at`) VALUES
(1, 'admin', 'hackathon@tecnocleveland.com', '$2y$10$DoUIyPiDmr2BXMJHjjxjauJTXaoMcVaW8slpMSP3oahP/0MFIZvXG', 1, '2025-09-24 15:29:55'),
(2, 'abril', 'abril@gmail.com', '$2y$10$DoUIyPiDmr2BXMJHjjxjauJTXaoMcVaW8slpMSP3oahP/0MFIZvXG', 2, '2026-08-11 14:33:06'),
(3, 'alexis', 'tecno.alexiscaceres@gmail.com', '$2y$10$kl5eE/KIKn0J.NohD9OFuOwElQM8040mthrwDrQ9HTW/4Zgh9SdSO', 2, '2026-08-25 15:39:31');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_challenges_event` (`event_id`);

--
-- Indices de la tabla `challenge_items`
--
ALTER TABLE `challenge_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indices de la tabla `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `event_categories`
--
ALTER TABLE `event_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indices de la tabla `judge_assignments`
--
ALTER TABLE `judge_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `judge_id` (`judge_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `judge_evaluations`
--
ALTER TABLE `judge_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `challenge_id` (`challenge_id`);

--
-- Indices de la tabla `judge_item_evaluations`
--
ALTER TABLE `judge_item_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item_eval` (`registration_id`,`challenge_item_id`,`judge_user_id`),
  ADD KEY `challenge_item_id` (`challenge_item_id`),
  ADD KEY `judge_user_id` (`judge_user_id`);

--
-- Indices de la tabla `judge_student_assignments`
--
ALTER TABLE `judge_student_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_assignment` (`registration_id`),
  ADD KEY `judge_id` (`judge_id`);

--
-- Indices de la tabla `partial_leads`
--
ALTER TABLE `partial_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_number` (`document_number`);

--
-- Indices de la tabla `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_document` (`document_type`,`document_number`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indices de la tabla `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration_payment` (`registration_id`);

--
-- Indices de la tabla `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant_event` (`participant_id`,`event_id`),
  ADD KEY `fk_reg_event` (`event_id`),
  ADD KEY `fk_reg_category` (`category_id`);

--
-- Indices de la tabla `registrations_old`
--
ALTER TABLE `registrations_old`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `document_number` (`document_number`),
  ADD KEY `idx_document_number` (`document_number`),
  ADD KEY `idx_registration_number` (`registration_number`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_name` (`name`);

--
-- Indices de la tabla `student_groups`
--
ALTER TABLE `student_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_users_role_id` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `challenge_items`
--
ALTER TABLE `challenge_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `event_categories`
--
ALTER TABLE `event_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `judge_assignments`
--
ALTER TABLE `judge_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `judge_evaluations`
--
ALTER TABLE `judge_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `judge_item_evaluations`
--
ALTER TABLE `judge_item_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `judge_student_assignments`
--
ALTER TABLE `judge_student_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `partial_leads`
--
ALTER TABLE `partial_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de la tabla `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de la tabla `registrations_old`
--
ALTER TABLE `registrations_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `student_groups`
--
ALTER TABLE `student_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `challenges`
--
ALTER TABLE `challenges`
  ADD CONSTRAINT `challenges_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_challenges_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `challenge_items`
--
ALTER TABLE `challenge_items`
  ADD CONSTRAINT `challenge_items_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `event_categories`
--
ALTER TABLE `event_categories`
  ADD CONSTRAINT `event_categories_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `judge_assignments`
--
ALTER TABLE `judge_assignments`
  ADD CONSTRAINT `judge_assignments_ibfk_1` FOREIGN KEY (`judge_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `judge_assignments_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `judge_assignments_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `judge_evaluations`
--
ALTER TABLE `judge_evaluations`
  ADD CONSTRAINT `judge_evaluations_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `judge_item_evaluations`
--
ALTER TABLE `judge_item_evaluations`
  ADD CONSTRAINT `fk_item_eval_item` FOREIGN KEY (`challenge_item_id`) REFERENCES `challenge_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_eval_judge` FOREIGN KEY (`judge_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_eval_reg` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `judge_student_assignments`
--
ALTER TABLE `judge_student_assignments`
  ADD CONSTRAINT `judge_student_assignments_ibfk_1` FOREIGN KEY (`judge_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `judge_student_assignments_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_registration` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `fk_reg_category` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reg_participant` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `student_groups`
--
ALTER TABLE `student_groups`
  ADD CONSTRAINT `fk_group_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
