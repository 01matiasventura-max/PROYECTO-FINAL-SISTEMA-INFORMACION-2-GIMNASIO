<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pdo    = getPDO();
$id     = (int)($_GET['id'] ?? 0);
$socio  = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
$socio->execute([$id]);
$socio  = $socio->fetch();
if (!$socio) { flashError('Socio no encontrado.'); header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');

    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '') ?: null;
    $telefono = trim($_POST['telefono'] ?? '') ?: null;
    $fechaNac = $_POST['fecha_nacimiento'] ?? '' ?: null;
    $direccion= trim($_POST['direccion'] ?? '') ?: null;
    $notas    = trim($_POST['notas'] ?? '') ?: null;
    $activo   = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if ($apellido === '') $errors[] = 'El apellido es requerido.';
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE socios SET nombre=?,apellido=?,email=?,telefono=?,fecha_nacimiento=?,direccion=?,notas=?,activo=? WHERE id=?")
            ->execute([$nombre, $apellido, $email, $telefono, $fechaNac, $direccion, $notas, $activo, $id]);
        flashSuccess('Socio actualizado correctamente.');
        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Editar Socio';
$breadcrumb = [['label' => 'Socios', 'url' => BASE_URL . '/socios/index.php'], ['label' => 'Editar', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen text-warning me-2"></i>Editar Socio</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? $socio['nombre']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
            <input type="text" name="apellido" class="form-control" value="<?= e($_POST['apellido'] ?? $socio['apellido']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Correo electrónico</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $socio['email']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Teléfono</label>
            <input type="text" name="telefono" class="form-control" value="<?= e($_POST['telefono'] ?? $socio['telefono']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= e($_POST['fecha_nacimiento'] ?? $socio['fecha_nacimiento']) ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Dirección</label>
            <input type="text" name="direccion" class="form-control" value="<?= e($_POST['direccion'] ?? $socio['direccion']) ?>">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Notas</label>
            <textarea name="notas" class="form-control" rows="3"><?= e($_POST['notas'] ?? $socio['notas']) ?></textarea>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                       <?= (isset($_POST['activo']) ? (bool)$_POST['activo'] : $socio['activo']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="activo">Socio activo</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning text-white"><i class="fa-solid fa-save me-1"></i>Actualizar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
