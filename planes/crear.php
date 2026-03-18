<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $nombre   = trim($_POST['nombre'] ?? '');
    $desc     = trim($_POST['descripcion'] ?? '') ?: null;
    $duracion = (int)($_POST['duracion_dias'] ?? 0);
    $precio   = (float)($_POST['precio'] ?? 0);
    $activo   = isset($_POST['activo']) ? 1 : 0;
    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if ($duracion <= 0) $errors[] = 'La duración debe ser mayor a 0.';
    if ($precio <= 0) $errors[] = 'El precio debe ser mayor a 0.';
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO planes (nombre, descripcion, duracion_dias, precio, activo) VALUES (?,?,?,?,?)")
            ->execute([$nombre, $desc, $duracion, $precio, $activo]);
        flashSuccess('Plan creado correctamente.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Nuevo Plan';
$breadcrumb = [['label' => 'Planes', 'url' => BASE_URL . '/planes/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-primary me-2"></i>Nuevo Plan</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Duración (días) <span class="text-danger">*</span></label>
            <input type="number" name="duracion_dias" class="form-control" min="1" value="<?= e($_POST['duracion_dias'] ?? '30') ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Precio ($) <span class="text-danger">*</span></label>
            <input type="number" name="precio" class="form-control" min="0.01" step="0.01" value="<?= e($_POST['precio'] ?? '') ?>" required></div>
        <div class="col-12"><label class="form-label fw-semibold">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= e($_POST['descripcion'] ?? '') ?></textarea></div>
        <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo" <?= (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Plan activo</label>
        </div></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar Plan</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
