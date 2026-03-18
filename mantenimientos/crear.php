<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo      = getPDO();
$equipos  = $pdo->query("SELECT id, nombre FROM equipos WHERE estado != 'baja' ORDER BY nombre")->fetchAll();
$errors   = [];
$equipoPreId = (int)($_GET['equipo_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $equipoId   = (int)($_POST['equipo_id'] ?? 0);
    $tipo       = $_POST['tipo'] ?? '';
    $fechaInicio= $_POST['fecha_inicio'] ?? date('Y-m-d');
    $descripcion= trim($_POST['descripcion'] ?? '');
    $costo      = !empty($_POST['costo']) ? (float)$_POST['costo'] : 0;
    $tecnico    = trim($_POST['tecnico'] ?? '') ?: null;
    if ($equipoId <= 0) $errors[] = 'Selecciona un equipo.';
    if (!in_array($tipo, ['preventivo', 'correctivo'], true)) $errors[] = 'Tipo inválido.';
    if ($descripcion === '') $errors[] = 'La descripción es requerida.';
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO mantenimientos (equipo_id, tipo, fecha_inicio, descripcion, costo, tecnico, registrado_por) VALUES (?,?,?,?,?,?,?)")
            ->execute([$equipoId, $tipo, $fechaInicio, $descripcion, $costo, $tecnico, currentUserId()]);
        flashSuccess('Mantenimiento registrado.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Nuevo Mantenimiento';
$breadcrumb = [['label' => 'Mantenimientos', 'url' => BASE_URL . '/mantenimientos/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-primary me-2"></i>Nuevo Mantenimiento</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Equipo <span class="text-danger">*</span></label>
            <select name="equipo_id" class="form-select" required>
                <option value="">— Seleccionar equipo —</option>
                <?php foreach ($equipos as $eq): ?>
                <option value="<?= $eq['id'] ?>" <?= ((int)($_POST['equipo_id'] ?? $equipoPreId) === (int)$eq['id']) ? 'selected' : '' ?>><?= e($eq['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
            <select name="tipo" class="form-select" required>
                <option value="">— Tipo —</option>
                <option value="preventivo" <?= (($_POST['tipo'] ?? '') === 'preventivo') ? 'selected' : '' ?>>Preventivo</option>
                <option value="correctivo" <?= (($_POST['tipo'] ?? '') === 'correctivo') ? 'selected' : '' ?>>Correctivo</option>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Fecha inicio <span class="text-danger">*</span></label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?= e($_POST['fecha_inicio'] ?? date('Y-m-d')) ?>" required></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
            <textarea name="descripcion" class="form-control" rows="3" required><?= e($_POST['descripcion'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Costo estimado ($)</label>
            <input type="number" name="costo" class="form-control" step="0.01" min="0" value="<?= e($_POST['costo'] ?? '0') ?>"></div>
        <div class="col-md-8"><label class="form-label fw-semibold">Técnico responsable</label>
            <input type="text" name="tecnico" class="form-control" value="<?= e($_POST['tecnico'] ?? '') ?>"></div>
    </div>
    <div class="alert alert-info mt-3 mb-0">
        <i class="fa-solid fa-info-circle me-1"></i>
        Si el tipo es <strong>Correctivo</strong>, el equipo cambiará automáticamente a estado "Mantenimiento".
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Registrar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
