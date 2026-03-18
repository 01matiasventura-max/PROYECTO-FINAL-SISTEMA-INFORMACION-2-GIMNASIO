<?php
require_once dirname(__DIR__) . '/includes/init.php';
requireLogin();
requireRole([1]); // Solo admin

$pdo = getPDO();
$errors = [];

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . BASE_URL . '/usuarios/');
    exit;
}

// Cargar usuario
$usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$usuario->execute([$id]);
$usuario = $usuario->fetch();
if (!$usuario) {
    flashError('Usuario no encontrado.');
    header('Location: ' . BASE_URL . '/usuarios/');
    exit;
}

$data = [
    'nombre'      => $usuario['nombre'],
    'apellido'    => $usuario['apellido'],
    'email'       => $usuario['email'],
    'telefono'    => $usuario['telefono'] ?? '',
    'rol_id'      => $usuario['rol_id'],
    'activo'      => $usuario['activo'],
    'cambiar_pass' => $usuario['cambiar_pass'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $data['nombre']       = trim($_POST['nombre'] ?? '');
    $data['apellido']     = trim($_POST['apellido'] ?? '');
    $data['email']        = trim($_POST['email'] ?? '');
    $data['telefono']     = trim($_POST['telefono'] ?? '');
    $data['rol_id']       = (int)($_POST['rol_id'] ?? 0);
    $data['activo']       = isset($_POST['activo']) ? 1 : 0;
    $data['cambiar_pass'] = isset($_POST['cambiar_pass']) ? 1 : 0;
    $password             = $_POST['password'] ?? '';
    $password_confirm     = $_POST['password_confirm'] ?? '';

    // Validaciones
    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if ($data['apellido'] === '') $errors[] = 'El apellido es obligatorio.';
    if ($data['email'] === '') {
        $errors[] = 'El email es obligatorio.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no tiene un formato válido.';
    }
    if ($data['rol_id'] === 0) $errors[] = 'Debe seleccionar un rol.';

    // Contraseña opcional al editar
    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $password_confirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
    }

    // No se puede desactivar al propio usuario administrador
    if ($id === (int)$_SESSION['user_id'] && !$data['activo']) {
        $errors[] = 'No puedes desactivar tu propia cuenta.';
    }

    // Email único (excluyendo el propio usuario)
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $check->execute([$data['email'], $id]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe otro usuario con ese email.';
        }
    }

    if (empty($errors)) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nombre=?, apellido=?, email=?, telefono=?,
                    password_hash=?, rol_id=?, activo=?, cambiar_pass=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                $data['telefono'] !== '' ? $data['telefono'] : null,
                $hash,
                $data['rol_id'],
                $data['activo'],
                $data['cambiar_pass'],
                $id,
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nombre=?, apellido=?, email=?, telefono=?,
                    rol_id=?, activo=?, cambiar_pass=?
                WHERE id=?
            ");
            $stmt->execute([
                $data['nombre'],
                $data['apellido'],
                $data['email'],
                $data['telefono'] !== '' ? $data['telefono'] : null,
                $data['rol_id'],
                $data['activo'],
                $data['cambiar_pass'],
                $id,
            ]);
        }
        flashSuccess('Usuario actualizado correctamente.');
        header('Location: ' . BASE_URL . '/usuarios/');
        exit;
    }
}

// Cargar roles
$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY nombre")->fetchAll();

$breadcrumb = [
    ['label' => 'Usuarios', 'url' => BASE_URL . '/usuarios/'],
    ['label' => 'Editar Usuario'],
];
$pageTitle = 'Editar Usuario';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Editar Usuario</h1>
    <a href="<?= BASE_URL ?>/usuarios/" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulario principal -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    <?= e($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="row g-3">
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control"
                                   value="<?= e($data['nombre']) ?>" required maxlength="100">
                        </div>
                        <!-- Apellido -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellido <span class="text-danger">*</span></label>
                            <input type="text" name="apellido" class="form-control"
                                   value="<?= e($data['apellido']) ?>" required maxlength="100">
                        </div>
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= e($data['email']) ?>" required maxlength="150">
                        </div>
                        <!-- Teléfono -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control"
                                   value="<?= e($data['telefono']) ?>" maxlength="20">
                        </div>
                        <!-- Rol -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select name="rol_id" class="form-select" required>
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id'] ?>" <?= $data['rol_id'] == $rol['id'] ? 'selected' : '' ?>>
                                    <?= e($rol['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Opciones -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-block">Opciones</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="activo" id="activo"
                                       <?= $data['activo'] ? 'checked' : '' ?>
                                       <?= $id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="activo">Usuario activo</label>
                                <?php if ($id === (int)$_SESSION['user_id']): ?>
                                    <input type="hidden" name="activo" value="1">
                                    <div class="form-text text-muted">No puedes desactivar tu propia cuenta.</div>
                                <?php endif; ?>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cambiar_pass" id="cambiar_pass"
                                       <?= $data['cambiar_pass'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cambiar_pass">
                                    Requerir cambio de contraseña
                                </label>
                            </div>
                        </div>

                        <!-- Contraseña (opcional al editar) -->
                        <div class="col-12">
                            <hr>
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-lock me-1"></i>
                                Cambiar Contraseña <small class="fw-normal">(dejar en blanco para mantener la actual)</small>
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nueva Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control"
                                       minlength="8" autocomplete="new-password"
                                       placeholder="Mínimo 8 caracteres">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmar Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password_confirm" id="password_confirm" class="form-control"
                                       autocomplete="new-password" placeholder="Repetir contraseña">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('password_confirm', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Guardar Cambios
                        </button>
                        <a href="<?= BASE_URL ?>/usuarios/" class="btn btn-outline-secondary px-4">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info lateral -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">ID</dt>
                    <dd class="col-sm-7"><?= $usuario['id'] ?></dd>
                    <dt class="col-sm-5">Registrado</dt>
                    <dd class="col-sm-7">
                        <?= $usuario['created_at'] ?? date('d/m/Y') ?>
                    </dd>
                    <dt class="col-sm-5">Último acceso</dt>
                    <dd class="col-sm-7">
                        <?= $usuario['ultimo_login']
                            ? date('d/m/Y H:i', strtotime($usuario['ultimo_login']))
                            : '<span class="text-muted">Nunca</span>' ?>
                    </dd>
                    <dt class="col-sm-5">Cambio pass</dt>
                    <dd class="col-sm-7">
                        <?php if ($usuario['cambiar_pass']): ?>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        <?php else: ?>
                            <span class="badge bg-success">No requerido</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>

        <?php if ($id === (int)$_SESSION['user_id']): ?>
        <div class="alert alert-info mt-3">
            <i class="fas fa-user-circle me-2"></i>
            Estás editando tu propia cuenta.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
