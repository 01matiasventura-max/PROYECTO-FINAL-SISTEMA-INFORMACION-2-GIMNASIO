<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo    = getPDO();
$errors = [];

$socioPreId = (int)($_GET['socio_id'] ?? 0);
$socios = $pdo->query("SELECT id, numero_socio, nombre, apellido FROM socios WHERE activo = 1 ORDER BY nombre")->fetchAll();
$planes = $pdo->query("SELECT * FROM planes WHERE activo = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $socioId     = (int)($_POST['socio_id'] ?? 0);
    $planId      = (int)($_POST['plan_id'] ?? 0);
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $motivo      = trim($_POST['motivo_cambio'] ?? '') ?: null;

    if ($socioId <= 0) $errors[] = 'Selecciona un socio.';
    if ($planId <= 0)  $errors[] = 'Selecciona un plan.';
    if ($fechaInicio === '') $errors[] = 'La fecha de inicio es requerida.';

    if (empty($errors)) {
        $plan = $pdo->prepare("SELECT duracion_dias FROM planes WHERE id = ?");
        $plan->execute([$planId]);
        $plan = $plan->fetch();
        $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' + ' . $plan['duracion_dias'] . ' days'));
        $pdo->prepare("INSERT INTO membresias (socio_id, plan_id, fecha_inicio, fecha_fin, created_by) VALUES (?,?,?,?,?)")
            ->execute([$socioId, $planId, $fechaInicio, $fechaFin, currentUserId()]);
        flashSuccess('Membresía registrada correctamente.');
        header('Location: index.php'); exit;
    }
}

$pageTitle = 'Nueva Membresía';
$breadcrumb = [['label' => 'Membresías', 'url' => BASE_URL . '/membresias/index.php'], ['label' => 'Nueva', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-id-card text-primary me-2"></i>Nueva Membresía</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Socio <span class="text-danger">*</span></label>
            <select name="socio_id" class="form-select" required>
                <option value="">— Seleccionar socio —</option>
                <?php foreach ($socios as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ((int)($_POST['socio_id'] ?? $socioPreId) === (int)$s['id']) ? 'selected' : '' ?>>
                    <?= e($s['nombre'] . ' ' . $s['apellido'] . ' (' . $s['numero_socio'] . ')') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Plan <span class="text-danger">*</span></label>
            <select name="plan_id" class="form-select" required>
                <option value="">— Seleccionar plan —</option>
                <?php foreach ($planes as $p): ?>
                <option value="<?= $p['id'] ?>" data-precio="<?= number_format($p['precio'], 2) ?>" data-dias="<?= (int)$p['duracion_dias'] ?>" <?= ((int)($_POST['plan_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                    <?= e($p['nombre']) ?> — $<?= number_format($p['precio'], 2) ?> (<?= $p['duracion_dias'] ?> días)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Fecha de inicio <span class="text-danger">*</span></label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= e($_POST['fecha_inicio'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div class="col-md-4" id="preview-box" style="display:none;">
            <label class="form-label fw-semibold">Vista previa</label>
            <div class="p-3 rounded border bg-light">
                <div class="mb-1"><i class="fa-solid fa-dollar-sign text-success me-1"></i>Precio: <strong id="prev-precio">—</strong></div>
                <div><i class="fa-solid fa-calendar-xmark text-danger me-1"></i>Vence: <strong id="prev-vence">—</strong></div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Registrar Membresía</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<script>
(function(){
    const selPlan   = document.querySelector('select[name="plan_id"]');
    const iniFecha  = document.getElementById('fecha_inicio');
    const box       = document.getElementById('preview-box');
    const prevPrecio = document.getElementById('prev-precio');
    const prevVence  = document.getElementById('prev-vence');

    function actualizarPreview(){
        const opt  = selPlan.options[selPlan.selectedIndex];
        const dias = parseInt(opt.dataset.dias);
        const precio = opt.dataset.precio;
        const inicio = iniFecha.value;
        if(!dias || !inicio){ box.style.display='none'; return; }
        const fechaFin = new Date(inicio);
        fechaFin.setDate(fechaFin.getDate() + dias);
        const dd = String(fechaFin.getDate()).padStart(2,'0');
        const mm = String(fechaFin.getMonth()+1).padStart(2,'0');
        const yy = fechaFin.getFullYear();
        prevPrecio.textContent = '$' + precio;
        prevVence.textContent  = dd + '/' + mm + '/' + yy;
        box.style.display = 'block';
    }

    selPlan.addEventListener('change', actualizarPreview);
    iniFecha.addEventListener('change', actualizarPreview);
    actualizarPreview();
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
