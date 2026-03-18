<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo  = getPDO();
$dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$reservas = $pdo->query("
    SELECT r.*, s.nombre, s.apellido, s.numero_socio,
           c.nombre AS clase, h.dia_semana, h.hora_inicio, r.fecha,
           u.nombre AS creado_nombre, u.apellido AS creado_apellido
    FROM reservas r
    JOIN socios s ON s.id = r.socio_id
    JOIN horarios h ON h.id = r.horario_id
    JOIN clases c ON c.id = h.clase_id
    LEFT JOIN usuarios u ON u.id = r.created_by
    ORDER BY r.fecha DESC, h.hora_inicio
")->fetchAll();
$pageTitle = 'Reservas';
$breadcrumb = [['label' => 'Reservas', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Reservas de Clases</h4>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nueva reserva</a>
</div>
<div class="table-card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Socio</th><th>Clase</th><th>Día</th><th>Hora</th><th>Fecha</th><th>Estado</th><?php if (isAdmin()): ?><th>Registrado por</th><?php endif; ?><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($reservas as $r): ?>
    <tr>
        <td><strong><?= e($r['nombre'] . ' ' . $r['apellido']) ?></strong><br><small class="text-muted"><?= e($r['numero_socio']) ?></small></td>
        <td><?= e($r['clase']) ?></td>
        <td><?= $dias[$r['dia_semana']] ?? $r['dia_semana'] ?></td>
        <td><?= substr($r['hora_inicio'], 0, 5) ?></td>
        <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
        <td>
            <?php if ($r['estado'] === 'confirmada'): ?><span class="badge badge-activa">Confirmada</span>
            <?php elseif ($r['estado'] === 'asistio'): ?><span class="badge bg-info text-white">Asistió</span>
            <?php else: ?><span class="badge badge-vencida">Cancelada</span><?php endif; ?>
        </td>
        <?php if (isAdmin()): ?>
        <td><small class="text-muted"><?= $r['creado_nombre'] ? e($r['creado_nombre'] . ' ' . $r['creado_apellido']) : '—' ?></small></td>
        <?php endif; ?>
        <td class="text-end">
            <?php if ($r['estado'] === 'confirmada'): ?>
            <a href="asistencia.php?id=<?= $r['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-info" title="Marcar asistencia"><i class="fa-solid fa-check"></i></a>
            <a href="cancelar.php?id=<?= $r['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('¿Cancelar reserva?')" title="Cancelar"><i class="fa-solid fa-ban"></i></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($reservas)): ?><tr><td colspan="<?= isAdmin() ? 8 : 7 ?>" class="text-center text-muted py-4">Sin reservas.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
