<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo = getPDO();

$buscar = trim($_GET['q'] ?? '');
$params = [];
$where  = '1=1';
if ($buscar !== '') {
    $where .= ' AND (f.numero_factura LIKE ? OR s.nombre LIKE ? OR s.apellido LIKE ?)';
    $params = ["%$buscar%", "%$buscar%", "%$buscar%"];
}

$facturas = $pdo->prepare("
    SELECT f.*, p.monto, p.concepto, p.metodo_pago, p.fecha_pago,
           s.nombre, s.apellido, s.numero_socio
    FROM facturas f
    JOIN pagos p ON p.id = f.pago_id
    JOIN socios s ON s.id = p.socio_id
    WHERE $where
    ORDER BY f.fecha_emision DESC
");
$facturas->execute($params);
$facturas = $facturas->fetchAll();

$totalFacturado = $pdo->query("SELECT COALESCE(SUM(total),0) FROM facturas WHERE MONTH(fecha_emision)=MONTH(NOW()) AND YEAR(fecha_emision)=YEAR(NOW())")->fetchColumn();

$pageTitle  = 'Facturas';
$breadcrumb = [['label' => 'Facturas', 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i>Facturas</h4>
        <p class="text-muted mb-0" style="font-size:.86rem;">Facturado este mes: <strong class="text-success">$<?= number_format($totalFacturado, 2) ?></strong></p>
    </div>
</div>

<div class="table-card p-3">
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar por nº factura o socio..." value="<?= e($buscar) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-search me-1"></i>Buscar</button>
            <?php if ($buscar): ?><a href="?" class="btn btn-sm btn-outline-danger ms-1">Limpiar</a><?php endif; ?>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Nº Factura</th>
                <th>Socio</th>
                <th>Concepto</th>
                <th>Subtotal</th>
                <th>IVA %</th>
                <th>Total</th>
                <th>Emisión</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($facturas as $f): ?>
        <tr>
            <td><span class="badge bg-warning text-dark fw-semibold"><?= e($f['numero_factura']) ?></span></td>
            <td>
                <strong><?= e($f['nombre'] . ' ' . $f['apellido']) ?></strong><br>
                <small class="text-muted"><?= e($f['numero_socio']) ?></small>
            </td>
            <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($f['concepto']) ?></td>
            <td>$<?= number_format($f['subtotal'], 2) ?></td>
            <td><?= $f['impuesto_pct'] > 0 ? number_format($f['impuesto_pct'], 1) . '%' : '<span class="text-muted">—</span>' ?></td>
            <td class="fw-bold text-success">$<?= number_format($f['total'], 2) ?></td>
            <td><?= date('d/m/Y', strtotime($f['fecha_emision'])) ?></td>
            <td class="text-end">
                <a href="ver.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver / Imprimir">
                    <i class="fa-solid fa-print"></i>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($facturas)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Sin facturas registradas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
