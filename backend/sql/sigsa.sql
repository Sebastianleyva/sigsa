-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2026 at 04:48 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigsa`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id_area` bigint(20) NOT NULL,
  `nombre` text NOT NULL,
  `descripcion` text NOT NULL,
  `id_subdireccion` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id_area`, `nombre`, `descripcion`, `id_subdireccion`) VALUES
(1, 'SUBDIRECCION DE TECNOLOGIAS', 'ENCARGADA DE  HARDWERE', NULL),
(2, 'SUBDIRECCION DE SISTEMAS', 'ENCARGADA DEL DESARROLO, MANTENIMIENTO E IMPLEMENTACION DE SISTEMAS', NULL),
(3, 'DEPARTAMENTO DE SOPORTE TECNICO A CENTROS ESCOLARES', '...', 1),
(4, 'DEPARTAMENTO DE SOPORTE TECNICO A CENTROS ADMINISTRATIVOS', '...', 1),
(5, 'DEPARTAMENTO DE REDES Y TELECOMUNICACIONES', '...', 1),
(6, 'DEPARTAMENTO DE OPERACIONES Y LOGISTICA', 'ENCARGADOS DE LA CONTADURIA Y PROCESOS ADMINISTRATVOS DENTRO DE LA DIRECCION', NULL),
(7, 'DEPARTAMENTO DE DESARROLLO DE SISTEMAS', 'ENCARGADOS DEL DESARROLLO Y MANTENIMIENTO DE SISTEMAS DENTRO Y RELACIONADOS CON LA DIRECCION', 2),
(8, 'DEPARTAMENTO DE IMPLEMENTACION DE SISTEMAS', '...', 2);

-- --------------------------------------------------------

--
-- Table structure for table `calendario`
--

CREATE TABLE `calendario` (
  `id_calendario` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal`
--

CREATE TABLE `personal` (
  `id_personal` bigint(20) NOT NULL,
  `rfc` varchar(13) NOT NULL,
  `curp` varchar(18) NOT NULL,
  `nombre` varchar(20) NOT NULL,
  `apellido_p` varchar(25) NOT NULL,
  `apellido_m` varchar(25) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `telefono` varchar(12) NOT NULL,
  `puesto` bigint(20) NOT NULL,
  `area` bigint(20) DEFAULT NULL,
  `id_jefe` bigint(20) DEFAULT NULL,
  `activo` char(1) NOT NULL,
  `contrasena` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal`
--

INSERT INTO `personal` (`id_personal`, `rfc`, `curp`, `nombre`, `apellido_p`, `apellido_m`, `correo`, `telefono`, `puesto`, `area`, `id_jefe`, `activo`, `contrasena`) VALUES
(13, 'son13valores1', 'rils060301hdgytba2', 'Sebastian', 'Rivera', 'Leyva', 'sebastian@gmail.com', '6181161702', 1, NULL, NULL, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(14, 'son13valores2', 'legs060301hdgytba2', 'Sebastian', 'Leyva', 'Gutierrez', 'leyva@gmail.com', '6181161703', 2, 2, 13, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(15, 'son13valores3', 'rils060301hdgytba3', 'Luis', 'Jimenez', 'Delgado', 'delgado@gmail.com', '6181161704', 3, 7, 14, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(16, 'son13valores4', 'rils060301hdgytba4', 'Diego', 'Erre', 'So', 'diego@gmail.com', '6181161705', 4, 7, 15, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(18, 'son13valores5', 'rils060301hdgytba5', 'Jose', 'Lomas', 'Turbas', 'jose@gmail.com', '6181161706', 2, 1, 13, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(20, 'son13valores6', 'rils060301hdgytba6', 'Itzel', 'Ochoa', 'Gandara', 'itzel@gmail.com', '6181161707', 3, 8, 18, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(21, 'son13valores7', 'rils060301hdgytba7', 'Mafer', 'luna', 'soto', 'mafer@gmail.com', '6181161708', 4, 8, 20, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu');

-- --------------------------------------------------------

--
-- Table structure for table `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` bigint(20) NOT NULL,
  `nombre` text NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `puestos`
--

INSERT INTO `puestos` (`id_puesto`, `nombre`, `descripcion`) VALUES
(1, 'DIRECTOR', 'DIRECTOR DE LA DIRECCION DE SISTEMAS Y TECNOLOGIAS'),
(2, 'SUBDIRECTOR', 'ENCARGADO DE UNA SUBDIRECCION'),
(3, 'JEFE DE DEPARTAMENTO', 'ENCARGADO DE UN DEPARTAMENTO'),
(4, 'TRABAJADOR DE DEPARTAMENTO', 'TRABAJADOR QUE SE DESEMPEÑA DENTRO DE UN DEPARTAMENTO EN LAS TAREAS QUE SE LE ASIGNAN');

-- --------------------------------------------------------

--
-- Table structure for table `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` bigint(20) NOT NULL,
  `tipo_reporte` text NOT NULL,
  `requisicion` char(1) DEFAULT NULL,
  `id_personal` bigint(20) NOT NULL,
  `id_servicio` bigint(20) NOT NULL,
  `id_area` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisiciones`
--

CREATE TABLE `requisiciones` (
  `id_requisicion` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `id_solicitante` bigint(20) NOT NULL,
  `id_autoriza` bigint(20) NOT NULL,
  `tipo_financiamiento` text NOT NULL,
  `descripcion` text NOT NULL,
  `justificacion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salidas`
--

CREATE TABLE `salidas` (
  `id_salida` bigint(20) NOT NULL,
  `tipo_salida` varchar(25) NOT NULL,
  `motivo` text NOT NULL COMMENT 'razon de la salida',
  `destino` text NOT NULL COMMENT 'el lugar a donde se dirije',
  `fecha` date NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `hora_llegada` time DEFAULT NULL,
  `estado` text NOT NULL COMMENT 'guarda el estaod de la salida (pendiente o concluida)',
  `id_personal` bigint(20) NOT NULL,
  `id_autoriza` bigint(20) DEFAULT NULL,
  `solicita_respaldo` tinyint(1) NOT NULL DEFAULT 0,
  `hora_estimada` time DEFAULT NULL,
  `adeudo_tiempo` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salidas`
--

INSERT INTO `salidas` (`id_salida`, `tipo_salida`, `motivo`, `destino`, `fecha`, `hora_salida`, `hora_llegada`, `estado`, `id_personal`, `id_autoriza`, `hora_estimada`, `adeudo_tiempo`) VALUES
(7, 'PERSONAL', 'Voy por el festival del día de las madres en la escuela de mi hijo', 'Secundaria #3', '2026-10-22', '16:59:40', NULL, 'FINALIZADO', 21, NULL, '18:59:40', NULL),
(8, 'MEDICA', 'me duele la panza', 'issste', '2026-10-25', NULL, NULL, 'RECHAZADA', 21, NULL, NULL, NULL),
(9, 'PERSONAL', 'voy a cuidar a mi mama', 'manuel rosas #304', '2026-10-26', NULL, NULL, 'RECHAZADA', 21, NULL, NULL, NULL),
(10, 'MEDICA', 'me duele todo', 'xicoli 305', '2026-10-27', '08:42:30', '09:46:52', 'FINALIZADO', 21, 20, '10:42:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` bigint(20) NOT NULL,
  `tipo_servicio` text NOT NULL,
  `requisicion` char(1) DEFAULT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id_area`),
  ADD KEY `id_subdireccion` (`id_subdireccion`);

--
-- Indexes for table `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`id_calendario`);

--
-- Indexes for table `personal`
--
ALTER TABLE `personal`
  ADD PRIMARY KEY (`id_personal`),
  ADD UNIQUE KEY `rfc` (`rfc`),
  ADD UNIQUE KEY `curp` (`curp`),
  ADD UNIQUE KEY `telefono` (`telefono`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `puesto` (`puesto`),
  ADD KEY `area` (`area`),
  ADD KEY `id_jefe` (`id_jefe`);

--
-- Indexes for table `puestos`
--
ALTER TABLE `puestos`
  ADD PRIMARY KEY (`id_puesto`);

--
-- Indexes for table `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_personal` (`id_personal`),
  ADD KEY `id_servicio` (`id_servicio`),
  ADD KEY `id_area` (`id_area`);

--
-- Indexes for table `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD PRIMARY KEY (`id_requisicion`),
  ADD KEY `id_solicitante` (`id_solicitante`),
  ADD KEY `id_autoriza` (`id_autoriza`);

--
-- Indexes for table `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id_salida`),
  ADD KEY `id_personal` (`id_personal`),
  ADD KEY `id_autoriza` (`id_autoriza`),
  ADD KEY `id_personal_2` (`id_personal`);

--
-- Indexes for table `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id_calendario` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id_requisicion` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id_salida` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`id_subdireccion`) REFERENCES `areas` (`id_area`);

--
-- Constraints for table `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`puesto`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `personal_ibfk_3` FOREIGN KEY (`id_jefe`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `salidas_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `salidas_ibfk_2` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
