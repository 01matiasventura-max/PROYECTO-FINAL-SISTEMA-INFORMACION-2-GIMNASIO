<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo         = getPDO();
$instructores = $pdo->query("SELECT id, nombre, apellido FROM empleados WHERE es_instructor = 1 AND activo = 1 ORDER BY nombre")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $nombre      = trim($_POST['nombre'] ?? '');
    $desc        = trim($_POST['descripcion'] ?? '') ?: null;
    $instructorId= (int)($_POST['instructor_id'] ?? 0);
    $duracion    = (int)($_POST['duracion_min'] ?? 0);
    $capacidad   = (int)($_POST['capacidad_max'] ?? 0);
    $activo      = isset($_POST['activo']) ? 1 : 0;
    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if ($instructorId <= 0) $errors[] = 'Selecciona un instructor.';
    if ($duracion <= 0) $errors[] = 'La duración debe ser mayor a 0.';
    if ($capacidad <= 0) $errors[] = 'La capacidad debe ser mayor a 0.';
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO clases (nombre, descripcion, instructor_id, duracion_min, capacidad_max, activo) VALUES (?,?,?,?,?,?)")
            ->execute([$nombre, $desc, $instructorId, $duracion, $capacidad, $activo]);
        flashSuccess('Clase creada correctamente.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Nueva Clase';
$breadcrumb = [['label' => 'Clases', 'url' => BASE_URL . '/clases/index.php'], ['label' => 'Nueva', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-primary me-2"></i>Nueva Clase</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if (empty($instructores)): ?><div class="alert alert-warning">No hay instructores registrados. <a href="<?= BASE_URL ?>/empleados/crear.php">Crear empleado instructor</a>.</div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Instructor <span class="text-danger">*</span></label>
            <select name="instructor_id" class="form-select" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($instructores as $i): ?>
                <option value="<?= $i['id'] ?>" <?= ((int)($_POST['instructor_id'] ?? 0) === (int)$i['id']) ? 'selected' : '' ?>><?= e($i['nombre'] . ' ' . $i['apellido']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Duración (min) <span class="text-danger">*</span></label>
            <input type="number" name="duracion_min" class="form-control" min="1" value="<?= e($_POST['duracion_min'] ?? '60') ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Capacidad máxima <span class="text-danger">*</span></label>
            <input type="number" name="capacidad_max" class="form-control" min="1" value="<?= e($_POST['capacidad_max'] ?? '20') ?>" required></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= e($_POST['descripcion'] ?? '') ?></textarea></div>
        <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Clase activa</label>
        </div></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar Clase</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
