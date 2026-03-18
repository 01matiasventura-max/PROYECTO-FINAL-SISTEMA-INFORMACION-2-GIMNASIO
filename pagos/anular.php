<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }
$pdo = getPDO();
$motivo = trim($_GET['motivo'] ?? 'Anulado por administrador');
$pdo->prepare("UPDATE pagos SET estado='anulado', motivo_anulacion=? WHERE id=?")->execute([$motivo, $id]);
flashSuccess('Pago anulado.');
header('Location: index.php'); exit;
