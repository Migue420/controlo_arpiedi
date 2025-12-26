<?php
require_once 'conexion.php';

$pedido_id = $_GET['pedido_id'] ?? null;
$proveedor_id = $_GET['proveedor_id'] ?? null;

if (!$pedido_id || !$proveedor_id) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros.']);
    exit;
}

// 1. Obtener datos
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

$stmtProv = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmtProv->execute([$proveedor_id]);
$proveedor = $stmtProv->fetch();

$stmtMats = $pdo->prepare("
    SELECT md.*
    FROM modelos_detalle md
    LEFT JOIN articulos a ON md.descripcion = a.descripcion
    WHERE md.pedido_id = ? AND md.tipo = 'Material' AND a.id_proveedor = ?
");
$stmtMats->execute([$pedido_id, $proveedor_id]);
$materiales = $stmtMats->fetchAll();

if (!$pedido || !$proveedor || empty($materiales)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no encontrados.']);
    exit;
}

$to = $proveedor['email'];
if (empty($to)) {
    echo json_encode(['status' => 'error', 'message' => 'El proveedor no tiene email configurado.']);
    exit;
}

$subject = "Nuevo Pedido de Compra - Arpiedi (Ref: " . $pedido['referencia_pedido'] . ")";

// Construir cuerpo HTML (similar al PDF)
$message = "
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { border-bottom: 2px solid #c5a059; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f4f4f4; padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total { font-weight: bold; color: #c5a059; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>ARPIEDI - Orden de Compra</h1>
        <p>Ref: " . $pedido['referencia_pedido'] . "</p>
    </div>
    <p>Estimado/a " . htmlspecialchars($proveedor['nombre_comercial']) . ",</p>
    <p>Adjuntamos los detalles del nuevo pedido solicitado:</p>
    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>";

$total_gral = 0;
foreach ($materiales as $m) {
    $total_gral += $m['total'];
    $message .= "
            <tr>
                <td>" . htmlspecialchars($m['descripcion']) . "</td>
                <td>" . number_format($m['consumo'], 3, ',', '.') . "</td>
                <td>" . number_format($m['precio'], 2, ',', '.') . " €</td>
                <td>" . number_format($m['total'], 2, ',', '.') . " €</td>
            </tr>";
}

$message .= "
        </tbody>
    </table>
    <p class='total'>TOTAL PEDIDO: " . number_format($total_gral, 2, ',', '.') . " €</p>
    <p><strong>Referencia de Trazabilidad:</strong> " . $pedido['referencia_pedido'] . "</p>
    <p>Por favor, confirmen la recepción de este pedido.</p>
    <br>
    <p>Atentamente,<br>Equipo de Compras Arpiedi</p>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: compras@arpiedi.es" . "\r\n";

// Intentar enviar
if (mail($to, $subject, $message, $headers)) {
    // Marcar como pedidos si se envió con éxito
    $ids = array_column($materiales, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtUpdate = $pdo->prepare("UPDATE modelos_detalle SET pedido_proveedor_enviado = 1 WHERE id IN ($placeholders)");
    $stmtUpdate->execute($ids);

    echo json_encode(['status' => 'success', 'message' => 'Email enviado correctamente a ' . $to]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al enviar el email. Verifique la configuración del servidor de correo.']);
}
