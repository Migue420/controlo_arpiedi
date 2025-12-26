<?php
require_once 'conexion.php';
include 'header.php';

$stmt = $pdo->query("SELECT f.*, p.referencia_pedido, p.paciente FROM fabricacion f JOIN pedidos p ON f.pedido_id = p.id ORDER BY f.created_at DESC");
$procesos = $stmt->fetchAll();
?>

<div class="card">
    <h1>Control de Taller / Fabricación</h1>
    <p>Seguimiento en tiempo real de los procesos productivos.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        <?php foreach ($procesos as $proc): ?>
        <div class="card" style="padding: 1.5rem; border-left: 4px solid var(--accent-gold);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <small style="text-transform: uppercase; color: #999; letter-spacing: 1px;">Ref: <?php echo $proc['referencia_pedido']; ?></small>
                    <h3 style="margin: 0.5rem 0;"><?php echo $proc['paciente']; ?></h3>
                </div>
                <span style="background: var(--charcoal-gray); color: white; padding: 0.2rem 0.5rem; font-size: 0.7rem; border-radius: 4px;"><?php echo $proc['prioridad']; ?></span>
            </div>
            <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border-color);">
            <p style="font-size: 0.9rem;">Estado actual: <strong><?php echo $proc['estado_proceso']; ?></strong></p>
            <div style="background: #eee; height: 8px; border-radius: 4px; margin-top: 0.5rem; overflow: hidden;">
                <div style="background: var(--accent-gold); width: 25%; height: 100%;"></div>
            </div>
            <button class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; font-size: 0.8rem;">ACTUALIZAR ESTADO</button>
        </div>
        <?php endforeach; ?>
        <?php if (empty($procesos)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
            <p style="color: #999;">No hay procesos de fabricación activos.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
