<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo   = getPDO();
$planes = $pdo->query("SELECT * FROM planes ORDER BY precio ASC")->fetchAll();
$pageTitle = 'Planes';
$breadcrumb = [['label' => 'Planes', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i>Planes de Membresía</h4>
    <?php if (isAdmin()): ?><a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo plan</a><?php endif; ?>
</div>
<div class="table-card p-3">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Nombre</th><th>Duración (días)</th><th>Precio</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($planes as $p): ?>
    <tr>
        <td><strong><?= e($p['nombre']) ?></strong><?php if ($p['descripcion']): ?><br><small class="text-muted"><?= e($p['descripcion']) ?></small><?php endif; ?></td>
        <td><?= $p['duracion_dias'] ?> días</td>
        <td class="fw-bold text-success">$<?= number_format($p['precio'], 2) ?></td>
        <td><span class="badge <?= $p['activo'] ? 'badge-activa' : 'badge-vencida' ?>"><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td class="text-end">
            <?php if (isAdmin()): ?>
            <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
            <a href="eliminar.php?id=<?= $p['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('¿Eliminar este plan?')"><i class="fa-solid fa-trash"></i></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($planes)): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin planes registrados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
