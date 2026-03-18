<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo = getPDO();
$mantenimientos = $pdo->query("SELECT m.*, e.nombre AS equipo, u.nombre AS registrado_nombre, u.apellido AS registrado_apellido FROM mantenimientos m JOIN equipos e ON e.id = m.equipo_id JOIN usuarios u ON u.id = m.registrado_por ORDER BY m.fecha_inicio DESC")->fetchAll();
$pageTitle = 'Mantenimientos';
$breadcrumb = [['label' => 'Mantenimientos', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i>Mantenimientos</h4>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo mantenimiento</a>
</div>
<div class="table-card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Equipo</th><th>Tipo</th><th>Descripción</th><th>Inicio</th><th>Cierre</th><th>Costo</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($mantenimientos as $m): ?>
    <tr>
        <td><strong><?= e($m['equipo']) ?></strong></td>
        <td><span class="badge <?= $m['tipo']==='correctivo' ? 'bg-danger' : 'bg-info text-white' ?>"><?= ucfirst($m['tipo']) ?></span></td>
        <td><?= e(substr($m['descripcion'], 0, 60)) ?><?= strlen($m['descripcion']) > 60 ? '...' : '' ?></td>
        <td><?= date('d/m/Y', strtotime($m['fecha_inicio'])) ?></td>
        <td><?= $m['fecha_cierre'] ? date('d/m/Y', strtotime($m['fecha_cierre'])) : '<span class="badge badge-suspendida">En curso</span>' ?></td>
        <td>$<?= number_format($m['costo'], 2) ?></td>
        <td><?= $m['fecha_cierre'] ? '<span class="badge badge-activa">Cerrado</span>' : '<span class="badge badge-suspendida">Abierto</span>' ?></td>
        <td class="text-end">
            <?php if (!$m['fecha_cierre']): ?>
            <a href="cerrar.php?id=<?= $m['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-success"
               onclick="return confirm('¿Marcar como cerrado?')" title="Cerrar mantenimiento"><i class="fa-solid fa-check"></i></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($mantenimientos)): ?><tr><td colspan="8" class="text-center text-muted py-4">Sin mantenimientos.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
