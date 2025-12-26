USE controlo_arpiedi;

-- Asegurar que la tabla de borradores es robusta
CREATE TABLE IF NOT EXISTS pedidos_borradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT,
    referencia_pedido VARCHAR(100),
    id_cliente_fiscal INT,
    datos_json JSON,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_paciente (id_paciente)
);
