-- ============================================================
--  PROYECTO INTEGRADOR – BASE DE DATOS AVANZADAS
--  Sistema: DentalConnect
--  Equipo: Francesco Romero, Marco Antonio Osorio, Abril Miranda
-- ============================================================

USE dental_connect_db;

-- ============================================================
-- CRITERIO 3: COLUMNAS GENERADAS (10 pts)
-- (Procedures con IF NOT EXISTS para no marcar error si ya existen)
-- ============================================================

DROP PROCEDURE IF EXISTS sp_add_col_nombre_completo;
DELIMITER //
CREATE PROCEDURE sp_add_col_nombre_completo()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'pacientes'
                     AND COLUMN_NAME  = 'nombre_completo') THEN
        ALTER TABLE pacientes
            ADD COLUMN nombre_completo VARCHAR(200)
            GENERATED ALWAYS AS (CONCAT(nombre, ' ', apellido_paterno, ' ', IFNULL(apellido_materno, '')))
            STORED;
    END IF;
END //
DELIMITER ;
CALL sp_add_col_nombre_completo();

DROP PROCEDURE IF EXISTS sp_add_col_anio_nac;
DELIMITER //
CREATE PROCEDURE sp_add_col_anio_nac()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'pacientes'
                     AND COLUMN_NAME  = 'anio_nacimiento') THEN
        ALTER TABLE pacientes
            ADD COLUMN anio_nacimiento INT
            GENERATED ALWAYS AS (YEAR(fecha_nacimiento))
            STORED;
    END IF;
END //
DELIMITER ;
CALL sp_add_col_anio_nac();

DROP PROCEDURE IF EXISTS sp_add_col_duracion;
DELIMITER //
CREATE PROCEDURE sp_add_col_duracion()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'citas'
                     AND COLUMN_NAME  = 'duracion_minutos') THEN
        ALTER TABLE citas
            ADD COLUMN duracion_minutos INT
            GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, fecha_hora_inicio, fecha_hora_fin))
            STORED;
    END IF;
END //
DELIMITER ;
CALL sp_add_col_duracion();

DROP PROCEDURE IF EXISTS sp_add_col_precio_iva;
DELIMITER //
CREATE PROCEDURE sp_add_col_precio_iva()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'catalogo_servicios'
                     AND COLUMN_NAME  = 'precio_con_iva') THEN
        ALTER TABLE catalogo_servicios
            ADD COLUMN precio_con_iva DECIMAL(10,2)
            GENERATED ALWAYS AS (ROUND(precio_base * 1.16, 2))
            STORED;
    END IF;
END //
DELIMITER ;
CALL sp_add_col_precio_iva();


-- ============================================================
-- CRITERIO 4: SLUGS (10 pts)
-- ============================================================

-- Slug para clinicas
DROP PROCEDURE IF EXISTS sp_add_slug_clinicas;
DELIMITER //
CREATE PROCEDURE sp_add_slug_clinicas()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'clinicas'
                     AND COLUMN_NAME  = 'slug') THEN
        ALTER TABLE clinicas ADD COLUMN slug VARCHAR(200);
    END IF;
END //
DELIMITER ;
CALL sp_add_slug_clinicas();

UPDATE clinicas
SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
               nombre_comercial,' ','-'),'á','a'),'é','e'),'í','i'),'ó','o'))
WHERE slug IS NULL OR slug = '';

-- Slug para catalogo_servicios (necesario para Demo 4)
DROP PROCEDURE IF EXISTS sp_add_slug_servicios;
DELIMITER //
CREATE PROCEDURE sp_add_slug_servicios()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'catalogo_servicios'
                     AND COLUMN_NAME  = 'slug') THEN
        ALTER TABLE catalogo_servicios ADD COLUMN slug VARCHAR(200);
    END IF;
END //
DELIMITER ;
CALL sp_add_slug_servicios();

UPDATE catalogo_servicios
SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
               nombre_servicio,' ','-'),'á','a'),'é','e'),'í','i'),'ó','o'))
WHERE slug IS NULL OR slug = '';


-- ============================================================
-- CRITERIO 5: VISTAS (10 pts)
-- ============================================================

CREATE OR REPLACE VIEW vista_citas_completas AS
SELECT
    c.id_cita,
    cl.nombre_comercial AS clinica,
    p.nombre_completo   AS paciente,
    u.nombre_completo   AS doctor,
    c.fecha_hora_inicio,
    c.fecha_hora_fin,
    c.duracion_minutos,
    c.motivo,
    c.costo_estimado,
    c.estado_cita
FROM citas c
INNER JOIN clinicas         cl ON c.id_clinica  = cl.id_clinica
INNER JOIN pacientes         p  ON c.id_paciente = p.id_paciente
INNER JOIN doctores          d  ON c.id_doctor   = d.id_doctor
INNER JOIN usuarios_sistema  u  ON d.id_usuario  = u.id_usuario;


-- ============================================================
-- CRITERIO 6: VISTAS MATERIALIZADAS (10 pts)
-- CORRECCIÓN: tabla incluye citas_completadas e ingreso_promedio
-- que son referenciadas en Demo 3
-- ============================================================

DROP TABLE IF EXISTS mat_ingresos_clinica;
CREATE TABLE mat_ingresos_clinica (
    id_clinica           BIGINT UNSIGNED NOT NULL,
    nombre_comercial     VARCHAR(150),
    total_citas          INT            DEFAULT 0,
    citas_completadas    INT            DEFAULT 0,
    ingresos_totales     DECIMAL(12,2)  DEFAULT 0.00,
    ingreso_promedio     DECIMAL(10,2)  DEFAULT 0.00,
    ultima_actualizacion DATETIME       DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_clinica)
) ENGINE=InnoDB;

DROP PROCEDURE IF EXISTS sp_refrescar_mat_ingresos;
DELIMITER //
CREATE PROCEDURE sp_refrescar_mat_ingresos()
BEGIN
    DELETE FROM mat_ingresos_clinica;

    INSERT INTO mat_ingresos_clinica
        (id_clinica, nombre_comercial, total_citas, citas_completadas,
         ingresos_totales, ingreso_promedio, ultima_actualizacion)
    SELECT
        cl.id_clinica,
        cl.nombre_comercial,
        COUNT(c.id_cita),
        SUM(CASE WHEN c.estado_cita = 'completada' THEN 1    ELSE 0    END),
        IFNULL(SUM(CASE WHEN c.estado_cita = 'completada' THEN c.costo_estimado ELSE 0    END), 0),
        IFNULL(AVG(CASE WHEN c.estado_cita = 'completada' THEN c.costo_estimado ELSE NULL END), 0),
        NOW()
    FROM clinicas cl
    LEFT JOIN citas c ON cl.id_clinica = c.id_clinica
    GROUP BY cl.id_clinica, cl.nombre_comercial;
END //
DELIMITER ;

CALL sp_refrescar_mat_ingresos();


-- ============================================================
-- CRITERIO 7: TRIGGERS (10 pts)
-- ============================================================

DROP TRIGGER IF EXISTS trg_citas_insert;
DELIMITER //
CREATE TRIGGER trg_citas_insert AFTER INSERT ON citas FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (id_usuario, accion, tabla_afectada, id_registro, detalles, created_at)
    VALUES (NULL, 'insert', 'citas', NEW.id_cita,
            JSON_OBJECT('estado', NEW.estado_cita, 'motivo', NEW.motivo), NOW());
END //
DELIMITER ;

DROP TRIGGER IF EXISTS trg_citas_delete;
DELIMITER //
CREATE TRIGGER trg_citas_delete BEFORE DELETE ON citas FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (id_usuario, accion, tabla_afectada, id_registro, detalles, created_at)
    VALUES (NULL, 'delete', 'citas', OLD.id_cita,
            JSON_OBJECT('estado', OLD.estado_cita, 'motivo', OLD.motivo), NOW());
END //
DELIMITER ;

DROP TRIGGER IF EXISTS trg_pacientes_insert;
DELIMITER //
CREATE TRIGGER trg_pacientes_insert AFTER INSERT ON pacientes FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (id_usuario, accion, tabla_afectada, id_registro, detalles, created_at)
    VALUES (NEW.id_usuario, 'insert', 'pacientes', NEW.id_paciente,
            JSON_OBJECT('nombre', NEW.nombre, 'email', NEW.correo_electronico), NOW());
END //
DELIMITER ;

DROP TRIGGER IF EXISTS trg_pacientes_update;
DELIMITER //
CREATE TRIGGER trg_pacientes_update BEFORE UPDATE ON pacientes FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (id_usuario, accion, tabla_afectada, id_registro, detalles, created_at)
    VALUES (OLD.id_usuario, 'update', 'pacientes', OLD.id_paciente,
            JSON_OBJECT('email_old', OLD.correo_electronico, 'email_new', NEW.correo_electronico), NOW());
END //
DELIMITER ;

DROP TRIGGER IF EXISTS trg_pacientes_delete;
DELIMITER //
CREATE TRIGGER trg_pacientes_delete BEFORE DELETE ON pacientes FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (id_usuario, accion, tabla_afectada, id_registro, detalles, created_at)
    VALUES (OLD.id_usuario, 'delete', 'pacientes', OLD.id_paciente,
            JSON_OBJECT('nombre', OLD.nombre), NOW());
END //
DELIMITER ;


-- ============================================================
-- CRITERIOS 8 y 9: ÍNDICES (20 pts)
-- ============================================================

DROP PROCEDURE IF EXISTS sp_crear_indices;
DELIMITER //
CREATE PROCEDURE sp_crear_indices()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'pacientes'
                     AND INDEX_NAME   = 'idx_pacientes_correo') THEN
        CREATE INDEX idx_pacientes_correo ON pacientes(correo_electronico);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                   WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME   = 'citas'
                     AND INDEX_NAME   = 'idx_citas_estado') THEN
        CREATE INDEX idx_citas_estado ON citas(estado_cita);
    END IF;
END //
DELIMITER ;

CALL sp_crear_indices();


-- ============================================================
-- CRITERIO 1: INNER JOIN (10 pts)
-- ============================================================

-- 1.1 Pacientes con sus citas y el nombre del doctor
SELECT p.nombre AS paciente_nombre, p.apellido_paterno, p.apellido_materno,
       c.fecha_hora_inicio, c.estado_cita, c.motivo,
       u.nombre_completo AS doctor_nombre
FROM pacientes p
INNER JOIN citas            c ON p.id_paciente = c.id_paciente
INNER JOIN doctores         d ON c.id_doctor   = d.id_doctor
INNER JOIN usuarios_sistema u ON d.id_usuario  = u.id_usuario
LIMIT 5;

-- 1.2 Clínicas con sus servicios disponibles
SELECT cl.nombre_comercial AS clinica, cl.localidad, cl.estado,
       cs.nombre_servicio, cs.precio_base, cs.categoria
FROM clinicas cl
INNER JOIN catalogo_servicios cs ON cl.id_clinica = cs.id_clinica
LIMIT 5;

-- 1.3 Citas con archivos adjuntos y datos del paciente
SELECT p.nombre, p.apellido_paterno,
       c.fecha_hora_inicio, c.motivo,
       a.tipo AS tipo_archivo, a.descripcion AS descripcion_archivo
FROM citas c
INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
INNER JOIN archivos  a ON c.id_cita     = a.id_cita
LIMIT 5;

-- 1.4 Pacientes con su usuario del sistema y clínica asignada
SELECT p.nombre, p.apellido_paterno, u.email, u.rol,
       cl.nombre_comercial AS clinica
FROM pacientes p
INNER JOIN usuarios_sistema u  ON p.id_usuario = u.id_usuario
INNER JOIN clinicas         cl ON u.id_clinica = cl.id_clinica
LIMIT 5;


-- ============================================================
-- CRITERIO 2: SELECTs ANIDADOS (10 pts)
-- ============================================================

-- 2.1 Pacientes con al menos una cita completada (IN)
SELECT p.id_paciente, p.nombre, p.apellido_paterno, p.correo_electronico
FROM pacientes p
WHERE p.id_paciente IN (
    SELECT DISTINCT c.id_paciente FROM citas c WHERE c.estado_cita = 'completada'
)
LIMIT 5;

-- 2.2 Servicios con precio mayor al promedio de su clínica (correlacionada)
SELECT cs.nombre_servicio, cs.precio_base, cs.id_clinica
FROM catalogo_servicios cs
WHERE cs.precio_base > (
    SELECT AVG(cs2.precio_base) FROM catalogo_servicios cs2
    WHERE cs2.id_clinica = cs.id_clinica
)
LIMIT 5;

-- 2.3 Doctores con citas en los últimos 30 días (3 niveles)
SELECT u.nombre_completo AS doctor, u.email
FROM usuarios_sistema u
WHERE u.id_usuario IN (
    SELECT d.id_usuario FROM doctores d
    WHERE d.id_doctor IN (
        SELECT DISTINCT c.id_doctor FROM citas c
        WHERE c.fecha_hora_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
)
LIMIT 5;

-- 2.4 Clínica con mayor número de pacientes (escalar)
SELECT cl.nombre_comercial,
    (SELECT COUNT(*) FROM pacientes p
     INNER JOIN usuarios_sistema u ON p.id_usuario = u.id_usuario
     WHERE u.id_clinica = cl.id_clinica) AS total_pacientes
FROM clinicas cl
WHERE cl.id_clinica = (
    SELECT u2.id_clinica
    FROM pacientes p2
    INNER JOIN usuarios_sistema u2 ON p2.id_usuario = u2.id_usuario
    GROUP BY u2.id_clinica
    ORDER BY COUNT(*) DESC
    LIMIT 1
);


-- ============================================================
-- CRITERIO 10: CONSULTAS DE DEMOSTRACIÓN (10 pts)
-- ============================================================

-- Demo 1: INNER JOIN + columnas generadas
SELECT p.nombre_completo,
       YEAR(CURDATE()) - p.anio_nacimiento AS edad_paciente,
       c.motivo, c.duracion_minutos, c.costo_estimado,
       cl.nombre_comercial AS clinica
FROM citas c
INNER JOIN pacientes p  ON c.id_paciente = p.id_paciente
INNER JOIN clinicas  cl ON c.id_clinica  = cl.id_clinica
WHERE c.estado_cita = 'completada'
ORDER BY c.fecha_hora_inicio DESC
LIMIT 10;

-- Demo 2: Vista + subconsulta
SELECT * FROM vista_citas_completas
WHERE id_cita IN (
    SELECT id_cita FROM citas WHERE costo_estimado > 1000
)
ORDER BY fecha_hora_inicio DESC
LIMIT 10;

-- Demo 3: Vistas materializadas (citas_completadas e ingreso_promedio ya existen en la tabla)
SELECT m.nombre_comercial, m.total_citas, m.citas_completadas,
       m.ingresos_totales, m.ingreso_promedio
FROM mat_ingresos_clinica m
ORDER BY m.ingresos_totales DESC;

-- Demo 4: Slugs en uso (clinicas + servicios, ambas tablas tienen columna slug)
SELECT cl.nombre_comercial, cl.slug,
       cs.nombre_servicio, cs.slug AS servicio_slug,
       cs.precio_base, cs.precio_con_iva
FROM clinicas cl
INNER JOIN catalogo_servicios cs ON cl.id_clinica = cs.id_clinica
ORDER BY cl.id_clinica
LIMIT 10;

-- Demo 5: Auditoría - últimos cambios por triggers
SELECT al.id_log, al.accion, al.tabla_afectada,
       al.id_registro, al.detalles, al.created_at
FROM audit_logs al
ORDER BY al.created_at DESC
LIMIT 20;
