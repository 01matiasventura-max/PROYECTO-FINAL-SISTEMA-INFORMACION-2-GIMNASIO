<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$pdo  = getPDO();
$filtroEstado = $_GET['estado'] ?? '';
$sql  = "SELECT e.*, c.nombre AS categoria FROM equipos e JOIN categorias_equipo c ON c.id = e.categoria_id WHERE 1=1";
$params = [];
if ($filtroEstado !== '') { $sql .= " AND e.estado = ?"; $params[] = $filtroEstado; }
$sql .= " ORDER BY e.nombre";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$equipos = $stmt->fetchAll();
$pageTitle = 'Equipos';
$breadcrumb = [['label' => 'Equipos', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-wrench text-primary me-2"></i>Inventario de Equipos</h4>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo equipo</a>
</div>
<div class="table-card p-3">
    <form class="row g-2 mb-3" method="GET">
        <div class="col-auto">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="operativo" <?= $filtroEstado==='operativo'?'selected':'' ?>>Operativo</option>
                <option value="mantenimiento" <?= $filtroEstado==='mantenimiento'?'selected':'' ?>>Mantenimiento</option>
                <option value="baja" <?= $filtroEstado==='baja'?'selected':'' ?>>Baja</option>
            </select>
        </div>
        <?php if ($filtroEstado): ?><div class="col-auto"><a href="?" class="btn btn-sm btn-outline-danger">Limpiar</a></div><?php endif; ?>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Nombre</th><th>Categoría</th><th>Marca/Modelo</th><th>Ubicación</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($equipos as $eq): ?>
        <tr>
            <td><strong><?= e($eq['nombre']) ?></strong><?php if ($eq['numero_serie']): ?><br><small class="text-muted">S/N: <?= e($eq['numero_serie']) ?></small><?php endif; ?></td>
            <td><?= e($eq['categoria']) ?></td>
            <td><?= e(trim(($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''))) ?: '—' ?></td>
            <td><?= e($eq['ubicacion'] ?? '—') ?></td>
            <td><span class="badge badge-<?= e($eq['estado']) ?>"><?= ucfirst(e($eq['estado'])) ?></span></td>
            <td class="text-end">
                <a href="<?= BASE_URL ?>/mantenimientos/crear.php?equipo_id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Registrar mantenimiento"><i class="fa-solid fa-screwdriver-wrench"></i></a>
                <a href="editar.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
                <a href="eliminar.php?id=<?= $eq['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('¿Dar de baja este equipo?')"><i class="fa-solid fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($equipos)): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin equipos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
