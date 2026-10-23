<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../index.html");
    exit();
}

include __DIR__ . "/../config/conexion.php";

$es_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["producto_id"])) {
    $producto_id = intval($_POST["producto_id"]);
    $cantidad = isset($_POST["cantidad"]) ? max(1, intval($_POST["cantidad"])) : 1;

    // Consultamos los datos completos del producto para la notificación (toast)
    $stmt = $conexion->prepare("SELECT id, nombre, precio, imagen, categoria, descripcion, stock FROM productos WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $cantidad_actual = isset($_SESSION['carrito'][$producto_id]) ? $_SESSION['carrito'][$producto_id] : 0;
        $nueva_cantidad = $cantidad_actual + $cantidad;

        if (intval($producto['stock']) <= 0 || $nueva_cantidad > intval($producto['stock'])) {
            // El stock se agotó o no alcanza para agregar otra unidad
            $toast_mensaje = [
                'tipo' => 'agotado',
                'producto' => [
                    'nombre' => $producto['nombre'],
                    'imagen' => $producto['imagen']
                ]
            ];
        } elseif ($nueva_cantidad > 20) {
            // Límite alcanzado
            $_SESSION['carrito'][$producto_id] = 20;

            $toast_mensaje = [
                'tipo' => 'limite',
                'producto' => [
                    'nombre' => $producto['nombre'],
                    'imagen' => $producto['imagen']
                ]
            ];
        } else {
            // Se agrega normalmente
            $_SESSION['carrito'][$producto_id] = $nueva_cantidad;

            $toast_mensaje = [
                'tipo' => 'exito',
                'producto' => [
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'imagen' => $producto['imagen'],
                    'categoria' => $producto['categoria'] ?? '',
                    'descripcion' => $producto['descripcion'] ?? ''
                ]
            ];
        }

        // Acumulamos las notificaciones para no perder avisos al agregar
        // varios productos antes de volver a mostrar la tienda.
        if (!$es_ajax) {
            if (!isset($_SESSION['toast_mensajes']) || !is_array($_SESSION['toast_mensajes'])) {
                $_SESSION['toast_mensajes'] = [];
            }
            $_SESSION['toast_mensajes'][] = $toast_mensaje;
        }
    }

    $stmt->close();
}

$conexion->close();

if ($es_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => isset($toast_mensaje),
        'mensaje' => $toast_mensaje ?? null,
        'cantidad_carrito' => isset($_SESSION['carrito']) ? array_sum($_SESSION['carrito']) : 0
    ]);
    exit();
}

// 1. Mantener categoría activa
$categoria = isset($_POST['categoria']) ? $_POST['categoria'] : '';
$url_redireccion = "../tienda.php" . (!empty($categoria) ? "?categoria=" . urlencode($categoria) : "");

// 2. Mantener la posición de la pantalla (Scroll)
if (isset($_POST['scroll_pos'])) {
    $url_redireccion .= (strpos($url_redireccion, '?') !== false ? '&' : '?') . "scroll=" . intval($_POST['scroll_pos']);
}

header("Location: " . $url_redireccion);
exit();
?>
