<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();

}

// Bloquear el acceso si el usuario es administrador
if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin") {
    // Redirigir al panel de administración o a la tienda
    header("Location: tienda.php");
    exit();
}


include __DIR__ . "/config/conexion.php";

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$productos_carrito = [];
$total_compra = 0;

if (!empty($_SESSION['carrito'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['carrito'])));
    $sql = "SELECT * FROM productos WHERE id IN ($ids)";
    $resultado = $conexion->query($sql);

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $id = $row['id'];
            $row['cantidad'] = $_SESSION['carrito'][$id];
            $row['subtotal'] = $row['precio'] * $row['cantidad'];
            $total_compra += $row['subtotal'];
            $productos_carrito[] = $row;
        }
    }
}
?>

<?php
// Supongamos que aquí obtienes los productos del carrito desde la BD en un array $productos_carrito:

$subtotal_general = 0;

foreach ($productos_carrito as $item) {
    $subtotal_producto = $item['precio'] * $item['cantidad'];
    $subtotal_general += $subtotal_producto;
}

// Cálculo de IVA (5%) y Total final
$iva = $subtotal_general * 0.05;
$total_final = $subtotal_general + $iva;

// Lógica de compra mínima
$monto_minimo = 15000;
$faltante = $monto_minimo - $total_final;
$cumple_minimo = ($total_final >= $monto_minimo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <!-- Encabezado Superior -->
    <header class="store-nav">
        <div class="logo">
            <h1><span>Online</span><strong>Store</strong></h1>
        </div>
        <div class="nav-user">
            <!-- Botón Volver a la Tienda destacado -->
            <a href="tienda.php" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Tienda
            </a>

            <span>Hola, <strong><?php echo htmlspecialchars($_SESSION["nombre"]); ?></strong></span>

            <a href="cerrar_sesion.php" class="btn-salir" title="Cerrar sesión">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="cart-container">
            <h2><i class="fa-solid fa-cart-shopping"></i> Tu Carrito de Compras</h2>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
                <div style="background-color: #fdecea; color: #b42318; border: 1px solid #f5c2c0; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; text-align: center;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    No puedes agregar más unidades de <strong><?php echo htmlspecialchars($_GET['prod'] ?? 'este producto'); ?></strong>: el producto está agotado o no hay suficiente stock.
                </div>
            <?php endif; ?>

            <?php if (!empty($productos_carrito)): ?>
                
                <!-- BANNER LLAMATIVO DE COMPRA MÍNIMA -->
                <div class="banner-minimo <?php echo $cumple_minimo ? 'banner-exito' : 'banner-alerta'; ?>">
                    <?php if (!$cumple_minimo): ?>
                        <i class="fa-solid fa-circle-exclamation icon-banner"></i>
                        <div>
                            <strong>La compra mínima es de $<?php echo number_format($monto_minimo, 2, ',', '.'); ?>.</strong>
                            <span>Te faltan <strong>$<?php echo number_format($faltante, 2, ',', '.'); ?></strong> para alcanzar la compra mínima requerida.</span>
                        </div>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-check icon-banner"></i>
                        <div>
                            <strong>¡Alcanzaste la compra mínima!</strong>
                            <span>Ya puedes finalizar tu compra o seguir comprando.</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="cart-layout">
                    
                    <!-- Tabla de Productos -->
                    <div class="cart-table-wrapper">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Productos</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos_carrito as $item): ?>
                                    <tr>
                                        <td class="cart-product-info">
                                            <img src="uploads/<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                                <span class="badge-cat"><?php echo htmlspecialchars($item['categoria']); ?></span>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($item['precio'], 2, ',', '.'); ?></td>
                                        <td>
                                            <!-- Selector de cantidad con botones - y + -->
                                            <form action="carrito/actualizar.php" method="POST" class="qty-form" data-stock="<?php echo intval($item['stock']); ?>">
                                                <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                                <div class="qty-control">
                                                    <button type="button" class="btn-qty btn-minus" onclick="cambiarCantidad(this, -1)">
                                                        <i class="fa-solid fa-minus"></i>
                                                    </button>
                                                    <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" max="20" readonly class="input-qty">
                                                    <button type="button" class="btn-qty btn-plus" onclick="cambiarCantidad(this, 1)">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="subtotal">$<?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                                        <td>
                                            <a href="carrito/eliminar.php?id=<?php echo $item['id']; ?>" class="btn-remove" title="Eliminar del carrito">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumen de Pedido -->
                    <div class="resumen-pedido">
                        <h3>Resumen del Pedido</h3>
                        
                        <!-- Lista detallada de ítems en el resumen -->
                        <div class="resumen-productos">
                            <?php foreach ($productos_carrito as $item): ?>
                                <div class="resumen-item">
                                    <div class="resumen-item-info">
                                        <span class="resumen-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                        <span class="resumen-cantidad-precio">
                                            x<?php echo $item['cantidad']; ?> ($<?php echo number_format($item['precio'], 2, ',', '.'); ?> c/u)
                                        </span>
                                    </div>
                                    <span class="resumen-subtotal-item">
                                        $<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="resumen-divider">

                        <!-- Desglose de totales -->
                        <div class="resumen-linea">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($subtotal_general, 2, ',', '.'); ?></span>
                        </div>

                        <div class="resumen-linea">
                            <span>IVA (5%)</span>
                            <span>$<?php echo number_format($iva, 2, ',', '.'); ?></span>
                        </div>

                        <div class="resumen-linea">
                            <span>Envío</span>
                            <span class="envio-gratis">¡Gratis!</span>
                        </div>

                        <hr class="resumen-divider">

                        <div class="resumen-linea total-linea">
                            <strong>Total:</strong>
                            <strong>$<?php echo number_format($total_final, 2, ',', '.'); ?></strong>
                        </div>

                        <!-- Botones de acción -->
                        <a href="checkout.php" class="btn-finalizar <?php echo !$cumple_minimo ? 'disabled' : ''; ?>" <?php echo !$cumple_minimo ? 'style="pointer-events: none; opacity: 0.6;"' : ''; ?>>
                            <i class="fa-solid fa-credit-card"></i> Continuar Compra
                        </a>

                        <form action="carrito/vaciar.php" method="POST" onsubmit="return confirm('¿Deseas vaciar todo el carrito?');">
                            <button type="submit" class="btn-vaciar">
                                <i class="fa-solid fa-trash"></i> Vaciar Carrito
                            </button>
                        </form>
                    </div>

                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fa-solid fa-basket-shopping empty-icon"></i>
                    <h3>Tu carrito está vacío</h3>
                    <p>Parece que aún no has agregado ningún producto a tu carrito de compras.</p>
                    <a href="tienda.php" class="btn-start-shopping">Explorar Productos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script JS para controlar los botones - y + con límite 20 -->
    <script>
        function cambiarCantidad(btn, cambio) {
            const form = btn.closest('form');
            const input = form.querySelector('.input-qty');
            let valorActual = parseInt(input.value) || 1;
            let nuevoValor = valorActual + cambio;

            const stockDisponible = parseInt(form.dataset.stock) || 0;
            if (cambio > 0 && nuevoValor > stockDisponible) {
                alert('Este producto está agotado o no hay suficiente stock disponible.');
                return;
            }

            // Validar límites entre 1 y 20
            if (nuevoValor >= 1 && nuevoValor <= 20) {
                input.value = nuevoValor;
                form.submit(); // Envía el formulario automáticamente
            }
        }
    </script>

    <script>
// 1. Guardar la posición exacta justo antes de que la página se recargue por cualquier motivo
window.addEventListener('beforeunload', function() {
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    sessionStorage.setItem('carritoScroll', scrollY);
});

// 2. Restaurar el scroll inmediatamente cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const scrollPos = sessionStorage.getItem('carritoScroll');
    if (scrollPos !== null) {
        window.scrollTo({
            top: parseInt(scrollPos),
            behavior: 'instant'
        });
        sessionStorage.removeItem('carritoScroll');
    }
});
</script>

</body>
</html>

<style>
/* Tarjeta principal del Resumen de Compra */
.resumen-pedido {
    background-color: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.resumen-pedido h3 {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 1.25rem;
    color: #2b303a;
}

/* Detalle de productos en la tarjeta */
.resumen-productos {
    max-height: 180px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-right: 4px;
}

.resumen-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.resumen-item-info {
    display: flex;
    flex-direction: column;
}

.resumen-nombre {
    font-weight: 600;
    color: #333333;
}

.resumen-cantidad-precio {
    font-size: 0.8rem;
    color: #666666;
}

.resumen-subtotal-item {
    font-weight: 600;
    color: #444444;
}

/* Líneas divisoras y filas de totales */
.resumen-divider {
    border: none;
    border-top: 1px solid #eeeeee;
    margin: 6px 0;
}

.resumen-linea {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.95rem;
    color: #555555;
}

.envio-gratis {
    color: #27ae60;
    font-weight: bold;
}

.total-linea {
    font-size: 1.2rem;
    color: #111827;
}

/* Botón Finalizar Compra */
.btn-finalizar {
    width: 100%;
    background-color: #27ae60;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background-color 0.2s ease, transform 0.1s ease;
    margin-top: 8px;
}

.btn-finalizar:hover {
    background-color: #219150;
}

.btn-finalizar:active {
    transform: scale(0.99);
}

/* Botón Vaciar Carrito */
.btn-vaciar {
    width: 100%;
    background-color: transparent;
    color: #e74c3c;
    border: 1px solid #e74c3c;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.btn-vaciar:hover {
    background-color: #e74c3c;
    color: #ffffff;
}
</style>

<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
