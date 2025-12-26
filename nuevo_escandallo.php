<?php
require_once 'conexion.php';
include 'header.php';

// Lógica para modo EDICIÓN
$edit_id = $_GET['edit_id'] ?? null;
$pedido_edit = null;
$mats_edit = [];
$mos_edit = [];

if ($edit_id) {
    $stmtE = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmtE->execute([$edit_id]);
    $pedido_edit = $stmtE->fetch();

    if ($pedido_edit) {
        // Cargar detalles para edición
        $stmtD = $pdo->prepare("SELECT * FROM modelos_detalle WHERE pedido_id = ?");
        $stmtD->execute([$edit_id]);
        $detalles_edit = $stmtD->fetchAll();
        
        $mats_edit = array_filter($detalles_edit, fn($d) => $d['tipo'] === 'Material');
        $mos_edit = array_filter($detalles_edit, fn($d) => $d['tipo'] === 'Mano de Obra');
    }
}
?>

<div style="display: flex; gap: 2rem; align-items: flex-start;">
    <!-- Formulario (Izquierda) -->
    <div style="flex: 1;">
        <form id="escandallo-form" action="guardar_escandallo.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
            <div class="card" style="border-top: 5px solid var(--accent-gold);">
                <h2 style="margin-bottom: 2rem; letter-spacing: 2px; text-transform: uppercase;">
                    <?php echo $edit_id ? 'Editar Escandallo' : 'Ficha Técnica de Escandallo'; ?>
                </h2>
                
                <!-- BLOQUE 1: CABECERA -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #666;">Referencia del Pedido</label>
                        <input type="text" name="referencia" placeholder="Ej: REF-2025-001" value="<?php echo htmlspecialchars($pedido_edit['referencia_pedido'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #666;">Paciente (Taller / Biomecánica)</label>
                        <?php
                        $stmtP = $pdo->query("SELECT id, nombre, diagnostico, medidas_json, id_cliente_fiscal FROM pacientes ORDER BY nombre ASC");
                        $pacientes_list = $stmtP->fetchAll();
                        ?>
                        <select name="id_paciente" id="paciente-select" required onchange="updatePatientInfo()">
                            <option value="">-- Seleccione Paciente --</option>
                            <?php 
                            $target_id = $pedido_edit['id_paciente'] ?? ($_GET['id_paciente'] ?? null);
                            foreach ($pacientes_list as $p): 
                            ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-diag="<?php echo htmlspecialchars($p['diagnostico']); ?>"
                                        data-meds='<?php echo htmlspecialchars($p['medidas_json']); ?>'
                                        data-fiscal="<?php echo $p['id_cliente_fiscal']; ?>"
                                        <?php echo ($target_id == $p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div>
                        <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #666;">Cliente (Facturación Automática)</label>
                        <?php
                        $stmtF = $pdo->query("SELECT id, nombre_comercial, razon_social FROM clientes_fiscales ORDER BY nombre_comercial ASC");
                        $fiscales = $stmtF->fetchAll();
                        ?>
                        <select name="id_cliente_fiscal" id="fiscal-select" required>
                            <option value="">-- Ortopedia vinculada --</option>
                            <?php foreach ($fiscales as $f): ?>
                                <option value="<?php echo $f['id']; ?>" <?php echo (isset($pedido_edit['id_cliente_fiscal']) && $pedido_edit['id_cliente_fiscal'] == $f['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($f['nombre_comercial'] . " (" . $f['razon_social'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- CARGA DE IMAGEN DEL MODELO -->
                <div style="margin-bottom: 2rem; padding: 1.5rem; background: #fff; border: 1px dashed var(--accent-gold); border-radius: 4px;">
                    <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--accent-gold); display: block; margin-bottom: 1rem;">📸 Imagen del Modelo / Diseño</label>
                    <div style="display: flex; gap: 2rem; align-items: center;">
                        <input type="file" name="imagen_modelo" accept="image/*" style="font-size: 0.8rem;">
                        <?php if (!empty($pedido_edit['imagen_modelo'])): ?>
                            <div style="text-align: center;">
                                <img src="<?php echo htmlspecialchars($pedido_edit['imagen_modelo']); ?>" style="height: 80px; border-radius: 4px; border: 1px solid #eee;">
                                <p style="font-size: 0.6rem; color: #888; margin-top: 0.3rem;">Imagen Actual</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- NOTA CLÍNICA DINÁMICA -->
                <div id="client-info-box" style="display: none; margin-bottom: 2rem; padding: 1.5rem; background: #f4f1ea; border-left: 4px solid var(--accent-gold); border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between;">
                        <div style="flex: 1;">
                            <small style="text-transform: uppercase; font-weight: 700; color: #888; font-size: 0.65rem;">Diagnóstico Clínico</small>
                            <p id="info-diagnostico" style="font-weight: 700; color: var(--text-desert-black); margin-top: 0.2rem;"></p>
                        </div>
                        <div style="flex: 1; display: flex; gap: 2rem; justify-content: flex-end;">
                            <div id="info-meds-izq" style="font-size: 0.75rem;"></div>
                            <div id="info-meds-der" style="font-size: 0.75rem;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUE 2: LISTA DE MATERIALES -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Lista de Materiales</h3>
                
                <?php
                // Obtener artículos para el datalist y auto-precio
                $stmtA = $pdo->query("SELECT descripcion, precio_coste FROM articulos ORDER BY descripcion ASC");
                $articulos_db = $stmtA->fetchAll();
                ?>
                <datalist id="articulos-list">
                    <?php foreach ($articulos_db as $art): ?>
                        <option value="<?php echo htmlspecialchars($art['descripcion']); ?>" data-precio="<?php echo $art['precio_coste']; ?>">
                    <?php endforeach; ?>
                </datalist>

                <table id="tabla-materiales">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Descripción Material</th>
                            <th style="width: 15%;">Consumo</th>
                            <th style="width: 15%;">Precio (€)</th>
                            <th style="width: 15%;">Total (€)</th>
                            <th style="width: 15%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="body-materiales">
                        <?php if ($pedido_edit && !empty($mats_edit)): ?>
                            <?php foreach ($mats_edit as $m): ?>
                            <tr>
                                <td><input type="text" name="mat_desc[]" list="articulos-list" class="art-desc-input" value="<?php echo htmlspecialchars($m['descripcion']); ?>"></td>
                                <td><input type="number" step="0.001" name="mat_cons[]" class="calc-trigger" value="<?php echo $m['consumo']; ?>"></td>
                                <td><input type="number" step="0.01" name="mat_prec[]" class="calc-trigger art-price-input" value="<?php echo $m['precio']; ?>"></td>
                                <td><input type="text" class="row-total" value="<?php echo number_format($m['total'], 2, ',', '.'); ?> €" readonly style="background: #f9f9f9; font-weight: 700;"></td>
                                <td><button type="button" class="btn btn-primary btn-remove" style="background: #cc0000; padding: 0.5rem;">×</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td><input type="text" name="mat_desc[]" list="articulos-list" class="art-desc-input" placeholder="Ej: Piel Vacuno"></td>
                                <td><input type="number" step="0.001" name="mat_cons[]" class="calc-trigger" value="0.000"></td>
                                <td><input type="number" step="0.01" name="mat_prec[]" class="calc-trigger art-price-input" value="0.00"></td>
                                <td><input type="text" class="row-total" value="0,00" readonly style="background: #f9f9f9; font-weight: 700;"></td>
                                <td><button type="button" class="btn btn-primary btn-remove" style="background: #cc0000; padding: 0.5rem;">×</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" id="add-material" class="btn btn-accent" style="margin-top: 1rem; font-size: 0.8rem;">+ Añadir Material</button>
            </div>

            <!-- BLOQUE 3: MANO DE OBRA -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Mano de Obra</h3>
                <table id="tabla-mano-obra">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Concepto de Trabajo</th>
                            <th style="width: 15%;">Horas/Cant</th>
                            <th style="width: 15%;">Precio/H (€)</th>
                            <th style="width: 15%;">Total (€)</th>
                            <th style="width: 15%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="body-mano-obra">
                        <?php if ($pedido_edit && !empty($mos_edit)): ?>
                            <?php foreach ($mos_edit as $mo): ?>
                            <tr>
                                <td><input type="text" name="mo_desc[]" value="<?php echo htmlspecialchars($mo['descripcion']); ?>"></td>
                                <td><input type="number" step="0.01" name="mo_cons[]" class="calc-trigger" value="<?php echo $mo['consumo']; ?>"></td>
                                <td><input type="number" step="0.01" name="mo_prec[]" class="calc-trigger" value="<?php echo $mo['precio']; ?>"></td>
                                <td><input type="text" class="row-total" value="<?php echo number_format($mo['total'], 2, ',', '.'); ?> €" readonly style="background: #f9f9f9; font-weight: 700;"></td>
                                <td><button type="button" class="btn btn-primary btn-remove" style="background: #cc0000; padding: 0.5rem;">×</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php
                            $operaciones = ['GESTIÓN', 'MODELAJE', 'HORMA PERSONAL', 'CORTADO', 'APARADO', 'MONTADO', 'ACABADO', 'EMPAQUETADO', 'PRUEBA CALCE'];
                            foreach ($operaciones as $op):
                            ?>
                            <tr>
                                <td><input type="text" name="mo_desc[]" value="<?php echo $op; ?>"></td>
                                <td><input type="number" step="0.01" name="mo_cons[]" class="calc-trigger" value="1.00"></td>
                                <td><input type="number" step="0.01" name="mo_prec[]" class="calc-trigger" value="0.00"></td>
                                <td><input type="text" class="row-total" value="0,00" readonly style="background: #f9f9f9; font-weight: 700;"></td>
                                <td><button type="button" class="btn btn-primary btn-remove" style="background: #cc0000; padding: 0.5rem;">×</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" id="add-mo" class="btn btn-accent" style="margin-top: 1rem; font-size: 0.8rem;">+ Añadir Trabajo</button>
            </div>

            <!-- RESUMEN FINAL -->
            <div class="card" style="background: var(--text-desert-black); color: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="font-family: var(--font-heading);">TOTAL ESCANDALLO</h2>
                    <div id="total-general" style="font-size: 2.5rem; font-weight: 900; color: var(--accent-gold);">0,00 €</div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="button" id="btn-save-draft" class="btn" style="background: #444; color: white; padding: 1.2rem; font-size: 1rem; letter-spacing: 1px; border: 1px solid #666;">
                        💾 GUARDAR PROYECTO
                    </button>
                    <button type="submit" id="btn-confirm-launch" class="btn btn-accent" style="padding: 1.2rem; font-size: 1rem; letter-spacing: 1px;">
                        🚀 <?php echo $edit_id ? 'ACTUALIZAR PEDIDO' : 'CONFIRMAR Y LANZAR'; ?>
                    </button>
                </div>
                
                <div id="save-status" style="margin-top: 1rem; text-align: center; font-size: 0.85rem; height: 1.2rem; opacity: 0; transition: opacity 0.3s ease;">
                    <span style="color: #4cd137;">✅ Progreso asegurado</span>
                </div>
            </div>
        </form>
    </div>

    <!-- Visor 3D (Derecha - Sticky) -->
    <div style="width: 450px; position: sticky; top: 100px;">
        <div class="card" style="background: var(--charcoal-gray); color: white; padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div id="viewer-3d" style="height: 500px; width: 100%; cursor: move;"></div>
        </div>
    </div>
</div>

<script>
function updatePatientInfo() {
    const select = document.getElementById('paciente-select');
    const fiscalSelect = document.getElementById('fiscal-select');
    const infoBox = document.getElementById('client-info-box');
    const diagP = document.getElementById('info-diagnostico');
    const medsIzq = document.getElementById('info-meds-izq');
    const medsDer = document.getElementById('info-meds-der');

    if (select.value === "") {
        infoBox.style.display = 'none';
        return;
    }

    const option = select.options[select.selectedIndex];
    const diag = option.getAttribute('data-diag');
    const meds = JSON.parse(option.getAttribute('data-meds') || '{}');
    const fiscalId = option.getAttribute('data-fiscal');

    // Auto-seleccionar cliente fiscal
    fiscalSelect.value = fiscalId;

    diagP.innerText = diag || "Sin diagnóstico";
    if (diag.toLowerCase().includes('artritis') || diag.toLowerCase().includes('deformidad')) {
        infoBox.style.borderLeftColor = '#cc0000';
        diagP.style.color = '#cc0000';
    } else {
        infoBox.style.borderLeftColor = 'var(--accent-gold)';
        diagP.style.color = 'var(--text-desert-black)';
    }

    medsIzq.innerHTML = `<strong>IZQ:</strong> C:${meds.izq?.contorno || '-'} A:${meds.izq?.altura || '-'} L:${meds.izq?.longitud || '-'}`;
    medsDer.innerHTML = `<strong>DER:</strong> C:${meds.der?.contorno || '-'} A:${meds.der?.altura || '-'} L:${meds.der?.longitud || '-'}`;

    infoBox.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    // --- Lógica de Filas Dinámicas ---
    function addRow(tbodyId) {
        const tbody = document.getElementById(tbodyId);
        const rows = tbody.querySelectorAll('tr');
        const newRow = rows[0].cloneNode(true);
        const inputs = newRow.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.classList.contains('row-total')) {
                input.value = '0,00';
            } else if (input.type === 'number') {
                input.value = (tbodyId === 'body-materiales') ? '0.000' : '1.00';
            } else {
                input.value = '';
            }
        });
        tbody.appendChild(newRow);
        attachEvents(newRow);
    }

    document.getElementById('add-material').addEventListener('click', () => addRow('body-materiales'));
    document.getElementById('add-mo').addEventListener('click', () => addRow('body-mano-obra'));

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove')) {
            const row = e.target.closest('tr');
            if (row.parentNode.rows.length > 1) {
                row.remove();
                calculateTotals();
            }
        }
    });

    function calculateTotals() {
        let grandTotal = 0;
        document.querySelectorAll('tr').forEach(row => {
            const consInput = row.querySelector('.calc-trigger[name*="cons"]');
            const precInput = row.querySelector('.calc-trigger[name*="prec"]');
            const totalInput = row.querySelector('.row-total');
            if (consInput && precInput && totalInput) {
                const total = (parseFloat(consInput.value) || 0) * (parseFloat(precInput.value) || 0);
                totalInput.value = total.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';
                grandTotal += total;
            }
        });
        document.getElementById('total-general').innerText = grandTotal.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';
    }

    function attachEvents(element) {
        element.querySelectorAll('.calc-trigger').forEach(trigger => {
            trigger.addEventListener('input', calculateTotals);
        });

        // Lógica de Auto-Precio
        const descInput = element.querySelector('.art-desc-input');
        const priceInput = element.querySelector('.art-price-input');
        if (descInput && priceInput) {
            descInput.addEventListener('input', function() {
                const val = this.value;
                const options = document.getElementById('articulos-list').options;
                for (let i = 0; i < options.length; i++) {
                    if (options[i].value === val) {
                        priceInput.value = options[i].getAttribute('data-precio');
                        calculateTotals();
                        break;
                    }
                }
            });
        }
    }

    document.querySelectorAll('tr').forEach(attachEvents);
    calculateTotals(); // Calcular inicial si hay datos cargados

    // --- Lógica de Guardado (AJAX y Auto-guardado) ---
    function saveDraft(isAuto = false) {
        const form = document.getElementById('escandallo-form');
        const formData = new FormData(form);
        const statusDiv = document.getElementById('save-status');

        // DEBUG: Ver qué enviamos
        const dataObj = {};
        formData.forEach((value, key) => { dataObj[key] = value; });
        console.log("📤 Enviando datos al servidor:", dataObj);

        fetch('guardar_borrador.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                statusDiv.style.opacity = '1';
                setTimeout(() => { statusDiv.style.opacity = '0'; }, 3000);
                console.log(isAuto ? '✅ Auto-guardado completado' : '✅ Borrador guardado manualmente');
            } else {
                console.error("❌ Error en respuesta:", data.message);
                if (!isAuto) alert("⚠️ ERROR AL GUARDAR: " + data.message);
            }
        })
        .catch(error => {
            console.error('❌ Error de red/servidor:', error);
            if (!isAuto) alert("⚠️ EMERGENCIA: No se pudo conectar con el servidor. ¡No cierres la página!");
        });
    }

    // Recuperación Automática (Solo si no estamos editando un pedido real)
    <?php if (!$edit_id): ?>
    function checkRecovery() {
        const idPaciente = document.getElementById('paciente-select').value;
        if (!idPaciente) return;

        fetch(`obtener_borrador.php?id_paciente=${idPaciente}`)
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success' && res.data) {
                if (confirm('📂 He encontrado un borrador sin terminar para este paciente. ¿Quieres recuperarlo?')) {
                    loadDraftData(res.data);
                }
            }
        });
    }

    function loadDraftData(data) {
        console.log("📥 Recuperando datos:", data);
        if (data.referencia) document.querySelector('input[name="referencia"]').value = data.referencia;
        if (data.id_cliente_fiscal) document.getElementById('fiscal-select').value = data.id_cliente_fiscal;

        const clearTable = (tbodyId) => {
            const tbody = document.getElementById(tbodyId);
            while (tbody.rows.length > 1) tbody.deleteRow(1);
        };
        clearTable('body-materiales');
        clearTable('body-mano-obra');

        if (data.mat_desc) {
            data.mat_desc.forEach((desc, i) => {
                if (i === 0) {
                    const row = document.getElementById('body-materiales').rows[0];
                    row.querySelector('input[name="mat_desc[]"]').value = desc;
                    row.querySelector('input[name="mat_cons[]"]').value = data.mat_cons[i];
                    row.querySelector('input[name="mat_prec[]"]').value = data.mat_prec[i];
                } else {
                    addRow('body-materiales');
                    const row = document.getElementById('body-materiales').lastElementChild;
                    row.querySelector('input[name="mat_desc[]"]').value = desc;
                    row.querySelector('input[name="mat_cons[]"]').value = data.mat_cons[i];
                    row.querySelector('input[name="mat_prec[]"]').value = data.mat_prec[i];
                }
            });
        }

        if (data.mo_desc) {
            data.mo_desc.forEach((desc, i) => {
                const tbody = document.getElementById('body-mano-obra');
                let row = tbody.rows[i];
                if (!row) {
                    addRow('body-mano-obra');
                    row = tbody.lastElementChild;
                }
                row.querySelector('input[name="mo_desc[]"]').value = desc;
                row.querySelector('input[name="mo_cons[]"]').value = data.mo_cons[i];
                row.querySelector('input[name="mo_prec[]"]').value = data.mo_prec[i];
            });
        }
        calculateTotals();
    }
    <?php endif; ?>

    // Guardado manual
    document.getElementById('btn-save-draft').addEventListener('click', () => {
        if (!document.getElementById('paciente-select').value) {
            alert("⚠️ Selecciona un paciente primero para guardar el borrador.");
            return;
        }
        saveDraft(false);
    });

    // Auto-guardado cada 2 minutos
    setInterval(() => saveDraft(true), 120000);

    // Validación antes de Confirmar y Lanzar
    document.getElementById('escandallo-form').addEventListener('submit', function(e) {
        const idPaciente = document.getElementById('paciente-select').value;
        const idFiscal = document.getElementById('fiscal-select').value;

        if (!idPaciente || !idFiscal) {
            e.preventDefault();
            alert('⚠️ ERROR: Debe seleccionar un Paciente y una Ortopedia antes de lanzar el pedido.');
            return false;
        }
    });

    // Trigger inicial
    if (document.getElementById('paciente-select').value !== "") {
        updatePatientInfo();
        <?php if (!$edit_id): ?>
        checkRecovery();
        <?php endif; ?>
    }

    <?php if (!$edit_id): ?>
    document.getElementById('paciente-select').addEventListener('change', checkRecovery);
    <?php endif; ?>

    // --- 3D Viewer ---
    if (typeof THREE !== 'undefined') {
        const container = document.getElementById('viewer-3d');
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x2c2c2c);
        const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 5;
        const renderer = new THREE.WebGLRenderer({ antialias: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        container.appendChild(renderer.domElement);
        const controls = new THREE.OrbitControls(camera, renderer.domElement);
        const gridHelper = new THREE.GridHelper(10, 10, 0x444444, 0x333333);
        scene.add(gridHelper);
        function animate() { requestAnimationFrame(animate); controls.update(); renderer.render(scene, camera); }
        animate();
    }
});
</script>

<?php include 'footer.php'; ?>
