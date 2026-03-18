<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo = getPDO();

$filtroEstado = $_GET['estado'] ?? '';
$sql  = "SELECT m.*, s.nombre, s.apellido, s.numero_socio, p.nombre AS plan, p.precio,
               u.nombre AS creado_nombre, u.apellido AS creado_apellido
        FROM membresias m
        JOIN socios s ON s.id = m.socio_id
        JOIN planes p ON p.id = m.plan_id
        LEFT JOIN usuarios u ON u.id = m.created_by
        WHERE 1=1";
$params = [];
if ($filtroEstado !== '') { $sql .= " AND m.estado = ?"; $params[] = $filtroEstado; }
$sql .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$membresias = $stmt->fetchAll();

$pageTitle = 'Membresías';
$breadcrumb = [['label' => 'Membresías', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-id-card text-primary me-2"></i>Membresías</h4>
    <a href="crear.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nueva membresía</a>
</div>
<div class="table-card p-3">
    <form class="row g-2 mb-3" method="GET">
        <div class="col-auto">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="activa" <?= $filtroEstado==='activa'?'selected':'' ?>>Activa</option>
                <option value="vencida" <?= $filtroEstado==='vencida'?'selected':'' ?>>Vencida</option>
                <option value="cancelada" <?= $filtroEstado==='cancelada'?'selected':'' ?>>Cancelada</option>
                <option value="suspendida" <?= $filtroEstado==='suspendida'?'selected':'' ?>>Suspendida</option>
            </select>
        </div>
        <?php if ($filtroEstado): ?><div class="col-auto"><a href="?" class="btn btn-sm btn-outline-danger">Limpiar</a></div><?php endif; ?>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Socio</th><th>Plan</th><th>Inicio</th><th>Fin</th><th>Estado</th><?php if (isAdmin()): ?><th>Registrado por</th><?php endif; ?><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($membresias as $m): ?>
        <tr>
            <td>
                <a href="<?= BASE_URL ?>/socios/ver.php?id=<?= $m['socio_id'] ?>" class="text-decoration-none">
                    <strong><?= e($m['nombre'] . ' ' . $m['apellido']) ?></strong>
                </a><br>
                <small class="text-muted"><?= e($m['numero_socio']) ?></small>
            </td>
            <td><?= e($m['plan']) ?><br><small class="text-muted">$<?= number_format($m['precio'], 2) ?></small></td>
            <td><?= date('d/m/Y', strtotime($m['fecha_inicio'])) ?></td>
            <td><?= date('d/m/Y', strtotime($m['fecha_fin'])) ?></td>
            <td><span class="badge badge-<?= e($m['estado']) ?>"><?= ucfirst(e($m['estado'])) ?></span></td>
            <?php if (isAdmin()): ?>
            <td><small class="text-muted"><?= $m['creado_nombre'] ? e($m['creado_nombre'] . ' ' . $m['creado_apellido']) : '—' ?></small></td>
            <?php endif; ?>
            <td class="text-end">
                <a href="editar.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fa-solid fa-pen"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($membresias)): ?><tr><td colspan="<?= isAdmin() ? 7 : 6 ?>" class="text-center text-muted py-4">Sin membresías.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
