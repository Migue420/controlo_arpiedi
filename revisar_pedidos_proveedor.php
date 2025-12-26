<?php
require_once 'conexion.php';
include 'header.php';

$pedido_id = $_GET['id'] ?? null;
if (!$pedido_id) die("ID de pedido no proporcionado.");

// 1. Obtener datos del pedido
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();
if (!$pedido) die("Pedido no encontrado.");

// 2. Obtener materiales y cruzarlos con proveedores
// Buscamos en 'articulos' por descripcion para obtener el proveedor
$stmtMats = $pdo->prepare("
    SELECT md.*, a.id_proveedor, p.nombre_comercial as proveedor_nombre, p.email as proveedor_email
    FROM modelos_detalle md
    LEFT JOIN articulos a ON md.descripcion = a.descripcion
    LEFT JOIN proveedores p ON a.id_proveedor = p.id
    WHERE md.pedido_id = ? AND md.tipo = 'Material'
");
$stmtMats->execute([$pedido_id]);
$materiales = $stmtMats->fetchAll();

// Agrupar por proveedor
$agrupados = [];
foreach ($materiales as $m) {
    $prov_id = $m['id_proveedor'] ?: 0;
    $prov_nombre = $m['proveedor_nombre'] ?: 'SIN PROVEEDOR ASIGNADO';
    if (!isset($agrupados[$prov_id])) {
        $agrupados[$prov_id] = [
            'nombre' => $prov_nombre,
            'email' => $m['proveedor_email'],
            'materiales' => []
        ];
    }
    $agrupados[$prov_id]['materiales'][] = $m;
}

// Lógica de guardado de cambios y marcado como pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Actualizar cantidades y precios si se han modificado
    if (isset($_POST['consumo']) && isset($_POST['precio'])) {
        foreach ($_POST['consumo'] as $id => $cons) {
            $prec = $_POST['precio'][$id];
            $total = floatval($cons) * floatval($prec);
            $stmtUpd = $pdo->prepare("UPDATE modelos_detalle SET consumo = ?, precio = ?, total = ? WHERE id = ?");
            $stmtUpd->execute([$cons, $prec, $total, $id]);
        }
    }

    // 2. Marcar como pedidos si se pulsó el botón correspondiente
    if (isset($_POST['marcar_pedidos'])) {
        $ids_a_marcar = $_POST['material_ids'] ?? [];
        if (!empty($ids_a_marcar)) {
            $placeholders = implode(',', array_fill(0, count($ids_a_marcar), '?'));
            $stmtUpdate = $pdo->prepare("UPDATE modelos_detalle SET pedido_proveedor_enviado = 1 WHERE id IN ($placeholders)");
            $stmtUpdate->execute($ids_a_marcar);
            header("Location: revisar_pedidos_proveedor.php?id=$pedido_id&msg=updated");
            exit;
        }
    }
    
    // Si solo se guardaron cambios
    header("Location: revisar_pedidos_proveedor.php?id=$pedido_id&msg=saved");
    exit;
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="text-transform: uppercase; letter-spacing: 2px;">Gestión de Compras a Proveedores</h1>
        <p style="color: #666;">Pedido Ref: <strong><?php echo htmlspecialchars($pedido['referencia_pedido']); ?></strong> | Paciente: <?php echo htmlspecialchars($pedido['paciente']); ?></p>
    </div>
    <a href="pedidos.php" class="btn" style="background: #666; color: white;">Volver al Listado</a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        ✅ Materiales marcados como "Pedidos" correctamente.
    </div>
<?php endif; ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
    <div style="background: #e3f2fd; color: #0d47a1; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        💾 Cambios guardados correctamente.
    </div>
<?php endif; ?>

<form method="POST" action="revisar_pedidos_proveedor.php?id=<?php echo $pedido_id; ?>">
    <?php foreach ($agrupados as $prov_id => $data): ?>
        <div class="card" style="margin-bottom: 2rem; border-left: 5px solid <?php echo $prov_id == 0 ? '#cc0000' : 'var(--accent-gold)'; ?>;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h3 style="margin: 0; color: var(--text-desert-black);"><?php echo htmlspecialchars($data['nombre']); ?></h3>
                    <small style="color: #888;"><?php echo htmlspecialchars($data['email'] ?: 'Sin email configurado'); ?></small>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <small style="color: #cc0000; font-weight: bold;">⚠️ Guarda cambios antes de procesar -> </small>
                    <?php if ($prov_id != 0): ?>
                        <a href="pdf_pedido_proveedor.php?pedido_id=<?php echo $pedido_id; ?>&proveedor_id=<?php echo $prov_id; ?>" target="_blank" class="btn" style="background: #444; color: white; font-size: 0.8rem;">🖨️ GENERAR PDF</a>
                        <button type="button" onclick="enviarEmail(<?php echo $pedido_id; ?>, <?php echo $prov_id; ?>)" class="btn" style="background: #007bff; color: white; font-size: 0.8rem;">📧 ENVIAR POR EMAIL</button>
                    <?php endif; ?>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9f9f9; font-size: 0.75rem; text-transform: uppercase;">
                        <th style="padding: 10px; text-align: left; width: 50px;">Sel.</th>
                        <th style="padding: 10px; text-align: left;">Material</th>
                        <th style="padding: 10px; text-align: center;">Cantidad</th>
                        <th style="padding: 10px; text-align: right;">Precio (€)</th>
                        <th style="padding: 10px; text-align: right;">Total (€)</th>
                        <th style="padding: 10px; text-align: center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['materiales'] as $m): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">
                                <input type="checkbox" name="material_ids[]" value="<?php echo $m['id']; ?>" <?php echo $m['pedido_proveedor_enviado'] ? 'disabled' : 'checked'; ?>>
                            </td>
                            <td style="padding: 10px; font-weight: 700;"><?php echo htmlspecialchars($m['descripcion']); ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <input type="number" step="0.001" name="consumo[<?php echo $m['id']; ?>]" value="<?php echo $m['consumo']; ?>" style="width: 80px; text-align: center;">
                            </td>
                            <td style="padding: 10px; text-align: right;">
                                <input type="number" step="0.01" name="precio[<?php echo $m['id']; ?>]" value="<?php echo $m['precio']; ?>" style="width: 80px; text-align: right;"> €
                            </td>
                            <td style="padding: 10px; text-align: right; font-weight: 700;"><?php echo number_format($m['total'], 2, ',', '.'); ?> €</td>
                            <td style="padding: 10px; text-align: center;">
                                <?php if ($m['pedido_proveedor_enviado']): ?>
                                    <span style="color: #28a745; font-size: 0.7rem; font-weight: 800;">📦 PEDIDO</span>
                                <?php else: ?>
                                    <span style="color: #ffc107; font-size: 0.7rem; font-weight: 800;">⏳ PENDIENTE</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="card" style="background: #fdfbf7; text-align: right; padding: 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
        <p style="font-size: 0.8rem; color: #666; margin: 0; flex: 1; text-align: left;">Selecciona los materiales que ya has procesado para marcarlos como pedidos y evitar duplicados.</p>
        <button type="submit" name="save_only" class="btn" style="background: #444; color: white; padding: 1rem 2rem;">💾 GUARDAR CAMBIOS</button>
        <button type="submit" name="marcar_pedidos" class="btn btn-accent" style="padding: 1rem 2rem;">📦 MARCAR SELECCIONADOS</button>
    </div>
</form>

<script>
function enviarEmail(pedidoId, proveedorId) {
    if (confirm('¿Deseas enviar este pedido por email al proveedor?')) {
        const btn = event.target;
        const originalText = btn.innerText;
        btn.innerText = '⌛ ENVIANDO...';
        btn.disabled = true;

        fetch(`enviar_email_proveedor.php?pedido_id=${pedidoId}&proveedor_id=${proveedorId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('✅ ' + data.message);
                location.reload(); // Recargar para ver el estado actualizado
            } else {
                alert('❌ ' + data.message);
                btn.innerText = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            alert('❌ Error de conexión con el servidor.');
            btn.innerText = originalText;
            btn.disabled = false;
        });
    }
}
</script>

<?php include 'footer.php'; ?>
