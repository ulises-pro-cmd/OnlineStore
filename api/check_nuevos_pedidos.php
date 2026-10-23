<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    echo json_json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

include __DIR__ . "/../config/conexion.php";

// Contar pedidos pendientes/no vistos
$sql = "SELECT COUNT(*) AS total FROM ventas WHERE estado = 'pendiente'";
$resultado = $conexion->query($sql);
$fila = $resultado->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'nuevos' => intval($fila['total'])
]);
