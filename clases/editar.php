<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo   = getPDO();
$id    = (int)($_GET['id'] ?? 0);
$clase = $pdo->prepare("SELECT * FROM clases WHERE id = ?");
$clase->execute([$id]); $clase = $clase->fetch();
if (!$clase) { flashError('Clase no encontrada.'); header('Location: index.php'); exit; }
$instructores = $pdo->query("SELECT id, nombre, apellido FROM empleados WHERE es_instructor = 1 AND activo = 1 ORDER BY nombre")->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $nombre       = trim($_POST['nombre'] ?? '');
    $desc         = trim($_POST['descripcion'] ?? '') ?: null;
    $instructorId = (int)($_POST['instructor_id'] ?? 0);
    $duracion     = (int)($_POST['duracion_min'] ?? 0);
    $capacidad    = (int)($_POST['capacidad_max'] ?? 0);
    $activo       = isset($_POST['activo']) ? 1 : 0;
    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if ($instructorId <= 0) $errors[] = 'Selecciona un instructor.';
    if (empty($errors)) {
        $pdo->prepare("UPDATE clases SET nombre=?,descripcion=?,instructor_id=?,duracion_min=?,capacidad_max=?,activo=? WHERE id=?")
            ->execute([$nombre, $desc, $instructorId, $duracion, $capacidad, $activo, $id]);
        flashSuccess('Clase actualizada.'); header('Location: index.php'); exit;
    }
}
$pageTitle = 'Editar Clase';
$breadcrumb = [['label' => 'Clases', 'url' => BASE_URL . '/clases/index.php'], ['label' => 'Editar', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen text-warning me-2"></i>Editar Clase</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? $clase['nombre']) ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Instructor</label>
            <select name="instructor_id" class="form-select">
                <?php foreach ($instructores as $i): ?>
                <option value="<?= $i['id'] ?>" <?= ((int)($_POST['instructor_id'] ?? $clase['instructor_id']) === (int)$i['id']) ? 'selected' : '' ?>><?= e($i['nombre'] . ' ' . $i['apellido']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Duración (min)</label>
            <input type="number" name="duracion_min" class="form-control" min="1" value="<?= e($_POST['duracion_min'] ?? $clase['duracion_min']) ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Capacidad máxima</label>
            <input type="number" name="capacidad_max" class="form-control" min="1" value="<?= e($_POST['capacidad_max'] ?? $clase['capacidad_max']) ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= e($_POST['descripcion'] ?? $clase['descripcion']) ?></textarea></div>
        <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo"
                   <?= (isset($_POST['activo']) ? (bool)$_POST['activo'] : $clase['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Clase activa</label>
        </div></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning text-white"><i class="fa-solid fa-save me-1"></i>Actualizar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
