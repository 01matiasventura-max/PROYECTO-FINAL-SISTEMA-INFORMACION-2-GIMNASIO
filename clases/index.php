<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo    = getPDO();
$clases = $pdo->query("SELECT c.*, e.nombre AS instructor_nombre, e.apellido AS instructor_apellido FROM clases c JOIN empleados e ON e.id = c.instructor_id ORDER BY c.nombre")->fetchAll();
$pageTitle = 'Clases';
$breadcrumb = [['label' => 'Clases', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-person-running text-primary me-2"></i>Clases</h4>
    <?php if (isAdmin()): ?><a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nueva clase</a><?php endif; ?>
</div>
<div class="table-card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Nombre</th><th>Instructor</th><th>Duración</th><th>Capacidad</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($clases as $c): ?>
    <tr>
        <td><strong><?= e($c['nombre']) ?></strong><?php if ($c['descripcion']): ?><br><small class="text-muted"><?= e(substr($c['descripcion'], 0, 60)) ?>...</small><?php endif; ?></td>
        <td><?= e($c['instructor_nombre'] . ' ' . $c['instructor_apellido']) ?></td>
        <td><?= $c['duracion_min'] ?> min</td>
        <td><?= $c['capacidad_max'] ?> personas</td>
        <td><span class="badge <?= $c['activo'] ? 'badge-activa' : 'badge-vencida' ?>"><?= $c['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
        <td class="text-end">
            <?php if (isAdmin()): ?>
            <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
            <a href="eliminar.php?id=<?= $c['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('¿Desactivar esta clase?')"><i class="fa-solid fa-trash"></i></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($clases)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin clases registradas.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
