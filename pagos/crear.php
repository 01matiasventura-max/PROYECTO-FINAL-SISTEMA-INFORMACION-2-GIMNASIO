<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo    = getPDO();
$errors = [];
$socioPreId = (int)($_GET['socio_id'] ?? 0);
// Socios sin pago 'pagado' registrado hoy
$socios = $pdo->query("
    SELECT id, numero_socio, nombre, apellido
    FROM socios
    WHERE activo = 1
      AND id NOT IN (
          SELECT DISTINCT socio_id FROM pagos
          WHERE estado = 'pagado'
            AND DATE(fecha_pago) = CURDATE()
      )
    ORDER BY nombre
")->fetchAll();

// Mini lista: socios que ya pagaron hoy
$pagadosHoy = $pdo->query("
    SELECT p.id AS pago_id, p.concepto, p.monto, p.metodo_pago, p.fecha_pago,
           s.nombre, s.apellido, s.numero_socio
    FROM pagos p
    JOIN socios s ON s.id = p.socio_id
    WHERE p.estado = 'pagado' AND DATE(p.fecha_pago) = CURDATE()
    ORDER BY p.fecha_pago DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $socioId   = (int)($_POST['socio_id'] ?? 0);
    $memId     = ($_POST['membresia_id'] ?? '') !== '' ? (int)$_POST['membresia_id'] : null;
    $concepto  = trim($_POST['concepto'] ?? '');
    $monto     = (float)($_POST['monto'] ?? 0);
    $metodo    = $_POST['metodo_pago'] ?? '';
    $estado    = $_POST['estado'] ?? 'pagado';
    $obs       = trim($_POST['observaciones'] ?? '') ?: null;
    $metodos   = ['efectivo','tarjeta_credito','tarjeta_debito','transferencia','otro'];
    if ($socioId <= 0) $errors[] = 'Selecciona un socio.';
    if ($concepto === '') $errors[] = 'El concepto es requerido.';
    if ($monto <= 0) $errors[] = 'El monto debe ser mayor a 0.';
    if (!in_array($metodo, $metodos, true)) $errors[] = 'Método de pago inválido.';
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO pagos (socio_id, membresia_id, concepto, monto, metodo_pago, estado, cobrado_por, observaciones) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$socioId, $memId, $concepto, $monto, $metodo, $estado, currentUserId(), $obs]);
        flashSuccess('Pago registrado correctamente.');
        header('Location: index.php'); exit;
    }
}

// Si hay socio preseleccionado (GET o reenvío POST), cargar sus membresías
$membresiasSelect = [];
$selectedSocio = (int)($_POST['socio_id'] ?? $socioPreId);
if ($selectedSocio > 0) {
    $stmtM = $pdo->prepare("SELECT m.id, p.nombre, m.fecha_fin FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.socio_id = ? AND m.estado = 'activa'");
    $stmtM->execute([$selectedSocio]);
    $membresiasSelect = $stmtM->fetchAll();
}
$memIdPrev = (int)($_POST['membresia_id'] ?? 0);

$pageTitle = 'Registrar Pago';
$breadcrumb = [['label' => 'Pagos', 'url' => BASE_URL . '/pagos/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-success me-2"></i>Registrar Pago</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Socio <span class="text-danger">*</span></label>
            <select name="socio_id" id="socio_id" class="form-select" required>
                <option value="">— Seleccionar socio —</option>
                <?php foreach ($socios as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($selectedSocio === (int)$s['id']) ? 'selected' : '' ?>>
                    <?= e($s['nombre'] . ' ' . $s['apellido'] . ' (' . $s['numero_socio'] . ')') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Membresía asociada
                <span id="mem-loading" class="text-muted ms-2" style="font-size:.8rem;display:none;">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>Cargando...
                </span>
            </label>
            <select name="membresia_id" id="membresia_id" class="form-select">
                <option value="">— Sin membresía —</option>
                <?php foreach ($membresiasSelect as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($memIdPrev === (int)$m['id']) ? 'selected' : '' ?>>
                    <?= e($m['nombre']) ?> (vence <?= date('d/m/Y', strtotime($m['fecha_fin'])) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <div id="mem-msg" class="form-text"></div>
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Concepto <span class="text-danger">*</span></label>
            <input type="text" name="concepto" id="concepto" class="form-control" value="<?= e($_POST['concepto'] ?? '') ?>" placeholder="Ej: Pago mensualidad enero" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Monto ($) <span class="text-danger">*</span></label>
            <input type="number" name="monto" id="monto" class="form-control" min="0.01" step="0.01" value="<?= e($_POST['monto'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Método de pago <span class="text-danger">*</span></label>
            <select name="metodo_pago" class="form-select" required>
                <option value="">— Seleccionar —</option>
                <?php foreach (['efectivo'=>'Efectivo','tarjeta_credito'=>'Tarjeta crédito','tarjeta_debito'=>'Tarjeta débito','transferencia'=>'Transferencia','otro'=>'Otro'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= (($_POST['metodo_pago'] ?? '') === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Estado</label>
            <select name="estado" class="form-select">
                <option value="pagado" <?= (($_POST['estado'] ?? 'pagado') === 'pagado') ? 'selected' : '' ?>>Pagado</option>
                <option value="pendiente" <?= (($_POST['estado'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"><?= e($_POST['observaciones'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-save me-1"></i>Registrar Pago</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>

<?php if (!empty($pagadosHoy)): ?>
<div class="table-card p-3 mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="fw-bold mb-0">
            <i class="fa-solid fa-circle-check text-success me-2"></i>Pagos registrados hoy
        </h6>
        <span class="badge bg-success"><?= count($pagadosHoy) ?> pago(s)</span>
    </div>
    <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead style="font-size:.82rem;">
            <tr><th>Socio</th><th>Concepto</th><th>Método</th><th class="text-end">Monto</th><th>Hora</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pagadosHoy as $ph): ?>
        <tr>
            <td>
                <span class="fw-semibold"><?= e($ph['nombre'] . ' ' . $ph['apellido']) ?></span><br>
                <small class="text-muted"><?= e($ph['numero_socio']) ?></small>
            </td>
            <td style="font-size:.85rem;"><?= e($ph['concepto']) ?></td>
            <td><span class="badge bg-light text-dark" style="font-size:.78rem;"><?= e(str_replace('_',' ',$ph['metodo_pago'])) ?></span></td>
            <td class="text-end fw-bold text-success">$<?= number_format($ph['monto'],2) ?></td>
            <td style="font-size:.82rem;color:#6c757d;"><?= date('H:i', strtotime($ph['fecha_pago'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f8f9fa;">
                <td colspan="3" class="fw-bold text-end pe-3" style="font-size:.85rem;">Total cobrado hoy:</td>
                <td class="text-end fw-bold text-success">$<?= number_format(array_sum(array_column($pagadosHoy,'monto')),2) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    const socioSel  = document.getElementById('socio_id');
    const memSel    = document.getElementById('membresia_id');
    const loading   = document.getElementById('mem-loading');
    const msg       = document.getElementById('mem-msg');
    const concepto  = document.getElementById('concepto');
    const monto     = document.getElementById('monto');

    // Mapa para guardar precio por membresía id
    let memData = {};

    socioSel.addEventListener('change', function () {
        const socioId = this.value;
        memSel.innerHTML = '<option value="">— Sin membresía —</option>';
        msg.textContent = '';
        memData = {};
        if (!socioId) return;

        loading.style.display = 'inline';
        fetch('api_membresias.php?socio_id=' + encodeURIComponent(socioId))
            .then(r => r.json())
            .then(data => {
                loading.style.display = 'none';
                if (data.error) { msg.textContent = data.error; return; }
                if (data.length === 0) {
                    msg.innerHTML = '<span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>El socio no tiene membresías activas.</span>';
                    return;
                }
                data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.nombre + ' (vence ' + m.fecha_fin + ')';
                    memSel.appendChild(opt);
                    memData[m.id] = { nombre: m.nombre, precio: m.precio };
                });
                msg.innerHTML = '<span class="text-success"><i class="fa-solid fa-check me-1"></i>' + data.length + ' membresía(s) activa(s) encontrada(s).</span>';
            })
            .catch(() => {
                loading.style.display = 'none';
                msg.textContent = 'Error al cargar membresías.';
            });
    });

    // Autocompletar concepto y monto al elegir membresía
    memSel.addEventListener('change', function () {
        const id = this.value;
        if (id && memData[id]) {
            if (!concepto.value.trim()) {
                concepto.value = 'Pago ' + memData[id].nombre;
            }
            if (!monto.value) {
                monto.value = parseFloat(memData[id].precio).toFixed(2);
            }
        }
    });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
