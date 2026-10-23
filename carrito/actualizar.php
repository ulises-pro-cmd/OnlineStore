<?php
session_start();

include __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['producto_id'])) {
    $id = intval($_POST['producto_id']);
    $cantidad_solicitada = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

    // Forzar que la cantidad esté entre 1 y 20
    $cantidad = min(20, max(1, $cantidad_solicitada));

    $stmt = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto || $cantidad > intval($producto['stock'])) {
        $nombre_producto = $producto['nombre'] ?? 'este producto';
        $conexion->close();
        header("Location: ../carrito.php?error=stock&prod=" . urlencode($nombre_producto));
        exit();
    }

    // Guardar en la sesión
    $_SESSION['carrito'][$id] = $cantidad;
}

$conexion->close();

// Redirigir de nuevo al carrito
header("Location: ../carrito.php");
exit();
?>
