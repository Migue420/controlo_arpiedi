<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$id_paciente = $_GET['id_paciente'] ?? null;

if (!$id_paciente) {
    echo json_encode(['status' => 'error', 'message' => 'ID de paciente no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT datos_json FROM pedidos_borradores WHERE id_paciente = ?");
    $stmt->execute([$id_paciente]);
    $borrador = $stmt->fetchColumn();

    if ($borrador) {
        echo json_encode(['status' => 'success', 'data' => json_decode($borrador)]);
    } else {
        echo json_encode(['status' => 'none']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
