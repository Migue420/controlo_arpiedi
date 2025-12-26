USE controlo_arpiedi;

-- 1. Asegurar tabla clientes_fiscales
CREATE TABLE IF NOT EXISTS clientes_fiscales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_comercial VARCHAR(255) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    cif VARCHAR(20) NOT NULL,
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar Ortoaparisi si no existe
INSERT IGNORE INTO clientes_fiscales (id, nombre_comercial, razon_social, cif, direccion)
VALUES (1, 'Ortoaparisi', 'Nieves Aparisi Rosello S.L.', 'B 53617049', 'C/ Dr. Semperez 16, Alcoy, Alicante');

-- 2. Renombrar tabla clientes a pacientes para consistencia total
-- Primero comprobamos si existe 'clientes' y no 'pacientes'
SET @dbname = DATABASE();
SET @tablename = 'clientes';
SET @newtablename = 'pacientes';

-- Procedimiento para renombrar si existe
DROP PROCEDURE IF EXISTS RenameTable;
DELIMITER //
CREATE PROCEDURE RenameTable()
BEGIN
    IF EXISTS (SELECT * FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'clientes') THEN
        RENAME TABLE clientes TO pacientes;
    END IF;
END//
DELIMITER ;
CALL RenameTable();
DROP PROCEDURE RenameTable;

-- 3. Asegurar estructura de tabla pacientes
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS id_cliente_fiscal INT AFTER id;
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS diagnostico TEXT;
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS medidas_json JSON;
ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS observaciones TEXT;

-- 4. Actualizar pedidos para referenciar pacientes
ALTER TABLE pedidos CHANGE COLUMN cliente_id id_paciente INT;
