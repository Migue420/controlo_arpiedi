<?php
header('Content-Type: application/json');
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_paciente = $_POST['id_paciente'] ?? null;
    $referencia = $_POST['referencia'] ?? '';
    $id_cliente_fiscal = $_POST['id_cliente_fiscal'] ?? null;
    
    if (!$id_paciente) {
        echo json_encode(['status' => 'error', 'message' => 'Falta ID de Paciente para guardar borrador.']);
        exit;
    }

    // Guardamos todo el POST como JSON
    $datos_json = json_encode($_POST);

    try {
        // Usamos INSERT ... ON DUPLICATE KEY UPDATE gracias al índice UNIQUE en id_paciente
        $stmt = $pdo->prepare("INSERT INTO pedidos_borradores (id_paciente, referencia_pedido, id_cliente_fiscal, datos_json) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE 
                               referencia_pedido = VALUES(referencia_pedido), 
                               id_cliente_fiscal = VALUES(id_cliente_fiscal), 
                               datos_json = VALUES(datos_json),
                               fecha_modificacion = NOW()");
        
        $stmt->execute([$id_paciente, $referencia, $id_cliente_fiscal, $datos_json]);

        echo json_encode(['status' => 'success', 'message' => 'Borrador asegurado correctamente.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
