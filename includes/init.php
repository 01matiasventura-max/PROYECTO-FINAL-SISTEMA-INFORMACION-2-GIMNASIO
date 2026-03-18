<?php
// Detectar base URL automáticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = dirname($_SERVER['SCRIPT_NAME']);

// Subir hasta la raíz del proyecto
$parts = explode('/', trim($script, '/'));
$root  = '';
foreach ($parts as $p) {
    $root .= '/' . $p;
    if (strtolower($p) === 'proyecto final' || strtolower($p) === 'proyecto%20final') break;
}

define('BASE_URL', $protocol . '://' . $host . '/PROYECTO%20FINAL');
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/db.php';
require_once ROOT_PATH . '/includes/auth.php';

// Auto-vencer membresías cuya fecha_fin ya pasó
getPDO()->query("UPDATE membresias SET estado = 'vencida' WHERE estado = 'activa' AND fecha_fin < CURDATE()");
