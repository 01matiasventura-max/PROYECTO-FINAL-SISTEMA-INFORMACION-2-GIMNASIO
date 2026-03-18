<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
$socio = $pdo->prepare("SELECT * FROM socios WHERE id = ?");
$socio->execute([$id]);
$socio = $socio->fetch();
if (!$socio) { flashError('Socio no encontrado.'); header('Location: index.php'); exit; }

$membresias = $pdo->prepare("SELECT m.*, p.nombre AS plan, p.precio FROM membresias m JOIN planes p ON p.id = m.plan_id WHERE m.socio_id = ? ORDER BY m.created_at DESC");
$membresias->execute([$id]);
$membresias = $membresias->fetchAll();

$pagos = $pdo->prepare("SELECT * FROM pagos WHERE socio_id = ? ORDER BY fecha_pago DESC LIMIT 10");
$pagos->execute([$id]);
$pagos = $pagos->fetchAll();

$accesos = $pdo->prepare("SELECT * FROM accesos WHERE socio_id = ? ORDER BY fecha_hora_entrada DESC LIMIT 10");
$accesos->execute([$id]);
$accesos = $accesos->fetchAll();

$pageTitle = 'Perfil de Socio';
$breadcrumb = [['label' => 'Socios', 'url' => BASE_URL . '/socios/index.php'], ['label' => e($socio['nombre'] . ' ' . $socio['apellido']), 'active' => true]];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0"><i class="fa-solid fa-user text-info me-2"></i>Perfil del Socio</h4>
    <div class="d-flex gap-2">
        <a href="editar.php?id=<?= $id ?>" class="btn btn-outline-warning"><i class="fa-solid fa-pen me-1"></i>Editar</a>
        <a href="index.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
    </div>
</div>

<div class="row g-3">
    <!-- Info del socio -->
    <div class="col-md-4">
        <div class="table-card p-4 text-center">
            <div class="mb-3">
                <div style="width:80px;height:80px;background:linear-gradient(135deg,#e94560,#c1121f);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff;margin:0 auto;">
                    <?= strtoupper(substr($socio['nombre'], 0, 1)) ?>
                </div>
            </div>
            <h5 class="fw-bold mb-1"><?= e($socio['nombre'] . ' ' . $socio['apellido']) ?></h5>
            <span class="badge bg-secondary mb-3"><?= e($socio['numero_socio']) ?></span>
            <div class="text-start">
                <?php if ($socio['email']): ?>
                <div class="mb-2"><i class="fa-solid fa-envelope text-muted me-2"></i><?= e($socio['email']) ?></div>
                <?php endif; ?>
                <?php if ($socio['telefono']): ?>
                <div class="mb-2"><i class="fa-solid fa-phone text-muted me-2"></i><?= e($socio['telefono']) ?></div>
                <?php endif; ?>
                <?php if ($socio['fecha_nacimiento']): ?>
                <div class="mb-2"><i class="fa-solid fa-cake-candles text-muted me-2"></i><?= date('d/m/Y', strtotime($socio['fecha_nacimiento'])) ?></div>
                <?php endif; ?>
                <?php if ($socio['direccion']): ?>
                <div class="mb-2"><i class="fa-solid fa-location-dot text-muted me-2"></i><?= e($socio['direccion']) ?></div>
                <?php endif; ?>
                <div class="mb-2">
                    <i class="fa-solid fa-circle text-muted me-2"></i>
                    <?php if ($socio['activo']): ?>
                    <span class="badge badge-activa">Activo</span>
                    <?php else: ?>
                    <span class="badge badge-vencida">Inactivo</span>
                    <?php endif; ?>
                </div>
                <div><i class="fa-solid fa-calendar text-muted me-2"></i>Desde <?= date('d/m/Y', strtotime($socio['created_at'])) ?></div>
            </div>
            <?php if ($socio['notas']): ?>
            <div class="mt-3 p-2 bg-light rounded text-start"><small><?= e($socio['notas']) ?></small></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Membresías -->
        <div class="table-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-id-card text-primary me-2"></i>Membresías</h6>
                <a href="<?= BASE_URL ?>/membresias/crear.php?socio_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">+ Nueva</a>
            </div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Plan</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($membresias as $m): ?>
                <tr>
                    <td><?= e($m['plan']) ?></td>
                    <td><?= date('d/m/Y', strtotime($m['fecha_inicio'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($m['fecha_fin'])) ?></td>
                    <td><span class="badge badge-<?= e($m['estado']) ?>"><?= e($m['estado']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($membresias)): ?><tr><td colspan="4" class="text-muted text-center py-2">Sin membresías</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Últimos pagos -->
        <div class="table-card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-money-bill-wave text-success me-2"></i>Últimos pagos</h6>
                <a href="<?= BASE_URL ?>/pagos/crear.php?socio_id=<?= $id ?>" class="btn btn-sm btn-outline-success">+ Nuevo pago</a>
            </div>
            <table class="table table-sm mb-0">
                <thead><tr><th>Concepto</th><th>Monto</th><th>Método</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= e($p['concepto']) ?></td>
                    <td class="text-success fw-bold">$<?= number_format($p['monto'], 2) ?></td>
                    <td><?= e(str_replace('_', ' ', $p['metodo_pago'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pagos)): ?><tr><td colspan="4" class="text-muted text-center py-2">Sin pagos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Últimos accesos -->
        <div class="table-card p-3">
            <h6 class="fw-bold mb-2"><i class="fa-solid fa-door-open text-warning me-2"></i>Últimos accesos</h6>
            <table class="table table-sm mb-0">
                <thead><tr><th>Entrada</th><th>Salida</th><th>Duración</th></tr></thead>
                <tbody>
                <?php foreach ($accesos as $a): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($a['fecha_hora_entrada'])) ?></td>
                    <td><?= $a['fecha_hora_salida'] ? date('d/m/Y H:i', strtotime($a['fecha_hora_salida'])) : '<span class="badge badge-activa">Dentro</span>' ?></td>
                    <td><?= $a['duracion_min'] ? $a['duracion_min'] . ' min' : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($accesos)): ?><tr><td colspan="3" class="text-muted text-center py-2">Sin accesos</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
