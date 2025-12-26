<?php
require_once 'conexion.php';
include 'header.php';

// 1. LÓGICA DE BORRADO
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM articulos WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: articulos.php?msg=deleted");
    exit;
}

// 2. LÓGICA DE GUARDADO (ALTA Y EDICIÓN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_articulo'])) {
    $id = $_POST['id'] ?? null;
    $codigo = $_POST['codigo'];
    $descripcion = $_POST['descripcion'];
    $id_proveedor = $_POST['id_proveedor'];
    $precio_coste = $_POST['precio_coste'];
    $unidad = $_POST['unidad'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE articulos SET codigo = ?, descripcion = ?, id_proveedor = ?, precio_coste = ?, unidad = ? WHERE id = ?");
        $stmt->execute([$codigo, $descripcion, $id_proveedor, $precio_coste, $unidad, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO articulos (codigo, descripcion, id_proveedor, precio_coste, unidad) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $descripcion, $id_proveedor, $precio_coste, $unidad]);
    }
    header("Location: articulos.php?msg=success");
    exit;
}

// 3. CARGA DE DATOS PARA EDICIÓN
$edit_art = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_art = $stmt->fetch();
}

// Obtenemos artículos con el nombre del proveedor unido (JOIN)
$stmt = $pdo->query("SELECT a.*, p.nombre_comercial as proveedor 
                     FROM articulos a 
                     LEFT JOIN proveedores p ON a.id_proveedor = p.id 
                     ORDER BY a.descripcion ASC");
$articulos = $stmt->fetchAll();

// Obtenemos proveedores para el desplegable del formulario
$stmtP = $pdo->query("SELECT id, nombre_comercial FROM proveedores ORDER BY nombre_comercial ASC");
$proveedores = $stmtP->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="text-transform: uppercase; letter-spacing: 2px;">Catálogo de Artículos y Materiales</h1>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
        Operación realizada con éxito.
    </div>
<?php endif; ?>

<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 3rem;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead style="background: var(--text-desert-black); color: white;">
            <tr>
                <th style="padding: 1rem;">CÓDIGO</th>
                <th style="padding: 1rem;">DESCRIPCIÓN</th>
                <th style="padding: 1rem;">PROVEEDOR</th>
                <th style="padding: 1rem;">U. MEDIDA</th>
                <th style="padding: 1rem;">P. COSTE</th>
                <th style="padding: 1rem; text-align: center;">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articulos as $a): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 1rem; font-family: monospace;"><?php echo htmlspecialchars($a['codigo']); ?></td>
                <td style="padding: 1rem; font-weight: bold;"><?php echo htmlspecialchars($a['descripcion']); ?></td>
                <td style="padding: 1rem; color: var(--accent-gold); font-weight: bold;"><?php echo htmlspecialchars($a['proveedor'] ?: 'S/P'); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($a['unidad']); ?></td>
                <td style="padding: 1rem; font-weight: bold;"><?php echo number_format($a['precio_coste'], 2, ',', '.'); ?> €</td>
                <td style="padding: 1rem; text-align: center;">
                    <a href="articulos.php?edit=<?php echo $a['id']; ?>" style="color: var(--accent-gold); text-decoration: none; font-weight: bold; margin-right: 1rem;">EDITAR</a>
                    <a href="articulos.php?delete=<?php echo $a['id']; ?>" onclick="return confirm('¿Eliminar artículo?')" style="color: #cc0000; text-decoration: none; font-weight: bold;">BORRAR</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($articulos)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 3rem; color: #999;">No hay artículos registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto 100px auto; background: var(--bg-cream);">
    <h2 style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <?php echo $edit_art ? 'Editar Artículo' : 'Nuevo Artículo'; ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $edit_art['id'] ?? ''; ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">CÓDIGO INTERNO</label>
                <input type="text" name="codigo" value="<?php echo htmlspecialchars($edit_art['codigo'] ?? ''); ?>" style="width:100%;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">DESCRIPCIÓN DEL MATERIAL</label>
                <input type="text" name="descripcion" value="<?php echo htmlspecialchars($edit_art['descripcion'] ?? ''); ?>" required style="width:100%;">
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">PROVEEDOR RESPONSABLE</label>
            <select name="id_proveedor" required style="width:100%; padding: 0.8rem;">
                <option value="">-- Seleccionar Proveedor --</option>
                <?php foreach ($proveedores as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo (isset($edit_art['id_proveedor']) && $edit_art['id_proveedor'] == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nombre_comercial']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">PRECIO COSTE (€)</label>
                <input type="number" step="0.01" name="precio_coste" value="<?php echo $edit_art['precio_coste'] ?? ''; ?>" required style="width:100%;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">UNIDAD DE MEDIDA</label>
                <select name="unidad" style="width:100%; padding: 0.8rem;">
                    <option value="Pies" <?php echo (isset($edit_art['unidad']) && $edit_art['unidad'] == 'Pies') ? 'selected' : ''; ?>>Pies</option>
                    <option value="Metros" <?php echo (isset($edit_art['unidad']) && $edit_art['unidad'] == 'Metros') ? 'selected' : ''; ?>>Metros</option>
                    <option value="Par" <?php echo (isset($edit_art['unidad']) && $edit_art['unidad'] == 'Par') ? 'selected' : ''; ?>>Par</option>
                    <option value="Unidad" <?php echo (isset($edit_art['unidad']) && $edit_art['unidad'] == 'Unidad') ? 'selected' : ''; ?>>Unidad</option>
                </select>
            </div>
        </div>
        
        <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
            <button type="submit" name="save_articulo" class="btn btn-primary" style="flex: 2; padding: 1rem;">GUARDAR ARTÍCULO</button>
            <button type="button" onclick="window.location.href='articulos.php'" class="btn" style="flex: 1; background: #ccc; padding: 1rem;">CANCELAR</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
