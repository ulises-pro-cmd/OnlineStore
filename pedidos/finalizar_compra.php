<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../index.html");
    exit();
}

include __DIR__ . "/../config/conexion.php";

$usuario_id = $_SESSION["usuario_id"];
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

if (empty($carrito) || $_SERVER["REQUEST_METHOD"] !== "POST") {
        header("Location: ../carrito.php");
    exit();
}

$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$metodo_pago = isset($_POST['metodo_pago']) ? trim($_POST['metodo_pago']) : 'Efectivo';
$notas     = $_POST['notas'] ?? '';

// 1. Verificar stock disponible para CADA producto antes de iniciar la compra
foreach ($carrito as $producto_id => $cantidad_carrito) {
    $stmt_check = $conexion->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
    $stmt_check->bind_param("i", $producto_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    $cant_validada = min(20, max(1, intval(is_array($cantidad_carrito) ? $cantidad_carrito['cantidad'] : $cantidad_carrito)));

    if (!$res_check || $res_check['stock'] < $cant_validada) {
        // Redirigir al carrito con error de stock
        header("Location: ../carrito.php?error=stock&prod=" . urlencode($res_check['nombre'] ?? ''));
        exit();
    }
}

// Obtener datos actualizados de los productos del carrito
$ids = array_keys($carrito);
$ids_string = implode(',', array_map('intval', $ids));

$query = "SELECT id, nombre, precio FROM productos WHERE id IN ($ids_string)";
$resultado = $conexion->query($query);

$subtotal = 0;
$detalles = [];

while ($producto = $resultado->fetch_assoc()) {
    $id = $producto['id'];
    // Manejar si el carrito guarda un número directo o un array con la cantidad
    $cant = min(20, max(1, intval(is_array($carrito[$id]) ? $carrito[$id]['cantidad'] : $carrito[$id])));
    $precio = floatval($producto['precio']);
    
    $subtotal += ($precio * $cant); // <- CORREGIDO (agregado el *)
    $detalles[] = [
        'producto_id' => $id,
        'cantidad' => $cant,
        'precio' => $precio
    ];
}

$iva = $subtotal * 0.05; // <- CORREGIDO (agregado el *)
$total_final = $subtotal + $iva;

$conexion->begin_transaction();

try {
    // 2. Guardar la venta principal
    $stmt_venta = $conexion->prepare("INSERT INTO ventas (usuario_id, total, direccion, telefono, metodo_pago, notas, estado) VALUES (?, ?, ?, ?, ?, ?, 'pendiente')");
    $stmt_venta->bind_param("idssss", $usuario_id, $total_final, $direccion, $telefono, $metodo_pago, $notas);
    $stmt_venta->execute();
    $venta_id = $stmt_venta->insert_id;
    $stmt_venta->close();

    // 3. Guardar detalles y actualizar stock en tiempo real
    $stmt_detalle = $conexion->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt_stock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($detalles as $det) {
        // Insertar detalle
        $stmt_detalle->bind_param("iiid", $venta_id, $det['producto_id'], $det['cantidad'], $det['precio']);
        $stmt_detalle->execute();

        // Descontar stock
        $stmt_stock->bind_param("ii", $det['cantidad'], $det['producto_id']);
        $stmt_stock->execute();
    }

    $stmt_detalle->close();
    $stmt_stock->close();

    // Confirmar transacción
    $conexion->commit();
    unset($_SESSION['carrito']);

    header("Location: ../confirmacion_compra.php?id=" . $venta_id);
    exit();

} catch (Exception $e) {
    // Si algo falla, revertir cambios
    $conexion->rollback();
    header("Location: ../carrito.php?error=1");
    exit();
}
?>
