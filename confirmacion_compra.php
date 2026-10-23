<?php
session_start();

if (!isset($_SESSION["usuario_id"]) || !isset($_GET['id'])) {
    header("Location: tienda.php");
    exit();
}

include __DIR__ . "/config/conexion.php";

$venta_id = intval($_GET['id']);
$usuario_id = $_SESSION["usuario_id"];

// Consultar los datos de la venta asegurando que pertenezca al usuario logueado
$stmt = $conexion->prepare("SELECT * FROM ventas WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $venta_id, $usuario_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    header("Location: tienda.php");
    exit();
}

// Consultar los detalles de la venta
$query_detalles = "SELECT d.*, p.nombre, p.imagen, p.categoria 
                  FROM detalle_ventas d 
                  JOIN productos p ON d.producto_id = p.id 
                  WHERE d.venta_id = $venta_id";
$resultado_detalles = $conexion->query($query_detalles);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Compra Confirmada! - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <div class="main-wrapper">
        <div class="confirmacion-box">
            <div class="confirmacion-header">
                <i class="fa-solid fa-circle-check icon-exito"></i>
                <h2>¡Gracias por tu compra, <?php echo htmlspecialchars($_SESSION["nombre"] ?? "Cliente"); ?>!</h2>
                <p>Tu pedido <strong>#<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></strong> se ha procesado con éxito.</p>
                <span class="fecha-compra"><i class="fa-regular fa-calendar"></i> <?php echo date("d/m/Y H:i", strtotime($venta['fecha'])); ?> hs</span>
            </div>

            <div class="resumen-confirmacion">
                <h3>Detalle de tu Pedido</h3>
                <div class="lista-detalles">
                    <?php while ($item = $resultado_detalles->fetch_assoc()): ?>
                        <div class="detalle-item">
                            <img src="uploads/<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                            <div class="detalle-info">
                                <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                <span>Cantidad: x<?php echo $item['cantidad']; ?></span>
                            </div>
                            <span class="detalle-precio">
                                $<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2, ',', '.'); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>

                <hr class="resumen-divider">

                <div class="total-confirmacion">
                    <span>Total Pagado (IVA incluido.):</span>
                    <strong>$<?php echo number_format($venta['total'], 2, ',', '.'); ?></strong>
                </div>
            </div>

<div class="acciones-confirmacion">
                    <a href="pedidos/generar_pdf.php?id=<?php echo $venta_id; ?>" target="_blank" class="btn-vaciar" style="background: #dc3545; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; text-decoration: none;">
        <i class="fa-solid fa-file-pdf"></i> Descargar Recibo (PDF)
    </a>
    
    <a href="tienda.php" class="btn-volver-tienda">
        <i class="fa-solid fa-store"></i> Volver a la Tienda
    </a>
</div>
        </div>
    </div>

<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>

<style>

/* ==========================================
   ESTILOS PARA BOTONES DE CONFIRMACIÓN
   ========================================== */
.acciones-confirmacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px; /* Separación horizontal entre botones */
    margin-top: 25px; /* Separación con respecto a la caja del total */
    flex-wrap: wrap; /* Permite adaptarse a pantallas chicas */
}

.acciones-confirmacion .btn-pdf {
    background-color: #dc3545;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s, transform 0.1s;
}

.acciones-confirmacion .btn-pdf:hover {
    background-color: #c82333;
    transform: translateY(-2px);
}

.acciones-confirmacion .btn-volver-tienda {
    background-color: #0d6efd;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s, transform 0.1s;
}

.acciones-confirmacion .btn-volver-tienda:hover {
    background-color: #0b5ed7;
    transform: translateY(-2px);
}

</style>
