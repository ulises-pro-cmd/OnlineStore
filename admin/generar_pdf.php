<?php
session_start();

// Validar que el usuario logueado sea administrador
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin' || !isset($_GET['id'])) {
    header("Location: ../tienda.php");
    exit();
}

include __DIR__ . "/../config/conexion.php";

$venta_id = intval($_GET['id']);

// Consultar la venta y los datos del cliente
$stmt = $conexion->prepare("SELECT v.*, u.nombre AS usuario_nombre, u.email 
                            FROM ventas v 
                            JOIN usuarios u ON v.usuario_id = u.id 
                            WHERE v.id = ?");
$stmt->bind_param("i", $venta_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    echo "Pedido no encontrado.";
    exit();
}

// Consultar los productos del pedido
$query_detalles = "SELECT d.*, p.nombre 
                   FROM detalle_ventas d 
                   JOIN productos p ON d.producto_id = p.id 
                   WHERE d.venta_id = $venta_id";
$resultado_detalles = $conexion->query($query_detalles);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Cliente<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
        .invoice-box { max-width: 700px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #ff8c00; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #ff8c00; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .details-table th { background-color: #f2f2f2; }
        .total-row { font-weight: bold; font-size: 16px; text-align: right; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print();" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
            🖨️ Guardar como PDF / Imprimir
        </button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div>
                <div class="logo">OnlineStore</div>
                <p>Comprobante de Compra (Copia Administrador)</p>
            </div>
            <div style="text-align: right;">
                <h3>Pedido #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                <p>Fecha: <?php echo date("d/m/Y H:i", strtotime($venta['fecha'])); ?></p>
            </div>
        </div>

        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['usuario_nombre']); ?> (<?php echo htmlspecialchars($venta['email']); ?>)</p>
        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($venta['direccion']); ?></p>
        <p><strong>Notas adicionales:</strong> <?php echo htmlspecialchars($venta['notas'] ?? 'N/A'); ?></p>
        <p><strong>Estado:</strong> <?php echo ucfirst(htmlspecialchars($venta['estado'] ?? 'pendiente')); ?></p>

        
        <table class="details-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $resultado_detalles->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td><?php echo $item['cantidad']; ?></td>
                        <td>$<?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?></td>
                        <td>$<?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p class="total-row" style="margin-top: 20px;">
            Total Pagado (IVA Incluido): $<?php echo number_format($venta['total'], 2, ',', '.'); ?>
        </p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
