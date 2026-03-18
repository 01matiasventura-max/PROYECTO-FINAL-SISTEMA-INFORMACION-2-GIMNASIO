<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

header('Content-Type: application/json');

$socioId = (int)($_GET['socio_id'] ?? 0);
if ($socioId <= 0) {
    echo json_encode(['error' => 'ID de socio inválido.']);
    exit;
}

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("
        SELECT m.id,
               p.nombre,
               DATE_FORMAT(m.fecha_fin, '%d/%m/%Y') AS fecha_fin,
               p.precio
        FROM membresias m
        JOIN planes p ON p.id = m.plan_id
        WHERE m.socio_id = ? AND m.estado = 'activa'
        ORDER BY m.fecha_fin ASC
    ");
    $stmt->execute([$socioId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar membresías.']);
}
