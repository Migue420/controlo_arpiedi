<?php
require_once 'conexion.php';
include 'header.php';

// Obtener pedidos con información de cliente fiscal y paciente
$stmt = $pdo->query("SELECT p.*, cf.nombre_comercial as cliente_fiscal, f.numero_factura 
                     FROM pedidos p 
                     LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id 
                     LEFT JOIN facturas f ON p.id = f.pedido_id
                     ORDER BY p.created_at DESC");
$pedidos = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="text-transform: uppercase; letter-spacing: 2px;">Listado de Pedidos</h1>
    <a href="nuevo_escandallo.php" class="btn btn-accent">+ Nuevo Pedido</a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <div style="background: #d4edda; color: #155724; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #28a745;">
        <div>
            <strong>✅ ¡Pedido Guardado!</strong> El pedido y la factura proforma se han generado correctamente.
        </div>
        <?php if (isset($_GET['last_id'])): ?>
            <a href="imprimir_escandallo.php?id=<?php echo $_GET['last_id']; ?>" target="_blank" class="btn btn-accent" style="padding: 0.6rem 1.2rem; font-size: 0.8rem;">🖨️ IMPRIMIR AHORA</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Ref. Pedido</th>
                <th>Cliente (Fiscal)</th>
                <th>Paciente (Taller)</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Factura</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($p['referencia_pedido']); ?></strong></td>
                <td style="font-size: 0.8rem; font-weight: 700; color: var(--accent-gold);"><?php echo htmlspecialchars($p['cliente_fiscal'] ?: '---'); ?></td>
                <td><?php echo htmlspecialchars($p['paciente']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($p['fecha_pedido'])); ?></td>
                <td><span style="padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.75rem; background: #eee; text-transform: uppercase;"><?php echo $p['estado']; ?></span></td>
                <td style="font-weight: 700;"><?php echo number_format($p['total_pedido'], 2, ',', '.'); ?> €</td>
                <td><small><?php echo htmlspecialchars($p['numero_factura'] ?: '---'); ?></small></td>
                <td style="text-align: center; display: flex; gap: 0.5rem; justify-content: center;">
                    <a href="nuevo_escandallo.php?edit_id=<?php echo $p['id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: var(--accent-gold); color: white;">📝 EDITAR</a>
                    <a href="revisar_pedidos_proveedor.php?id=<?php echo $p['id']; ?>" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #28a745; color: white;">📦 COMPRAS</a>
                    <a href="imprimir_escandallo.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #444; color: white;">🖨️ IMPRIMIR</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pedidos)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 3rem; color: #999;">No hay pedidos registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
