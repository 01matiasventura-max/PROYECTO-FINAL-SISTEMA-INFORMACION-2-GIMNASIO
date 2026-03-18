<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo  = getPDO();
$id   = (int)($_GET['id'] ?? 0);
$h    = $pdo->prepare("SELECT * FROM horarios WHERE id = ?");
$h->execute([$id]); $h = $h->fetch();
if (!$h) { flashError('Horario no encontrado.'); header('Location: index.php'); exit; }
$clases = $pdo->query("SELECT id, nombre FROM clases WHERE activo = 1 ORDER BY nombre")->fetchAll();
$dias   = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $claseId    = (int)($_POST['clase_id'] ?? 0);
    $diaSemana  = (int)($_POST['dia_semana'] ?? 0);
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFin    = $_POST['hora_fin'] ?? '';
    $sala       = trim($_POST['sala'] ?? '');
    $activo     = isset($_POST['activo']) ? 1 : 0;
    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE horarios SET clase_id=?,dia_semana=?,hora_inicio=?,hora_fin=?,sala=?,activo=? WHERE id=?")
                ->execute([$claseId, $diaSemana, $horaInicio, $horaFin, $sala, $activo, $id]);
            flashSuccess('Horario actualizado.'); header('Location: index.php'); exit;
        } catch (PDOException $ex) {
            $errors[] = 'Ya existe un horario en esa sala para ese día y hora.';
        }
    }
}
$pageTitle = 'Editar Horario';
$breadcrumb = [['label' => 'Horarios', 'url' => BASE_URL . '/horarios/index.php'], ['label' => 'Editar', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-pen text-warning me-2"></i>Editar Horario</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Clase</label>
            <select name="clase_id" class="form-select">
                <?php foreach ($clases as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_POST['clase_id'] ?? $h['clase_id']) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Día</label>
            <select name="dia_semana" class="form-select">
                <?php foreach ($dias as $num => $nombre): ?>
                <option value="<?= $num ?>" <?= ((int)($_POST['dia_semana'] ?? $h['dia_semana']) === $num) ? 'selected' : '' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Sala</label>
            <input type="text" name="sala" class="form-control" value="<?= e($_POST['sala'] ?? $h['sala']) ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Hora inicio</label>
            <input type="time" name="hora_inicio" class="form-control" value="<?= e($_POST['hora_inicio'] ?? $h['hora_inicio']) ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Hora fin</label>
            <input type="time" name="hora_fin" class="form-control" value="<?= e($_POST['hora_fin'] ?? $h['hora_fin']) ?>"></div>
        <div class="col-12"><div class="form-check">
            <input class="form-check-input" type="checkbox" name="activo" id="activo"
                   <?= (isset($_POST['activo']) ? (bool)$_POST['activo'] : $h['activo']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="activo">Activo</label>
        </div></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-warning text-white"><i class="fa-solid fa-save me-1"></i>Actualizar</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
