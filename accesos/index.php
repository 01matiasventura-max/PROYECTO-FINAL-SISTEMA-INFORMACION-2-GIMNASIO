<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1, 2]); // Admin (solo ver) y Recepcionista (registrar + ver)
$pdo     = getPDO();
$errors  = [];
$esRecep = (int)($_SESSION['rol_id'] ?? 0) === 2;

// Registrar entrada — solo recepcionista
if ($esRecep && isset($_POST['action']) && $_POST['action'] === 'entrada') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $socioId = (int)($_POST['socio_id'] ?? 0);
    if ($socioId > 0) {
        $mem = $pdo->prepare("SELECT id FROM membresias WHERE socio_id = ? AND estado = 'activa' AND fecha_fin >= CURDATE() LIMIT 1");
        $mem->execute([$socioId]);
        $mem = $mem->fetch();
        if ($mem) {
            $abierto = $pdo->prepare("SELECT id FROM accesos WHERE socio_id = ? AND fecha_hora_salida IS NULL");
            $abierto->execute([$socioId]);
            if ($abierto->fetch()) {
                $errors[] = 'Este socio ya está registrado dentro del gimnasio.';
            } else {
                $pdo->prepare("INSERT INTO accesos (socio_id, membresia_id, registrado_por) VALUES (?,?,?)")
                    ->execute([$socioId, $mem['id'], currentUserId()]);
                flashSuccess('Entrada registrada correctamente.');
                header('Location: index.php'); exit;
            }
        } else {
            // Verificar si tiene membresía vencida para dar mensaje específico
            $memVencida = $pdo->prepare("SELECT id FROM membresias WHERE socio_id = ? AND estado = 'vencida' LIMIT 1");
            $memVencida->execute([$socioId]);
            if ($memVencida->fetch()) {
                $errors[] = 'La membresía está vencida. Debe renovarla antes de ingresar.';
            } else {
                $errors[] = 'El socio no tiene membresía activa.';
            }
        }
    }
}

// Registrar salida — solo recepcionista
if ($esRecep && isset($_POST['action']) && $_POST['action'] === 'salida') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) die('Token inválido.');
    $accesoId = (int)($_POST['acceso_id'] ?? 0);
    if ($accesoId > 0) {
        $pdo->prepare("UPDATE accesos SET fecha_hora_salida = NOW() WHERE id = ?")->execute([$accesoId]);
        flashSuccess('Salida registrada.');
        header('Location: index.php'); exit;
    }
}

// Socios con entrada abierta (dentro ahora)
$dentroAhora = $pdo->query("SELECT a.id AS acceso_id, a.fecha_hora_entrada, s.nombre, s.apellido, s.numero_socio FROM accesos a JOIN socios s ON s.id = a.socio_id WHERE a.fecha_hora_salida IS NULL ORDER BY a.fecha_hora_entrada DESC")->fetchAll();

// Todos los socios activos
$socios = $pdo->query("SELECT id, numero_socio, nombre, apellido FROM socios WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Historial del día
$historial = $pdo->query("
    SELECT a.*, s.nombre, s.apellido, s.numero_socio,
           u.nombre AS reg_nombre, u.apellido AS reg_apellido
    FROM accesos a
    JOIN socios s ON s.id = a.socio_id
    LEFT JOIN usuarios u ON u.id = a.registrado_por
    WHERE DATE(a.fecha_hora_entrada) = CURDATE()
    ORDER BY a.fecha_hora_entrada DESC
    LIMIT 50
")->fetchAll();

$pageTitle = 'Accesos';
$breadcrumb = [['label' => 'Accesos', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-door-open text-warning me-2"></i>Control de Accesos</h4>
        <?php if (!$esRecep): ?>
        <span class="badge bg-secondary"><i class="fa-solid fa-eye me-1"></i>Solo lectura — modo Administrador</span>
        <?php endif; ?>
    </div>
    <span class="badge bg-success fs-6"><?= count($dentroAhora) ?> persona(s) dentro</span>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $er): echo e($er); endforeach; ?></div><?php endif; ?>

<?php if ($esRecep): ?>
<div class="row g-3 mb-4">
    <!-- Registrar entrada -->
    <div class="col-md-6">
        <div class="table-card p-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-sign-in-alt text-success me-2"></i>Registrar Entrada</h6>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="entrada">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Socio</label>
                    <select name="socio_id" class="form-select" required>
                        <option value="">— Seleccionar socio —</option>
                        <?php foreach ($socios as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['nombre'] . ' ' . $s['apellido'] . ' (' . $s['numero_socio'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>Registrar Entrada
                </button>
            </form>
        </div>
    </div>

    <!-- Socios dentro ahora -->
    <div class="col-md-6">
        <div class="table-card p-3">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-users text-primary me-2"></i>Dentro ahora</h6>
            <?php if (empty($dentroAhora)): ?>
            <p class="text-muted text-center py-3">Sin socios en el gimnasio.</p>
            <?php else: ?>
            <table class="table table-sm mb-0">
                <thead><tr><th>Socio</th><th>Entrada</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ($dentroAhora as $d): ?>
                <tr>
                    <td><?= e($d['nombre'] . ' ' . $d['apellido']) ?></td>
                    <td><?= date('H:i', strtotime($d['fecha_hora_entrada'])) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="salida">
                            <input type="hidden" name="acceso_id" value="<?= $d['acceso_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-right-from-bracket"></i> Salida
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Admin: resumen de quién está dentro ahora (solo lectura) -->
<div class="table-card p-3 mb-4">
    <h6 class="fw-bold mb-3"><i class="fa-solid fa-users text-primary me-2"></i>Socios dentro ahora</h6>
    <?php if (empty($dentroAhora)): ?>
    <p class="text-muted text-center py-3">Nadie en el gimnasio en este momento.</p>
    <?php else: ?>
    <div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>Socio</th><th>Nº Socio</th><th>Hora entrada</th></tr></thead>
        <tbody>
        <?php foreach ($dentroAhora as $d): ?>
        <tr>
            <td class="fw-semibold"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></td>
            <td><span class="badge bg-secondary"><?= e($d['numero_socio']) ?></span></td>
            <td><?= date('H:i', strtotime($d['fecha_hora_entrada'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Historial hoy -->
<div class="table-card p-3">
    <h6 class="fw-bold mb-3"><i class="fa-solid fa-history text-muted me-2"></i>Historial de hoy</h6>
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
        <thead><tr><th>Socio</th><th>Nº</th><th>Entrada</th><th>Salida</th><th>Duración</th><?php if (isAdmin()): ?><th>Registrado por</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($historial as $h): ?>
        <tr>
            <td><?= e($h['nombre'] . ' ' . $h['apellido']) ?></td>
            <td><span class="badge bg-secondary"><?= e($h['numero_socio']) ?></span></td>
            <td><?= date('H:i', strtotime($h['fecha_hora_entrada'])) ?></td>
            <td><?= $h['fecha_hora_salida'] ? date('H:i', strtotime($h['fecha_hora_salida'])) : '<span class="badge badge-activa">Dentro</span>' ?></td>
            <td><?= $h['duracion_min'] ? $h['duracion_min'] . ' min' : '—' ?></td>
            <?php if (isAdmin()): ?>
            <td><small class="text-muted"><?= $h['reg_nombre'] ? e($h['reg_nombre'] . ' ' . $h['reg_apellido']) : '—' ?></small></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($historial)): ?><tr><td colspan="<?= isAdmin() ? 6 : 5 ?>" class="text-center text-muted py-3">Sin accesos hoy.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
