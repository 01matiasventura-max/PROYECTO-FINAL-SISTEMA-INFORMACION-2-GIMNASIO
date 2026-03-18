<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo      = getPDO();
$empleados = $pdo->query("SELECT * FROM empleados ORDER BY nombre")->fetchAll();
$pageTitle = 'Empleados';
$breadcrumb = [['label' => 'Empleados', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-user-tie text-primary me-2"></i>Empleados</h4>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo empleado</a>
</div>
<div class="table-card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Nombre</th><th>Cargo</th><th>Email</th><th>Instructor</th><th>Contratado</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($empleados as $emp): ?>
    <tr>
        <td><strong><?= e($emp['nombre'] . ' ' . $emp['apellido']) ?></strong></td>
        <td><?= e($emp['cargo']) ?></td>
        <td><?= e($emp['email']) ?></td>
        <td><?= $emp['es_instructor'] ? '<span class="badge badge-activa">Sí</span>' : '<span class="badge bg-light text-dark">No</span>' ?></td>
        <td><?= date('d/m/Y', strtotime($emp['fecha_contratacion'])) ?></td>
        <td><span class="badge <?= $emp['activo'] ? 'badge-activa' : 'badge-vencida' ?>"><?= $emp['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
        <td class="text-end">
            <a href="editar.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
            <a href="eliminar.php?id=<?= $emp['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
               onclick="return confirm('¿Desactivar empleado?')"><i class="fa-solid fa-trash"></i></a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($empleados)): ?><tr><td colspan="7" class="text-center text-muted py-4">Sin empleados.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
