<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
requireRole([1]);
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }
getPDO()->prepare("DELETE FROM horarios WHERE id = ?")->execute([$id]);
flashSuccess('Horario eliminado.'); header('Location: index.php'); exit;
