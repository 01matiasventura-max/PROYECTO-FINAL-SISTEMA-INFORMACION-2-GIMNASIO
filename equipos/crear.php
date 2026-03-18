<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo        = getPDO();
$categorias = $pdo->query("SELECT * FROM categorias_equipo ORDER BY nombre")->fetchAll();
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $catId    = (int)($_POST['categoria_id'] ?? 0);
    $nombre   = trim($_POST['nombre'] ?? '');
    $marca    = trim($_POST['marca'] ?? '') ?: null;
    $modelo   = trim($_POST['modelo'] ?? '') ?: null;
    $serie    = trim($_POST['numero_serie'] ?? '') ?: null;
    $fechaAdq = $_POST['fecha_adquisicion'] ?? '' ?: null;
    $costo    = $_POST['costo_adquisicion'] !== '' ? (float)$_POST['costo_adquisicion'] : null;
    $ubicacion= trim($_POST['ubicacion'] ?? '') ?: null;
    $notas    = trim($_POST['notas'] ?? '') ?: null;
    if ($catId <= 0) $errors[] = 'Selecciona una categoría.';
    if ($nombre === '') $errors[] = 'El nombre es requerido.';
    if (empty($errors)) {
        try {
            $pdo->prepare("INSERT INTO equipos (categoria_id, nombre, marca, modelo, numero_serie, fecha_adquisicion, costo_adquisicion, ubicacion, notas) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$catId, $nombre, $marca, $modelo, $serie, $fechaAdq, $costo, $ubicacion, $notas]);
            flashSuccess('Equipo registrado.');
            header('Location: index.php'); exit;
        } catch (PDOException $ex) {
            $errors[] = 'Ya existe un equipo con ese número de serie.';
        }
    }
}

// Si no hay categorías, crear una por defecto
if (empty($categorias)) {
    $pdo->exec("INSERT IGNORE INTO categorias_equipo (nombre) VALUES ('General')");
    $categorias = $pdo->query("SELECT * FROM categorias_equipo ORDER BY nombre")->fetchAll();
}

$pageTitle = 'Nuevo Equipo';
$breadcrumb = [['label' => 'Equipos', 'url' => BASE_URL . '/equipos/index.php'], ['label' => 'Nuevo', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-plus text-primary me-2"></i>Nuevo Equipo</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control" value="<?= e($_POST['nombre'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
            <select name="categoria_id" class="form-select" required>
                <option value="">— Seleccionar —</option>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_POST['categoria_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Marca</label>
            <input type="text" name="marca" class="form-control" value="<?= e($_POST['marca'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Modelo</label>
            <input type="text" name="modelo" class="form-control" value="<?= e($_POST['modelo'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Número de serie</label>
            <input type="text" name="numero_serie" class="form-control" value="<?= e($_POST['numero_serie'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Fecha adquisición</label>
            <input type="date" name="fecha_adquisicion" class="form-control" value="<?= e($_POST['fecha_adquisicion'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Costo adquisición ($)</label>
            <input type="number" name="costo_adquisicion" class="form-control" step="0.01" value="<?= e($_POST['costo_adquisicion'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Ubicación / Sala</label>
            <input type="text" name="ubicacion" class="form-control" value="<?= e($_POST['ubicacion'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Notas</label>
            <textarea name="notas" class="form-control" rows="2"><?= e($_POST['notas'] ?? '') ?></textarea></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Guardar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
