<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pdo    = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Token inválido.');
    }

    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email    = trim($_POST['email'] ?? '') ?: null;
    $telefono = trim($_POST['telefono'] ?? '') ?: null;
    $fechaNac = $_POST['fecha_nacimiento'] ?? '' ?: null;
    $direccion= trim($_POST['direccion'] ?? '') ?: null;
    $notas    = trim($_POST['notas'] ?? '') ?: null;

    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if ($apellido === '') $errors[] = 'El apellido es requerido.';
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';

    if (empty($errors)) {
        // Generar número de socio
        $last = $pdo->query("SELECT numero_socio FROM socios ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = 1;
        if ($last) {
            preg_match('/(\d+)$/', $last, $m);
            $num = (int)($m[1] ?? 0) + 1;
        }
        $numSocio = 'SOC-' . str_pad($num, 5, '0', STR_PAD_LEFT);

        try {
            $stmt = $pdo->prepare("INSERT INTO socios (numero_socio, nombre, apellido, email, telefono, fecha_nacimiento, direccion, notas, created_by) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$numSocio, $nombre, $apellido, $email, $telefono, $fechaNac, $direccion, $notas, currentUserId()]);
            flashSuccess("Socio $nombre $apellido creado correctamente.");
            header('Location: ' . BASE_URL . '/socios/index.php');
            exit;
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $errors[] = 'El email "' . htmlspecialchars($email) . '" ya está registrado en otro socio.';
            } else {
                throw $ex;
            }
        }
    }
}

$pageTitle = 'Nuevo Socio';
$breadcrumb = [['label' => 'Socios', 'url' => BASE_URL . '/socios/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-user-plus text-primary me-2"></i>Nuevo Socio</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
            <input type="text" name="apellido" class="form-control" value="<?= e($_POST['apellido'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Correo electrónico</label>
            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Teléfono</label>
            <input type="text" name="telefono" class="form-control" value="<?= e($_POST['telefono'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento" class="form-control" value="<?= e($_POST['fecha_nacimiento'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Dirección</label>
            <input type="text" name="direccion" class="form-control" value="<?= e($_POST['direccion'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Notas</label>
            <textarea name="notas" class="form-control" rows="3"><?= e($_POST['notas'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar Socio</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
