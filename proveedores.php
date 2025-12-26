<?php
require_once 'conexion.php';
include 'header.php';

// 1. LÓGICA DE BORRADO
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: proveedores.php?msg=deleted");
    exit;
}

// 2. LÓGICA DE GUARDADO (ALTA Y EDICIÓN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_proveedor'])) {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre_comercial'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE proveedores SET nombre_comercial = ?, contacto = ?, telefono = ?, email = ? WHERE id = ?");
        $stmt->execute([$nombre, $contacto, $telefono, $email, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO proveedores (nombre_comercial, contacto, telefono, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $contacto, $telefono, $email]);
    }
    header("Location: proveedores.php?msg=success");
    exit;
}

// 3. CARGA DE DATOS PARA EDICIÓN
$edit_prov = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_prov = $stmt->fetch();
}

// 4. LISTADO GENERAL
$stmt = $pdo->query("SELECT * FROM proveedores ORDER BY nombre_comercial ASC");
$proveedores = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="text-transform: uppercase; letter-spacing: 2px;">Gestión de Proveedores</h1>
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
                <th style="padding: 1rem;">NOMBRE COMERCIAL</th>
                <th style="padding: 1rem;">CONTACTO</th>
                <th style="padding: 1rem;">TELÉFONO</th>
                <th style="padding: 1rem;">EMAIL</th>
                <th style="padding: 1rem; text-align: center;">ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proveedores as $p): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 1rem; font-weight: bold;"><?php echo htmlspecialchars($p['nombre_comercial']); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($p['contacto']); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($p['telefono']); ?></td>
                <td style="padding: 1rem;"><?php echo htmlspecialchars($p['email']); ?></td>
                <td style="padding: 1rem; text-align: center;">
                    <a href="proveedores.php?edit=<?php echo $p['id']; ?>" style="color: var(--accent-gold); text-decoration: none; font-weight: bold; margin-right: 1rem;">EDITAR</a>
                    <a href="proveedores.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('¿Eliminar proveedor?')" style="color: #cc0000; text-decoration: none; font-weight: bold;">BORRAR</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($proveedores)): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 3rem; color: #999;">No hay proveedores registrados.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto 100px auto; background: var(--bg-cream);">
    <h2 style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
        <?php echo $edit_prov ? 'Editar Proveedor' : 'Nuevo Proveedor'; ?>
    </h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $edit_prov['id'] ?? ''; ?>">
        
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">NOMBRE DEL PROVEEDOR</label>
        <input type="text" name="nombre_comercial" value="<?php echo htmlspecialchars($edit_prov['nombre_comercial'] ?? ''); ?>" required style="width:100%; margin-bottom: 1.5rem;">
        
        <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">PERSONA DE CONTACTO</label>
        <input type="text" name="contacto" value="<?php echo htmlspecialchars($edit_prov['contacto'] ?? ''); ?>" style="width:100%; margin-bottom: 1.5rem;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">TELÉFONO</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($edit_prov['telefono'] ?? ''); ?>" style="width:100%;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 700; font-size: 0.8rem; color: #666;">EMAIL</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($edit_prov['email'] ?? ''); ?>" style="width:100%;">
            </div>
        </div>
        
        <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
            <button type="submit" name="save_proveedor" class="btn btn-primary" style="flex: 2; padding: 1rem;">GUARDAR PROVEEDOR</button>
            <button type="button" onclick="window.location.href='proveedores.php'" class="btn" style="flex: 1; background: #ccc; padding: 1rem;">CANCELAR</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
