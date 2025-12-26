<?php
require_once 'conexion.php';
include 'header.php';

// Obtener los últimos 5 pedidos
$stmt = $pdo->query("SELECT p.*, cf.nombre_comercial as cliente_nombre 
                     FROM pedidos p 
                     LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 5");
$ultimos_pedidos = $stmt->fetchAll();
?>

<div style="margin-bottom: 3rem;">
    <h1 style="font-size: 2.5rem; letter-spacing: 1px; text-transform: uppercase;">Panel de Control Arpiedi</h1>
    <p style="color: #888; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px;">Gestión Editorial de Producción y Escandallos</p>
</div>

<!-- DASHBOARD CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
    <a href="nuevo_escandallo.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-bottom: 4px solid var(--accent-gold); transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <h3 style="margin-bottom: 1rem;">Nuevo Pedido</h3>
            <p style="font-size: 0.9rem; color: #666;">Crear ficha técnica y escandallo manual profesional.</p>
            <div style="margin-top: 1.5rem; font-weight: 700; color: var(--accent-gold); font-size: 0.8rem;">ACCEDER →</div>
        </div>
    </a>

    <a href="pacientes.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-bottom: 4px solid var(--text-desert-black); transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <h3 style="margin-bottom: 1rem;">GESTIÓN DE PACIENTES</h3>
            <p style="font-size: 0.9rem; color: #666;">Fichas biomecánicas, diagnósticos y medidas clínicas.</p>
            <div style="margin-top: 1.5rem; font-weight: 700; color: var(--text-desert-black); font-size: 0.8rem;">ACCEDER →</div>
        </div>
    </a>

    <a href="fabricacion.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-bottom: 4px solid var(--text-desert-black); transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <h3 style="margin-bottom: 1rem;">Estado de Fabricación</h3>
            <p style="font-size: 0.9rem; color: #666;">Seguimiento en tiempo real de los procesos en taller.</p>
            <div style="margin-top: 1.5rem; font-weight: 700; color: var(--text-desert-black); font-size: 0.8rem;">ACCEDER →</div>
        </div>
    </a>

    <a href="facturacion.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="border-bottom: 4px solid var(--text-desert-black); transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <h3 style="margin-bottom: 1rem;">Facturación Mensual</h3>
            <p style="font-size: 0.9rem; color: #666;">Control de proformas, facturas y estados de pago.</p>
            <div style="margin-top: 1.5rem; font-weight: 700; color: var(--text-desert-black); font-size: 0.8rem;">ACCEDER →</div>
        </div>
    </a>
</div>

<!-- RESUMEN DE ACTIVIDAD -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="font-family: var(--font-heading); letter-spacing: 1px;">ÚLTIMOS 5 PEDIDOS CREADOS</h2>
        <a href="pedidos.php" class="btn btn-primary" style="font-size: 0.7rem; padding: 0.5rem 1rem;">VER TODOS</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Cliente (Fiscal)</th>
                <th>Paciente</th>
                <th>Fecha</th>
                <th>Total Escandallo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ultimos_pedidos as $p): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($p['referencia_pedido']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['cliente_nombre'] ?: 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($p['paciente']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
                <td style="font-weight: 700; color: var(--accent-gold);"><?php echo number_format($p['total_pedido'], 2, ',', '.'); ?> €</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ultimos_pedidos)): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 3rem; color: #999;">No hay pedidos registrados recientemente.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
