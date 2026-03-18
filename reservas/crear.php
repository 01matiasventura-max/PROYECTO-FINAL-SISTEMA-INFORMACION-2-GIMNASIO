<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo    = getPDO();
$errors = [];
$socios   = $pdo->query("SELECT id, numero_socio, nombre, apellido FROM socios WHERE activo = 1 ORDER BY nombre")->fetchAll();
$horarios = $pdo->query("
    SELECT h.id, c.nombre AS clase, h.dia_semana, h.hora_inicio, h.hora_fin, h.sala,
           c.capacidad_max,
           e.nombre AS inst_nombre, e.apellido AS inst_apellido,
           (SELECT COUNT(*) FROM reservas r WHERE r.horario_id = h.id AND r.fecha = CURDATE() AND r.estado = 'confirmada') AS ocupadas_hoy
    FROM horarios h
    JOIN clases c ON c.id = h.clase_id
    LEFT JOIN empleados e ON e.id = c.instructor_id
    WHERE h.activo = 1
    ORDER BY h.dia_semana, h.hora_inicio
")->fetchAll();
$dias     = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $socioId   = (int)($_POST['socio_id'] ?? 0);
    $horarioId = (int)($_POST['horario_id'] ?? 0);
    $fecha     = $_POST['fecha'] ?? '';
    if ($socioId <= 0) $errors[] = 'Selecciona un socio.';
    if ($horarioId <= 0) $errors[] = 'Selecciona un horario.';
    if ($fecha === '') $errors[] = 'La fecha es requerida.';
    if (empty($errors)) {
        // Verificar que el socio tiene membresía activa
        $mem = $pdo->prepare("SELECT id FROM membresias WHERE socio_id = ? AND estado = 'activa' AND fecha_fin >= ? LIMIT 1");
        $mem->execute([$socioId, $fecha]);
        if (!$mem->fetch()) {
            $errors[] = 'El socio no tiene membresía activa para esa fecha.';
        }
    }
    if (empty($errors)) {
        // Verificar capacidad máxima de la clase en esa fecha y horario
        $cap = $pdo->prepare("
            SELECT c.capacidad_max,
                   (SELECT COUNT(*) FROM reservas r2
                    WHERE r2.horario_id = ? AND r2.fecha = ? AND r2.estado = 'confirmada') AS ocupadas
            FROM horarios h JOIN clases c ON c.id = h.clase_id
            WHERE h.id = ?
        ");
        $cap->execute([$horarioId, $fecha, $horarioId]);
        $capRow = $cap->fetch();
        if ($capRow && (int)$capRow['ocupadas'] >= (int)$capRow['capacidad_max']) {
            $errors[] = 'El horario seleccionado ya alcanzó la capacidad máxima (' . $capRow['capacidad_max'] . ' personas).';
        }
    }
    if (empty($errors)) {
        try {
            $pdo->prepare("INSERT INTO reservas (socio_id, horario_id, fecha, created_by) VALUES (?,?,?,?)")
                ->execute([$socioId, $horarioId, $fecha, currentUserId()]);
            flashSuccess('Reserva creada correctamente.');
            header('Location: index.php'); exit;
        } catch (PDOException $ex) {
            $errors[] = 'El socio ya tiene una reserva en ese horario y fecha.';
        }
    }
}
$pageTitle = 'Nueva Reserva';
$breadcrumb = [['label' => 'Reservas', 'url' => BASE_URL . '/reservas/index.php'], ['label' => 'Nueva', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-calendar-plus text-primary me-2"></i>Nueva Reserva</h4>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
</div>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="table-card p-4">
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Socio <span class="text-danger">*</span></label>
            <select name="socio_id" class="form-select" required>
                <option value="">— Seleccionar socio —</option>
                <?php foreach ($socios as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ((int)($_POST['socio_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['nombre'] . ' ' . $s['apellido'] . ' (' . $s['numero_socio'] . ')') ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Horario <span class="text-danger">*</span></label>
            <select name="horario_id" class="form-select" required>
                <option value="">— Seleccionar horario —</option>
                <?php foreach ($horarios as $h): ?>
                <option value="<?= $h['id'] ?>" <?= ((int)($_POST['horario_id'] ?? 0) === (int)$h['id']) ? 'selected' : '' ?>>
                    <?= e($h['clase']) ?> — <?= $dias[$h['dia_semana']] ?> <?= substr($h['hora_inicio'], 0, 5) ?>–<?= substr($h['hora_fin'], 0, 5) ?> · Inst: <?= e($h['inst_nombre'] . ' ' . $h['inst_apellido']) ?> · <?= (int)$h['capacidad_max'] - (int)$h['ocupadas_hoy'] ?>/<?= $h['capacidad_max'] ?> lugares
                </option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Fecha de sesión <span class="text-danger">*</span></label>
            <input type="date" name="fecha" class="form-control" value="<?= e($_POST['fecha'] ?? date('Y-m-d')) ?>" required></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i>Crear Reserva</button>
        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
