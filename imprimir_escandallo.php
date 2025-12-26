<?php
require_once 'conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID de pedido no proporcionado.");

// 1. Obtener datos del pedido
$stmt = $pdo->prepare("SELECT p.*, cf.nombre_comercial as ortopedia, cf.razon_social, cf.cif, cf.direccion as fiscal_dir, 
                              pa.nombre as paciente_nombre, pa.diagnostico, pa.medidas_json
                       FROM pedidos p
                       LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id
                       LEFT JOIN pacientes pa ON p.id_paciente = pa.id
                       WHERE p.id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch();

if (!$pedido) die("Pedido no encontrado.");

// 2. Obtener detalles (Materiales y Mano de Obra)
$stmtDet = $pdo->prepare("SELECT * FROM modelos_detalle WHERE pedido_id = ?");
$stmtDet->execute([$id]);
$detalles = $stmtDet->fetchAll();

$materiales = array_filter($detalles, fn($d) => $d['tipo'] === 'Material');
$mano_obra = array_filter($detalles, fn($d) => $d['tipo'] === 'Mano de Obra');

$medidas = json_decode($pedido['medidas_json'] ?? '{}', true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Escandallo_<?php echo $pedido['referencia_pedido']; ?></title>
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
            padding: 10px;
            border-bottom: 2px solid #eee;
        }
        td {
            padding: 10px;
            font-size: 0.85rem;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-weight: 900;
            font-size: 1.1rem;
            background: #fdfbf7;
        }
        .medidas-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #fdfbf7;
            padding: 15px;
            border-radius: 4px;
        }
        .footer-note {
            margin-top: 50px;
            font-size: 0.7rem;
            color: #aaa;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: var(--accent-gold); color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">🖨️ IMPRIMIR PDF</button>
    </div>

    <div class="header">
        <div class="logo">ARPIEDI</div>
        <div class="doc-title">
            <h1>Ficha Técnica de Escandallo</h1>
            <p style="margin: 5px 0; font-weight: 700;">REF: <?php echo $pedido['referencia_pedido']; ?></p>
            <p style="margin: 0; font-size: 0.8rem; color: #666;">Fecha: <?php echo date('d/m/Y', strtotime($pedido['fecha_pedido'])); ?></p>
        </div>
    </div>

    <div class="grid">
        <div class="info-box">
            <div class="section-title">Datos del Cliente (Facturación)</div>
            <p><strong><?php echo htmlspecialchars($pedido['ortopedia']); ?></strong></p>
            <p><?php echo htmlspecialchars($pedido['razon_social']); ?></p>
            <p>CIF: <?php echo htmlspecialchars($pedido['cif']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($pedido['fiscal_dir'])); ?></p>
        </div>
        <div class="info-box">
            <div class="section-title">Datos del Paciente (Taller)</div>
            <p><strong>Paciente:</strong> <?php echo htmlspecialchars($pedido['paciente_nombre']); ?></p>
            <p><strong>Diagnóstico:</strong> <?php echo htmlspecialchars($pedido['diagnostico'] ?: 'No especificado'); ?></p>
            <div class="medidas-grid" style="margin-top: 10px;">
                <div>
                    <small>PIE IZQUIERDO</small><br>
                    L: <?php echo $medidas['izq']['longitud'] ?? '-'; ?> | A: <?php echo $medidas['izq']['altura'] ?? '-'; ?> | C: <?php echo $medidas['izq']['contorno'] ?? '-'; ?>
                </div>
                <div>
                    <small>PIE DERECHO</small><br>
                    L: <?php echo $medidas['der']['longitud'] ?? '-'; ?> | A: <?php echo $medidas['der']['altura'] ?? '-'; ?> | C: <?php echo $medidas['der']['contorno'] ?? '-'; ?>
                </div>
            </div>
            <?php if (!empty($pedido['imagen_modelo'])): ?>
                <div style="margin-top: 20px; text-align: center; border: 1px solid #eee; padding: 10px; background: #fff;">
                    <div class="section-title" style="border:none; margin-bottom: 5px;">Imagen del Modelo</div>
                    <img src="<?php echo htmlspecialchars($pedido['imagen_modelo']); ?>" style="max-width: 100%; max-height: 200px; border-radius: 4px;">
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-title">Desglose de Materiales</div>
    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="text-align: center;">Consumo</th>
                <th style="text-align: right;">Precio (€)</th>
                <th style="text-align: right;">Subtotal (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_mat = 0;
            foreach ($materiales as $m): 
                $total_mat += $m['total'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($m['descripcion']); ?></td>
                <td style="text-align: center;"><?php echo number_format($m['consumo'], 3, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($m['precio'], 2, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($m['total'], 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #fafafa;">
                <td colspan="3" style="text-align: right; font-weight: 700;">Total Materiales:</td>
                <td style="text-align: right; font-weight: 700;"><?php echo number_format($total_mat, 2, ',', '.'); ?> €</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Mano de Obra y Operaciones</div>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th style="text-align: center;">H/Cant</th>
                <th style="text-align: right;">Precio/H (€)</th>
                <th style="text-align: right;">Subtotal (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_mo = 0;
            foreach ($mano_obra as $mo): 
                $total_mo += $mo['total'];
            ?>
            <tr>
                <td><?php echo htmlspecialchars($mo['descripcion']); ?></td>
                <td style="text-align: center;"><?php echo number_format($mo['consumo'], 2, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($mo['precio'], 2, ',', '.'); ?></td>
                <td style="text-align: right;"><?php echo number_format($mo['total'], 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background: #fafafa;">
                <td colspan="3" style="text-align: right; font-weight: 700;">Total Mano de Obra:</td>
                <td style="text-align: right; font-weight: 700;"><?php echo number_format($total_mo, 2, ',', '.'); ?> €</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="width: 300px; border: 2px solid var(--accent-gold); padding: 20px; text-align: right;">
            <div style="font-size: 0.8rem; text-transform: uppercase; color: #888; margin-bottom: 5px;">Coste Total de Fabricación</div>
            <div style="font-size: 2rem; font-weight: 900; color: var(--text-black);"><?php echo number_format($pedido['total_pedido'], 2, ',', '.'); ?> €</div>
        </div>
    </div>

    <div class="footer-note">
        Documento generado automáticamente por el sistema Arpiedi ERP. Este documento tiene carácter técnico y de control interno.
    </div>

    <script>
        // Auto-abrir diálogo de impresión si se desea
        // window.onload = () => window.print();
    </script>
</body>
</html>
