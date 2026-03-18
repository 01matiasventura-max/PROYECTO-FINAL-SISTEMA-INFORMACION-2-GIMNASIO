SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

CREATE TABLE `accesos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `socio_id` int(10) UNSIGNED NOT NULL,
  `membresia_id` int(10) UNSIGNED NOT NULL,
  `fecha_hora_entrada` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_hora_salida` datetime DEFAULT NULL COMMENT 'NULL = sigue dentro del gimnasio',
  `duracion_min` smallint(5) UNSIGNED GENERATED ALWAYS AS (timestampdiff(MINUTE,`fecha_hora_entrada`,`fecha_hora_salida`)) STORED COMMENT 'Calculado automáticamente al registrar salida',
  `registrado_por` int(10) UNSIGNED DEFAULT NULL COMMENT 'Usuario que registró la entrada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de entradas y salidas al gimnasio';

CREATE TABLE `categorias_equipo` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Categorías de equipos (Cardio, Fuerza, Accesorios, etc.)';

CREATE TABLE `clases` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `instructor_id` int(10) UNSIGNED NOT NULL,
  `duracion_min` smallint(5) UNSIGNED NOT NULL COMMENT 'Duración en minutos',
  `capacidad_max` tinyint(3) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Catálogo de clases ofrecidas (Yoga, Spinning, Zumba, etc.)';

CREATE TABLE `empleados` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Usuario del sistema vinculado (opcional)',
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `salario` decimal(10,2) DEFAULT NULL,
  `fecha_contratacion` date NOT NULL,
  `es_instructor` tinyint(1) NOT NULL DEFAULT 0,
  `especialidad` varchar(200) DEFAULT NULL COMMENT 'Solo si es_instructor = TRUE',
  `bio` text DEFAULT NULL COMMENT 'Descripción pública del instructor',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Personal del gimnasio; los instructores son empleados con es_instructor = TRUE';

CREATE TABLE `equipos` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `costo_adquisicion` decimal(10,2) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL COMMENT 'Sala o área dentro del gimnasio',
  `estado` enum('operativo','mantenimiento','baja') NOT NULL DEFAULT 'operativo',
  `motivo_baja` varchar(255) DEFAULT NULL COMMENT 'Requerido si estado = baja',
  `fecha_baja` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Inventario de máquinas y equipos del gimnasio';

CREATE TABLE `facturas` (
  `id` int(10) UNSIGNED NOT NULL,
  `pago_id` bigint(20) UNSIGNED NOT NULL,
  `numero_factura` varchar(30) NOT NULL,
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL,
  `impuesto_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Facturas opcionales generadas por pago';

CREATE TABLE `horarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `clase_id` int(10) UNSIGNED NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Lunes … 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `sala` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Franjas horarias de cada clase';

CREATE TABLE `mantenimientos` (
  `id` int(10) UNSIGNED NOT NULL,
  `equipo_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('preventivo','correctivo') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_cierre` date DEFAULT NULL COMMENT 'NULL = en curso',
  `descripcion` text NOT NULL,
  `costo` decimal(10,2) DEFAULT 0.00,
  `tecnico` varchar(150) DEFAULT NULL COMMENT 'Nombre o empresa del técnico',
  `registrado_por` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial de mantenimientos por equipo';

DELIMITER $$
CREATE TRIGGER `trg_apertura_mantenimiento_correctivo` AFTER INSERT ON `mantenimientos` FOR EACH ROW BEGIN
    IF NEW.tipo = 'correctivo' THEN
        UPDATE equipos SET estado = 'mantenimiento' WHERE id = NEW.equipo_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_cierre_mantenimiento` AFTER UPDATE ON `mantenimientos` FOR EACH ROW BEGIN
    IF NEW.fecha_cierre IS NOT NULL AND OLD.fecha_cierre IS NULL THEN
        UPDATE equipos SET estado = 'operativo' WHERE id = NEW.equipo_id;
    END IF;
END
$$
DELIMITER ;

CREATE TABLE `membresias` (
  `id` int(10) UNSIGNED NOT NULL,
  `socio_id` int(10) UNSIGNED NOT NULL,
  `plan_id` int(10) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activa','vencida','cancelada','suspendida') NOT NULL DEFAULT 'activa',
  `motivo_cambio` varchar(255) DEFAULT NULL COMMENT 'Razón de cancelación o suspensión',
  `created_by` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que asignó la membresía',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Membresías asignadas a cada socio';

CREATE TABLE `pagos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `socio_id` int(10) UNSIGNED NOT NULL,
  `membresia_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si es pago no vinculado a membresía',
  `concepto` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` enum('efectivo','tarjeta_credito','tarjeta_debito','transferencia','otro') NOT NULL,
  `estado` enum('pagado','pendiente','anulado') NOT NULL DEFAULT 'pagado',
  `motivo_anulacion` varchar(255) DEFAULT NULL COMMENT 'Requerido si estado = anulado',
  `cobrado_por` int(10) UNSIGNED NOT NULL COMMENT 'Usuario que registró el pago',
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de todos los pagos recibidos';

CREATE TABLE `planes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_dias` smallint(5) UNSIGNED NOT NULL COMMENT 'Ej: 30 = mensual, 365 = anual',
  `precio` decimal(10,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Planes / tipos de membresía disponibles';

CREATE TABLE `reservas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `socio_id` int(10) UNSIGNED NOT NULL,
  `horario_id` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL COMMENT 'Fecha específica de la sesión',
  `estado` enum('confirmada','cancelada','asistio') NOT NULL DEFAULT 'confirmada',
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Reservas de socios a sesiones de clases';

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Roles de acceso al sistema (Administrador, Recepcionista, etc.)';

CREATE TABLE `socios` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_socio` varchar(20) NOT NULL COMMENT 'Código único generado',
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `foto_url` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `notas` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'Usuario que registró al socio',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Miembros / clientes del gimnasio';

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `rol_id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `cambiar_pass` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Forzar cambio en primer inicio',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuarios operadores del sistema';

ALTER TABLE `accesos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_accesos_membresia` (`membresia_id`),
  ADD KEY `fk_accesos_usuario` (`registrado_por`),
  ADD KEY `idx_accesos_socio_fecha` (`socio_id`,`fecha_hora_entrada`),
  ADD KEY `idx_accesos_entrada_abierta` (`socio_id`,`fecha_hora_salida`);

ALTER TABLE `categorias_equipo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `clases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_clases_instructor` (`instructor_id`);

ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_serie` (`numero_serie`),
  ADD KEY `idx_equipos_estado` (`estado`),
  ADD KEY `idx_equipos_categoria` (`categoria_id`);

ALTER TABLE `facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pago_id` (`pago_id`),
  ADD UNIQUE KEY `numero_factura` (`numero_factura`);

ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_horarios_sala` (`sala`,`dia_semana`,`hora_inicio`) COMMENT 'Sin traslape por sala',
  ADD KEY `fk_horarios_clase` (`clase_id`),
  ADD KEY `idx_horarios_dia` (`dia_semana`);

ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mant_usuario` (`registrado_por`),
  ADD KEY `idx_mant_equipo` (`equipo_id`);

ALTER TABLE `membresias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_membresias_plan` (`plan_id`),
  ADD KEY `fk_membresias_created` (`created_by`),
  ADD KEY `idx_membresias_socio_estado` (`socio_id`,`estado`),
  ADD KEY `idx_membresias_fecha_fin` (`fecha_fin`);

ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagos_membresia` (`membresia_id`),
  ADD KEY `fk_pagos_usuario` (`cobrado_por`),
  ADD KEY `idx_pagos_socio_fecha` (`socio_id`,`fecha_pago`),
  ADD KEY `idx_pagos_estado` (`estado`);

ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_reserva_unica` (`socio_id`,`horario_id`,`fecha`),
  ADD KEY `fk_reservas_usuario` (`created_by`),
  ADD KEY `idx_reservas_horario_fecha` (`horario_id`,`fecha`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

ALTER TABLE `socios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_socio` (`numero_socio`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_socios_created_by` (`created_by`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_rol` (`rol_id`);

ALTER TABLE `accesos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `categorias_equipo`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `clases`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `empleados`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `equipos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `facturas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `horarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mantenimientos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `membresias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `pagos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `planes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `reservas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `socios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `accesos`
  ADD CONSTRAINT `fk_accesos_membresia` FOREIGN KEY (`membresia_id`) REFERENCES `membresias` (`id`),
  ADD CONSTRAINT `fk_accesos_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`),
  ADD CONSTRAINT `fk_accesos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

ALTER TABLE `clases`
  ADD CONSTRAINT `fk_clases_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `empleados` (`id`);

ALTER TABLE `empleados`
  ADD CONSTRAINT `fk_empleados_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

ALTER TABLE `equipos`
  ADD CONSTRAINT `fk_equipos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_equipo` (`id`);

ALTER TABLE `facturas`
  ADD CONSTRAINT `fk_facturas_pago` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`);

ALTER TABLE `horarios`
  ADD CONSTRAINT `fk_horarios_clase` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`);

ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `fk_mant_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `fk_mant_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

ALTER TABLE `membresias`
  ADD CONSTRAINT `fk_membresias_created` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_membresias_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  ADD CONSTRAINT `fk_membresias_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`);

ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pagos_membresia` FOREIGN KEY (`membresia_id`) REFERENCES `membresias` (`id`),
  ADD CONSTRAINT `fk_pagos_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`),
  ADD CONSTRAINT `fk_pagos_usuario` FOREIGN KEY (`cobrado_por`) REFERENCES `usuarios` (`id`);

ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_horario` FOREIGN KEY (`horario_id`) REFERENCES `horarios` (`id`),
  ADD CONSTRAINT `fk_reservas_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`),
  ADD CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

ALTER TABLE `socios`
  ADD CONSTRAINT `fk_socios_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`);

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

DELIMITER $$
CREATE DEFINER=`root`@`localhost` EVENT `evt_vencer_membresias` ON SCHEDULE EVERY 1 DAY STARTS '2026-03-05 16:26:53' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE membresias
  SET estado = 'vencida'
  WHERE estado = 'activa'
    AND fecha_fin < CURDATE()$$

DELIMITER ;
COMMIT;

