<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

include __DIR__ . "/config/conexion.php";
$usuario_id = $_SESSION["usuario_id"];

// Consultar compras del cliente logueado
$stmt = $conexion->prepare("SELECT * FROM ventas WHERE usuario_id = ? ORDER BY fecha DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$compras = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Compras - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <header class="store-nav">
        <div class="logo">
            <h1><span>Online</span><strong>Store</strong></h1>
        </div>
        <div class="nav-user">
            <a href="tienda.php" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Tienda
            </a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="cart-container" style="max-width: 1000px;">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Historial de Compras</h2>

            <?php if ($compras->num_rows > 0): ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>N° Pedido</th>
                            <th>Fecha</th>
                            <th>Método Pago</th>
                            <th>Total</th>
                            <th>Estado</th> <!-- NUEVA COLUMNA -->
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = $compras->fetch_assoc()): 
                            // Obtener el estado actual (por defecto 'pendiente' si está vacío)
                            $estado = !empty($c['estado']) ? strtolower(trim($c['estado'])) : 'pendiente';
                        ?>
                            <tr>
                                <td><strong>#<?php echo str_pad($c['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($c['fecha'])); ?> hs</td>
                                <td><?php echo htmlspecialchars($c['metodo_pago'] ?? 'Efectivo'); ?></td>
                                <td><strong>$<?php echo number_format($c['total'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <!-- Insignia visual según el estado -->
                                    <?php if ($estado === 'atendido'): ?>
                                        <span style="background-color: #28a745; color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-circle-check"></i> Atendido
                                        </span>
                                    <?php else: ?>
                                        <span style="background-color: #ffc107; color: #212529; padding: 5px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-solid fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="display: flex; gap: 8px; justify-content: center;">
                                    <a href="confirmacion_compra.php?id=<?php echo $c['id']; ?>" class="btn-volver-tienda" style="padding: 6px 12px; font-size:0.85rem;">
                                        <i class="fa-solid fa-eye"></i> Ver Recibo
                                    </a>
                    <a href="pedidos/generar_pdf.php?id=<?php echo $c['id']; ?>" target="_blank" class="btn-vaciar" style="padding: 6px 12px; font-size:0.85rem; background:#dc3545; color:#fff; border:none; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fa-solid fa-receipt empty-icon"></i>
                    <h3>Aún no has realizado compras</h3>
                    <a href="tienda.php" class="btn-start-shopping">Explorar Productos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
