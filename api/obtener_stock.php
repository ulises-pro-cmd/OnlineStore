<?php
header('Content-Type: application/json');
include __DIR__ . "/../config/conexion.php";

$sql = "SELECT id, nombre, stock FROM productos";
$resultado = $conexion->query($sql);

$productos = [];
while ($row = $resultado->fetch_assoc()) {
    $productos[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'stock' => intval($row['stock'])
    ];
}

echo json_encode($productos);
