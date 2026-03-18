<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Fit Bull Center') ?> | Fit Bull Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #1a1a2e;
            --sidebar-accent: #e94560;
            --sidebar-text: #a8b2c1;
            --sidebar-hover: rgba(233,69,96,0.15);
            --sidebar-width: 260px;
        }
        body { background: #f4f6fb; font-family: 'Segoe UI', sans-serif; }
        /* ── Sidebar ── */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .25s;
            overflow-y: auto;
        }
        #sidebar .brand {
            padding: 20px 24px 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        #sidebar .brand h4 {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
        }
        #sidebar .brand span { color: var(--sidebar-accent); }
        #sidebar .nav-label {
            color: rgba(168,178,193,.45);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 18px 24px 6px;
        }
        #sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 9px 24px;
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: .88rem;
            border-left: 3px solid transparent;
            transition: all .18s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #fff;
            background: var(--sidebar-hover);
            border-left-color: var(--sidebar-accent);
        }
        #sidebar .nav-link i { width: 18px; text-align: center; }
        /* ── Main ── */
        #main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e5e9f0;
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 500;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .page-content { padding: 28px; flex: 1; }
        /* Cards */
        .stat-card {
            border-radius: 12px;
            border: none;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
        }
        .stat-icon {
            width: 54px; height: 54px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .table-card { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06); border:none; }
        .table thead th { background:#f8f9fb; font-size:.78rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#6b7280; border-bottom-width:1px; }
        .badge-activa   { background:#d1fae5; color:#065f46; }
        .badge-vencida  { background:#fee2e2; color:#991b1b; }
        .badge-cancelada{ background:#f3f4f6; color:#374151; }
        .badge-suspendida{ background:#fef3c7; color:#92400e; }
        .badge-operativo  { background:#d1fae5; color:#065f46; }
        .badge-mantenimiento { background:#fef3c7; color:#92400e; }
        .badge-baja       { background:#fee2e2; color:#991b1b; }
        /* Responsive */
        @media(max-width:768px){
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #main { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php
$flash = getFlash();
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
function navActive(string $dir, string $file = 'index.php'): string {
    global $currentDir, $currentFile;
    if ($dir === 'root') return ($currentFile === $file && $currentDir !== basename(dirname(dirname($_SERVER['PHP_SELF'])))) ? 'active' : '';
    return ($currentDir === $dir) ? 'active' : '';
}
?>
<!-- SIDEBAR -->
<nav id="sidebar">
    <div class="brand">
        <h4><i class="fa-solid fa-dumbbell" style="color:var(--sidebar-accent)"></i> Fit Bull<span> Center</span></h4>
        <small style="color:var(--sidebar-text);font-size:.75rem;">
            <?= e($_SESSION['nombre'] ?? '') ?> <?= e($_SESSION['apellido'] ?? '') ?>
            <span class="badge ms-1" style="background:var(--sidebar-accent);font-size:.65rem;"><?= e($_SESSION['rol_nombre'] ?? '') ?></span>
        </small>
    </div>

    <div class="nav-label">Principal</div>
    <a href="<?= BASE_URL ?>/index.php" class="nav-link <?= ($currentFile==='index.php' && $currentDir==='PROYECTO FINAL') ? 'active':'' ?>">
        <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="nav-label">Socios</div>
    <a href="<?= BASE_URL ?>/socios/index.php" class="nav-link <?= navActive('socios') ?>">
        <i class="fa-solid fa-users"></i> Socios
    </a>
    <a href="<?= BASE_URL ?>/membresias/index.php" class="nav-link <?= navActive('membresias') ?>">
        <i class="fa-solid fa-id-card"></i> Membresías
    </a>
    <a href="<?= BASE_URL ?>/pagos/index.php" class="nav-link <?= navActive('pagos') ?>">
        <i class="fa-solid fa-money-bill-wave"></i> Pagos
    </a>
    <a href="<?= BASE_URL ?>/accesos/index.php" class="nav-link <?= navActive('accesos') ?>">
        <i class="fa-solid fa-door-open"></i> Accesos
    </a>
    <a href="<?= BASE_URL ?>/reservas/index.php" class="nav-link <?= navActive('reservas') ?>">
        <i class="fa-solid fa-calendar-check"></i> Reservas
    </a>

    <div class="nav-label">Operaciones</div>
    <a href="<?= BASE_URL ?>/planes/index.php" class="nav-link <?= navActive('planes') ?>">
        <i class="fa-solid fa-list-check"></i> Planes
    </a>
    <a href="<?= BASE_URL ?>/clases/index.php" class="nav-link <?= navActive('clases') ?>">
        <i class="fa-solid fa-person-running"></i> Clases
    </a>
    <a href="<?= BASE_URL ?>/horarios/index.php" class="nav-link <?= navActive('horarios') ?>">
        <i class="fa-solid fa-clock"></i> Horarios
    </a>

    <?php if (isAdmin()): ?>
    <div class="nav-label">Recursos</div>
    <a href="<?= BASE_URL ?>/empleados/index.php" class="nav-link <?= navActive('empleados') ?>">
        <i class="fa-solid fa-user-tie"></i> Empleados
    </a>
    <a href="<?= BASE_URL ?>/equipos/index.php" class="nav-link <?= navActive('equipos') ?>">
        <i class="fa-solid fa-wrench"></i> Equipos
    </a>
    <a href="<?= BASE_URL ?>/mantenimientos/index.php" class="nav-link <?= navActive('mantenimientos') ?>">
        <i class="fa-solid fa-screwdriver-wrench"></i> Mantenimientos
    </a>
    <?php endif; ?>

    <?php if ((int)($_SESSION['rol_id'] ?? 0) === 1): ?>
    <div style="margin:12px 16px 6px;border-top:1px solid rgba(233,69,96,.4);"></div>
    <div style="display:flex;align-items:center;gap:8px;padding:8px 20px 4px;">
        <i class="fa-solid fa-shield-halved" style="color:#e94560;font-size:.8rem;"></i>
        <span style="color:#e94560;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">Administración</span>
    </div>
    <a href="<?= BASE_URL ?>/usuarios/index.php"
       class="nav-link <?= navActive('usuarios') ?>"
       style="margin:2px 12px 8px;border-radius:8px;border-left:none;background:rgba(233,69,96,.12);color:#ffb3be;">
        <i class="fa-solid fa-user-shield" style="color:#e94560;"></i>
        <span>Usuarios del Sistema</span>
        <i class="fa-solid fa-chevron-right" style="margin-left:auto;font-size:.65rem;opacity:.6;"></i>
    </a>
    <?php endif; ?>

    <div style="padding:20px 24px;">
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm w-100" style="background:var(--sidebar-accent);color:#fff;">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
        </a>
    </div>
</nav>

<!-- MAIN -->
<div id="main">
    <div id="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="fa-solid fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0" style="font-size:.82rem;">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Inicio</a></li>
                    <?php if (!empty($breadcrumb)): foreach ($breadcrumb as $b): ?>
                    <li class="breadcrumb-item <?= isset($b['active']) ? 'active':'' ?>">
                        <?= isset($b['url']) ? '<a href="'.e($b['url']).'" class="text-decoration-none">'.e($b['label']).'</a>' : e($b['label']) ?>
                    </li>
                    <?php endforeach; endif; ?>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:.82rem;"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <div class="page-content">
        <?php if (!empty($flash['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i><?= e($flash['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (!empty($flash['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($flash['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
