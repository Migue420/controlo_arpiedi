<?php
require_once 'conexion.php';
include 'header.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: pacientes.php?msg=deleted");
    exit;
}

// Handle Form Submission (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_paciente'])) {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'];
    $diagnostico = $_POST['diagnostico'];
    $observaciones = $_POST['observaciones'];
    $id_cliente_fiscal = $_POST['id_cliente_fiscal'];
    
    // Medidas
    $medidas = [
        'izq' => [
            'contorno' => $_POST['m_izq_contorno'],
            'altura' => $_POST['m_izq_altura'],
            'longitud' => $_POST['m_izq_longitud']
        ],
        'der' => [
            'contorno' => $_POST['m_der_contorno'],
            'altura' => $_POST['m_der_altura'],
            'longitud' => $_POST['m_der_longitud']
        ]
    ];
    $medidas_json = json_encode($medidas);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE pacientes SET nombre = ?, diagnostico = ?, medidas_json = ?, observaciones = ?, id_cliente_fiscal = ? WHERE id = ?");
        $stmt->execute([$nombre, $diagnostico, $medidas_json, $observaciones, $id_cliente_fiscal, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pacientes (nombre, diagnostico, medidas_json, observaciones, id_cliente_fiscal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $diagnostico, $medidas_json, $observaciones, $id_cliente_fiscal]);
    }
    header("Location: pacientes.php?msg=saved");
    exit;
}

// Get Patients with Fiscal Client Info
$stmt = $pdo->query("SELECT p.*, cf.nombre_comercial as ortopedia 
                     FROM pacientes p 
                     LEFT JOIN clientes_fiscales cf ON p.id_cliente_fiscal = cf.id 
                     ORDER BY p.nombre ASC");
$pacientes = $stmt->fetchAll();

// Get Patient for Editing
$edit_paciente = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
    $stmt->execute([$id]);
    $edit_paciente = $stmt->fetch();
    $medidas_edit = json_decode($edit_paciente['medidas_json'] ?? '{}', true);
}

// Get Fiscal Clients for Dropdown
$stmtF = $pdo->query("SELECT id, nombre_comercial, razon_social FROM clientes_fiscales ORDER BY nombre_comercial ASC");
$fiscales = $stmtF->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="text-transform: uppercase; letter-spacing: 2px;">GESTIÓN DE PACIENTES</h1>
    <button onclick="document.getElementById('modal-paciente').style.display='block'" class="btn btn-accent">[+ NUEVO PACIENTE]</button>
</div>

<!-- BUSCADOR RÁPIDO -->
<div class="card" style="margin-bottom: 2rem; padding: 1rem;">
    <input type="text" id="paciente-search" placeholder="🔍 Buscar paciente por nombre..." style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
</div>

<?php if (isset($_GET['msg'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        Operación realizada con éxito.
    </div>
<?php endif; ?>

<div id="pacientes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
    <?php foreach ($pacientes as $p): 
        $meds = json_decode($p['medidas_json'] ?? '{}', true);
        $diag_lower = mb_strtolower($p['diagnostico'] ?? '');
        $is_sensitive = (strpos($diag_lower, 'artritis') !== false || strpos($diag_lower, 'deformidad') !== false);
    ?>
    <div class="card paciente-card" data-nombre="<?php echo htmlspecialchars(mb_strtolower($p['nombre'])); ?>" style="position: relative; border-top: 4px solid <?php echo $is_sensitive ? '#cc0000' : 'var(--accent-gold)'; ?>; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($p['nombre']); ?></h3>
                    <small style="color: var(--accent-gold); font-weight: 700; text-transform: uppercase; font-size: 0.7rem;">
                        Ortopedia: <?php echo htmlspecialchars($p['ortopedia'] ?: 'SIN VINCULAR'); ?>
                    </small>
                </div>
                <?php if ($is_sensitive): ?>
                    <span style="background: #cc0000; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.6rem; font-weight: 700;">ALERTA CLÍNICA</span>
                <?php endif; ?>
            </div>
            
            <div style="margin: 1rem 0; padding: 0.8rem; background: <?php echo $is_sensitive ? 'rgba(204, 0, 0, 0.05)' : '#f9f9f9'; ?>; border-radius: 4px;">
                <small style="text-transform: uppercase; font-weight: 700; color: #888; font-size: 0.65rem;">Diagnóstico</small>
                <p style="font-size: 0.9rem; color: <?php echo $is_sensitive ? '#cc0000' : 'inherit'; ?>; font-weight: <?php echo $is_sensitive ? '700' : 'normal'; ?>;">
                    <?php echo htmlspecialchars($p['diagnostico'] ?: 'Sin diagnóstico'); ?>
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.8rem; margin-bottom: 1.5rem;">
                <div>
                    <strong style="display: block; border-bottom: 1px solid #eee; margin-bottom: 0.3rem;">Pie Izquierdo</strong>
                    <span title="Longitud/Altura/Contorno">L: <?php echo $meds['izq']['longitud'] ?? '-'; ?> | A: <?php echo $meds['izq']['altura'] ?? '-'; ?> | C: <?php echo $meds['izq']['contorno'] ?? '-'; ?></span>
                </div>
                <div>
                    <strong style="display: block; border-bottom: 1px solid #eee; margin-bottom: 0.3rem;">Pie Derecho</strong>
                    <span title="Longitud/Altura/Contorno">L: <?php echo $meds['der']['longitud'] ?? '-'; ?> | A: <?php echo $meds['der']['altura'] ?? '-'; ?> | C: <?php echo $meds['der']['contorno'] ?? '-'; ?></span>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.5rem; border-top: 1px solid #eee; padding-top: 1rem; margin-top: auto;">
            <a href="nuevo_escandallo.php?id_paciente=<?php echo $p['id']; ?>" class="btn btn-accent" style="width: 100%; text-align: center; font-size: 0.8rem; padding: 0.6rem;">⚙️ NUEVO PEDIDO</a>
            <div style="display: flex; gap: 0.5rem;">
                <a href="pacientes.php?edit=<?php echo $p['id']; ?>" class="btn" style="flex: 1; text-align: center; font-size: 0.75rem; background: #eee; color: #333;">📝 EDITAR</a>
                <a href="pacientes.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar a este paciente?')" class="btn" style="flex: 1; text-align: center; font-size: 0.75rem; background: #f8d7da; color: #721c24;">🗑️ BORRAR</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Form -->
<div id="modal-paciente" style="display: <?php echo $edit_paciente ? 'block' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; overflow-y: auto;">
    <div class="card" style="max-width: 800px; margin: 50px auto; background: var(--bg-cream);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2><?php echo $edit_paciente ? 'Editar Paciente' : 'Nuevo Paciente'; ?></h2>
            <button onclick="window.location.href='pacientes.php'" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
        </div>

        <form action="pacientes.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_paciente['id'] ?? ''; ?>">
            
            <div style="margin-bottom: 1.5rem;">
                <label>Cliente / Ortopedia Vinculada</label>
                <select name="id_cliente_fiscal" required>
                    <option value="">-- Seleccione Ortopedia --</option>
                    <?php foreach ($fiscales as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo (isset($edit_paciente['id_cliente_fiscal']) && $edit_paciente['id_cliente_fiscal'] == $f['id']) || ($f['nombre_comercial'] == 'Ortoaparisi') ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($f['nombre_comercial'] . " (" . $f['razon_social'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label>Nombre Completo del Paciente</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($edit_paciente['nombre'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>Diagnóstico Clínico</label>
                    <input type="text" name="diagnostico" value="<?php echo htmlspecialchars($edit_paciente['diagnostico'] ?? ''); ?>" placeholder="Ej: Artritis, Pie Cavo...">
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 0.9rem;">TABLA DE MEDIDAS (mm)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Pie Izquierdo -->
                    <div style="background: #fff; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
                        <h4 style="margin-bottom: 1rem; color: var(--accent-gold);">PIE IZQUIERDO</h4>
                        <label>Contorno Metatarsal</label>
                        <input type="number" name="m_izq_contorno" value="<?php echo $medidas_edit['izq']['contorno'] ?? ''; ?>">
                        <label>Altura de Dedos</label>
                        <input type="number" name="m_izq_altura" value="<?php echo $medidas_edit['izq']['altura'] ?? ''; ?>">
                        <label>Longitud Total</label>
                        <input type="number" name="m_izq_longitud" value="<?php echo $medidas_edit['izq']['longitud'] ?? ''; ?>">
                    </div>
                    <!-- Pie Derecho -->
                    <div style="background: #fff; padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 4px;">
                        <h4 style="margin-bottom: 1rem; color: var(--accent-gold);">PIE DERECHO</h4>
                        <label>Contorno Metatarsal</label>
                        <input type="number" name="m_der_contorno" value="<?php echo $medidas_edit['der']['contorno'] ?? ''; ?>">
                        <label>Altura de Dedos</label>
                        <input type="number" name="m_der_altura" value="<?php echo $medidas_edit['der']['altura'] ?? ''; ?>">
                        <label>Longitud Total</label>
                        <input type="number" name="m_der_longitud" value="<?php echo $medidas_edit['der']['longitud'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem;">
                <label>Observaciones Adicionales</label>
                <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($edit_paciente['observaciones'] ?? ''); ?></textarea>
            </div>

            <button type="submit" name="save_paciente" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem;">GUARDAR FICHA DE PACIENTE</button>
        </form>
    </div>
</div>

<script>
document.getElementById('paciente-search').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.paciente-card');
    
    cards.forEach(card => {
        const nombre = card.getAttribute('data-nombre');
        if (nombre.includes(term)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

<?php include 'footer.php'; ?>
