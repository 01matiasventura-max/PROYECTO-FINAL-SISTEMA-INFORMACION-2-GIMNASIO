<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$id   = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }
getPDO()->prepare("UPDATE mantenimientos SET fecha_cierre = CURDATE() WHERE id = ?")->execute([$id]);
flashSuccess('Mantenimiento cerrado. El equipo volvió a estado operativo gracias al trigger.');
header('Location: index.php'); exit;
