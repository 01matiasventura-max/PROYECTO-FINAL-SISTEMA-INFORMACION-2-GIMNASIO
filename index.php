<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$pdo = getPDO();

$socios        = $pdo->query("SELECT COUNT(*) FROM socios WHERE activo = 1")->fetchColumn();
$memActivas    = $pdo->query("SELECT COUNT(*) FROM membresias WHERE estado = 'activa'")->fetchColumn();
$ingresosMes   = $pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE estado = 'pagado' AND MONTH(fecha_pago) = MONTH(NOW()) AND YEAR(fecha_pago) = YEAR(NOW())")->fetchColumn();
$clases        = $pdo->query("SELECT COUNT(*) FROM clases WHERE activo = 1")->fetchColumn();
$equiposOp     = $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado = 'operativo'")->fetchColumn();
$equiposMant   = $pdo->query("SELECT COUNT(*) FROM equipos WHERE estado = 'mantenimiento'")->fetchColumn();
$accesoHoy     = $pdo->query("SELECT COUNT(*) FROM accesos WHERE DATE(fecha_hora_entrada) = CURDATE()")->fetchColumn();
$memVencen     = $pdo->query("SELECT COUNT(*) FROM membresias WHERE estado = 'activa' AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// Últimos accesos
$ultAccesos = $pdo->query("SELECT a.fecha_hora_entrada, s.nombre, s.apellido, s.numero_socio FROM accesos a JOIN socios s ON s.id = a.socio_id ORDER BY a.fecha_hora_entrada DESC LIMIT 8")->fetchAll();

// Últimos pagos
$ultPagos = $pdo->query("SELECT p.fecha_pago, p.monto, p.metodo_pago, s.nombre, s.apellido FROM pagos p JOIN socios s ON s.id = p.socio_id WHERE p.estado = 'pagado' ORDER BY p.fecha_pago DESC LIMIT 6")->fetchAll();

// Membresías por vencer
$proxVencer = $pdo->query("SELECT m.fecha_fin, s.nombre, s.apellido, s.numero_socio, pl.nombre AS plan FROM membresias m JOIN socios s ON s.id = m.socio_id JOIN planes pl ON pl.id = m.plan_id WHERE m.estado = 'activa' AND m.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY m.fecha_fin LIMIT 6")->fetchAll();

$pageTitle = 'Dashboard';
$breadcrumb = [['label' => 'Dashboard', 'active' => true]];
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1">Dashboard</h4>
        <p class="text-muted mb-0" style="font-size:.86rem;">Bienvenido, <?= e($_SESSION['nombre']) ?>. Aquí tienes el resumen del día.</p>
    </div>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($socios) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Socios activos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="fa-solid fa-id-card"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($memActivas) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Membresías activas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fa-solid fa-dollar-sign"></i></div>
            <div>
                <div class="fw-bold fs-4">$<?= number_format($ingresosMes, 2) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Ingresos este mes</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fa-solid fa-door-open"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($accesoHoy) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Accesos hoy</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="fa-solid fa-person-running"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($clases) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Clases activas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="fa-solid fa-wrench"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($equiposOp) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Equipos operativos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($equiposMant) ?></div>
                <div class="text-muted" style="font-size:.8rem;">En mantenimiento</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white">
            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fa-solid fa-bell"></i></div>
            <div>
                <div class="fw-bold fs-4"><?= number_format($memVencen) ?></div>
                <div class="text-muted" style="font-size:.8rem;">Membresías por vencer</div>
            </div>
        </div>
    </div>
</div>

<!-- Tables row -->
<div class="row g-3">
    <!-- Últimos accesos -->
    <div class="col-12 col-lg-6">
        <div class="table-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-door-open text-warning me-2"></i>Últimos accesos</h6>
                <a href="<?= BASE_URL ?>/accesos/index.php" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <table class="table table-hover mb-0">
                <thead><tr><th>Socio</th><th>Nº</th><th>Entrada</th></tr></thead>
                <tbody>
                <?php foreach ($ultAccesos as $a): ?>
                <tr>
                    <td><?= e($a['nombre'] . ' ' . $a['apellido']) ?></td>
                    <td><span class="badge bg-secondary"><?= e($a['numero_socio']) ?></span></td>
                    <td><?= date('d/m H:i', strtotime($a['fecha_hora_entrada'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ultAccesos)): ?><tr><td colspan="3" class="text-center text-muted py-3">Sin accesos registrados</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Membresías por vencer -->
    <div class="col-12 col-lg-6">
        <div class="table-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-bell text-danger me-2"></i>Membresías por vencer (7 días)</h6>
                <a href="<?= BASE_URL ?>/membresias/index.php" class="btn btn-sm btn-outline-secondary">Ver todas</a>
            </div>
            <table class="table table-hover mb-0">
                <thead><tr><th>Socio</th><th>Plan</th><th>Vence</th></tr></thead>
                <tbody>
                <?php foreach ($proxVencer as $m): ?>
                <tr>
                    <td><?= e($m['nombre'] . ' ' . $m['apellido']) ?></td>
                    <td><?= e($m['plan']) ?></td>
                    <td><span class="badge badge-vencida"><?= date('d/m/Y', strtotime($m['fecha_fin'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($proxVencer)): ?><tr><td colspan="3" class="text-center text-muted py-3">Sin alertas de vencimiento</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Últimos pagos -->
    <div class="col-12">
        <div class="table-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Últimos pagos</h6>
                <a href="<?= BASE_URL ?>/pagos/index.php" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <table class="table table-hover mb-0">
                <thead><tr><th>Socio</th><th>Monto</th><th>Método</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php foreach ($ultPagos as $p): ?>
                <tr>
                    <td><?= e($p['nombre'] . ' ' . $p['apellido']) ?></td>
                    <td class="fw-bold text-success">$<?= number_format($p['monto'], 2) ?></td>
                    <td><span class="badge bg-light text-dark"><?= e(str_replace('_', ' ', $p['metodo_pago'])) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($p['fecha_pago'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ultPagos)): ?><tr><td colspan="4" class="text-center text-muted py-3">Sin pagos registrados</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
