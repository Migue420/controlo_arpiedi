<?php
require_once 'conexion.php';
include 'header.php';
?>

<div class="card">
    <h1>Módulo de Facturación</h1>
    
    <table>
        <thead>
            <tr>
                <th>Nº Factura</th>
                <th>Cliente (Fiscal)</th>
                <th>Pedido</th>
                <th>Fecha</th>
                <th>Total (Base)</th>
                <th>IVA (21%)</th>
                <th>Total (IVA Inc.)</th>
                <th>Estado</th>
                <th style="text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $stmtF = $pdo->query("SELECT f.*, p.referencia_pedido, cf.razon_social, cf.nombre_comercial 
                                 FROM facturas f 
                                 JOIN pedidos p ON f.pedido_id = p.id 
                                 LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id
                                 ORDER BY f.fecha_factura DESC");
            $facturas = $stmtF->fetchAll();
            foreach ($facturas as $f): 
                $base = $f['total'];
                $iva = $base * 0.21;
                $total_inc = $base + $iva;
            ?>
            <tr>
                <td><strong><?php echo $f['numero_factura']; ?></strong></td>
                <td>
                    <div style="font-weight: 700;"><?php echo htmlspecialchars($f['nombre_comercial'] ?: '---'); ?></div>
                    <div style="font-size: 0.7rem; color: #888;"><?php echo htmlspecialchars($f['razon_social'] ?: ''); ?></div>
                </td>
                <td><?php echo $f['referencia_pedido']; ?></td>
                <td><?php echo date('d/m/Y', strtotime($f['fecha_factura'])); ?></td>
                <td><?php echo number_format($base, 2, ',', '.'); ?> €</td>
                <td><?php echo number_format($iva, 2, ',', '.'); ?> €</td>
                <td style="font-weight: 900; color: var(--accent-gold);"><?php echo number_format($total_inc, 2, ',', '.'); ?> €</td>
                <td><span style="padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; background: #d4edda; color: #155724;"><?php echo $f['estado']; ?></span></td>
                <td style="text-align: center;">
                    <a href="imprimir_factura.php?id=<?php echo $f['id']; ?>" target="_blank" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #444; color: white;">🖨️ IMPRIMIR</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($facturas)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 3rem; color: #999;">No hay facturas generadas.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
