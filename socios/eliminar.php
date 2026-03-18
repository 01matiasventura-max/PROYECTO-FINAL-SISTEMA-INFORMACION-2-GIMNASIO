<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$id    = (int)($_GET['id'] ?? 0);
$csrf  = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }

$pdo = getPDO();
$pdo->prepare("UPDATE socios SET activo = 0 WHERE id = ?")->execute([$id]);
flashSuccess('Socio desactivado correctamente.');
header('Location: index.php');
exit;
