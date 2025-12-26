<?php
require_once 'conexion.php';
include 'header.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clientes_fiscales WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: clientes_fiscales.php?msg=deleted");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fiscal'])) {
    $id = $_POST['id'] ?? null;
    $nombre_comercial = $_POST['nombre_comercial'];
    $razon_social = $_POST['razon_social'];
    $cif = $_POST['cif'];
    $direccion = $_POST['direccion'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE clientes_fiscales SET nombre_comercial = ?, razon_social = ?, cif = ?, direccion = ? WHERE id = ?");
        $stmt->execute([$nombre_comercial, $razon_social, $cif, $direccion, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes_fiscales (nombre_comercial, razon_social, cif, direccion) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre_comercial, $razon_social, $cif, $direccion]);
    }
    header("Location: clientes_fiscales.php?msg=saved");
    exit;
}

$stmt = $pdo->query("SELECT * FROM clientes_fiscales ORDER BY nombre_comercial ASC");
$fiscales = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="text-transform: uppercase; letter-spacing: 2px;">Clientes Fiscales (Facturación)</h1>
    <button onclick="document.getElementById('modal-fiscal').style.display='block'" class="btn btn-accent">+ Nuevo Cliente Fiscal</button>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem;">
    <?php foreach ($fiscales as $f): ?>
    <div class="card" style="border-left: 5px solid var(--text-desert-black);">
        <div style="display: flex; justify-content: space-between;">
            <h3 style="color: var(--accent-gold);"><?php echo htmlspecialchars($f['nombre_comercial']); ?></h3>
            <a href="clientes_fiscales.php?delete=<?php echo $f['id']; ?>" onclick="return confirm('¿Borrar cliente fiscal?')" style="color: #cc0000; text-decoration: none; font-size: 0.8rem;">BORRAR</a>
        </div>
        <div style="margin-top: 1rem; font-size: 0.9rem;">
            <p><strong>Razón Social:</strong> <?php echo htmlspecialchars($f['razon_social']); ?></p>
            <p><strong>CIF:</strong> <?php echo htmlspecialchars($f['cif']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($f['direccion']); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="modal-fiscal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000;">
    <div class="card" style="max-width: 600px; margin: 100px auto; background: var(--bg-cream);">
        <h2>Nuevo Cliente Fiscal</h2>
        <form action="clientes_fiscales.php" method="POST" style="margin-top: 1.5rem;">
            <label>Nombre Comercial</label>
            <input type="text" name="nombre_comercial" required placeholder="Ej: Ortoaparisi">
            <label>Razón Social</label>
            <input type="text" name="razon_social" required placeholder="Ej: Nieves Aparisi Rosello S.L.">
            <label>CIF</label>
            <input type="text" name="cif" required>
            <label>Dirección</label>
            <textarea name="direccion" rows="3"></textarea>
            <button type="submit" name="save_fiscal" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">GUARDAR DATOS FISCALES</button>
            <button type="button" onclick="document.getElementById('modal-fiscal').style.display='none'" class="btn" style="width: 100%; margin-top: 0.5rem; background: #ccc;">CANCELAR</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
