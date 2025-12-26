<?php
require_once 'conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID de factura no proporcionado.");

// 1. Obtener datos de la factura, pedido y cliente fiscal
$stmt = $pdo->prepare("SELECT f.*, p.referencia_pedido, p.paciente, 
                              cf.nombre_comercial as ortopedia, cf.razon_social, cf.cif, cf.direccion as fiscal_dir
                       FROM facturas f
                       LEFT JOIN pedidos p ON f.pedido_id = p.id
                       LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id
                       WHERE f.id = ?");
$stmt->execute([$id]);
$factura = $stmt->fetch();

if (!$factura) die("Factura no encontrada.");

// Cálculos de IVA
$subtotal = $factura['total'] / 1.21; // Asumiendo que el total guardado ya incluye IVA o es la base
// Si el total guardado es la BASE:
$base_imponible = $factura['total'];
$iva_cuota = $base_imponible * 0.21;
$total_factura = $base_imponible + $iva_cuota;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura_<?php echo $factura['numero_factura']; ?></title>
    <style>
        :root {
            --accent-gold: #c5a059;
            --text-black: #1a1614;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-black);
            margin: 0;
            padding: 50px;
            background: white;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 60px;
        }
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: 3px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h1 {
            margin: 0;
            color: var(--accent-gold);
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            margin-bottom: 50px;
        }
        .section-title {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .company-data p, .client-data p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th {
            text-align: left;
            padding: 15px;
            background: #fdfbf7;
            border-bottom: 2px solid var(--accent-gold);
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        .totals-container {
            display: flex;
            justify-content: flex-end;
        }
        .totals-table {
            width: 300px;
        }
        .totals-table td {
            border: none;
            padding: 8px 15px;
        }
        .total-final {
            background: var(--text-black);
            color: white;
            font-weight: 900;
            font-size: 1.2rem;
        }
        .footer {
            margin-top: 100px;
            font-size: 0.7rem;
            color: #aaa;
            text-align: center;
            line-height: 1.6;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 30px; text-align: right;">
        <button onclick="window.print()" style="padding: 12px 25px; background: var(--accent-gold); color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; box-shadow: 0 4px 10px rgba(197, 160, 89, 0.3);">🖨️ IMPRIMIR FACTURA</button>
    </div>

    <div class="header">
        <div class="logo">ARPIEDI</div>
        <div class="invoice-info">
            <h1>FACTURA</h1>
            <p style="margin: 5px 0; font-weight: 800;"><?php echo $factura['numero_factura']; ?></p>
            <p style="margin: 0; font-size: 0.8rem; color: #666;">Fecha: <?php echo date('d/m/Y', strtotime($factura['fecha_factura'])); ?></p>
        </div>
    </div>

    <div class="grid">
        <div class="company-data">
            <div class="section-title">Emisor</div>
            <p><strong>LASTING MODE S.L.</strong></p>
            <p>CIF: B54805346</p>
            <p>Calle Villena 8</p>
            <p>03203 Elche (Alicante)</p>
        </div>
        <div class="client-data">
            <div class="section-title">Cliente</div>
            <p><strong><?php echo htmlspecialchars($factura['ortopedia']); ?></strong></p>
            <p><?php echo htmlspecialchars($factura['razon_social']); ?></p>
            <p>CIF: <?php echo htmlspecialchars($factura['cif']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($factura['fiscal_dir'])); ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 60%;">Descripción / Referencia</th>
                <th style="text-align: right;">Cantidad</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Servicio de Fabricación a Medida</strong><br>
                    <small style="color: #666;">Ref. Pedido: <?php echo htmlspecialchars($factura['referencia_pedido']); ?> | Paciente: <?php echo htmlspecialchars($factura['paciente']); ?></small>
                </td>
                <td style="text-align: right;">1</td>
                <td style="text-align: right;"><?php echo number_format($base_imponible, 2, ',', '.'); ?> €</td>
                <td style="text-align: right;"><?php echo number_format($base_imponible, 2, ',', '.'); ?> €</td>
            </tr>
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td>Base Imponible:</td>
                <td style="text-align: right;"><?php echo number_format($base_imponible, 2, ',', '.'); ?> €</td>
            </tr>
            <tr>
                <td>I.V.A. (21%):</td>
                <td style="text-align: right;"><?php echo number_format($iva_cuota, 2, ',', '.'); ?> €</td>
            </tr>
            <tr class="total-final">
                <td style="padding: 15px;">TOTAL FACTURA:</td>
                <td style="text-align: right; padding: 15px;"><?php echo number_format($total_factura, 2, ',', '.'); ?> €</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Gracias por confiar en Arpiedi. La excelencia en biomecánica a su servicio.</p>
        <p style="margin-top: 20px;">Forma de pago: Transferencia bancaria | IBAN: ES00 0000 0000 0000 0000 0000</p>
        <p>Inscrita en el Registro Mercantil de Alicante, Tomo XXX, Folio XXX, Hoja XXX.</p>
    </div>
</body>
</html>
