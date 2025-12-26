<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referencia = $_POST['referencia'] ?? 'N/A';
    $id_paciente = $_POST['id_paciente'] ?? null;
    $id_cliente_fiscal = $_POST['id_cliente_fiscal'] ?? null;
    
    if (!$id_paciente || !$id_cliente_fiscal) {
        die("Error: Faltan datos críticos (Paciente u Ortopedia).");
    }

    // Obtener nombre del paciente para registro histórico
    $stmtP = $pdo->prepare("SELECT nombre FROM pacientes WHERE id = ?");
    $stmtP->execute([$id_paciente]);
    $paciente_nombre = $stmtP->fetchColumn() ?: 'N/A';
    
    // Materiales
    $mat_descs = $_POST['mat_desc'] ?? [];
    $mat_cons = $_POST['mat_cons'] ?? [];
    $mat_precs = $_POST['mat_prec'] ?? [];
    
    // Mano de Obra
    $mo_descs = $_POST['mo_desc'] ?? [];
    $mo_cons = $_POST['mo_cons'] ?? [];
    $mo_precs = $_POST['mo_prec'] ?? [];

    // Lógica de Imagen
    $imagen_path = null;
    if (isset($_FILES['imagen_modelo']) && $_FILES['imagen_modelo']['error'] === UPLOAD_ERR_OK) {
        $original_name = $_FILES['imagen_modelo']['name'];
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        // Sanitizar nombre: quitar espacios y poner timestamp para evitar duplicados
        $clean_name = preg_replace("/[^a-zA-Z0-9]/", "_", pathinfo($original_name, PATHINFO_FILENAME));
        $filename = $clean_name . "_" . time() . "." . $ext;
        $target = "uploads/modelos/" . $filename;
        
        if (move_uploaded_file($_FILES['imagen_modelo']['tmp_name'], $target)) {
            $imagen_path = $target;
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Calcular Total General
        $total_general = 0;
        foreach ($mat_cons as $i => $cons) {
            $total_general += (floatval($cons) * floatval($mat_precs[$i] ?? 0));
        }
        foreach ($mo_cons as $i => $cons) {
            $total_general += (floatval($cons) * floatval($mo_precs[$i] ?? 0));
        }

        $edit_id = $_POST['edit_id'] ?? null;

        if ($edit_id) {
            // MODO EDICIÓN
            $sql = "UPDATE pedidos SET referencia_pedido = ?, id_cliente_fiscal = ?, id_paciente = ?, paciente = ?, total_pedido = ?";
            $params = [$referencia, $id_cliente_fiscal, $id_paciente, $paciente_nombre, $total_general];
            if ($imagen_path) {
                $sql .= ", imagen_modelo = ?";
                $params[] = $imagen_path;
            }
            $sql .= " WHERE id = ?";
            $params[] = $edit_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pedido_id = $edit_id;

            // Limpiar detalles antiguos para re-insertar
            $pdo->prepare("DELETE FROM modelos_detalle WHERE pedido_id = ?")->execute([$pedido_id]);
        } else {
            // MODO NUEVO
            $stmt = $pdo->prepare("INSERT INTO pedidos (referencia_pedido, id_cliente_fiscal, id_paciente, paciente, fecha_pedido, estado, total_pedido, imagen_modelo) VALUES (?, ?, ?, ?, CURDATE(), 'Pendiente', ?, ?)");
            $stmt->execute([$referencia, $id_cliente_fiscal, $id_paciente, $paciente_nombre, $total_general, $imagen_path]);
            $pedido_id = $pdo->lastInsertId();
        }

        // 3. Insertar Materiales en modelos_detalle
        $stmtMat = $pdo->prepare("INSERT INTO modelos_detalle (pedido_id, tipo, descripcion, consumo, precio, total) VALUES (?, 'Material', ?, ?, ?, ?)");
        foreach ($mat_descs as $i => $desc) {
            if (!empty($desc)) {
                $c = floatval($mat_cons[$i] ?? 0);
                $p = floatval($mat_precs[$i] ?? 0);
                $t = $c * $p;
                $stmtMat->execute([$pedido_id, $desc, $c, $p, $t]);
            }
        }

        // 4. Insertar Mano de Obra en modelos_detalle
        $stmtMo = $pdo->prepare("INSERT INTO modelos_detalle (pedido_id, tipo, descripcion, consumo, precio, total) VALUES (?, 'Mano de Obra', ?, ?, ?, ?)");
        foreach ($mo_descs as $i => $desc) {
            if (!empty($desc)) {
                $c = floatval($mo_cons[$i] ?? 0);
                $p = floatval($mo_precs[$i] ?? 0);
                $t = $c * $p;
                $stmtMo->execute([$pedido_id, $desc, $c, $p, $t]);
            }
        }

        if (!$edit_id) {
            // 5. Generar Factura Proforma (Solo si es nuevo)
            $numero_proforma = "PROF-" . date('Ymd') . "-" . $pedido_id;
            $stmtFact = $pdo->prepare("INSERT INTO facturas (pedido_id, numero_factura, fecha_factura, total, estado) VALUES (?, ?, CURDATE(), ?, 'Pendiente')");
            $stmtFact->execute([$pedido_id, $numero_proforma, $total_general]);

            // 6. Crear entrada en Fabricación
            $stmtFab = $pdo->prepare("INSERT INTO fabricacion (pedido_id, estado_proceso, fecha_inicio) VALUES (?, 'Corte', CURDATE())");
            $stmtFab->execute([$pedido_id]);
        }

        // 7. Eliminar borrador si existe
        $stmtDelDraft = $pdo->prepare("DELETE FROM pedidos_borradores WHERE id_paciente = ?");
        $stmtDelDraft->execute([$id_paciente]);

        $pdo->commit();
        header("Location: pedidos.php?msg=success&last_id=" . $pedido_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error crítico al procesar el lanzamiento: " . $e->getMessage());
    }
}
?>
