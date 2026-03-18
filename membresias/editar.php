<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
$mem = $pdo->prepare("SELECT m.*, s.nombre, s.apellido FROM membresias m JOIN socios s ON s.id = m.socio_id WHERE m.id = ?");
$mem->execute([$id]); $mem = $mem->fetch();
if (!$mem) { flashError('Membresía no encontrada.'); header('Location: index.php'); exit; }
$planes = $pdo->query("SELECT * FROM planes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $estado  = $_POST['estado'] ?? '';
    $motivo  = trim($_POST['motivo_cambio'] ?? '') ?: null;
    $estados = ['activa', 'vencida', 'cancelada', 'suspendida'];
    if (!in_array($estado, $estados, true)) $errors[] = 'Estado inválido.';
    if (empty($errors)) {
        $pdo->prepare("UPDATE membresias SET estado=?, motivo_cambio=? WHERE id=?")->execute([$estado, $motivo, $id]);
        flashSuccess('Membresía actualizada.'); header('Location: index.php'); exit;
    }
}
$pageTitle = 'Editar Membresía';
$breadcrumb = [['label' => 'Membresías', 'url' => BASE_URL . '/membresias/index.php'], ['label' => 'Editar', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen text-warning me-2"></i>Editar Membresía</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
    <div class="alert alert-info mb-4">
        <strong>Socio:</strong> <?= e($mem['nombre'] . ' ' . $mem['apellido']) ?> &nbsp;|&nbsp;
        <strong>Periodo:</strong> <?= date('d/m/Y', strtotime($mem['fecha_inicio'])) ?> – <?= date('d/m/Y', strtotime($mem['fecha_fin'])) ?>
    </div>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Estado</label>
            <select name="estado" class="form-select">
                <?php foreach (['activa', 'vencida', 'cancelada', 'suspendida'] as $est): ?>
                <option value="<?= $est ?>" <?= ($mem['estado'] === $est) ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">Motivo del cambio</label>
            <input type="text" name="motivo_cambio" class="form-control" value="<?= e($mem['motivo_cambio'] ?? '') ?>">
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning text-white"><i class="fa-solid fa-save me-1"></i>Actualizar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
