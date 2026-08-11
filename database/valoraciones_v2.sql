-- Script SQL para la creación de la nueva tabla de valoraciones v2 (SST-F-15 + PAR-Q)

CREATE TABLE IF NOT EXISTS `valoraciones_v2` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id` BIGINT UNSIGNED NOT NULL,
  `entrenador_id` BIGINT UNSIGNED DEFAULT NULL,
  `imc_clasificacion_id` BIGINT UNSIGNED DEFAULT NULL,
  `convenio` TINYINT(1) NOT NULL DEFAULT 0,
  `nombre_empresa` VARCHAR(255) DEFAULT NULL,
  
  -- Perímetros (medición antropométrica)
  `estatura` DECIMAL(8,2) DEFAULT NULL,
  `peso` DECIMAL(8,2) DEFAULT NULL,
  `edad` INT DEFAULT NULL,
  `sexo` VARCHAR(20) DEFAULT 'Hombre',
  `pecho` DECIMAL(8,2) DEFAULT 0.00,
  `brazo_izquierdo` DECIMAL(8,2) DEFAULT 0.00,
  `brazo_derecho` DECIMAL(8,2) DEFAULT 0.00,
  `perimetro_abdominal` DECIMAL(8,2) DEFAULT 0.00,
  `pierna_izquierda` DECIMAL(8,2) DEFAULT 0.00,
  `pierna_derecha` DECIMAL(8,2) DEFAULT 0.00,

  -- Frecuencia Cardíaca
  `frecuencia1` INT DEFAULT NULL,
  `frecuencia2` INT DEFAULT NULL,
  `frecuencia3` INT DEFAULT NULL,

  -- Test Físico
  `push_up` INT DEFAULT NULL,
  `abdominales` INT DEFAULT NULL,

  -- Composición Corporal
  `imc` DECIMAL(8,2) DEFAULT NULL,
  `porcentaje_grasa` DECIMAL(8,2) DEFAULT NULL,
  `porcentaje_musculo` DECIMAL(8,2) DEFAULT NULL,
  `grasa_visceral` DECIMAL(8,2) DEFAULT NULL,
  `masa_musculo_esqueletica` DECIMAL(8,2) DEFAULT NULL,

  -- Antecedentes
  `hipertension` TINYINT(1) NOT NULL DEFAULT 0,
  `fuma` TINYINT(1) NOT NULL DEFAULT 0,
  `lesion` TINYINT(1) NOT NULL DEFAULT 0,
  `tension_arterial` VARCHAR(50) DEFAULT NULL,
  `antecedentes_cardio_personales` TINYINT(1) NOT NULL DEFAULT 0,
  `antecedentes_cardio_familiares` TINYINT(1) NOT NULL DEFAULT 0,
  `colesterol_alto` TINYINT(1) NOT NULL DEFAULT 0,
  `trigliceridos_altos` TINYINT(1) NOT NULL DEFAULT 0,
  `toma_medicamento` TINYINT(1) NOT NULL DEFAULT 0,
  `toma_foto` TINYINT(1) NOT NULL DEFAULT 0,
  `descripcion_lesion` TEXT DEFAULT NULL,

  -- Observaciones Generales
  `observaciones` TEXT DEFAULT NULL,

  -- Plan de Entrenamiento (si aplica)
  `plan_tipo` VARCHAR(255) DEFAULT NULL,
  `plan_hombre_bp` TINYINT(1) NOT NULL DEFAULT 0,
  `plan_hombre_gm` TINYINT(1) NOT NULL DEFAULT 0,
  `plan_mujer_bp` TINYINT(1) NOT NULL DEFAULT 0,
  `plan_mujer_gm` TINYINT(1) NOT NULL DEFAULT 0,

  -- Cuestionario PAR-Q (7 preguntas)
  `parq_q1` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q2` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q3` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q4` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q5` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q6` TINYINT(1) NOT NULL DEFAULT 0,
  `parq_q7` TINYINT(1) NOT NULL DEFAULT 0,

  -- Fechas y Trimestre
  `fecha_registro` DATETIME DEFAULT NULL,
  `fecha_inicio` DATE DEFAULT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `trimestre` TINYINT UNSIGNED DEFAULT NULL,
  `tipo_valoracion` VARCHAR(50) DEFAULT NULL,
  
  -- Timestamps
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `fk_valoraciones_v2_cliente_idx` (`cliente_id`),
  KEY `fk_valoraciones_v2_entrenador_idx` (`entrenador_id`),
  KEY `fk_valoraciones_v2_imc_idx` (`imc_clasificacion_id`),

  CONSTRAINT `fk_valoraciones_v2_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
