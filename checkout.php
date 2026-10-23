<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

include __DIR__ . "/config/conexion.php";

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

if (empty($carrito)) {
    header("Location: carrito.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Datos de Envío y Pago - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <div class="main-wrapper">
        <div class="confirmacion-box">
            <h2><i class="fa-solid fa-truck-fast"></i> Datos de Entrega y Pago</h2>
            <p>Por favor completa los siguientes datos para finalizar tu pedido.</p>

            <form action="pedidos/finalizar_compra.php" method="POST" class="form-checkout">
                <div class="form-group">
                    <label>Dirección de Envío:</label>
                    <input type="text" name="direccion" required placeholder="Calle, número, piso/depto">
                </div>

                <div class="form-group">
                    <label>Teléfono de Contacto:</label>
                    <input type="tel" name="telefono" required placeholder="Ej: 3755 123456">
                </div>

                <div class="form-group">
                    <label>Método de Pago:</label>
                    <select name="metodo_pago" required>
                        <option value="Efectivo en Entrega">Efectivo al recibir</option>
                        <option value="Transferencia Bancaria">Transferencia Bancaria / Mercado Pago</option>
                        <option value="Tarjeta de Crédito/Débito">Tarjeta de Crédito / Débito</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notas adicionales (Opcional):</label>
                    <textarea name="notas" rows="3" placeholder="Horario preferido de entrega, aclaraciones..."></textarea>
                </div>

                <div class="acciones-checkout">
                    <a href="carrito.php" class="btn-vaciar">Volver al Carrito</a>
                    <button type="submit" class="btn-finalizar">Confirmar y Comprar</button>
                </div>
            </form>
        </div>
    </div>

<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
