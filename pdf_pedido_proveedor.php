<?php
require_once 'conexion.php';

$pedido_id = $_GET['pedido_id'] ?? null;
$proveedor_id = $_GET['proveedor_id'] ?? null;

if (!$pedido_id || !$proveedor_id) die("Faltan parámetros.");

// 1. Obtener datos del pedido
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

// 2. Obtener datos del proveedor
$stmtProv = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmtProv->execute([$proveedor_id]);
$proveedor = $stmtProv->fetch();

// 3. Obtener materiales de este pedido que pertenecen a este proveedor
$stmtMats = $pdo->prepare("
    SELECT md.*
    FROM modelos_detalle md
    LEFT JOIN articulos a ON md.descripcion = a.descripcion
    WHERE md.pedido_id = ? AND md.tipo = 'Material' AND a.id_proveedor = ?
");
$stmtMats->execute([$pedido_id, $proveedor_id]);
$materiales = $stmtMats->fetchAll();

if (empty($materiales)) die("No hay materiales para este proveedor en este pedido.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido_Proveedor_<?php echo $proveedor['nombre_comercial']; ?>_<?php echo $pedido['referencia_pedido']; ?></title>
    <style>
        :root {
            --accent-gold: #c5a059;
            --text-black: #1a1614;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text-black);
            line-height: 1.4;
            margin: 0;
            padding: 40px;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid var(--accent-gold);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 2px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            margin: 0;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--accent-gold);
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #888;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #f9f9f9;
            text-align: left;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 12px;
            border-bottom: 2px solid #eee;
        }
        td {
            padding: 12px;
            font-size: 0.9rem;
            border-bottom: 1px solid #eee;
        }
        .footer-note {
            margin-top: 50px;
            font-size: 0.75rem;
            color: #666;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: var(--accent-gold); color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">🖨️ IMPRIMIR PEDIDO</button>
    </div>

    <div class="header">
        <div class="logo">ARPIEDI</div>
        <div class="doc-title">
            <h1>Orden de Compra</h1>
            <p style="margin: 5px 0; font-weight: 700;">Nº PEDIDO: <?php echo $pedido['referencia_pedido']; ?>-<?php echo $proveedor_id; ?></p>
            <p style="margin: 0; font-size: 0.8rem; color: #666;">Fecha: <?php echo date('d/m/Y'); ?></p>
        </div>
    </div>

    <div class="grid">
        <div class="info-box">
            <div class="section-title">De: (Comprador)</div>
            <p><strong>ARPIEDI S.L.</strong></p>
            <p>Calle de la Artesanía, 12</p>
            <p>28001 Madrid, España</p>
            <p>Email: compras@arpiedi.es</p>
        </div>
        <div class="info-box">
            <div class="section-title">Para: (Proveedor)</div>
            <p><strong><?php echo htmlspecialchars($proveedor['nombre_comercial']); ?></strong></p>
            <p>Contacto: <?php echo htmlspecialchars($proveedor['contacto']); ?></p>
            <p>Email: <?php echo htmlspecialchars($proveedor['email']); ?></p>
            <p>Tel: <?php echo htmlspecialchars($proveedor['telefono']); ?></p>
        </div>
    </div>

    <div style="margin-bottom: 20px; background: #fdfbf7; padding: 15px; border-left: 4px solid var(--accent-gold);">
        <p style="margin: 0; font-size: 0.9rem;"><strong>Referencia de Trazabilidad:</strong> <?php echo htmlspecialchars($pedido['referencia_pedido']); ?> (Paciente: <?php echo htmlspecialchars($pedido['paciente']); ?>)</p>
    </div>

    <div class="section-title">Detalle de Materiales Solicitados</div>
    <table>
        <thead>
            <tr>
                <th>Descripción del Material</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: right;">Precio Unit. (€)</th>
                <th style="text-align: right;">Total (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_pedido = 0;
            foreach ($materiales as $m): 
                $total_pedido += $m['total'];
            ?>
            <tr>
                <td style="font-weight: 700;"><?php echo htmlspecialchars($m['descripcion']); ?></td>
                <td style="text-align: center;"><?php echo number_format($m['consumo'], 3, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($m['precio'], 2, ',', '.'); ?></td>
                <td style="text-align: right; font-weight: 700;"><?php echo number_format($m['total'], 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #f9f9f9;">
                <td colspan="3" style="text-align: right; font-weight: 800; padding: 15px;">TOTAL PEDIDO:</td>
                <td style="text-align: right; font-weight: 800; padding: 15px; font-size: 1.1rem; color: var(--accent-gold);"><?php echo number_format($total_pedido, 2, ',', '.'); ?> €</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px;">
        <div class="section-title">Instrucciones de Envío</div>
        <p style="font-size: 0.85rem;">Por favor, incluyan la referencia <strong><?php echo $pedido['referencia_pedido']; ?></strong> en el albarán de entrega para facilitar nuestra trazabilidad interna. Envíen la factura correspondiente a facturacion@arpiedi.es.</p>
    </div>

    <div class="footer-note">
        Este es un documento oficial de pedido generado por el sistema ERP de Arpiedi.
    </div>

    <script>
        // window.onload = () => window.print();
    </script>
</body>
</html>
