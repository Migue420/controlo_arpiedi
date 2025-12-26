USE controlo_arpiedi;

-- 1. Crear tabla clientes_fiscales
CREATE TABLE IF NOT EXISTS clientes_fiscales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_comercial VARCHAR(255) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    cif VARCHAR(20) NOT NULL,
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Registrar Ortoaparisi
INSERT INTO clientes_fiscales (nombre_comercial, razon_social, cif, direccion)
VALUES ('Ortoaparisi', 'Nieves Aparisi Rosello S.L.', 'B 53617049', 'C/ Dr. Semperez 16, Alcoy, Alicante');

-- 3. Modificar tabla clientes (que ahora actúa como pacientes/biomecánica)
-- Renombrar conceptualmente o añadir el campo de vinculación fiscal
ALTER TABLE clientes ADD COLUMN id_cliente_fiscal INT;
ALTER TABLE clientes ADD FOREIGN KEY (id_cliente_fiscal) REFERENCES clientes_fiscales(id) ON DELETE SET NULL;

-- 4. Actualizar tabla pedidos para soportar ambos IDs
ALTER TABLE pedidos ADD COLUMN id_cliente_fiscal INT AFTER id;
ALTER TABLE pedidos ADD FOREIGN KEY (id_cliente_fiscal) REFERENCES clientes_fiscales(id) ON DELETE SET NULL;
