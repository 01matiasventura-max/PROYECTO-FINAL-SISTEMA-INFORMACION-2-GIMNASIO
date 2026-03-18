<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo  = getPDO();
$dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
$diasFull = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

// Traer todos los horarios con datos de clase e instructor
$rows = $pdo->query("
    SELECT h.id, h.dia_semana, h.hora_inicio, h.hora_fin, h.sala, h.activo,
           c.id AS clase_id, c.nombre AS clase, c.duracion_min, c.capacidad_max,
           CONCAT(e.nombre,' ',e.apellido) AS instructor
    FROM horarios h
    JOIN clases c ON c.id = h.clase_id
    LEFT JOIN empleados e ON e.id = c.instructor_id
    ORDER BY c.nombre, h.dia_semana, h.hora_inicio
")->fetchAll();

// Agrupar por clase
$porClase = [];
foreach ($rows as $r) {
    $cid = $r['clase_id'];
    if (!isset($porClase[$cid])) {
        $porClase[$cid] = [
            'nombre'       => $r['clase'],
            'duracion_min' => $r['duracion_min'],
            'capacidad_max'=> $r['capacidad_max'],
            'instructor'   => $r['instructor'],
            'franjas'      => [],
        ];
    }
    $porClase[$cid]['franjas'][] = [
        'id'          => $r['id'],
        'dia'         => (int)$r['dia_semana'],
        'hora_inicio' => $r['hora_inicio'],
        'hora_fin'    => $r['hora_fin'],
        'sala'        => $r['sala'],
        'activo'      => $r['activo'],
    ];
}

$pageTitle  = 'Horarios';
$breadcrumb = [['label' => 'Horarios', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-calendar-week text-primary me-2"></i>Horarios de Clases</h4>
    <?php if (isAdmin()): ?><a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo horario</a><?php endif; ?>
</div>

<?php if (empty($porClase)): ?>
<div class="table-card p-5 text-center text-muted">
    <i class="fa-solid fa-clock fa-3x mb-3 opacity-25"></i>
    <p class="mb-0">Sin horarios registrados.</p>
    <?php if (isAdmin()): ?><a href="crear.php" class="btn btn-primary mt-3"><i class="fa-solid fa-plus me-1"></i>Crear primer horario</a><?php endif; ?>
</div>
<?php else: ?>

<?php foreach ($porClase as $cid => $clase): ?>
<div class="table-card mb-4 overflow-hidden">
    <!-- Cabecera de la disciplina -->
    <div class="d-flex align-items-center justify-content-between px-4 py-3"
         style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-bottom:3px solid #e94560;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width:46px;height:46px;background:rgba(233,69,96,.2);flex-shrink:0;">
                <i class="fa-solid fa-dumbbell" style="color:#e94560;font-size:1.1rem;"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-white"><?= e($clase['nombre']) ?></h5>
                <div class="d-flex gap-3 mt-1" style="font-size:.82rem;color:#adb5bd;">
                    <?php if ($clase['instructor']): ?>
                    <span><i class="fa-solid fa-user-tie me-1"></i><?= e($clase['instructor']) ?></span>
                    <?php endif; ?>
                    <span><i class="fa-solid fa-clock me-1"></i><?= $clase['duracion_min'] ?> min</span>
                    <span><i class="fa-solid fa-users me-1"></i>Cap. <?= $clase['capacidad_max'] ?></span>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary"><?= count($clase['franjas']) ?> franja<?= count($clase['franjas']) !== 1 ? 's' : '' ?></span>
            <?php if (isAdmin()): ?>
            <a href="crear.php" class="btn btn-sm btn-outline-light" title="Agregar franja a esta clase">
                <i class="fa-solid fa-plus"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Agrupar franjas por sala para mostrarlo mejor -->
    <?php
    $porSala = [];
    foreach ($clase['franjas'] as $f) {
        $porSala[$f['sala']][] = $f;
    }
    ?>

    <div class="p-3">
    <?php foreach ($porSala as $sala => $franjas): ?>
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-location-dot text-muted" style="font-size:.85rem;"></i>
                <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.05em;"><?= e($sala) ?></span>
                <hr class="flex-grow-1 my-0 ms-2">
            </div>
            <div class="d-flex flex-wrap gap-2">
            <?php foreach ($franjas as $f): ?>
                <div class="d-flex align-items-center border rounded-3 overflow-hidden <?= $f['activo'] ? '' : 'opacity-50' ?>"
                     style="background:#f8f9fa;min-width:170px;">
                    <!-- Badge día -->
                    <div class="d-flex flex-column align-items-center justify-content-center px-3 py-2"
                         style="background:<?= $f['activo'] ? '#0d6efd' : '#6c757d' ?>;color:#fff;min-width:52px;">
                        <span style="font-size:.72rem;font-weight:700;letter-spacing:.05em;"><?= $dias[$f['dia']] ?></span>
                        <span style="font-size:.65rem;opacity:.85;"><?= $diasFull[$f['dia']] ?></span>
                    </div>
                    <!-- Horas -->
                    <div class="px-3 py-2 flex-grow-1">
                        <div class="fw-bold" style="font-size:.9rem;color:#212529;">
                            <?= substr($f['hora_inicio'],0,5) ?> <span style="color:#adb5bd;">–</span> <?= substr($f['hora_fin'],0,5) ?>
                        </div>
                        <?php if (!$f['activo']): ?>
                        <div style="font-size:.72rem;color:#dc3545;">Inactivo</div>
                        <?php endif; ?>
                    </div>
                    <!-- Acciones -->
                    <?php if (isAdmin()): ?>
                    <div class="d-flex flex-column border-start" style="background:#fff;">
                        <a href="editar.php?id=<?= $f['id'] ?>"
                           class="btn btn-sm text-warning border-0 border-bottom rounded-0 py-2 px-2"
                           title="Editar" style="border-radius:0!important;">
                            <i class="fa-solid fa-pen" style="font-size:.78rem;"></i>
                        </a>
                        <a href="eliminar.php?id=<?= $f['id'] ?>&csrf=<?= generateCsrfToken() ?>"
                           class="btn btn-sm text-danger border-0 rounded-0 py-2 px-2"
                           onclick="return confirm('¿Eliminar el horario del <?= $diasFull[$f['dia']] ?> <?= substr($f['hora_inicio'],0,5) ?>?')"
                           title="Eliminar" style="border-radius:0!important;">
                            <i class="fa-solid fa-trash" style="font-size:.78rem;"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

