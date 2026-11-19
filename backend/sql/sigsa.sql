-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 06:31 PM
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
  `id_area` bigint(20) NOT NULL COMMENT 'identificador de área\r\n',
  `nombre` text NOT NULL COMMENT 'nombre de la subdirección o departamento',
  `descripcion` text NOT NULL COMMENT 'descripción del trabajo realizado en el área de trabajo',
  `id_subdireccion` bigint(20) DEFAULT NULL COMMENT 'identificador de la subdirección a la que pertenece el departamento'
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
  `id_calendario` bigint(20) NOT NULL COMMENT 'identificador del calendario',
  `fecha_destinada` date NOT NULL COMMENT 'fecha en la que se realizara el servicio',
  `estado` varchar(20) NOT NULL COMMENT 'estado en el que se encuentra el servicio (pendiente, en proceso, realizado, atrasado)',
  `id_servicio` bigint(20) NOT NULL COMMENT 'identificador del servicio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendario`
--

INSERT INTO `calendario` (`id_calendario`, `fecha_destinada`, `estado`, `id_servicio`) VALUES
(15, '2026-11-10', 'PENDIENTE', 5),
(16, '2026-12-15', 'PENDIENTE', 5),
(17, '2027-01-19', 'PENDIENTE', 5),
(18, '2026-11-16', 'PENDIENTE', 6),
(19, '2026-12-14', 'EN CURSO', 6),
(20, '2027-01-11', 'PENDIENTE', 6);

-- --------------------------------------------------------

--
-- Table structure for table `codigos_contables`
--

CREATE TABLE `codigos_contables` (
  `id_codigo` bigint(20) NOT NULL COMMENT 'identificador del numero de cuenta',
  `codigo_contable` varchar(14) NOT NULL COMMENT 'codigo del numero de cuenta',
  `descripcion` text DEFAULT NULL COMMENT 'descripción del uso del código de cuenta',
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `codigos_contables`
--

INSERT INTO `codigos_contables` (`id_codigo`, `codigo_contable`, `descripcion`, `activo`) VALUES
(1, '51221001-22101', 'ALIMENTACION', 1),
(2, '51246001-24601', 'MATERIAL ELECTRICO', 1),
(3, '51261001-26101', 'GASOLINA', 1),
(4, '51291001-29101', 'HERRAMIENTAS MENORES', 1),
(5, '51294001-29401', 'REFACCIONES DE LAS TICS', 1),
(6, '51296001-29601', 'REFACCIONES EQUIPO DE TRANSPORTE', 1),
(7, '51327003-32703', 'LICENCIAS (ARRENDAMIENTOS ACTIVOS INTANGIBLES)', 1),
(8, '51353001-35301', 'MANTENIMIENTO DE LAS TICS', 1),
(9, '51355001-35501', 'MANTENIMIENTO DE EQUIPO DE TRANSPORTE', 1),
(10, '51371001-37101', 'PASAJES AEREOS', 1),
(11, '51372001-37201', 'PASAJES TERRESTRES', 1),
(12, '51372002-37202', 'CUOTAS Y PEAJES', 1),
(13, '51375001-37501', 'VIATICOS EN EL PAIS', 1);

-- --------------------------------------------------------

--
-- Table structure for table `comisiones`
--

CREATE TABLE `comisiones` (
  `id_comision` bigint(20) NOT NULL COMMENT 'identificador de la comisión',
  `fecha` date NOT NULL COMMENT 'fecha en la que se registra la solicitud de comisión',
  `tipo_comision` varchar(30) NOT NULL COMMENT 'tipo de comisión (urbana o fuera de la mancha urbana)',
  `tipo_destino` varchar(50) NOT NULL,
  `area_designada` bigint(20) NOT NULL COMMENT 'area encargada de realizar la comisión',
  `ubicacion` varchar(150) NOT NULL COMMENT 'ubicación de la institución de solicita la comsión',
  `institucion_solicita` varchar(150) DEFAULT NULL COMMENT 'nombre de la institución que solicita la comisión',
  `nombre_solicita` varchar(150) DEFAULT NULL COMMENT 'nombre de la persona que solicita la comisión',
  `id_personal` bigint(20) NOT NULL COMMENT 'identificador del personal que registra la comisión',
  `estado` varchar(30) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_final` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidencias`
--

CREATE TABLE `incidencias` (
  `id_incidencia` bigint(20) NOT NULL,
  `id_personal` bigint(20) NOT NULL COMMENT 'identificador del personal',
  `id_autoriza` bigint(20) DEFAULT NULL COMMENT 'identificador del personal que autoriza o rechaza el seguimiento a la incidencia',
  `descripcion` text NOT NULL COMMENT 'descripción de la incidencia',
  `fecha` date NOT NULL COMMENT 'fecha en la que se reporta la incidencia',
  `urgencia` varchar(20) NOT NULL COMMENT 'urgencia de la incidencia (baja, media o alta)',
  `estado` varchar(20) NOT NULL COMMENT 'estado de revisión en que se encuentra la incidencia (pendiente, aceptada o rechazada)',
  `lugar` varchar(60) NOT NULL COMMENT 'lugar de la dirección en el que ocurrió la incidencia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incidencias`
--

INSERT INTO `incidencias` (`id_incidencia`, `id_personal`, `id_autoriza`, `descripcion`, `fecha`, `urgencia`, `estado`, `lugar`) VALUES
(2, 20, 20, 'se cayó el techo y esta roto el suelo', '2026-11-09', 'MEDIA', 'ACEPTADA', 'pasillo afuera del departamento de sistemas'),
(3, 20, 20, 'se murio', '2026-11-11', 'ALTA', 'ACEPTADA', 'en el departamento de sistrenas'),
(4, 20, 20, 'ssss', '2026-11-11', 'MEDIA', 'ACEPTADA', 'ennnn'),
(5, 20, 20, 'se fundió un foco y quemo el techo', '2026-11-11', 'BAJA', 'ACEPTADA', 'departamento de sistemas'),
(6, 20, 13, 'se rompió el suelo', '2026-11-17', 'MEDIA', 'ACEPTADA', 'pasillo de la cocina'),
(7, 13, 13, 'sse rompio una puerta', '2026-11-17', 'MEDIA', 'ACEPTADA', 'en la cocina'),
(8, 20, 18, 'exploto el microondas', '2026-11-17', 'MEDIA', 'ACEPTADA', 'cocina'),
(9, 16, 18, 'se cayó un pedazo del techo y se esta pudriendo alrededor de ello', '2026-11-17', 'BAJA', 'ACEPTADA', 'en l,a oficina de carlitos'),
(10, 20, 14, 'kknn', '2026-11-18', 'MEDIA', 'ACEPTADA', 'cocina'),
(11, 14, 14, 'sssssss', '2026-11-19', 'BAJA', 'ACEPTADA', 'sssssss'),
(12, 14, 14, 'ssss', '2026-11-19', 'MEDIA', 'ACEPTADA', 'sss'),
(13, 14, 14, 'sssss', '2026-11-19', 'BAJA', 'ACEPTADA', 'sss'),
(14, 14, 14, 'sssss', '2026-11-19', 'ALTA', 'ACEPTADA', 'sss');

-- --------------------------------------------------------

--
-- Table structure for table `orden_servicio`
--

CREATE TABLE `orden_servicio` (
  `id_orden` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `n_reporte` int(20) NOT NULL,
  `responsable_institucion` varchar(150) NOT NULL,
  `nombre_institucion` varchar(150) NOT NULL,
  `clave_institucion` varchar(10) NOT NULL,
  `domicilio_institucion` varchar(150) NOT NULL,
  `localidad_institucion` varchar(50) NOT NULL,
  `tel_institucion` varchar(10) NOT NULL,
  `falla_reportada` text NOT NULL,
  `actividad_realizada` text NOT NULL,
  `observaciones` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal`
--

CREATE TABLE `personal` (
  `id_personal` bigint(20) NOT NULL COMMENT 'identificador del personal',
  `rfc` varchar(13) NOT NULL COMMENT 'rfc del personal',
  `curp` varchar(18) NOT NULL COMMENT 'curp del personal',
  `nombre` varchar(20) NOT NULL COMMENT 'nombre del personal',
  `apellido_p` varchar(25) NOT NULL COMMENT 'apellido paterno del personal',
  `apellido_m` varchar(25) NOT NULL COMMENT 'apellido materno del personal',
  `correo` varchar(150) NOT NULL COMMENT 'correo asignado del personal. con este correo ingresará al sistema',
  `telefono` varchar(12) NOT NULL COMMENT 'número de telefono del personal',
  `puesto` bigint(20) NOT NULL COMMENT 'identificador del puesto del personal',
  `area` bigint(20) DEFAULT NULL COMMENT 'identificador del departamento o subdirección a la que pertenece el personal',
  `id_jefe` bigint(20) DEFAULT NULL COMMENT 'identificador del personal que está a cargo del personal registrado',
  `activo` char(1) NOT NULL COMMENT 'estado en el que se encuentra el personal (1=activo, 0=inactivo)',
  `contrasena` varchar(255) NOT NULL COMMENT 'contraseña del personal para ingresar al sistema'
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
(20, 'son13valores6', 'rils060301hdgytba6', 'Itzel', 'Ochoa', 'Gandara', 'itzel@gmail.com', '6181161707', 3, 3, 18, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu'),
(21, 'son13valores7', 'rils060301hdgytba7', 'Mafer', 'luna', 'soto', 'mafer@gmail.com', '6181161708', 4, 3, 20, '1', '$2y$10$abcdefghijklmnopqrstuuq3FgY9h6pgSXk4AFFgiWBGrnbPlzyyu');

-- --------------------------------------------------------

--
-- Table structure for table `personal_comisionado`
--

CREATE TABLE `personal_comisionado` (
  `id_personalcomisionado` bigint(20) NOT NULL COMMENT 'identificador de la tabla',
  `id_comision` bigint(20) DEFAULT NULL COMMENT 'identificador de la comisión en tramite',
  `id_personal` bigint(20) DEFAULT NULL COMMENT 'identificador del personal comisionado',
  `viaticos` decimal(10,2) DEFAULT NULL COMMENT 'viaticos destinados al personal comisionado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `puestos`
--

CREATE TABLE `puestos` (
  `id_puesto` bigint(20) NOT NULL COMMENT 'identificador del puesto',
  `nombre` text NOT NULL COMMENT 'nombre del puesto de trabajo',
  `descripcion` text NOT NULL COMMENT 'descripción del puesto de trabajo\r\n'
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
  `id_reporte` bigint(20) NOT NULL COMMENT 'identificador del reporte',
  `tipo` text NOT NULL COMMENT 'guarda información sobre la procedencia del reporte (reporte por incidencia o calendario)',
  `id_calendario` bigint(20) DEFAULT NULL COMMENT 'identificador del calendario',
  `id_incidencia` bigint(20) DEFAULT NULL COMMENT 'identificador de la incidencia',
  `id_personal` bigint(20) DEFAULT NULL COMMENT 'identificador del personal',
  `fecha_inicio` date NOT NULL COMMENT 'fecha en la que se comienza el reporte del servicio',
  `fecha_final` date DEFAULT NULL COMMENT 'fecha en la que se finaliza el servicio y su reporte',
  `estado` varchar(20) NOT NULL COMMENT 'estado en el que se encuentra el servicio de mantenimiento (en curso, en espera de requisicon, finalizado)',
  `descripcion` text DEFAULT NULL COMMENT 'descripción del servicio realizado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reportes`
--

INSERT INTO `reportes` (`id_reporte`, `tipo`, `id_calendario`, `id_incidencia`, `id_personal`, `fecha_inicio`, `fecha_final`, `estado`, `descripcion`) VALUES
(2, 'incidencia', NULL, NULL, NULL, '2026-11-04', NULL, 'pendiente', 'si'),
(3, 'incidencia', NULL, 3, NULL, '2026-11-11', '2026-11-17', 'FINALIZADO', 'lo revivieron'),
(4, 'incidencia', NULL, 4, NULL, '2026-11-11', NULL, 'EN ESPERA DE RECURSO', 'ssss'),
(5, 'incidencia', NULL, 5, NULL, '2026-11-11', '2026-11-10', 'FINALIZADO', 'se compró un nuevo foco en tal sucursal con tal dinero y lo puso tal poersona'),
(6, 'incidencia', NULL, 6, NULL, '2026-11-17', '2026-11-17', 'FINALIZADO', 'se resano el piso con dos horas de antricipacion'),
(7, 'incidencia', NULL, 7, NULL, '2026-11-17', NULL, 'EN ESPERA DE RECURSO', ''),
(8, 'incidencia', NULL, 8, 18, '2026-11-17', NULL, 'EN ESPERA DE RECURSO', ''),
(9, 'incidencia', NULL, 9, 18, '2026-11-17', NULL, 'EN ESPERA DE RECURSO', ''),
(10, 'incidencia', NULL, 10, 14, '2026-11-18', NULL, 'EN ESPERA DE RECURSO', ''),
(11, 'servicio programado', 19, NULL, 13, '2026-11-19', NULL, 'EN CURSO', ''),
(12, 'incidencia', NULL, 11, 14, '2026-11-19', NULL, 'EN ESPERA DE RECURSO', ''),
(13, 'incidencia', NULL, 12, 14, '2026-11-19', NULL, 'EN ESPERA DE RECURSO', ''),
(14, 'incidencia', NULL, 13, 14, '2026-11-19', NULL, 'EN ESPERA DE RECURSO', ''),
(15, 'incidencia', NULL, 14, 14, '2026-11-19', NULL, 'EN ESPERA DE RECURSO', '');

-- --------------------------------------------------------

--
-- Table structure for table `requisiciones`
--

CREATE TABLE `requisiciones` (
  `id_requisicion` bigint(20) NOT NULL COMMENT 'identificador de requisición',
  `tipo_requisicion` varchar(30) NOT NULL,
  `id_reporte` bigint(20) NOT NULL COMMENT 'identificador del reporte',
  `id_solicita` bigint(20) NOT NULL COMMENT 'identificador del personal solicitante de la requisición',
  `id_autoriza` bigint(20) DEFAULT NULL COMMENT 'identificador del personal que autoriza la requisición',
  `fecha` date NOT NULL COMMENT 'fecha en la que se realiza la requisición',
  `fecha_entrega` date DEFAULT NULL COMMENT 'fecha en la que se entrega la requisición a las oficinas centrales',
  `justificacion` text DEFAULT NULL,
  `descripcion_servicio` text NOT NULL COMMENT 'descripción del servicio para el que se necesita la requisición',
  `estado` varchar(20) NOT NULL COMMENT 'estado en el que se encuentra la requisición (pendiente, en curso, aprovada o rechazada)',
  `tipo_financiamiento` text NOT NULL COMMENT 'tipo de financiamiento que recibe la requisición',
  `id_elabora` bigint(20) NOT NULL COMMENT 'identificador del personal que realiza el tramite de la requisición',
  `tipo_codigo` enum('CC_DIRECCION','CC_CENTRAL') NOT NULL COMMENT 'Tipo de codigo contable al que se cargara la requisición',
  `id_codigo` bigint(20) DEFAULT NULL COMMENT 'identificador del codigo contable',
  `cc_central` varchar(14) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisiciones`
--

INSERT INTO `requisiciones` (`id_requisicion`, `tipo_requisicion`, `id_reporte`, `id_solicita`, `id_autoriza`, `fecha`, `fecha_entrega`, `justificacion`, `descripcion_servicio`, `estado`, `tipo_financiamiento`, `id_elabora`, `tipo_codigo`, `id_codigo`, `cc_central`) VALUES
(2, '', 2, 14, 13, '2026-11-04', NULL, NULL, 'necesito dos panes', 'pendiente', 'est', 0, 'CC_DIRECCION', NULL, NULL),
(3, '', 2, 14, NULL, '2026-11-04', NULL, NULL, 'sss', 'sss', 'sss', 0, 'CC_DIRECCION', NULL, NULL),
(4, '', 9, 18, 13, '2026-11-17', NULL, NULL, 'reparacion de techo dentro de la dirección\ncuadros de tablaroca para techo acondicionado', 'EN ESPERA', 'FONE', 18, 'CC_DIRECCION', NULL, NULL),
(5, '', 8, 18, 13, '2026-11-17', NULL, NULL, 'mantenimiento de la cocina de la dirección\nun microondas nuevo', 'EN ESPERA', 'FONE', 18, 'CC_DIRECCION', NULL, NULL),
(6, '', 10, 14, 13, '2026-11-18', NULL, 'JJ', 'cocin', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', NULL, NULL),
(7, 'SERVICIOS', 7, 13, 13, '2026-11-19', NULL, 'de nuevo', 'mantenimiento', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 4, NULL),
(8, 'SERVICIOS', 4, 20, 13, '2026-11-19', NULL, 'adecuacion de luminarias dentro de la direccion, con el objetivo de mejorar el rendimiento dentro de ella', 'mantenimiento de luminarias en la direccion\nfocos electricos led de 45x15 cm', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 2, NULL),
(9, 'MATERIALES', 12, 14, 13, '2026-11-19', NULL, 'sssssss', 'ssss\nssss\nsss', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 2, NULL),
(10, 'SERVICIOS', 13, 14, 13, '2026-11-19', NULL, 'sssss', 'sss\nss', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 5, NULL),
(11, 'SERVICIOS', 14, 14, 13, '2026-11-19', NULL, 'sssss', 'ssss\nsss', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 4, NULL),
(12, 'MATERIALES', 15, 14, 13, '2026-11-11', NULL, 'dadfvcc', 'ssss\nsdedfd\nsss', 'EN ESPERA', 'FONE', 14, 'CC_DIRECCION', 5, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requisiciones_detalle`
--

CREATE TABLE `requisiciones_detalle` (
  `id_detalle` bigint(20) NOT NULL,
  `id_requisicion` bigint(20) NOT NULL,
  `num_lote` varchar(50) DEFAULT NULL,
  `cantidad` int(3) DEFAULT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisiciones_detalle`
--

INSERT INTO `requisiciones_detalle` (`id_detalle`, `id_requisicion`, `num_lote`, `cantidad`, `descripcion`) VALUES
(1, 4, NULL, NULL, 'reparacion de techo dentro de la dirección'),
(2, 4, '1', 4, 'cuadros de tablaroca para techo acondicionado'),
(3, 5, NULL, NULL, 'mantenimiento de la cocina de la dirección'),
(4, 5, '1', 1, 'un microondas nuevo'),
(5, 6, '1', 2, 'cocin'),
(6, 7, NULL, NULL, 'mantenimiento'),
(7, 8, NULL, NULL, 'mantenimiento de luminarias en la direccion'),
(8, 8, '1', 45, 'focos electricos led de 45x15 cm'),
(9, 9, NULL, NULL, 'ssss'),
(10, 9, '1', 12, 'ssss'),
(11, 9, '2', 22, 'sss'),
(12, 10, NULL, NULL, 'sss'),
(13, 10, '1', 2, 'ss'),
(14, 11, NULL, NULL, 'ssss'),
(15, 11, '1', 222, 'sss'),
(16, 12, NULL, NULL, 'ssss'),
(17, 12, '1', 33, 'sdedfd'),
(18, 12, '2', 33, 'sss');

-- --------------------------------------------------------

--
-- Table structure for table `salidas`
--

CREATE TABLE `salidas` (
  `id_salida` bigint(20) NOT NULL COMMENT 'identificador de la salida',
  `tipo_salida` varchar(25) NOT NULL COMMENT 'tipo de salida (medica, personal o cita)',
  `motivo` text NOT NULL COMMENT 'razón del personal para realizar la salida',
  `destino` text NOT NULL COMMENT 'el lugar a donde se dirije el personal',
  `fecha` date NOT NULL COMMENT 'fecha en la que se realiza la salida',
  `hora_salida` time DEFAULT NULL COMMENT 'hora en la que se aprueba la salida',
  `hora_llegada` time DEFAULT NULL COMMENT 'hora en la que se registra el regreso del personal a la dirección',
  `estado` text NOT NULL COMMENT 'guarda el estaod de la salida (pendiente o concluida)',
  `id_personal` bigint(20) NOT NULL COMMENT 'identificador del personal que realiza la salida',
  `id_autoriza` bigint(20) DEFAULT NULL COMMENT 'identificador del personal que le da revisión a la salida',
  `solicita_respaldo` tinyint(1) NOT NULL DEFAULT 0,
  `requiere_justificante` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'se refiere a si la salida requiere justificante o no, esto a consideración del personal que revise la salida (1=si, 0=no)',
  `hora_estimada` time DEFAULT NULL COMMENT 'hora limite para el regreso del personal (2h después de iniciar la salida)',
  `adeudo_tiempo` time DEFAULT NULL COMMENT 'en caso de que el personal haya cerrado su salida después de la hora estimada, este campo guarda el tiempo de diferencia que hubo',
  `justificante_entregado` tinyint(1) DEFAULT NULL COMMENT 'guarda la información sobre si se entregó el justificante o no en caso de ser necesario (1=si, 0=no)',
  `fecha_justificante_entregado` datetime DEFAULT NULL COMMENT 'fecha en la que se entregó el justificante en caso de que haya sido solicitado',
  `fecha_llegada` date DEFAULT NULL COMMENT 'fecha que registra cuando se cerro por completo la salida en caso de que haya habido complicaciones con el justificante'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salidas`
--

INSERT INTO `salidas` (`id_salida`, `tipo_salida`, `motivo`, `destino`, `fecha`, `hora_salida`, `hora_llegada`, `estado`, `id_personal`, `id_autoriza`, `solicita_respaldo`, `requiere_justificante`, `hora_estimada`, `adeudo_tiempo`, `justificante_entregado`, `fecha_justificante_entregado`, `fecha_llegada`) VALUES
(7, 'PERSONAL', 'Voy por el festival del día de las madres en la escuela de mi hijo', 'Secundaria #3', '2026-10-22', '16:59:40', NULL, 'FINALIZADO', 21, NULL, 0, 0, '18:59:40', NULL, 0, NULL, '0000-00-00'),
(8, 'MEDICA', 'me duele la panza', 'issste', '2026-10-25', NULL, NULL, 'RECHAZADA', 21, NULL, 0, 0, NULL, NULL, 0, NULL, '0000-00-00'),
(9, 'PERSONAL', 'voy a cuidar a mi mama', 'manuel rosas #304', '2026-10-26', NULL, NULL, 'RECHAZADA', 21, NULL, 0, 0, NULL, NULL, 0, NULL, '0000-00-00'),
(10, 'MEDICA', 'me duele todo', 'xicoli 305', '2026-10-27', '08:42:30', '09:46:52', 'FINALIZADO', 21, 20, 0, 0, '10:42:30', NULL, 0, NULL, '0000-00-00'),
(11, 'MEDICA', 'me rompi la pierna', 'no se 103', '2026-10-28', '11:45:40', '11:46:02', 'FINALIZADO', 16, 15, 0, 0, '13:45:40', NULL, 0, NULL, '0000-00-00'),
(12, 'PERSONAL', 'jjj', 'ñññ', '2026-11-09', '13:28:22', '13:28:45', 'FINALIZADO', 21, 20, 0, 0, '15:28:22', NULL, NULL, NULL, NULL),
(14, 'MEDICA', 'ww', 'ww', '2026-11-09', NULL, '13:56:41', 'FINALIZADO', 13, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(16, 'MEDICA', 'sss', 'casa #403', '2026-11-09', '14:22:53', '14:26:18', 'FINALIZADO', 21, 20, 1, 0, '16:22:53', NULL, NULL, NULL, NULL),
(17, 'MEDICA', '123', '7777', '2026-11-09', '14:28:46', '19:05:06', 'FINALIZADO', 21, 20, 0, 0, '16:28:46', '02:36:20', NULL, NULL, '2026-11-09'),
(18, 'PERSONAL', 'qqqq', 'qqq', '2026-11-10', '19:05:53', '19:06:02', 'FINALIZADO', 21, 20, 0, 1, '21:05:53', NULL, 1, NULL, '2026-11-09'),
(19, 'CITA', 'sss', 'sss', '2026-11-11', NULL, NULL, 'RECHAZADA', 21, 20, 0, 0, NULL, NULL, NULL, NULL, NULL),
(20, 'MEDICA', 'Cita con el doctor', 'ISSSTE', '2026-11-11', '20:50:10', '20:56:18', 'FINALIZADO', 20, 14, 0, 0, '22:50:10', NULL, NULL, NULL, '2026-11-10'),
(21, 'PERSONAL', 'voy al recital de mi hijo, va a decir las efemerides', 'primaria Manuel Rosas Santillan', '2026-11-17', '15:03:44', '15:04:15', 'FINALIZADO', 21, 20, 0, 0, '17:03:44', NULL, NULL, NULL, '2026-11-17'),
(22, 'PERSONAL', 'sss', '1ss', '2026-11-17', NULL, NULL, 'PENDIENTE', 15, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL),
(23, 'CITA', 'ss', 'sss', '2026-11-17', '15:05:02', '15:05:17', 'FINALIZADO', 20, 18, 0, 1, '17:05:02', NULL, 1, '2026-11-17 15:05:56', '2026-11-17');

-- --------------------------------------------------------

--
-- Table structure for table `servicios`
--

CREATE TABLE `servicios` (
  `id_servicio` bigint(20) NOT NULL COMMENT 'identificador del servicio',
  `nombre` varchar(150) NOT NULL COMMENT 'nombre del servicio',
  `periodicidad` int(3) NOT NULL COMMENT 'periodicidad del servicio en semanas',
  `descripcion` text NOT NULL COMMENT 'descricpción del trabajo que se realiza para el servicio',
  `activo` tinyint(1) NOT NULL COMMENT 'guarda información sobre si el servicio sigue siendo realizado por el personal o no (1=si, 0=no)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `servicios`
--

INSERT INTO `servicios` (`id_servicio`, `nombre`, `periodicidad`, `descripcion`, `activo`) VALUES
(2, 'ssss', 2, 'sss', 0),
(3, 'revision de extintores', 4, 'revision del llenado y funcionalidad de los extintores', 0),
(4, 'impermeabilización de techos', 42, 'se tiene que impermeabilizar el techo con', 0),
(5, 'mantenimiento de luminarias', 5, 'prender luminarias', 1),
(6, 'areas verdes', 4, 'jjj', 0);

-- --------------------------------------------------------

--
-- Table structure for table `solicitud_soporte`
--

CREATE TABLE `solicitud_soporte` (
  `id_solicitud` bigint(20) NOT NULL,
  `direccion` varchar(150) NOT NULL,
  `departamento` varchar(100) NOT NULL,
  `nombre_reporta` varchar(150) NOT NULL,
  `cisco` varchar(5) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `tecnico_asignado` bigint(20) NOT NULL,
  `solicitud_falla` text NOT NULL,
  `equipos_atendidos` int(4) NOT NULL,
  `usuarios_beneficiados` int(4) NOT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabulador_viaticos`
--

CREATE TABLE `tabulador_viaticos` (
  `id_tabulador` bigint(20) NOT NULL,
  `id_puesto` bigint(20) NOT NULL,
  `id_zona` bigint(20) NOT NULL,
  `tipo_comprobacion` enum('FACTURA','CONSTANCIA') NOT NULL,
  `pernocta` tinyint(1) NOT NULL COMMENT '0 = Sin pernoctar, 1 = Pernoctar',
  `monto_diario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tabulador_viaticos`
--

INSERT INTO `tabulador_viaticos` (`id_tabulador`, `id_puesto`, `id_zona`, `tipo_comprobacion`, `pernocta`, `monto_diario`) VALUES
(1, 1, 1, 'FACTURA', 0, 500.00),
(2, 1, 1, 'FACTURA', 1, 1250.00),
(3, 2, 1, 'FACTURA', 0, 400.00),
(4, 2, 1, 'FACTURA', 1, 1050.00),
(5, 3, 1, 'FACTURA', 0, 390.00),
(6, 3, 1, 'FACTURA', 1, 1000.00),
(7, 4, 1, 'FACTURA', 0, 350.00),
(8, 4, 1, 'FACTURA', 1, 900.00),
(9, 1, 1, 'CONSTANCIA', 0, 380.00),
(10, 1, 1, 'CONSTANCIA', 1, 760.00),
(11, 2, 1, 'CONSTANCIA', 0, 360.00),
(12, 2, 1, 'CONSTANCIA', 1, 720.00),
(13, 3, 1, 'CONSTANCIA', 0, 315.00),
(14, 3, 1, 'CONSTANCIA', 1, 630.00),
(15, 4, 1, 'CONSTANCIA', 0, 279.00),
(16, 4, 1, 'CONSTANCIA', 1, 558.00),
(17, 1, 2, 'FACTURA', 0, 700.00),
(18, 1, 2, 'FACTURA', 1, 1600.00),
(19, 2, 2, 'FACTURA', 0, 500.00),
(20, 2, 2, 'FACTURA', 1, 1300.00),
(21, 3, 2, 'FACTURA', 0, 450.00),
(22, 3, 2, 'FACTURA', 1, 1191.00),
(23, 4, 2, 'FACTURA', 0, 365.00),
(24, 4, 2, 'FACTURA', 1, 916.00),
(25, 1, 3, 'FACTURA', 0, 881.00),
(26, 1, 3, 'FACTURA', 1, 2221.00),
(27, 2, 3, 'FACTURA', 0, 549.00),
(28, 2, 3, 'FACTURA', 1, 1832.00),
(29, 3, 3, 'FACTURA', 0, 458.00),
(30, 3, 3, 'FACTURA', 1, 1465.00),
(31, 4, 3, 'FACTURA', 0, 367.00),
(32, 4, 3, 'FACTURA', 1, 1191.00),
(33, 1, 4, 'FACTURA', 0, 1258.00),
(34, 1, 4, 'FACTURA', 1, 2749.00),
(35, 2, 4, 'FACTURA', 0, 899.00),
(36, 2, 4, 'FACTURA', 1, 2108.00),
(37, 3, 4, 'FACTURA', 0, 719.00),
(38, 3, 4, 'FACTURA', 1, 1832.00),
(39, 4, 4, 'FACTURA', 0, 663.00),
(40, 4, 4, 'FACTURA', 1, 1691.00);

-- --------------------------------------------------------

--
-- Table structure for table `zonas_viaticos`
--

CREATE TABLE `zonas_viaticos` (
  `id_zona` bigint(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `permite_constancia` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `zonas_viaticos`
--

INSERT INTO `zonas_viaticos` (`id_zona`, `nombre`, `permite_constancia`) VALUES
(1, 'Dentro del estado más de 150 km', 1),
(2, 'Cabeceras municipales de Santiago Papasq., Santa María del Oro', 0),
(3, 'Zona Metropolitana Lagunera (Dgo-Coahuila)', 0),
(4, 'Otros estados (Mex-Mty-Jal-Etc.)', 0);

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
  ADD PRIMARY KEY (`id_calendario`),
  ADD KEY `id_servicio` (`id_servicio`);

--
-- Indexes for table `codigos_contables`
--
ALTER TABLE `codigos_contables`
  ADD PRIMARY KEY (`id_codigo`),
  ADD UNIQUE KEY `cuenta_cargo` (`codigo_contable`);

--
-- Indexes for table `comisiones`
--
ALTER TABLE `comisiones`
  ADD PRIMARY KEY (`id_comision`),
  ADD KEY `fk_area_designada` (`area_designada`),
  ADD KEY `fk_id_personal` (`id_personal`);

--
-- Indexes for table `incidencias`
--
ALTER TABLE `incidencias`
  ADD PRIMARY KEY (`id_incidencia`),
  ADD KEY `id_personal` (`id_personal`),
  ADD KEY `id_autoriza` (`id_autoriza`);

--
-- Indexes for table `orden_servicio`
--
ALTER TABLE `orden_servicio`
  ADD PRIMARY KEY (`id_orden`);

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
-- Indexes for table `personal_comisionado`
--
ALTER TABLE `personal_comisionado`
  ADD PRIMARY KEY (`id_personalcomisionado`),
  ADD KEY `fk_id_comision_en_personalcomisionado` (`id_comision`),
  ADD KEY `fk_id_personal_en_personalcomisionado` (`id_personal`);

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
  ADD KEY `id_calendario` (`id_calendario`),
  ADD KEY `id_incidencia` (`id_incidencia`),
  ADD KEY `id_personal` (`id_personal`);

--
-- Indexes for table `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD PRIMARY KEY (`id_requisicion`),
  ADD KEY `id_solicita` (`id_solicita`),
  ADD KEY `id_autoriza` (`id_autoriza`),
  ADD KEY `id_reporte` (`id_reporte`),
  ADD KEY `id_elabora` (`id_elabora`),
  ADD KEY `fk_id_codigo` (`id_codigo`);

--
-- Indexes for table `requisiciones_detalle`
--
ALTER TABLE `requisiciones_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_requisicion` (`id_requisicion`);

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
-- Indexes for table `solicitud_soporte`
--
ALTER TABLE `solicitud_soporte`
  ADD KEY `tecnico_asignado` (`tecnico_asignado`);

--
-- Indexes for table `tabulador_viaticos`
--
ALTER TABLE `tabulador_viaticos`
  ADD PRIMARY KEY (`id_tabulador`),
  ADD UNIQUE KEY `uk_tarifa_unica` (`id_puesto`,`id_zona`,`tipo_comprobacion`,`pernocta`),
  ADD KEY `fk_tabulador_zona` (`id_zona`);

--
-- Indexes for table `zonas_viaticos`
--
ALTER TABLE `zonas_viaticos`
  ADD PRIMARY KEY (`id_zona`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id_area` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de área\r\n', AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id_calendario` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del calendario', AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `codigos_contables`
--
ALTER TABLE `codigos_contables`
  MODIFY `id_codigo` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del numero de cuenta', AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `comisiones`
--
ALTER TABLE `comisiones`
  MODIFY `id_comision` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de la comisión';

--
-- AUTO_INCREMENT for table `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id_incidencia` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orden_servicio`
--
ALTER TABLE `orden_servicio`
  MODIFY `id_orden` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del personal', AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `personal_comisionado`
--
ALTER TABLE `personal_comisionado`
  MODIFY `id_personalcomisionado` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de la tabla';

--
-- AUTO_INCREMENT for table `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del puesto', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del reporte', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id_requisicion` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de requisición', AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `requisiciones_detalle`
--
ALTER TABLE `requisiciones_detalle`
  MODIFY `id_detalle` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id_salida` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de la salida', AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del servicio', AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tabulador_viaticos`
--
ALTER TABLE `tabulador_viaticos`
  MODIFY `id_tabulador` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `zonas_viaticos`
--
ALTER TABLE `zonas_viaticos`
  MODIFY `id_zona` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_ibfk_1` FOREIGN KEY (`id_subdireccion`) REFERENCES `areas` (`id_area`);

--
-- Constraints for table `calendario`
--
ALTER TABLE `calendario`
  ADD CONSTRAINT `id_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios` (`id_servicio`);

--
-- Constraints for table `comisiones`
--
ALTER TABLE `comisiones`
  ADD CONSTRAINT `fk_area_designada` FOREIGN KEY (`area_designada`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `fk_id_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `incidencias`
--
ALTER TABLE `incidencias`
  ADD CONSTRAINT `incidencias_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `incidencias_ibfk_2` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `personal`
--
ALTER TABLE `personal`
  ADD CONSTRAINT `personal_ibfk_1` FOREIGN KEY (`area`) REFERENCES `areas` (`id_area`),
  ADD CONSTRAINT `personal_ibfk_2` FOREIGN KEY (`puesto`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `personal_ibfk_3` FOREIGN KEY (`id_jefe`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `personal_comisionado`
--
ALTER TABLE `personal_comisionado`
  ADD CONSTRAINT `fk_id_comision_en_personalcomisionado` FOREIGN KEY (`id_comision`) REFERENCES `comisiones` (`id_comision`),
  ADD CONSTRAINT `fk_id_personal_en_personalcomisionado` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `id_calendario` FOREIGN KEY (`id_calendario`) REFERENCES `calendario` (`id_calendario`),
  ADD CONSTRAINT `id_incidencia` FOREIGN KEY (`id_incidencia`) REFERENCES `incidencias` (`id_incidencia`),
  ADD CONSTRAINT `id_personal` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `requisiciones`
--
ALTER TABLE `requisiciones`
  ADD CONSTRAINT `fk_id_codigo` FOREIGN KEY (`id_codigo`) REFERENCES `codigos_contables` (`id_codigo`),
  ADD CONSTRAINT `id_autoriza` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `id_solicita` FOREIGN KEY (`id_solicita`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `requisiciones_ibfk_1` FOREIGN KEY (`id_reporte`) REFERENCES `reportes` (`id_reporte`);

--
-- Constraints for table `requisiciones_detalle`
--
ALTER TABLE `requisiciones_detalle`
  ADD CONSTRAINT `requisiciones_detalle_ibfk_1` FOREIGN KEY (`id_requisicion`) REFERENCES `requisiciones` (`id_requisicion`);

--
-- Constraints for table `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `salidas_ibfk_1` FOREIGN KEY (`id_personal`) REFERENCES `personal` (`id_personal`),
  ADD CONSTRAINT `salidas_ibfk_2` FOREIGN KEY (`id_autoriza`) REFERENCES `personal` (`id_personal`);

--
-- Constraints for table `tabulador_viaticos`
--
ALTER TABLE `tabulador_viaticos`
  ADD CONSTRAINT `fk_tabulador_puesto` FOREIGN KEY (`id_puesto`) REFERENCES `puestos` (`id_puesto`),
  ADD CONSTRAINT `fk_tabulador_zona` FOREIGN KEY (`id_zona`) REFERENCES `zonas_viaticos` (`id_zona`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
