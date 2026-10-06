-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-10-2026 a las 19:53:11
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
(2, 'seb45678io123', 'rils060301hdgyba2', 'sebastian', 'rivera', 'leyva', '', '', 1, NULL, NULL, '', ''),
(4, 'seb45678io133', '1sssdfr12345678912', 'sebas', 'leyva', 'leyva', 'ssss', '12345678', 2, 1, 2, '', ''),
(6, 'son13valores1', 'aquison18valores12', 'jose', 'perez', 'paez', 'jose@gmail.com', '123456789', 3, 7, 4, '', ''),
(8, 'son12valores1', 'aquison18valores13', 'luis', 'jimenez', 'gonzalez', 'jimenez@gmail.com', '123456781', 4, 7, 6, '', ''),
(10, 'seb45678io321', 'rils060301hdgybe3', 'jose', 'leyva', 'leyva', 'riveraleyvasebastian@gmail.com', '987654321', 4, 7, 2, '', '$2y$10$uQUePipyahSE63IyYfw/CusHMotAGjzdJBDxOttg4Tt2mb26Istrq'),
(11, 'seb45876io123', 'rils060311hdgyba2', 'sebastian', 'Rivera', 'leyva', 'jossssse@gmail.com', '123488889', 3, 7, 4, '', '$2y$10$knVu6YvJTPSk69Tnoy1l/ebl9NXsp.AjiAnxCKCW1/eiduVSlnk4y'),
(12, 'son21valores1', 'Rils060302hdgtva4', 'sebas', 'leyva', 'rivera', 'jjj@', '6181171603', 2, 1, 2, '', '$2y$10$q/eHMNq/apl/8Wnwf7StJObUQV/9DPD.0BKLbAORfGjnuCjZHMOUq');

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
-- Estructura de tabla para la tabla `salidas`
--

CREATE TABLE `salidas` (
  `id_salida` bigint(20) NOT NULL,
  `tipo_salida` varchar(25) NOT NULL,
  `motivo` text NOT NULL COMMENT 'razon de la salida',
  `destino` text NOT NULL COMMENT 'el lugar a donde se dirije',
  `fecha` date NOT NULL,
  `hora_salida` time NOT NULL,
  `hora_llegada` time DEFAULT NULL,
  `estado` text NOT NULL COMMENT 'guarda el estaod de la salida (pendiente o concluida)',
  `id_personal` bigint(20) NOT NULL,
  `id_autoriza` bigint(20) DEFAULT NULL,
  `hora_estimada` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `salidas`
--

INSERT INTO `salidas` (`id_salida`, `tipo_salida`, `motivo`, `destino`, `fecha`, `hora_salida`, `hora_llegada`, `estado`, `id_personal`, `id_autoriza`, `hora_estimada`) VALUES
(1, 'permiso economico', 'cita en el sat', 'sat Calle #130', '2026-09-10', '00:34:17', '00:00:00', '0', 8, 8, '13:34:17'),
(2, 'Personal', 'Cita médica', 'Hospital General', '2026-03-30', '10:00:00', NULL, 'PENDIENTE', 10, 2, '12:00:00'),
(3, 'OFICIAL', 'Comisión de trabajo', 'Oficina Central', '2026-10-06', '12:45:45', NULL, 'EN CURSO', 12, NULL, '14:45:45');

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
-- Indices de la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id_salida`),
  ADD KEY `id_personal` (`id_personal`),
  ADD KEY `id_autoriza` (`id_autoriza`),
  ADD KEY `id_personal_2` (`id_personal`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id_salida` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`id_subdireccion`) REFERENCES `areas` (`id_area`);

--
-- Filtros para la tabla `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`puesto`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `personal_ibfk_3` FOREIGN KEY (`id_jefe`) REFERENCES `personal` (`id_personal`);

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
