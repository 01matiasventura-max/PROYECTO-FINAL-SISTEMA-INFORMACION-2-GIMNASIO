<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pdo = getPDO();
$search = trim($_GET['q'] ?? '');

$sql = "SELECT s.*, 
        (SELECT m.estado FROM membresias m WHERE m.socio_id = s.id AND m.estado = 'activa' ORDER BY m.fecha_fin DESC LIMIT 1) AS mem_estado,
        (SELECT m.fecha_fin FROM membresias m WHERE m.socio_id = s.id AND m.estado = 'activa' ORDER BY m.fecha_fin DESC LIMIT 1) AS mem_vence,
        u.nombre AS reg_nombre, u.apellido AS reg_apellido
        FROM socios s
        LEFT JOIN usuarios u ON u.id = s.created_by
        WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (s.nombre LIKE ? OR s.apellido LIKE ? OR s.numero_socio LIKE ? OR s.email LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$socios = $stmt->fetchAll();

$pageTitle = 'Socios';
$breadcrumb = [['label' => 'Socios', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-users text-primary me-2"></i>Socios</h4>
        <p class="text-muted mb-0" style="font-size:.86rem;"><?= count($socios) ?> socios encontrados</p>
    </div>
    <a href="<?= BASE_URL ?>/socios/crear.php" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i>Nuevo socio
    </a>
</div>

<div class="table-card p-3">
    <form class="row g-2 mb-3" method="GET">
        <div class="col-12 col-md-5">
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, número o email..."
                       value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-secondary">Buscar</button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-danger">Limpiar</a><?php endif; ?>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Nº Socio</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Membresía</th>
                <th>Estado</th>
                <?php if (isAdmin()): ?><th>Registrado por</th><?php endif; ?>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($socios as $s): ?>
        <tr>
            <td><span class="badge bg-secondary"><?= e($s['numero_socio']) ?></span></td>
            <td>
                <strong><?= e($s['nombre'] . ' ' . $s['apellido']) ?></strong>
                <?php if ($s['fecha_nacimiento']): ?>
                <br><small class="text-muted"><?= date('d/m/Y', strtotime($s['fecha_nacimiento'])) ?></small>
                <?php endif; ?>
            </td>
            <td><?= e($s['telefono'] ?? '—') ?></td>
            <td><?= e($s['email'] ?? '—') ?></td>
            <td>
                <?php if ($s['mem_estado'] === 'activa'): ?>
                    <span class="badge badge-activa">Activa hasta <?= date('d/m/Y', strtotime($s['mem_vence'])) ?></span>
                <?php else: ?>
                    <span class="badge bg-light text-muted">Sin membresía activa</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($s['activo']): ?>
                    <span class="badge badge-activa">Activo</span>
                <?php else: ?>
                    <span class="badge badge-vencida">Inactivo</span>
                <?php endif; ?>
            </td>
            <?php if (isAdmin()): ?>
            <td><?= $s['reg_nombre'] ? e($s['reg_nombre'] . ' ' . $s['reg_apellido']) : '<span class="text-muted">—</span>' ?></td>
            <?php endif; ?>
            <td class="text-end">
                <a href="ver.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver"><i class="fa-solid fa-eye"></i></a>
                <a href="editar.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar"><i class="fa-solid fa-pen"></i></a>
                <a href="eliminar.php?id=<?= $s['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
                   title="Eliminar" onclick="return confirm('¿Desactivar este socio?')"><i class="fa-solid fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($socios)): ?>
        <tr><td colspan="<?= isAdmin() ? 8 : 7 ?>" class="text-center text-muted py-4"><i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>No se encontraron socios.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
