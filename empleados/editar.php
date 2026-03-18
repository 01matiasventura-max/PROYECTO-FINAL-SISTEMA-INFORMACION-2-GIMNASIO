<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo  = getPDO();
$id   = (int)($_GET['id'] ?? 0);
$emp  = $pdo->prepare("SELECT * FROM empleados WHERE id = ?");
$emp->execute([$id]); $emp = $emp->fetch();
if (!$emp) { flashError('Empleado no encontrado.'); header('Location: index.php'); exit; }
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
    $activo       = isset($_POST['activo']) ? 1 : 0;
    if ($nombre === '') $errors[] = 'Nombre requerido.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (empty($errors)) {
        $pdo->prepare("UPDATE empleados SET nombre=?,apellido=?,email=?,telefono=?,cargo=?,salario=?,fecha_contratacion=?,es_instructor=?,especialidad=?,bio=?,activo=? WHERE id=?")
            ->execute([$nombre,$apellido,$email,$telefono,$cargo,$salario,$fechaCont,$esInstructor,$especialidad,$bio,$activo,$id]);
        flashSuccess('Empleado actualizado.');
        header('Location: index.php'); exit;
    }
}
$pageTitle = 'Editar Empleado';
$breadcrumb = [['label' => 'Empleados', 'url' => BASE_URL . '/empleados/index.php'], ['label' => 'Editar', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen text-warning me-2"></i>Editar Empleado</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? $emp['nombre']) ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Apellido</label>
            <input type="text" name="apellido" class="form-control" value="<?= e($_POST['apellido'] ?? $emp['apellido']) ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $emp['email']) ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Teléfono</label>
            <input type="text" name="telefono" class="form-control" value="<?= e($_POST['telefono'] ?? $emp['telefono']) ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Cargo</label>
            <input type="text" name="cargo" class="form-control" value="<?= e($_POST['cargo'] ?? $emp['cargo']) ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Salario</label>
            <input type="number" name="salario" class="form-control" step="0.01" value="<?= e($_POST['salario'] ?? $emp['salario']) ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Fecha contratación</label>
            <input type="date" name="fecha_contratacion" class="form-control" value="<?= e($_POST['fecha_contratacion'] ?? $emp['fecha_contratacion']) ?>"></div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="es_instructor" id="esInstructor"
                       <?= (isset($_POST['es_instructor']) ? (bool)$_POST['es_instructor'] : $emp['es_instructor']) ? 'checked' : '' ?>
                       onchange="document.getElementById('instructorFields').classList.toggle('d-none', !this.checked)">
                <label class="form-check-label" for="esInstructor">Es instructor</label>
            </div>
        </div>
        <?php $showInstructor = isset($_POST['es_instructor']) ? (bool)$_POST['es_instructor'] : $emp['es_instructor']; ?>
        <div class="col-12 <?= !$showInstructor ? 'd-none' : '' ?>" id="instructorFields">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label fw-semibold">Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" value="<?= e($_POST['especialidad'] ?? $emp['especialidad']) ?>"></div>
                <div class="col-md-6"><label class="form-label fw-semibold">Bio pública</label>
                    <textarea name="bio" class="form-control" rows="2"><?= e($_POST['bio'] ?? $emp['bio']) ?></textarea></div>
            </div>
        </div>
        <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo"
                   <?= (isset($_POST['activo']) ? (bool)$_POST['activo'] : $emp['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Empleado activo</label>
        </div></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning text-white"><i class="fa-solid fa-save me-1"></i>Actualizar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
