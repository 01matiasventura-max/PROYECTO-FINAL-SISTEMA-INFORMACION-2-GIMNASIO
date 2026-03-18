<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }
getPDO()->prepare("UPDATE equipos SET estado='baja', fecha_baja=CURDATE(), motivo_baja='Dado de baja desde el sistema' WHERE id=?")->execute([$id]);
flashSuccess('Equipo dado de baja.'); header('Location: index.php'); exit;
