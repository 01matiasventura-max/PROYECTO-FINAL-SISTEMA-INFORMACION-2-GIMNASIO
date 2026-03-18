<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo    = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $nombre       = trim($_POST['nombre'] ?? '');
    $apellido     = trim($_POST['apellido'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '') ?: null;
    $cargo        = trim($_POST['cargo'] ?? '');
    $salario      = $_POST['salario'] !== '' ? (float)$_POST['salario'] : null;
    $fechaCont    = $_POST['fecha_contratacion'] ?? '';
    $esInstructor = isset($_POST['es_instructor']) ? 1 : 0;
    $especialidad = trim($_POST['especialidad'] ?? '') ?: null;
    $bio          = trim($_POST['bio'] ?? '') ?: null;
    $activo       = 1;
    if ($nombre === '') $errors[] = 'Nombre requerido.';
    if ($apellido === '') $errors[] = 'Apellido requerido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if ($cargo === '') $errors[] = 'Cargo requerido.';
    if ($fechaCont === '') $errors[] = 'Fecha de contratación requerida.';
    if (empty($errors)) {
        $pdo->prepare("INSERT INTO empleados (nombre,apellido,email,telefono,cargo,salario,fecha_contratacion,es_instructor,especialidad,bio,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$nombre,$apellido,$email,$telefono,$cargo,$salario,$fechaCont,$esInstructor,$especialidad,$bio,$activo]);
        flashSuccess('Empleado creado.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Nuevo Empleado';
$breadcrumb = [['label' => 'Empleados', 'url' => BASE_URL . '/empleados/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-user-plus text-primary me-2"></i>Nuevo Empleado</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
            <input type="text" name="apellido" class="form-control" value="<?= e($_POST['apellido'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Teléfono</label>
            <input type="text" name="telefono" class="form-control" value="<?= e($_POST['telefono'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Cargo <span class="text-danger">*</span></label>
            <input type="text" name="cargo" class="form-control" value="<?= e($_POST['cargo'] ?? '') ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Salario</label>
            <input type="number" name="salario" class="form-control" min="0" step="0.01" value="<?= e($_POST['salario'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Fecha contratación <span class="text-danger">*</span></label>
            <input type="date" name="fecha_contratacion" class="form-control" value="<?= e($_POST['fecha_contratacion'] ?? date('Y-m-d')) ?>" required></div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="es_instructor" id="esInstructor"
                       <?= isset($_POST['es_instructor']) ? 'checked' : '' ?> onchange="document.getElementById('instructorFields').classList.toggle('d-none', !this.checked)">
                <label class="form-check-label" for="esInstructor">Es instructor</label>
            </div>
        </div>
        <div class="col-12 <?= !isset($_POST['es_instructor']) ? 'd-none' : '' ?>" id="instructorFields">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold">Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" value="<?= e($_POST['especialidad'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Bio pública</label>
                    <textarea name="bio" class="form-control" rows="2"><?= e($_POST['bio'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
