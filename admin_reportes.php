<?php
session_start();

if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    header('Location: tienda.php');
    exit();
}

include __DIR__ . '/config/conexion.php';

$hoy = date('Y-m-d');
$primer_dia_mes = date('Y-m-01');
$desde = $_GET['desde'] ?? $primer_dia_mes;
$hasta = $_GET['hasta'] ?? $hoy;
$estado_filtro = $_GET['estado'] ?? '';
$estados_validos = ['pendiente', 'atendido', 'cancelado'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !strtotime($desde)) {
    $desde = $primer_dia_mes;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) || !strtotime($hasta)) {
    $hasta = $hoy;
}
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}
if (!in_array($estado_filtro, $estados_validos, true)) {
    $estado_filtro = '';
}

$condicion_estado = $estado_filtro !== '' ? " AND v.estado = ?" : '';
$tipos_filtro = $estado_filtro !== '' ? 'sss' : 'ss';
$parametros_filtro = $estado_filtro !== '' ? [$desde, $hasta, $estado_filtro] : [$desde, $hasta];

function ejecutarReporte(mysqli $conexion, string $sql, string $tipos = '', array $parametros = []): mysqli_result
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        die('No se pudo preparar el reporte.');
    }

    if ($tipos !== '') {
        $referencias = [$tipos];
        foreach ($parametros as $indice => $parametro) {
            $referencias[] = &$parametros[$indice];
        }
        call_user_func_array([$stmt, 'bind_param'], $referencias);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
    return $resultado;
}

$resumen = ejecutarReporte(
    $conexion,
    "SELECT COUNT(*) AS pedidos,
            COALESCE(SUM(v.total), 0) AS facturacion,
            COALESCE(AVG(v.total), 0) AS promedio
     FROM ventas v
     WHERE DATE(v.fecha) BETWEEN ? AND ?{$condicion_estado}",
    $tipos_filtro,
    $parametros_filtro
)->fetch_assoc();

$productos = $conexion->query("SELECT COUNT(*) AS total,
                                      COALESCE(SUM(stock), 0) AS unidades,
                                      SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) AS stock_bajo
                               FROM productos")->fetch_assoc();

$clientes = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE LOWER(rol) <> 'admin' OR rol IS NULL")->fetch_assoc();

$ventas_estado = ejecutarReporte(
    $conexion,
    "SELECT COALESCE(v.estado, 'pendiente') AS estado,
            COUNT(*) AS cantidad,
            COALESCE(SUM(v.total), 0) AS total
     FROM ventas v
     WHERE DATE(v.fecha) BETWEEN ? AND ?{$condicion_estado}
     GROUP BY COALESCE(v.estado, 'pendiente')
     ORDER BY cantidad DESC",
    $tipos_filtro,
    $parametros_filtro
)->fetch_all(MYSQLI_ASSOC);

$productos_mas_vendidos = ejecutarReporte(
    $conexion,
    "SELECT p.nombre,
            SUM(d.cantidad) AS unidades,
            SUM(d.cantidad * d.precio_unitario) AS total
     FROM detalle_ventas d
     INNER JOIN ventas v ON v.id = d.venta_id
     INNER JOIN productos p ON p.id = d.producto_id
     WHERE DATE(v.fecha) BETWEEN ? AND ?{$condicion_estado}
     GROUP BY p.id, p.nombre
     ORDER BY unidades DESC
     LIMIT 8",
    $tipos_filtro,
    $parametros_filtro
)->fetch_all(MYSQLI_ASSOC);

$ventas_diarias = ejecutarReporte(
    $conexion,
    "SELECT DATE(v.fecha) AS dia,
            COUNT(*) AS pedidos,
            COALESCE(SUM(v.total), 0) AS total
     FROM ventas v
     WHERE DATE(v.fecha) BETWEEN ? AND ?{$condicion_estado}
     GROUP BY DATE(v.fecha)
     ORDER BY dia ASC",
    $tipos_filtro,
    $parametros_filtro
)->fetch_all(MYSQLI_ASSOC);

$stock_bajo_productos = $conexion->query(
    "SELECT id, nombre, categoria, imagen, stock
     FROM productos
     WHERE stock <= 5
     ORDER BY stock ASC, nombre ASC"
)->fetch_all(MYSQLI_ASSOC);

$pedidos_pendientes = (int) ($conexion->query(
    "SELECT COUNT(*) AS total FROM ventas WHERE estado = 'pendiente'"
)->fetch_assoc()['total'] ?? 0);

$conexion->close();

$formatear_dinero = static fn($valor) => '$' . number_format((float) $valor, 2, ',', '.');
$nombres_estado = [
    'pendiente' => 'Pendientes',
    'atendido' => 'Atendidos',
    'cancelado' => 'Cancelados'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y estadísticas - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .report-page { min-height: 100vh; padding: 32px 20px 50px; background: #f4f7fb; }
        .report-wrap { width: min(100%, 1180px); margin: auto; }
        .report-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 24px; }
        .report-title { display: flex; align-items: center; gap: 14px; }
        .report-title-icon { display: grid; place-items: center; width: 52px; height: 52px; color: #fff; background: linear-gradient(135deg,#ff8c00,#1d4ed8); border-radius: 15px; font-size: 1.35rem; }
        .report-title h1 { margin: 0; color: #172033; font-size: 1.7rem; }
        .report-title p { margin: 4px 0 0; color: #718096; }
        .report-header-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .report-back { padding: 10px 15px; color: #475467; background: #fff; border-radius: 9px; text-decoration: none; font-weight: 700; }
        .report-orders { position: relative; color: #fff; background: #6f42c1; }
        .report-filter { display: flex; flex-wrap: wrap; align-items: end; gap: 14px; padding: 18px; margin-bottom: 22px; background: #fff; border: 1px solid #e6ebf2; border-radius: 14px; }
        .report-filter label { display: flex; flex-direction: column; gap: 6px; color: #475467; font-size: .82rem; font-weight: 700; }
        .report-filter input, .report-filter select { min-height: 40px; padding: 0 10px; border: 1px solid #d9e0eb; border-radius: 8px; color: #344054; background: #fbfcfe; }
        .report-filter button { min-height: 40px; padding: 0 18px; border: 0; border-radius: 8px; color: #fff; background: #ff8c00; font-weight: 700; cursor: pointer; }
        .report-cards { display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 22px; }
        .report-card { padding: 18px; background: #fff; border: 1px solid #e6ebf2; border-radius: 14px; box-shadow: 0 5px 16px rgba(30,41,59,.04); }
        .report-card-label { color: #718096; font-size: .78rem; font-weight: 700; text-transform: uppercase; }
        .report-card-value { display: block; margin-top: 8px; color: #172033; font-size: 1.45rem; font-weight: 800; }
        .report-card-icon { float: right; color: #ff8c00; font-size: 1.2rem; }
        .report-card-link { display: inline-flex; margin-top: 12px; padding: 7px 10px; color: #2563eb; background: #eff6ff; border-radius: 7px; text-decoration: none; font-size: .78rem; font-weight: 700; }
        .report-card-link:hover { background: #dbeafe; }
        .report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .report-panel { padding: 22px; background: #fff; border: 1px solid #e6ebf2; border-radius: 14px; }
        .report-panel h2 { display: flex; align-items: center; gap: 9px; margin: 0 0 18px; color: #172033; font-size: 1.05rem; }
        .report-panel h2 i { color: #ff8c00; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { padding: 11px 8px; border-bottom: 1px solid #edf0f5; text-align: left; font-size: .88rem; }
        .report-table th { color: #667085; font-size: .76rem; text-transform: uppercase; }
        .report-table td:last-child, .report-table th:last-child { text-align: right; }
        .status-pill { display: inline-block; padding: 4px 8px; border-radius: 20px; color: #344054; background: #eef2f7; font-size: .76rem; font-weight: 700; }
        .status-pill.pendiente { color: #92400e; background: #fef3c7; }
        .status-pill.atendido { color: #166534; background: #dcfce7; }
        .status-pill.cancelado { color: #991b1b; background: #fee2e2; }
        .empty-report { padding: 25px 0; color: #98a2b3; text-align: center; }
        .low-stock-panel { grid-column: 1 / -1; }
        .low-stock-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .low-stock-product { display: flex; flex-direction: column; padding: 12px; border: 1px solid #e6ebf2; border-radius: 12px; background: #fbfcfe; }
        .low-stock-product img { width: 100%; height: 130px; object-fit: cover; border-radius: 9px; background: #eef2f7; }
        .low-stock-product h3 { margin: 11px 0 4px; color: #172033; font-size: .95rem; }
        .low-stock-category { color: #718096; font-size: .78rem; }
        .low-stock-meta { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 10px; }
        .low-stock-amount { color: #b42318; font-size: .84rem; font-weight: 800; }
        .low-stock-actions { display: flex; gap: 6px; }
        .low-stock-actions a { display: inline-flex; align-items: center; justify-content: center; width: 31px; height: 31px; border-radius: 7px; color: #fff; text-decoration: none; font-size: .78rem; }
        .low-stock-edit { background: #2563eb; }
        .low-stock-delete { background: #dc3545; }
        .low-stock-actions a:hover { filter: brightness(.92); }
        @media (max-width: 950px) { .report-cards { grid-template-columns: repeat(3, 1fr); } .low-stock-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 700px) { .report-header { align-items: flex-start; flex-direction: column; } .report-cards, .report-grid, .low-stock-grid { grid-template-columns: 1fr; } .report-filter > * { width: 100%; } .report-filter input, .report-filter select, .report-filter button { width: 100%; } }
    </style>
</head>
<body>
    <main class="report-page">
        <div class="report-wrap">
            <header class="report-header">
                <div class="report-title">
                    <span class="report-title-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <div>
                        <h1>Reportes y estadísticas</h1>
                        <p>Resumen del rendimiento de tu tienda.</p>
                    </div>
                </div>
                <div class="report-header-actions">
                    <a href="admin_pedidos.php" class="report-back report-orders"><i class="fa-solid fa-bell"></i> Ver pedidos <span id="badge-reportes" class="badge-notif" <?php echo $pedidos_pendientes > 0 ? '' : 'style="display: none;"'; ?>><?php echo $pedidos_pendientes; ?></span></a>
                    <a href="tienda.php" class="report-back"><i class="fa-solid fa-arrow-left"></i> Volver a la tienda</a>
                </div>
            </header>

            <form class="report-filter" method="GET" action="admin_reportes.php">
                <label>Desde <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>"></label>
                <label>Hasta <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>"></label>
                <label>Estado
                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados_validos as $estado): ?>
                            <option value="<?php echo $estado; ?>" <?php echo $estado_filtro === $estado ? 'selected' : ''; ?>><?php echo $nombres_estado[$estado]; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit"><i class="fa-solid fa-filter"></i> Aplicar filtros</button>
            </form>

            <section class="report-cards">
                <div class="report-card"><i class="fa-solid fa-dollar-sign report-card-icon"></i><span class="report-card-label">Facturación</span><strong class="report-card-value"><?php echo $formatear_dinero($resumen['facturacion']); ?></strong></div>
                <div class="report-card"><i class="fa-solid fa-receipt report-card-icon"></i><span class="report-card-label">Pedidos</span><strong class="report-card-value"><?php echo number_format((int) $resumen['pedidos']); ?></strong></div>
                <div class="report-card"><i class="fa-solid fa-chart-simple report-card-icon"></i><span class="report-card-label">Promedio</span><strong class="report-card-value"><?php echo $formatear_dinero($resumen['promedio']); ?></strong></div>
                <div class="report-card"><i class="fa-solid fa-boxes-stacked report-card-icon"></i><span class="report-card-label">Productos</span><strong class="report-card-value"><?php echo number_format((int) $productos['total']); ?></strong></div>
                <div class="report-card"><i class="fa-solid fa-triangle-exclamation report-card-icon"></i><span class="report-card-label">Stock bajo</span><strong class="report-card-value"><?php echo number_format((int) $productos['stock_bajo']); ?></strong><a class="report-card-link" href="#stock-bajo">Ver productos</a></div>
            </section>

            <section class="report-grid">
                <div class="report-panel">
                    <h2><i class="fa-solid fa-chart-pie"></i> Ventas por estado</h2>
                    <?php if ($ventas_estado): ?>
                        <table class="report-table"><thead><tr><th>Estado</th><th>Pedidos</th><th>Total</th></tr></thead><tbody>
                        <?php foreach ($ventas_estado as $venta): $estado = strtolower($venta['estado']); ?>
                            <tr><td><span class="status-pill <?php echo htmlspecialchars($estado); ?>"><?php echo htmlspecialchars($nombres_estado[$estado] ?? ucfirst($estado)); ?></span></td><td><?php echo number_format((int) $venta['cantidad']); ?></td><td><?php echo $formatear_dinero($venta['total']); ?></td></tr>
                        <?php endforeach; ?></tbody></table>
                    <?php else: ?><p class="empty-report">No hay ventas para el período seleccionado.</p><?php endif; ?>
                </div>

                <div class="report-panel">
                    <h2><i class="fa-solid fa-ranking-star"></i> Productos más vendidos</h2>
                    <?php if ($productos_mas_vendidos): ?>
                        <table class="report-table"><thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead><tbody>
                        <?php foreach ($productos_mas_vendidos as $producto): ?>
                            <tr><td><?php echo htmlspecialchars($producto['nombre']); ?></td><td><?php echo number_format((int) $producto['unidades']); ?></td><td><?php echo $formatear_dinero($producto['total']); ?></td></tr>
                        <?php endforeach; ?></tbody></table>
                    <?php else: ?><p class="empty-report">Todavía no hay productos vendidos.</p><?php endif; ?>
                </div>

                <div class="report-panel">
                    <h2><i class="fa-solid fa-calendar-days"></i> Ventas diarias</h2>
                    <?php if ($ventas_diarias): ?>
                        <table class="report-table"><thead><tr><th>Fecha</th><th>Pedidos</th><th>Total</th></tr></thead><tbody>
                        <?php foreach ($ventas_diarias as $venta): ?>
                            <tr><td><?php echo date('d/m/Y', strtotime($venta['dia'])); ?></td><td><?php echo number_format((int) $venta['pedidos']); ?></td><td><?php echo $formatear_dinero($venta['total']); ?></td></tr>
                        <?php endforeach; ?></tbody></table>
                    <?php else: ?><p class="empty-report">No hay ventas diarias para mostrar.</p><?php endif; ?>
                </div>

                <div class="report-panel">
                    <h2><i class="fa-solid fa-circle-info"></i> Inventario y clientes</h2>
                    <table class="report-table"><tbody>
                        <tr><td>Clientes registrados</td><td><?php echo number_format((int) $clientes['total']); ?></td></tr>
                        <tr><td>Unidades en stock</td><td><?php echo number_format((int) $productos['unidades']); ?></td></tr>
                        <tr><td>Productos con stock bajo</td><td><?php echo number_format((int) $productos['stock_bajo']); ?></td></tr>
                    </tbody></table>
                </div>

                <div class="report-panel low-stock-panel" id="stock-bajo">
                    <h2><i class="fa-solid fa-triangle-exclamation"></i> Productos con stock bajo</h2>
                    <?php if ($stock_bajo_productos): ?>
                        <div class="low-stock-grid">
                            <?php foreach ($stock_bajo_productos as $producto_bajo): ?>
                                <article class="low-stock-product">
                                    <img src="uploads/<?php echo htmlspecialchars($producto_bajo['imagen']); ?>" alt="<?php echo htmlspecialchars($producto_bajo['nombre']); ?>">
                                    <h3><?php echo htmlspecialchars($producto_bajo['nombre']); ?></h3>
                                    <span class="low-stock-category"><?php echo htmlspecialchars($producto_bajo['categoria']); ?></span>
                                    <div class="low-stock-meta">
                                        <span class="low-stock-amount"><i class="fa-solid fa-boxes-stacked"></i> <?php echo number_format((int) $producto_bajo['stock']); ?> unidades</span>
                                        <div class="low-stock-actions">
                                            <a class="low-stock-edit" href="admin_editar.php?id=<?php echo (int) $producto_bajo['id']; ?>" title="Editar producto"><i class="fa-solid fa-pen"></i></a>
                                            <a class="low-stock-delete" href="admin/eliminar.php?id=<?php echo (int) $producto_bajo['id']; ?>" title="Eliminar producto" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-report">No hay productos con stock bajo.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script>
        function actualizarPedidosPendientes() {
            fetch('api/check_nuevos_pedidos.php')
                .then(function(respuesta) { return respuesta.json(); })
                .then(function(datos) {
                    if (datos.status !== 'success') return;

                    const badge = document.getElementById('badge-reportes');
                    if (!badge) return;

                    if (datos.nuevos > 0) {
                        badge.textContent = datos.nuevos;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function(error) { console.error('Error comprobando pedidos:', error); });
        }

        actualizarPedidosPendientes();
        setInterval(actualizarPedidosPendientes, 4000);
    </script>
    <?php include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
