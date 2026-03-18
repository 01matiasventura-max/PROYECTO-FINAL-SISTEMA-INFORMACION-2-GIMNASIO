<?php
require_once dirname(__DIR__) . '/includes/init.php';
requireLogin();
requireRole([1]); // Solo admin

$pdo = getPDO();
$errors = [];
$data = [
    'nombre'     => '',
    'apellido'   => '',
    'email'      => '',
    'telefono'   => '',
    'rol_id'     => '',
    'activo'     => 1,
    'cambiar_pass' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $data['nombre']      = trim($_POST['nombre'] ?? '');
    $data['apellido']    = trim($_POST['apellido'] ?? '');
    $data['email']       = trim($_POST['email'] ?? '');
    $data['telefono']    = trim($_POST['telefono'] ?? '');
    $data['rol_id']      = (int)($_POST['rol_id'] ?? 0);
    $data['activo']      = isset($_POST['activo']) ? 1 : 0;
    $data['cambiar_pass'] = isset($_POST['cambiar_pass']) ? 1 : 0;
    $password            = $_POST['password'] ?? '';
    $password_confirm    = $_POST['password_confirm'] ?? '';

    // Validaciones
    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if ($data['apellido'] === '') $errors[] = 'El apellido es obligatorio.';
    if ($data['email'] === '') {
        $errors[] = 'El email es obligatorio.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no tiene un formato válido.';
    }
    if ($data['rol_id'] === 0) $errors[] = 'Debe seleccionar un rol.';
    if ($password === '') {
        $errors[] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    // Email único
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$data['email']]);
        if ($check->fetch()) {
            $errors[] = 'Ya existe un usuario con ese email.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, email, telefono, password_hash, rol_id, activo, cambiar_pass)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
        ]);
        flashSuccess('Usuario creado correctamente.');
        header('Location: ' . BASE_URL . '/usuarios/');
        exit;
    }
}

// Cargar roles
$roles = $pdo->query("SELECT id, nombre FROM roles ORDER BY nombre")->fetchAll();

$breadcrumb = [
    ['label' => 'Usuarios', 'url' => BASE_URL . '/usuarios/'],
    ['label' => 'Nuevo Usuario'],
];
$pageTitle = 'Nuevo Usuario';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Nuevo Usuario</h1>
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

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Datos del Usuario</h5>
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
                <!-- Contraseña -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control"
                               required minlength="8" autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePass('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <!-- Confirmar contraseña -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control"
                               required autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePass('password_confirm', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <!-- Opciones -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold d-block">Opciones</label>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo"
                               <?= $data['activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activo">Usuario activo</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="cambiar_pass" id="cambiar_pass"
                               <?= $data['cambiar_pass'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="cambiar_pass">
                            Requerir cambio de contraseña en primer inicio de sesión
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-1"></i> Crear Usuario
                </button>
                <a href="<?= BASE_URL ?>/usuarios/" class="btn btn-outline-secondary px-4">Cancelar</a>
            </div>
        </form>
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
