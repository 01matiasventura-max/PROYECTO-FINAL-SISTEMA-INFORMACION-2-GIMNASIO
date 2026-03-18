<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$factura = $pdo->prepare("
    SELECT f.*,
           p.concepto, p.monto AS pago_monto, p.metodo_pago, p.fecha_pago, p.estado AS pago_estado,
           s.nombre AS s_nombre, s.apellido AS s_apellido, s.numero_socio, s.email AS s_email,
           s.telefono AS s_telefono, s.direccion AS s_direccion,
           u.nombre AS u_nombre, u.apellido AS u_apellido
    FROM facturas f
    JOIN pagos p ON p.id = f.pago_id
    JOIN socios s ON s.id = p.socio_id
    LEFT JOIN usuarios u ON u.id = p.cobrado_por
    WHERE f.id = ?
");
$factura->execute([$id]);
$f = $factura->fetch();
if (!$f) { header('Location: index.php'); exit; }

$metodos = [
    'efectivo'       => 'Efectivo',
    'tarjeta_credito'=> 'Tarjeta de crédito',
    'tarjeta_debito' => 'Tarjeta de débito',
    'transferencia'  => 'Transferencia bancaria',
    'otro'           => 'Otro',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura <?= e($f['numero_factura']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6fb; }
        .factura-wrapper { max-width: 780px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.1); overflow: hidden; }
        .factura-header { background: linear-gradient(135deg,#1a1a2e,#16213e); color: #fff; padding: 32px 40px; }
        .factura-header h1 { font-size: 2rem; font-weight: 800; color: #e94560; margin: 0; letter-spacing: .04em; }
        .factura-header .subtitle { color: #adb5bd; font-size: .85rem; margin-top: 2px; }
        .factura-num { font-size: 1.1rem; font-weight: 700; color: #ffc107; }
        .factura-body { padding: 36px 40px; }
        .section-title { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #6c757d; margin-bottom: 8px; }
        .info-box { background: #f8f9fa; border-radius: 8px; padding: 16px 20px; }
        .table-factura thead { background: #1a1a2e; color: #fff; }
        .table-factura thead th { font-weight: 600; font-size: .85rem; padding: 10px 14px; }
        .table-factura tbody td { padding: 12px 14px; font-size: .9rem; }
        .total-row { background: #fff8e1; font-weight: 700; font-size: 1rem; }
        .badge-metodo { background: #e3f2fd; color: #0d47a1; font-size: .8rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; }
        .stamp { border: 3px solid #28a745; color: #28a745; border-radius: 8px; padding: 6px 16px; font-size: .85rem; font-weight: 700; display: inline-block; transform: rotate(-4deg); }
        .factura-footer { border-top: 1px solid #e9ecef; padding: 18px 40px; background: #f8f9fa; font-size: .8rem; color: #6c757d; }
        @media print {
            body { background: #fff; }
            .factura-wrapper { box-shadow: none; margin: 0; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<!-- Barra de acciones (no se imprime) -->
<div class="no-print d-flex justify-content-between align-items-center px-4 py-2" style="background:#1a1a2e;">
    <a href="index.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-arrow-left me-1"></i>Volver</a>
    <button onclick="window.print()" class="btn btn-sm btn-warning"><i class="fa-solid fa-print me-1"></i>Imprimir / PDF</button>
</div>

<div class="factura-wrapper">
    <!-- Encabezado -->
    <div class="factura-header d-flex justify-content-between align-items-start">
        <div>
            <h1><i class="fa-solid fa-dumbbell me-2"></i>Fit Bull Center</h1>
            <div class="subtitle">Sistema de Gestión de Gimnasio</div>
            <div class="subtitle mt-1"><i class="fa-solid fa-envelope me-1"></i>contacto@gimnasio.com</div>
        </div>
        <div class="text-end">
            <div style="font-size:.72rem;color:#adb5bd;text-transform:uppercase;letter-spacing:.1em;">Factura</div>
            <div class="factura-num"><?= e($f['numero_factura']) ?></div>
            <div style="color:#adb5bd;font-size:.82rem;margin-top:4px;">
                Emitida: <?= date('d/m/Y H:i', strtotime($f['fecha_emision'])) ?>
            </div>
        </div>
    </div>

    <div class="factura-body">
        <!-- Datos del cliente y pago -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="section-title"><i class="fa-solid fa-user me-1"></i>Cliente</div>
                <div class="info-box">
                    <div class="fw-bold fs-6"><?= e($f['s_nombre'] . ' ' . $f['s_apellido']) ?></div>
                    <div class="text-muted small">Nº Socio: <strong><?= e($f['numero_socio']) ?></strong></div>
                    <?php if ($f['s_email']): ?>
                    <div class="text-muted small mt-1"><i class="fa-solid fa-envelope me-1"></i><?= e($f['s_email']) ?></div>
                    <?php endif; ?>
                    <?php if ($f['s_telefono']): ?>
                    <div class="text-muted small"><i class="fa-solid fa-phone me-1"></i><?= e($f['s_telefono']) ?></div>
                    <?php endif; ?>
                    <?php if ($f['s_direccion']): ?>
                    <div class="text-muted small"><i class="fa-solid fa-location-dot me-1"></i><?= e($f['s_direccion']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-title"><i class="fa-solid fa-receipt me-1"></i>Detalles del pago</div>
                <div class="info-box">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Fecha de pago:</span>
                        <span class="small fw-semibold"><?= date('d/m/Y H:i', strtotime($f['fecha_pago'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Método:</span>
                        <span class="badge-metodo"><?= e($metodos[$f['metodo_pago']] ?? $f['metodo_pago']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Cobrado por:</span>
                        <span class="small fw-semibold"><?= $f['u_nombre'] ? e($f['u_nombre'] . ' ' . $f['u_apellido']) : '—' ?></span>
                    </div>
                    <div class="d-flex justify-content-between mt-2 pt-2" style="border-top:1px solid #dee2e6;">
                        <span class="text-muted small">Estado:</span>
                        <span class="stamp">PAGADO</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de conceptos -->
        <div class="section-title mb-2"><i class="fa-solid fa-list me-1"></i>Detalle</div>
        <table class="table table-factura table-bordered mb-4">
            <thead>
                <tr>
                    <th style="width:60%;">Descripción</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-center">IVA</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e($f['concepto']) ?></td>
                    <td class="text-end">$<?= number_format($f['subtotal'], 2) ?></td>
                    <td class="text-center">
                        <?= $f['impuesto_pct'] > 0 ? number_format($f['impuesto_pct'], 1) . '%' : '0%' ?>
                    </td>
                    <td class="text-end fw-bold">$<?= number_format($f['total'], 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end text-muted small">Subtotal:</td>
                    <td colspan="2" class="text-end">$<?= number_format($f['subtotal'], 2) ?></td>
                </tr>
                <?php if ($f['impuesto_pct'] > 0): ?>
                <tr>
                    <td colspan="2" class="text-end text-muted small">IVA (<?= number_format($f['impuesto_pct'], 1) ?>%):</td>
                    <td colspan="2" class="text-end">$<?= number_format($f['total'] - $f['subtotal'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="2" class="text-end">TOTAL:</td>
                    <td colspan="2" class="text-end text-success fs-5">$<?= number_format($f['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Nota -->
        <div class="text-center text-muted" style="font-size:.8rem;">
            <i class="fa-solid fa-circle-check text-success me-1"></i>
            Esta factura fue generada automáticamente por el sistema.
        </div>
    </div>

    <div class="factura-footer text-center">
        Gracias por su preferencia · Fit Bull Center &copy; <?= date('Y') ?>
    </div>
</div>

</body>
</html>
