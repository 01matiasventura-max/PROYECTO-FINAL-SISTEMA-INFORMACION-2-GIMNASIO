<?php
require_once dirname(__DIR__) . '/includes/init.php';
requireLogin();
requireRole([1]); // Solo admin

$pdo = getPDO();

// Toggle activo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_activo'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $id = (int)($_POST['usuario_id'] ?? 0);
    // No permitir desactivar al propio usuario
    if ($id !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id]);
        flashSuccess('Estado del usuario actualizado.');
    } else {
        flashError('No puedes desactivarte a ti mismo.');
    }
    header('Location: ' . BASE_URL . '/usuarios/');
    exit;
}

// Búsqueda
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$usuarios = $pdo->prepare("
    SELECT u.id, u.nombre, u.apellido, u.email, u.telefono,
           u.activo, u.ultimo_login, u.cambiar_pass,
           r.nombre AS rol_nombre
    FROM usuarios u
    JOIN roles r ON u.rol_id = r.id
    $where
    ORDER BY u.apellido, u.nombre
");
$usuarios->execute($params);
$usuarios = $usuarios->fetchAll();

$breadcrumb = [['label' => 'Usuarios']];
$pageTitle = 'Gestión de Usuarios';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Usuarios del Sistema</h1>
    <a href="<?= BASE_URL ?>/usuarios/crear.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Nuevo Usuario
    </a>
</div>

<!-- Búsqueda -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Buscar usuario</label>
                <input type="text" name="q" class="form-control" placeholder="Nombre, apellido o email..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </div>
            <?php if ($search): ?>
            <div class="col-md-auto">
                <a href="<?= BASE_URL ?>/usuarios/" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times me-1"></i> Limpiar
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Último acceso</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></div>
                            <?php if ($u['cambiar_pass']): ?>
                                <span class="badge bg-warning text-dark" title="El usuario debe cambiar su contraseña">
                                    <i class="fas fa-key me-1"></i>Cambio requerido
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['telefono'] ?? '-') ?></td>
                        <td>
                            <?php
                            $rolClases = ['Administrador' => 'bg-danger', 'Recepcionista' => 'bg-primary', 'Instructor' => 'bg-success'];
                            $clase = $rolClases[$u['rol_nombre']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $clase ?>"><?= e($u['rol_nombre']) ?></span>
                        </td>
                        <td>
                            <?= $u['ultimo_login'] ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : '<span class="text-muted">Nunca</span>' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($u['activo']): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>/usuarios/editar.php?id=<?= $u['id'] ?>"
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="toggle_activo" value="1"
                                        class="btn btn-outline-<?= $u['activo'] ? 'warning' : 'success' ?>"
                                        title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>"
                                        onclick="return confirm('¿Confirmar cambio de estado?')">
                                        <i class="fas fa-<?= $u['activo'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-muted small">
        Total: <?= count($usuarios) ?> usuario(s)
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
