<?php
session_start();
include __DIR__ . "/../config/conexion.php";

$sql = "SELECT v.*, u.nombre AS cliente, u.email 
        FROM ventas v 
        JOIN usuarios u ON v.usuario_id = u.id 
        ORDER BY v.fecha DESC";

$resultado = $conexion->query($sql);

if (!$resultado || $resultado->num_rows === 0) {
    echo "<p style='text-align:center; padding: 20px;'>No hay pedidos registrados.</p>";
    exit();
}
?>

<table class="cart-table">
    <thead>
        <tr>
            <th>N° Pedido</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $resultado->fetch_assoc()): 
            $estado = !empty($row['estado']) ? $row['estado'] : 'pendiente';
            $esPendiente = ($estado === 'pendiente');
            $venta_id = $row['id'];

            // Consultar el detalle de productos de esta venta
            $query_detalle = "SELECT d.*, p.nombre 
                              FROM detalle_ventas d 
                              JOIN productos p ON d.producto_id = p.id 
                              WHERE d.venta_id = $venta_id";
            $res_detalle = $conexion->query($query_detalle);
        ?>
            <!-- Fila principal del pedido -->
            <tr style="<?php echo $esPendiente ? 'background-color: #fff3cd;' : ''; ?>">
                <td><strong>#<?php echo str_pad($venta_id, 6, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo date("d/m/Y H:i", strtotime($row['fecha'])); ?> hs</td>
                <td>
                    <strong><?php echo htmlspecialchars($row['cliente']); ?></strong><br>
                    <small style="color: #6c757d;"><?php echo htmlspecialchars($row['email']); ?></small>
                </td>
                <td><strong>$<?php echo number_format($row['total'], 2, ',', '.'); ?></strong></td>
                <td>
                    <span class="badge-estado <?php echo htmlspecialchars($estado); ?>">
                        <?php echo ucfirst(htmlspecialchars($estado)); ?>
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                        <!-- Botón Ver Detalle -->
                        <button onclick="toggleDetalle(<?php echo $venta_id; ?>)" style="background:#17a2b8; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; font-weight:bold; font-size:0.85rem;">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>

                        <!-- Botón Descargar/Ver PDF -->
                        <a href="admin/generar_pdf.php?id=<?php echo $venta_id; ?>" target="_blank" style="background:#dc3545; color:white; text-decoration:none; padding:6px 10px; border-radius:4px; font-weight:bold; font-size:0.85rem; display:inline-flex; align-items:center; gap:4px;">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>

                        <!-- Botón Cambiar Estado -->
                        <?php if ($esPendiente): ?>
                            <button onclick="cambiarEstado(<?php echo $venta_id; ?>, 'atendido')" style="background:#28a745; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; font-weight:bold; font-size:0.85rem;">
                                <i class="fa-solid fa-check"></i> Atender
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            <!-- Fila desplegable con el detalle de productos -->
            <tr id="detalle-row-<?php echo $venta_id; ?>" style="display: none; background-color: #f8f9fa;">
                <td colspan="6" style="padding: 15px; text-align: left;">
                    <div style="border: 1px solid #dee2e6; background: #fff; padding: 12px; border-radius: 6px;">
                        <h4 style="margin-top:0; margin-bottom: 10px; color: #333;">
                            <i class="fa-solid fa-boxes-packing"></i> Productos comprados en el Pedido #<?php echo str_pad($venta_id, 6, '0', STR_PAD_LEFT); ?>
                        </h4>
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php while ($item = $res_detalle->fetch_assoc()): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($item['nombre']); ?></strong> - 
                                    Cantidad: <?php echo $item['cantidad']; ?> - 
                                    Precio Un.: $<?php echo number_format($item['precio_unitario'], 2, ',', '.'); ?> - 
                                    Subtotal: <strong>$<?php echo number_format($item['cantidad'] * $item['precio_unitario'], 2, ',', '.'); ?></strong>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                        <!-- TOTAL A PAGAR EN EL DETALLE -->
                        <hr style="margin: 12px 0; border: 0; border-top: 1px solid #dee2e6;">
                        <p style="text-align: right; margin: 0; font-size: 1.05rem; font-weight: bold; color: #333;">
                        Total a Pagar: $<?php echo number_format($row['total'], 2, ',', '.'); ?>
                        </p>
                        <p style="margin: 10px 0 0 0; color: #555; font-size: 0.95rem;">
                        <i class="fa-solid fa-location-dot" style="color: #dc3545;"></i> <strong>Dirección de entrega:</strong> <?php echo htmlspecialchars($row['direccion'] ?? 'N/A'); ?>
                        </p>
                        <p style="margin: 10px 0 0 0; color: #555; font-size: 0.95rem;">
                        <i class="fa-solid fa-credit-card" style="color: #0d6efd;"></i> <strong>Metodo de pago:</strong> <?php echo htmlspecialchars($row['metodo_pago'] ?? 'N/A'); ?>
                        </p>
                        <!-- NOTAS ADICIONALES EN EL PANEL -->
                        <?php if (!empty($row['notas'])): ?>
                        <p style="margin: 10px 0 0 0; color: #555; font-size: 0.95rem;">
                        <i class="fa-solid fa-note-sticky" style="color: #ffc107;"></i> <strong>Notas del cliente:</strong> <?php echo htmlspecialchars($row['notas']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
