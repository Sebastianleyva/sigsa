-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2026 at 03:37 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `comisiones`
--

CREATE TABLE `comisiones` (
  `id_comision` bigint(20) NOT NULL,
  `id_solicitud` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `tipo_comision` varchar(30) NOT NULL,
  `tipo_destino` varchar(50) NOT NULL,
  `id_area` bigint(20) NOT NULL,
  `estado` varchar(30) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_final` date DEFAULT NULL
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
  `municipio_institucion` varchar(50) NOT NULL,
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
  `contrasena` varchar(255) NOT NULL COMMENT 'contraseña del personal para ingresar al sistema',
  `comisionado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_comisionado`
--

CREATE TABLE `personal_comisionado` (
  `id_personalcomisionado` bigint(20) NOT NULL COMMENT 'identificador de la tabla',
  `id_comision` bigint(20) NOT NULL COMMENT 'identificador de la comisión en tramite',
  `id_personal` bigint(20) NOT NULL COMMENT 'identificador del personal comisionado',
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

-- --------------------------------------------------------

--
-- Table structure for table `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id_solicitud` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `institucion_solicita` varchar(150) NOT NULL,
  `ubicacion` varchar(150) NOT NULL,
  `nombre_solicita` varchar(150) NOT NULL,
  `falla_reportada` text NOT NULL,
  `id_personal` bigint(20) NOT NULL,
  `comisionada` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `id_solicitud` (`id_solicitud`),
  ADD KEY `id_area` (`id_area`);

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
-- Indexes for table `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `id_personal` (`id_personal`);

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
  MODIFY `id_area` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de área\r\n';

--
-- AUTO_INCREMENT for table `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id_calendario` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del calendario';

--
-- AUTO_INCREMENT for table `codigos_contables`
--
ALTER TABLE `codigos_contables`
  MODIFY `id_codigo` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del numero de cuenta';

--
-- AUTO_INCREMENT for table `comisiones`
--
ALTER TABLE `comisiones`
  MODIFY `id_comision` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidencias`
--
ALTER TABLE `incidencias`
  MODIFY `id_incidencia` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orden_servicio`
--
ALTER TABLE `orden_servicio`
  MODIFY `id_orden` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal`
--
ALTER TABLE `personal`
  MODIFY `id_personal` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del personal';

--
-- AUTO_INCREMENT for table `personal_comisionado`
--
ALTER TABLE `personal_comisionado`
  MODIFY `id_personalcomisionado` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de la tabla';

--
-- AUTO_INCREMENT for table `puestos`
--
ALTER TABLE `puestos`
  MODIFY `id_puesto` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del puesto';

--
-- AUTO_INCREMENT for table `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id_reporte` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del reporte';

--
-- AUTO_INCREMENT for table `requisiciones`
--
ALTER TABLE `requisiciones`
  MODIFY `id_requisicion` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de requisición';

--
-- AUTO_INCREMENT for table `requisiciones_detalle`
--
ALTER TABLE `requisiciones_detalle`
  MODIFY `id_detalle` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id_salida` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador de la salida';

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id_servicio` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'identificador del servicio';

--
-- AUTO_INCREMENT for table `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id_solicitud` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tabulador_viaticos`
--
ALTER TABLE `tabulador_viaticos`
  MODIFY `id_tabulador` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `zonas_viaticos`
--
ALTER TABLE `zonas_viaticos`
  MODIFY `id_zona` bigint(20) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `comisiones_ibfk_1` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitudes` (`id_solicitud`);

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
