<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo = getPDO();
$filtroEstado = $_GET['estado'] ?? '';
$sql = "SELECT p.*, s.nombre, s.apellido, s.numero_socio,
               u.nombre AS cobrado_nombre, u.apellido AS cobrado_apellido
        FROM pagos p
        JOIN socios s ON s.id = p.socio_id
        LEFT JOIN usuarios u ON u.id = p.cobrado_por
        WHERE 1=1";
$params = [];
if ($filtroEstado !== '') { $sql .= " AND p.estado = ?"; $params[] = $filtroEstado; }
$sql .= " ORDER BY p.fecha_pago DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$pagos = $stmt->fetchAll();
$totalMes = $pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado='pagado' AND MONTH(fecha_pago)=MONTH(NOW()) AND YEAR(fecha_pago)=YEAR(NOW())")->fetchColumn();

$pageTitle = 'Pagos';
$breadcrumb = [['label' => 'Pagos', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Pagos</h4>
        <p class="text-muted mb-0" style="font-size:.86rem;">Total cobrado este mes: <strong class="text-success">$<?= number_format($totalMes, 2) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (isAdmin()): ?>
        <a href="../facturas/index.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i>Ver facturas emitidas
        </a>
        <?php endif; ?>
        <a href="crear.php" class="btn btn-success"><i class="fa-solid fa-plus me-1"></i>Registrar pago</a>
    </div>
</div>
<div class="table-card p-3">
    <form class="row g-2 mb-3" method="GET">
        <div class="col-auto">
            <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="pagado" <?= $filtroEstado==='pagado'?'selected':'' ?>>Pagado</option>
                <option value="pendiente" <?= $filtroEstado==='pendiente'?'selected':'' ?>>Pendiente</option>
                <option value="anulado" <?= $filtroEstado==='anulado'?'selected':'' ?>>Anulado</option>
            </select>
        </div>
        <?php if ($filtroEstado): ?><div class="col-auto"><a href="?" class="btn btn-sm btn-outline-danger">Limpiar</a></div><?php endif; ?>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Socio</th><th>Concepto</th><th>Monto</th><th>Método</th><th>Estado</th><th>Fecha</th><?php if (isAdmin()): ?><th>Cobrado por</th><?php endif; ?><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($pagos as $p): ?>
        <tr>
            <td><strong><?= e($p['nombre'] . ' ' . $p['apellido']) ?></strong><br><small class="text-muted"><?= e($p['numero_socio']) ?></small></td>
            <td><?= e($p['concepto']) ?></td>
            <td class="fw-bold <?= $p['estado']==='anulado'?'text-danger':'text-success' ?>">$<?= number_format($p['monto'], 2) ?></td>
            <td><span class="badge bg-light text-dark"><?= e(str_replace('_', ' ', $p['metodo_pago'])) ?></span></td>
            <td>
                <?php if ($p['estado'] === 'pagado'): ?><span class="badge badge-activa">Pagado</span>
                <?php elseif ($p['estado'] === 'pendiente'): ?><span class="badge badge-suspendida">Pendiente</span>
                <?php else: ?><span class="badge badge-vencida">Anulado</span><?php endif; ?>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($p['fecha_pago'])) ?></td>
            <?php if (isAdmin()): ?>
            <td><small class="text-muted"><?= $p['cobrado_nombre'] ? e($p['cobrado_nombre'] . ' ' . $p['cobrado_apellido']) : '<em>N/A</em>' ?></small></td>
            <?php endif; ?>
            <td class="text-end d-flex gap-1 justify-content-end">
                <?php if ($p['estado'] === 'pagado'): ?>
                <a href="../facturas/crear.php?pago_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Generar factura">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </a>
                <?php endif; ?>
                <?php if ($p['estado'] !== 'anulado'): ?>
                <a href="anular.php?id=<?= $p['id'] ?>&csrf=<?= generateCsrfToken() ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('¿Anular este pago?')"><i class="fa-solid fa-ban"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($pagos)): ?><tr><td colspan="<?= isAdmin() ? 8 : 7 ?>" class="text-center text-muted py-4">Sin pagos.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
