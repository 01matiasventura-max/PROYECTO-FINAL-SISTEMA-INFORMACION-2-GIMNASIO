<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo    = getPDO();
$errors = [];

// Recibir pago_id por GET (desde botón en pagos/index.php)
$pagoId = (int)($_GET['pago_id'] ?? $_POST['pago_id'] ?? 0);
if ($pagoId <= 0) { header('Location: ../pagos/index.php'); exit; }

// Verificar que el pago existe, está pagado y no tiene factura aún
$pago = $pdo->prepare("
    SELECT p.*, s.nombre, s.apellido, s.numero_socio
    FROM pagos p
    JOIN socios s ON s.id = p.socio_id
    WHERE p.id = ? AND p.estado = 'pagado'
");
$pago->execute([$pagoId]);
$pago = $pago->fetch();

if (!$pago) {
    flashError('El pago no existe o no está en estado pagado.');
    header('Location: ../pagos/index.php'); exit;
}

// Verificar que no tenga factura ya
$existe = $pdo->prepare("SELECT id, numero_factura FROM facturas WHERE pago_id = ?");
$existe->execute([$pagoId]);
$facturaExistente = $existe->fetch();
if ($facturaExistente) {
    header('Location: ver.php?id=' . $facturaExistente['id']); exit;
}

// Generar número de factura: FAC-YYYY-NNNN
$anio    = date('Y');
$ultimo  = $pdo->query("SELECT COUNT(*) FROM facturas WHERE YEAR(fecha_emision) = $anio")->fetchColumn();
$numFac  = 'FAC-' . $anio . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');

    $impuestoPct = (float)($_POST['impuesto_pct'] ?? 0);
    $subtotal    = $pago['monto'];
    $total       = round($subtotal * (1 + $impuestoPct / 100), 2);

    if ($impuestoPct < 0 || $impuestoPct > 100) $errors[] = 'El porcentaje de IVA debe estar entre 0 y 100.';

    if (empty($errors)) {
        // Doble-chequeo de unicidad antes de insertar
        $check = $pdo->prepare("SELECT id FROM facturas WHERE pago_id = ?");
        $check->execute([$pagoId]);
        if ($check->fetch()) {
            flashError('Este pago ya tiene una factura generada.');
            header('Location: ../pagos/index.php'); exit;
        }

        $pdo->prepare("INSERT INTO facturas (pago_id, numero_factura, subtotal, impuesto_pct, total) VALUES (?,?,?,?,?)")
            ->execute([$pagoId, $numFac, $subtotal, $impuestoPct, $total]);

        $nuevaId = $pdo->lastInsertId();
        flashSuccess("Factura $numFac generada correctamente.");
        header('Location: ver.php?id=' . $nuevaId); exit;
    }
}

$pageTitle  = 'Generar Factura';
$breadcrumb = [
    ['label' => 'Facturas', 'url' => BASE_URL . '/facturas/index.php'],
    ['label' => 'Generar', 'active' => true],
];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i>Generar Factura</h4>
    <a href="../pagos/index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver a Pagos</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<!-- Resumen del pago -->
<div class="table-card p-4 mb-4">
    <div class="section-title text-muted small fw-bold text-uppercase mb-3" style="letter-spacing:.08em;">
        <i class="fa-solid fa-receipt me-1"></i>Pago a facturar
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="text-muted small">Socio</div>
            <div class="fw-bold"><?= e($pago['nombre'] . ' ' . $pago['apellido']) ?></div>
            <div class="text-muted small"><?= e($pago['numero_socio']) ?></div>
        </div>
        <div class="col-md-4">
            <div class="text-muted small">Concepto</div>
            <div class="fw-semibold"><?= e($pago['concepto']) ?></div>
        </div>
        <div class="col-md-2">
            <div class="text-muted small">Monto pagado</div>
            <div class="fw-bold text-success fs-5">$<?= number_format($pago['monto'], 2) ?></div>
        </div>
        <div class="col-md-2">
            <div class="text-muted small">Fecha de pago</div>
            <div class="fw-semibold"><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></div>
        </div>
    </div>
</div>

<!-- Formulario -->
<div class="table-card p-4">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="pago_id" value="<?= $pagoId ?>">

        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Nº de factura</label>
                <input type="text" class="form-control" value="<?= e($numFac) ?>" disabled>
                <div class="form-text">Generado automáticamente.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Subtotal</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="text" class="form-control" value="<?= number_format($pago['monto'], 2) ?>" disabled>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">IVA / Impuesto (%)</label>
                <div class="input-group">
                    <input type="number" name="impuesto_pct" id="impuesto_pct" class="form-control"
                           min="0" max="100" step="0.01"
                           value="<?= e($_POST['impuesto_pct'] ?? '0') ?>"
                           oninput="calcTotal(this.value)">
                    <span class="input-group-text">%</span>
                </div>
                <div class="form-text">0% si no aplica impuesto.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Total a facturar</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="text" id="total_preview" class="form-control fw-bold text-success" value="<?= number_format($pago['monto'], 2) ?>" disabled>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-warning fw-bold">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i>Generar Factura
            </button>
            <a href="../pagos/index.php" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
const subtotal = <?= (float)$pago['monto'] ?>;
function calcTotal(pct) {
    const total = subtotal * (1 + parseFloat(pct || 0) / 100);
    document.getElementById('total_preview').value = total.toFixed(2);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
