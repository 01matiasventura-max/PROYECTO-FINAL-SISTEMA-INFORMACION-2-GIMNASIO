<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo    = getPDO();
$clases = $pdo->query("SELECT id, nombre FROM clases WHERE activo = 1 ORDER BY nombre")->fetchAll();
$errors = [];
$dias   = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $claseId       = (int)($_POST['clase_id'] ?? 0);
    $diasSelec     = array_filter(array_map('intval', $_POST['dias_semana'] ?? []), fn($d) => $d >= 1 && $d <= 7);
    $horasInicio   = $_POST['hora_inicio'] ?? [];
    $horasFin      = $_POST['hora_fin'] ?? [];
    $sala          = trim($_POST['sala'] ?? '');
    $activo        = isset($_POST['activo']) ? 1 : 0;

    if ($claseId <= 0)     $errors[] = 'Selecciona una clase.';
    if (empty($diasSelec)) $errors[] = 'Selecciona al menos un día.';
    if ($sala === '')       $errors[] = 'La sala es requerida.';

    // Validar franjas
    $franjas = [];
    foreach ($horasInicio as $i => $hi) {
        $hf = $horasFin[$i] ?? '';
        if ($hi === '' || $hf === '') { $errors[] = "La franja #" . ($i+1) . " tiene horas vacías."; continue; }
        if ($hf <= $hi)               { $errors[] = "En la franja #" . ($i+1) . " la hora fin debe ser mayor que la hora inicio."; continue; }
        $franjas[] = [$hi, $hf];
    }
    if (empty($franjas) && empty(array_filter($errors, fn($e) => str_contains($e, 'franja')))) {
        $errors[] = 'Agrega al menos una franja horaria.';
    }

    if (empty($errors)) {
        $stmt    = $pdo->prepare("INSERT INTO horarios (clase_id, dia_semana, hora_inicio, hora_fin, sala, activo) VALUES (?,?,?,?,?,?)");
        $creados = 0;
        $conflictos = [];
        foreach ($diasSelec as $dia) {
            foreach ($franjas as [$hi, $hf]) {
                try {
                    $stmt->execute([$claseId, $dia, $hi, $hf, $sala, $activo]);
                    $creados++;
                } catch (PDOException $ex) {
                    $conflictos[] = $dias[$dia] . " $hi–$hf";
                }
            }
        }
        if ($creados > 0) {
            $msg = $creados === 1 ? 'Horario creado.' : "$creados horarios creados correctamente.";
            if ($conflictos) $msg .= ' Omitidos por conflicto: ' . implode(', ', $conflictos) . '.';
            flashSuccess($msg);
            header('Location: index.php'); exit;
        } else {
            $errors[] = 'Todos los horarios generaron conflicto de sala. Ninguno fue guardado.';
        }
    }
}

// Repoblar franjas si hubo error
$franjasPrev = [];
foreach (($_POST['hora_inicio'] ?? []) as $i => $hi) {
    $franjasPrev[] = [$hi, $_POST['hora_fin'][$i] ?? ''];
}
if (empty($franjasPrev)) $franjasPrev = [['', '']];

$pageTitle  = 'Nuevo Horario';
$breadcrumb = [['label' => 'Horarios', 'url' => BASE_URL . '/horarios/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-primary me-2"></i>Nuevo Horario</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<div class="table-card p-4">
<form method="POST" id="form-horario">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">

        <!-- Clase -->
        <div class="col-md-8">
            <label class="form-label fw-semibold">Clase <span class="text-danger">*</span></label>
            <select name="clase_id" class="form-select" required>
                <option value="">— Seleccionar clase —</option>
                <?php foreach ($clases as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_POST['clase_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Sala -->
        <div class="col-md-4">
            <label class="form-label fw-semibold">Sala <span class="text-danger">*</span></label>
            <input type="text" name="sala" class="form-control" value="<?= e($_POST['sala'] ?? '') ?>" placeholder="Ej: Sala A" required>
        </div>

        <!-- Días -->
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <label class="form-label fw-semibold mb-0">Días de la semana <span class="text-danger">*</span></label>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleDias(true)">Todos</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleDias(false)">Ninguno</button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleLaboral()">L–V</button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-1">
                <?php
                $diasSelecPost = array_map('intval', $_POST['dias_semana'] ?? []);
                foreach ($dias as $num => $nombre):
                    $checked = in_array($num, $diasSelecPost) ? 'checked' : '';
                ?>
                <div class="form-check form-check-inline border rounded px-3 py-2 dia-item" style="min-width:100px; cursor:pointer;">
                    <input class="form-check-input dia-check" type="checkbox" name="dias_semana[]"
                           id="dia_<?= $num ?>" value="<?= $num ?>" <?= $checked ?>>
                    <label class="form-check-label fw-semibold" for="dia_<?= $num ?>" style="cursor:pointer;"><?= $nombre ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Franjas horarias -->
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label fw-semibold mb-0">Franjas horarias <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm btn-success" onclick="addFranja()">
                    <i class="fa-solid fa-plus me-1"></i>Agregar franja
                </button>
            </div>
            <div id="franjas-container">
                <?php foreach ($franjasPrev as $idx => [$hi, $hf]): ?>
                <div class="franja-row d-flex align-items-center gap-2 mb-2 p-2 border rounded bg-light">
                    <span class="text-muted small fw-semibold" style="min-width:72px;">Franja <?= $idx+1 ?></span>
                    <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
                        <div>
                            <label class="form-label small mb-1">Hora inicio</label>
                            <input type="time" name="hora_inicio[]" class="form-control form-control-sm" value="<?= e($hi) ?>" required style="width:130px;">
                        </div>
                        <span class="text-muted mt-3">→</span>
                        <div>
                            <label class="form-label small mb-1">Hora fin</label>
                            <input type="time" name="hora_fin[]" class="form-control form-control-sm" value="<?= e($hf) ?>" required style="width:130px;">
                        </div>
                    </div>
                    <?php if ($idx > 0): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="removeFranja(this)" title="Eliminar franja">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <div style="width:34px;"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-text">Se creará <strong>un registro</strong> por cada combinación de día × franja horaria.</div>
        </div>

        <!-- Activo -->
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                       <?= (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Horarios activos</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar todos</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>

<style>
.dia-item { transition: background .15s, border-color .15s; }
.dia-item:has(.dia-check:checked) {
    background: rgba(13,110,253,.1);
    border-color: #0d6efd !important;
}
.franja-row { background: #f8f9fa; }
</style>
<script>
function toggleDias(sel) {
    document.querySelectorAll('.dia-check').forEach(cb => cb.checked = sel);
}
function toggleLaboral() {
    document.querySelectorAll('.dia-check').forEach(cb => {
        cb.checked = ['1','2','3','4','5'].includes(cb.value);
    });
}
let franjaCount = <?= count($franjasPrev) ?>;
function addFranja() {
    franjaCount++;
    const container = document.getElementById('franjas-container');
    const div = document.createElement('div');
    div.className = 'franja-row d-flex align-items-center gap-2 mb-2 p-2 border rounded bg-light';
    div.innerHTML = `
        <span class="text-muted small fw-semibold" style="min-width:72px;">Franja ${franjaCount}</span>
        <div class="d-flex align-items-center gap-2 flex-wrap flex-grow-1">
            <div>
                <label class="form-label small mb-1">Hora inicio</label>
                <input type="time" name="hora_inicio[]" class="form-control form-control-sm" required style="width:130px;">
            </div>
            <span class="text-muted mt-3">→</span>
            <div>
                <label class="form-label small mb-1">Hora fin</label>
                <input type="time" name="hora_fin[]" class="form-control form-control-sm" required style="width:130px;">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="removeFranja(this)" title="Eliminar franja">
            <i class="fa-solid fa-trash"></i>
        </button>`;
    container.appendChild(div);
    renumerarFranjas();
}
function removeFranja(btn) {
    const rows = document.querySelectorAll('.franja-row');
    if (rows.length <= 1) return;
    btn.closest('.franja-row').remove();
    renumerarFranjas();
}
function renumerarFranjas() {
    document.querySelectorAll('.franja-row').forEach((row, i) => {
        row.querySelector('span').textContent = 'Franja ' + (i + 1);
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

