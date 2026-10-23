<?php
session_start();

if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin') {
    header("Location: tienda.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Pedidos en Tiempo Real</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <header class="store-nav">
        <div class="logo">
            <h1><span>Online</span><strong>Store Admin</strong></h1>
        </div>
        <div class="nav-user">
            <a href="tienda.php" class="btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Tienda
            </a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="cart-container" style="max-width: 1100px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fa-solid fa-list-check"></i> Monitor de Pedidos</h2>
                <span style="font-size: 0.9rem; color: #28a745; font-weight: bold;">
                    <i class="fa-solid fa-circle-dot fa-beat"></i> Actualizando en tiempo real
                </span>
            </div>

            <div id="tabla-pedidos-container">
                <!-- Se carga dinámicamente con AJAX -->
            </div>
        </div>
    </div>

    <script>
    function cargarPedidos() {
        // 1. Guardar los IDs de los detalles que están abiertos actualmente
        const abiertos = [];
        document.querySelectorAll("[id^='detalle-row-']").forEach(el => {
            if (el.style.display === 'table-row') {
                abiertos.push(el.id);
            }
        });

        // 2. Traer la tabla actualizada
        fetch('api/obtener_pedidos_admin.php')
            .then(res => res.text())
            .then(html => {
                document.getElementById('tabla-pedidos-container').innerHTML = html;

                // 3. Volver a abrir automáticamente las filas que estaban desplegadas
                abiertos.forEach(id => {
                    const fila = document.getElementById(id);
                    if (fila) {
                        fila.style.display = 'table-row';
                    }
                });
            });
    }

    function cambiarEstado(ventaId, nuevoEstado) {
        const formData = new FormData();
        formData.append('venta_id', ventaId);
        formData.append('estado', nuevoEstado);

        fetch('api/actualizar_estado_pedido.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                cargarPedidos();
            }
        });
    }

    function toggleDetalle(id) {
        const fila = document.getElementById('detalle-row-' + id);
        if (fila.style.display === 'none' || fila.style.display === '') {
            fila.style.display = 'table-row';
        } else {
            fila.style.display = 'none';
        }
    }

    // Cargar inmediatamente y refrescar cada 4 segundos
    cargarPedidos();
    setInterval(cargarPedidos, 4000);
    </script>
    <?php include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
