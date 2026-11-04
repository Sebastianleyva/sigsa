-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-11-2026 a las 19:06:23
-- Versión del servidor: 10.4.25-MariaDB
-- Versión de PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sigsa`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `areas`
--

CREATE TABLE `areas` (
  `id_area` bigint(20) NOT NULL,
  `nombre` text NOT NULL,
  `descripcion` text NOT NULL,
  `id_subdireccion` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `areas`
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
-- Estructura de tabla para la tabla `calendario`
--

CREATE TABLE `calendario` (
  `id_calendario` bigint(20) NOT NULL,
  `fecha_destinada` date NOT NULL,
  `estado` varchar(20) NOT NULL,
  `id_servicio` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `calendario`
--

INSERT INTO `calendario` (`id_calendario`, `fecha_destinada`, `estado`, `id_servicio`) VALUES
(13, '2026-11-05', 'pendiente', 2),
(14, '2026-10-11', 'pendiente', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incidencias`
--

CREATE TABLE `incidencias` (
  `id_incidencia` bigint(20) NOT NULL,
  `id_personal` bigint(20) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` date NOT NULL,
  `urgencia` varchar(20) NOT NULL,
  `estado` varchar(20) NOT NULL,
  `lugar` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `incidencias`
--

INSERT INTO `incidencias` (`id_incidencia`, `id_personal`, `descripcion`, `fecha`, `urgencia`, `estado`, `lugar`) VALUES
(1, 20, 'hay un ollo en la pared', '2026-11-03', 'URGENTE', 'pendiente', 'Cocina');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `personal`
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
-- Estructura de tabla para la tabla `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` bigint(20) NOT NULL,
  `nombre` text NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `puestos`
--

INSERT INTO `puestos` (`id_puesto`, `nombre`, `descripcion`) VALUES
(1, 'DIRECTOR', 'DIRECTOR DE LA DIRECCION DE SISTEMAS Y TECNOLOGIAS'),
(2, 'SUBDIRECTOR', 'ENCARGADO DE UNA SUBDIRECCION'),
(3, 'JEFE DE DEPARTAMENTO', 'ENCARGADO DE UN DEPARTAMENTO'),
(4, 'TRABAJADOR DE DEPARTAMENTO', 'TRABAJADOR QUE SE DESEMPEÑA DENTRO DE UN DEPARTAMENTO EN LAS TAREAS QUE SE LE ASIGNAN');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id_reporte` bigint(20) NOT NULL,
  `tipo` text NOT NULL,
  `id_calendario` bigint(20) DEFAULT NULL,
  `id_incidencia` bigint(20) DEFAULT NULL,
  `id_personal` bigint(20) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_final` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id_reporte`, `tipo`, `id_calendario`, `id_incidencia`, `id_personal`, `fecha_inicio`, `fecha_final`, `estado`, `descripcion`) VALUES
(2, 'incidencia', NULL, NULL, NULL, '2026-11-04', NULL, 'pendiente', 'si');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requisiciones`
--

CREATE TABLE `requisiciones` (
  `id_requisicion` bigint(20) NOT NULL,
  `id_reporte` bigint(20) NOT NULL,
  `id_solicita` bigint(20) NOT NULL,
  `id_autoriza` bigint(20) DEFAULT NULL,
  `justificacion` text NOT NULL,
  `fecha` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `descripcion_servicio` text NOT NULL,
  `estado` varchar(20) NOT NULL,
  `tipo_financiamiento` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `requisiciones`
--

INSERT INTO `requisiciones` (`id_requisicion`, `id_reporte`, `id_solicita`, `id_autoriza`, `justificacion`, `fecha`, `fecha_entrega`, `descripcion_servicio`, `estado`, `tipo_financiamiento`) VALUES
(2, 2, 14, 13, 'hace falta', '2026-11-04', NULL, 'necesito dos panes', 'pendiente', 'est'),
(3, 2, 14, NULL, 'ssss', '2026-11-04', NULL, 'sss', 'sss', 'sss');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas`
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
  `adeudo_tiempo` time DEFAULT NULL,
  `justificante` tinyint(1) DEFAULT NULL,
  `fecha_llegada` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `salidas`
--

INSERT INTO `salidas` (`id_salida`, `tipo_salida`, `motivo`, `destino`, `fecha`, `hora_salida`, `hora_llegada`, `estado`, `id_personal`, `id_autoriza`, `solicita_respaldo`, `hora_estimada`, `adeudo_tiempo`, `justificante`, `fecha_llegada`) VALUES
(7, 'PERSONAL', 'Voy por el festival del día de las madres en la escuela de mi hijo', 'Secundaria #3', '2026-10-22', '16:59:40', NULL, 'FINALIZADO', 21, NULL, 0, '18:59:40', NULL, 0, '0000-00-00'),
(8, 'MEDICA', 'me duele la panza', 'issste', '2026-10-25', NULL, NULL, 'RECHAZADA', 21, NULL, 0, NULL, NULL, 0, '0000-00-00'),
(9, 'PERSONAL', 'voy a cuidar a mi mama', 'manuel rosas #304', '2026-10-26', NULL, NULL, 'RECHAZADA', 21, NULL, 0, NULL, NULL, 0, '0000-00-00'),
(10, 'MEDICA', 'me duele todo', 'xicoli 305', '2026-10-27', '08:42:30', '09:46:52', 'FINALIZADO', 21, 20, 0, '10:42:30', NULL, 0, '0000-00-00'),
(11, 'MEDICA', 'me rompi la pierna', 'no se 103', '2026-10-28', '11:45:40', '11:46:02', 'FINALIZADO', 16, 15, 0, '13:45:40', NULL, 0, '0000-00-00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` bigint(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `periodicidad` int(3) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id_servicio`, `nombre`, `periodicidad`, `descripcion`) VALUES
(2, 'ssss', 2, 'sss'),
(3, 'revision de extintores', 4, 'revision del llenado y funcionalidad de los extintores');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id_area`),
  ADD KEY `id_subdireccion` (`id_subdireccion`);

--
-- Indices de la tabla `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`id_calendario`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indices de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id_incidencia`),
  ADD KEY `id_personal` (`id_personal`);

--
-- Indices de la tabla `personal`
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
-- Indices de la tabla `puestos`
--
ALTER TABLE `puestos`
  ADD PRIMARY KEY (`id_puesto`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id_reporte`),
  ADD KEY `id_calendario` (`id_calendario`),
  ADD KEY `id_incidencia` (`id_incidencia`),
  ADD KEY `id_personal` (`id_personal`);

--
-- Indices de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD PRIMARY KEY (`id_requisicion`),
  ADD KEY `id_solicita` (`id_solicita`),
  ADD KEY `id_autoriza` (`id_autoriza`),
  ADD KEY `id_reporte` (`id_reporte`);

--
-- Indices de la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id_salida`),
  ADD KEY `id_personal` (`id_personal`),
  ADD KEY `id_autoriza` (`id_autoriza`),
  ADD KEY `id_personal_2` (`id_personal`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id_servicio`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id_calendario` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id_incidencia` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id_requisicion` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id_salida` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`id_subdireccion`) REFERENCES `areas` (`id_area`);

--
-- Filtros para la tabla `calendario`
--
ALTER TABLE `calendario`
  ADD CONSTRAINT `id_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`);

--
-- Filtros para la tabla `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`puesto`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `personal_ibfk_3` FOREIGN KEY (`id_jefe`) REFERENCES `personal` (`id_personal`);

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `id_calendario` FOREIGN KEY (`id_calendario`) REFERENCES `calendario` (`id_calendario`),
  ADD CONSTRAINT `id_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`),
  ADD CONSTRAINT `id_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Filtros para la tabla `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD CONSTRAINT `id_autoriza` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `id_solicita` FOREIGN KEY (`id_solicita`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `requisiciones_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`);

--
-- Filtros para la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `salidas_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `salidas_ibfk_2` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
