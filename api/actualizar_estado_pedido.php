<?php
session_start();
include __DIR__ . "/../config/conexion.php";

if (isset($_POST['venta_id']) && isset($_POST['estado'])) {
    $venta_id = intval($_POST['venta_id']);
    $estado = $_POST['estado'];

    $stmt = $conexion->prepare("UPDATE ventas SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $estado, $venta_id);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
}
