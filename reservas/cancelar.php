<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$csrf = $_GET['csrf'] ?? '';
if (!verifyCsrfToken($csrf)) { flashError('Token inválido.'); header('Location: index.php'); exit; }
getPDO()->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?")->execute([$id]);
flashSuccess('Reserva cancelada.'); header('Location: index.php'); exit;
