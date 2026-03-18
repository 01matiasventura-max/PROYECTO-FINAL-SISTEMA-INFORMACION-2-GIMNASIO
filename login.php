<?php
require_once __DIR__ . '/includes/init.php';

// Siempre cerrar sesión activa al llegar al login
if (isLoggedIn()) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor completa todos los campos.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT u.*, r.nombre AS rol_nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.email = ? AND u.activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['rol_id']     = $user['rol_id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['apellido']   = $user['apellido'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['rol_nombre'] = $user['rol_nombre'];

            // Actualizar último login
            $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Fit Bull Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 44px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
        }
        .brand-icon {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #e94560, #c1121f);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff;
            margin: 0 auto 18px;
        }
        .btn-login {
            background: linear-gradient(135deg, #e94560, #c1121f);
            color: #fff;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: opacity .2s;
        }
        .btn-login:hover { opacity: .9; color: #fff; }
        .form-control:focus { border-color: #e94560; box-shadow: 0 0 0 .2rem rgba(233,69,96,.2); }

        /* Selector de rol */
        .role-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 2px solid #f0f0f0;
            border-radius: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all .2s;
            background: #fff;
        }
        .role-btn:hover {
            border-color: #e94560;
            background: #fff5f7;
            transform: translateX(4px);
        }
        .role-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: #fff;
            flex-shrink: 0;
        }
        .role-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            font-size: .85rem;
            margin-bottom: 16px;
        }
        .btn-back {
            background: none;
            border: none;
            color: #888;
            font-size: .82rem;
            padding: 0;
            margin-bottom: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .btn-back:hover { color: #e94560; }
        .login-card { max-width: 440px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="brand-icon"><i class="fa-solid fa-dumbbell"></i></div>
    <h4 class="text-center fw-bold mb-1">Fit Bull Center</h4>
    <p class="text-center text-muted mb-4" style="font-size:.88rem;">Sistema de Gestión de Gimnasio</p>

    <!-- Selector de rol -->
    <div class="role-selector" id="roleSelector">
        <p class="text-center fw-semibold mb-3" style="font-size:.9rem;color:#555;">¿Cómo deseas ingresar?</p>
        <div class="role-btn" onclick="selectRole('admin')">
            <div class="role-icon" style="background:linear-gradient(135deg,#e94560,#c1121f);">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <div class="fw-bold">Administrador</div>
                <div class="text-muted" style="font-size:.78rem;">Acceso total al sistema</div>
            </div>
            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
        </div>
        <div class="role-btn" onclick="selectRole('recepcionista')">
            <div class="role-icon" style="background:linear-gradient(135deg,#0ea5e9,#0369a1);">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <div class="fw-bold">Recepcionista</div>
                <div class="text-muted" style="font-size:.78rem;">Gestión de socios y accesos</div>
            </div>
            <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
        </div>
    </div>

    <!-- Formulario (oculto hasta elegir rol) -->
    <div id="loginForm" style="display:none;">
        <button type="button" class="btn-back" onclick="goBack()">
            <i class="fa-solid fa-arrow-left me-2"></i><span id="backLabel">Volver</span>
        </button>

        <div class="role-badge mb-3" id="roleBadge"></div>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" id="emailInput" class="form-control"
                           placeholder="usuario@gym.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePass(this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100 fs-6" id="submitBtn">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar Sesión
            </button>
        </form>
    </div>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:.78rem;">
        © <?= date('Y') ?> Fit Bull Center — Todos los derechos reservados
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const roles = {
    admin: {
        label: 'Administrador',
        color: 'linear-gradient(135deg,#e94560,#c1121f)',
        icon:  'fa-user-shield'
    },
    recepcionista: {
        label: 'Recepcionista',
        color: 'linear-gradient(135deg,#0ea5e9,#0369a1)',
        icon:  'fa-user-tie'
    }
};

function selectRole(key) {
    const r = roles[key];
    document.getElementById('roleSelector').style.display = 'none';
    document.getElementById('loginForm').style.display    = 'block';
    document.getElementById('backLabel').textContent = 'Cambiar tipo de acceso';

    const badge = document.getElementById('roleBadge');
    badge.innerHTML = `<i class="fa-solid ${r.icon} me-2"></i>${r.label}`;
    badge.style.background = r.color;

    document.getElementById('submitBtn').style.background = r.color;
    document.getElementById('emailInput').focus();
}

function goBack() {
    document.getElementById('loginForm').style.display    = 'none';
    document.getElementById('roleSelector').style.display = 'block';
}

function togglePass(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Si hubo error en POST, mostrar el formulario directamente
<?php if ($error): ?>
document.getElementById('roleSelector').style.display = 'none';
document.getElementById('loginForm').style.display    = 'block';
document.getElementById('roleBadge').style.display    = 'none';
document.getElementById('backLabel').textContent = 'Cambiar tipo de acceso';
<?php endif; ?>
</script>
</body>
</html>
