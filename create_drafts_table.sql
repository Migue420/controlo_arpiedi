USE controlo_arpiedi;

CREATE TABLE IF NOT EXISTS pedidos_borradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referencia_pedido VARCHAR(100),
    id_paciente INT,
    id_cliente_fiscal INT,
    datos_json JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
