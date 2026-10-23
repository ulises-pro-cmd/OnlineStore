<?php
session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

include __DIR__ . "/config/conexion.php";

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$categoria_filtro = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

$sql = "SELECT * FROM productos WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $termino_limpio = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ]/u', '', $busqueda);
    $patron_aproximado = "%";
    
    if (mb_strlen($termino_limpio) > 2) {
        $caracteres = mb_str_split($termino_limpio);
        $patron_aproximado .= implode("%", $caracteres) . "%";
    } else {
        $patron_aproximado .= $busqueda . "%";
    }

    $sql .= " AND (nombre LIKE ? OR categoria LIKE ? OR descripcion LIKE ?)";
    $params[] = $patron_aproximado;
    $params[] = $patron_aproximado;
    $params[] = $patron_aproximado;
    $types .= "sss";
}

if (!empty($categoria_filtro)) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();

// Corrección: Array de categorías unificado con los botones del menú lateral
$categorias_lista = ["Lácteos", "Bebidas", "Almacén", "Limpieza", "Frutas y Verduras", "Carnes"];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnlineStore - Supermercado</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="page-tienda">

    <!-- Encabezado Superior Fijo -->
    <header class="store-nav">
        <div class="logo">
            <h1><span>Online</span><strong>Store</strong></h1>
        </div>
        <div class="nav-user">
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
        <a href="admin_agregar.php" class="btn-admin-add">
            <i class="fa-solid fa-plus"></i> Agregar Producto
        </a>
    <?php endif; ?>


<!-- Botón Mis Compras (Solo para clientes/usuarios normales) -->
        <?php if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'admin'): ?>
            <a href="mis_compras.php" class="btn-mis-compras" style="background-color: #0d6efd; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-receipt"></i> Mis Compras
            </a>
        <?php endif; ?>

    <!-- Botón de Carrito con Contador -->
    <?php 
        $cant_items = isset($_SESSION['carrito']) ? array_sum($_SESSION['carrito']) : 0; 
    ?>
    <a href="carrito.php" class="btn-carrito" title="Ver carrito">
        <i class="fa-solid fa-cart-shopping"></i> 
        <span>Carrito</span>
        <?php if ($cant_items > 0): ?>
            <span class="cart-badge"><?php echo $cant_items; ?></span>
        <?php endif; ?>
    </a>

    <span>Hola, <strong><?php echo htmlspecialchars($_SESSION["nombre"]); ?></strong></span>
    
    <a href="cerrar_sesion.php" class="btn-salir" title="Cerrar sesión">
        <i class="fa-solid fa-right-from-bracket"></i>
    </a>
       </div>
    </header>

    <!-- Botón Mis Compras (solo para clientes) -->
<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) !== 'admin'): ?>
    <a href="mis_compras.php" class="btn-mis-compras">
        <i class="fa-solid fa-receipt"></i> Mis Compras
    </a>
<?php endif; ?>

    <!-- Envoltorio con margen top para despejar la barra superior -->
    <div class="main-wrapper">

        <!-- Barra de Búsqueda -->
        <div class="top-search-bar">
            <form action="tienda.php" method="GET" class="search-form">
                <?php if (!empty($categoria_filtro)): ?>
                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria_filtro); ?>">
                <?php endif; ?>
                <div class="search-input-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="buscar" placeholder="¿Qué estás buscando? (Ej: leche, lech, fideo...)" value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <button type="submit" class="btn-search">Buscar</button>
                <?php if (!empty($busqueda) || !empty($categoria_filtro)): ?>
                    <a href="tienda.php" class="btn-reset" title="Limpiar filtros"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Botón Panel de Pedidos (Solo para Administrador) -->
<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin'): ?>
    <div class="admin-live-tools">
    <a href="admin_pedidos.php" class="btn-admin-pedidos">
        <i class="fa-solid fa-bell"></i> Pedidos
        <span id="badge-notificaciones" class="badge-notif" style="display: none;">0</span>
    </a>
    <a href="admin_reportes.php" class="btn-admin-pedidos" style="display: flex; width: fit-content; margin-top: 8px;">
        <i class="fa-solid fa-chart-line"></i> Estadísticas y reportes
    </a>
    </div>
<?php endif; ?>

        <!-- Contenedor Principal (Sidebar + Catálogo) -->
        <div class="store-layout">

            <!-- Menú Lateral de Categorías -->
            <aside class="sidebar-categories<?php echo (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') ? ' sidebar-admin' : ''; ?>">
                <h3>Categorías</h3>
                <nav class="category-buttons">
                    <a href="tienda.php<?php echo !empty($busqueda) ? '?buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo empty($categoria_filtro) ? 'active' : ''; ?>">
                       <i class="fa-solid fa-border-all"></i> Todas
                    </a>
                    <a href="tienda.php?categoria=Bebidas<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Bebidas') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-bottle-water"></i> Bebidas
                    </a>
                    <a href="tienda.php?categoria=Lácteos<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Lácteos') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-cheese"></i> Lácteos
                    </a>
                    <a href="tienda.php?categoria=Almacén<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Almacén') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-box-archive"></i> Almacén
                    </a>
                    <a href="tienda.php?categoria=Frutas y Verduras<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Frutas y Verduras') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-apple-whole"></i> Frutas y Verduras
                    </a>
                    <a href="tienda.php?categoria=Carnes<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Carnes') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-drumstick-bite"></i> Carnes
                    </a>
                    <a href="tienda.php?categoria=Limpieza<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" 
                       class="btn-cat <?php echo ($categoria_filtro == 'Limpieza') ? 'active' : ''; ?>">
                       <i class="fa-solid fa-pump-soap"></i> Limpieza
                    </a>
                </nav>
            </aside>

            <main class="catalog-container">
                <h2>
                    <?php 
                        if (!empty($categoria_filtro)) {
                            echo "Categoría: " . htmlspecialchars($categoria_filtro);
                        } else {
                            echo "Catálogo de Supermercado";
                        }
                    ?>
                </h2>

                <div class="products-grid">
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while($producto = $resultado->fetch_assoc()): ?>
                            <div class="product-card" data-id="<?php echo intval($producto['id']); ?>">
                                
                                <!-- Botones de edición SOLO visibles para Administradores -->
                                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                    <div class="admin-actions">
                                        <a href="admin_editar.php?id=<?php echo $producto['id']; ?>" class="btn-edit" title="Editar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="admin/eliminar.php?id=<?php echo $producto['id']; ?>" class="btn-delete" title="Eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este producto?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
    
                                    <div>
                                    <!-- MOSTRAR STOCK SOLO A ADMINISTRADORES -->
                                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                                    <p class="stock-admin<?php echo intval($producto['stock']) <= 0 ? ' stock-agotado' : (intval($producto['stock']) <= 5 ? ' stock-bajo' : ''); ?>">
                                          <i class="fa-solid fa-boxes-stacked"></i> Stock: <strong class="stock-cantidad"><?php echo intval($producto['stock']); ?></strong> unidades
                                    </p>
                                    <?php endif; ?>
                                    </div>


                                <?php endif; ?>

                                <div class="product-img-box">
                                    <img src="uploads/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                </div>

                                <span class="badge-cat"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                                
                                <h3><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p class="desc"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                <p class="price">$<?php echo number_format($producto['precio'], 2, ',', '.'); ?></p>

                                <!-- Botón de compra: se muestra a cualquier usuario registrado que NO sea 'admin' -->
<?php 
// Obtenemos el rol actual de la sesión (si existe)
$rol_usuario = isset($_SESSION['rol']) ? strtolower(trim($_SESSION['rol'])) : '';

// Verificamos si NO es administrador
if ($rol_usuario !== 'admin' && $rol_usuario !== 'administrador'): 
?>
    <form action="carrito/agregar.php" method="POST" class="form-agregar-carrito">
        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
        <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria_filtro); ?>">
        
        <button type="submit" class="btn-add">
            <i class="fa-solid fa-cart-plus"></i> Agregar al carrito
        </button>
    </form>
<?php endif; ?>
                            </div>
                            
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">
                            No se encontraron productos coincidentes.
                        </p>
                    <?php endif; ?>
                </div>
            </main>

        </div>
    </div>

    <!-- Contenedor para la notificación flotante (Toast) -->
<div id="toast-container" class="toast-container"></div>

<script>
    function mostrarToastAgregado(producto) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'toast';

        toast.innerHTML = `
            <img src="uploads/${producto.imagen}" alt="${producto.nombre}" class="toast-img">
            <div class="toast-content">
                <span class="toast-title"><i class="fa-solid fa-circle-check"></i> Agregado al carrito</span>
                <strong class="toast-nombre">${producto.nombre}</strong>
                <span class="toast-cat">${producto.categoria}</span>
                <span class="toast-desc">${producto.descripcion || ''}</span>
                <span class="toast-precio">$${parseFloat(producto.precio).toLocaleString('es-AR', {minimumFractionDigits: 2})}</span>
            </div>
            <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);

        // Desaparece a los 5 segundos
        setTimeout(() => {
            ocultarToast(toast);
        }, 5000);
    }

    function mostrarToastLimite(producto) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-error';

        toast.innerHTML = `
            <img src="uploads/${producto.imagen}" alt="${producto.nombre}" class="toast-img">
            <div class="toast-content">
                <span class="toast-title"><i class="fa-solid fa-triangle-exclamation"></i> Límite alcanzado</span>
                <strong class="toast-nombre">${producto.nombre}</strong>
                <span class="toast-desc">Solo puedes añadir un máximo de 20 unidades por producto.</span>
            </div>
            <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);

        // Desaparece a los 5 segundos
        setTimeout(() => {
            ocultarToast(toast);
        }, 5000);
    }

    function mostrarToastAgotado(producto) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-error';

        toast.innerHTML = `
            <img src="uploads/${producto.imagen}" alt="${producto.nombre}" class="toast-img">
            <div class="toast-content">
                <span class="toast-title"><i class="fa-solid fa-triangle-exclamation"></i> Producto agotado</span>
                <strong class="toast-nombre">${producto.nombre}</strong>
                <span class="toast-desc">Este producto ya está agotado.</span>
            </div>
            <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => ocultarToast(toast), 5000);
    }

    function ocultarToast(toast) {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }

    function cerrarToast(btn) {
        ocultarToast(btn.closest('.toast'));
    }

    document.addEventListener("DOMContentLoaded", function() {
        // 1. Mantener la posición del scroll tras recargar
        const scrollPos = sessionStorage.getItem("tienda_scroll");
        if (scrollPos !== null) {
            window.scrollTo(0, parseInt(scrollPos, 10));
            sessionStorage.removeItem("tienda_scroll");
        }

        const formularios = document.querySelectorAll("form[action='carrito/agregar.php']");
        formularios.forEach(function(form) {
            form.addEventListener("submit", function(evento) {
                evento.preventDefault();

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(respuesta) { return respuesta.json(); })
                .then(function(datos) {
                    if (!datos.ok || !datos.mensaje) return;

                    const mensajeData = datos.mensaje;
                    if (mensajeData.tipo === 'exito') {
                        mostrarToastAgregado(mensajeData.producto);
                    } else if (mensajeData.tipo === 'limite') {
                        mostrarToastLimite(mensajeData.producto);
                    } else if (mensajeData.tipo === 'agotado') {
                        mostrarToastAgotado(mensajeData.producto);
                    }

                    const insignia = document.querySelector('.cart-badge');
                    const enlaceCarrito = document.querySelector('.btn-carrito');
                    if (datos.cantidad_carrito > 0) {
                        if (insignia) {
                            insignia.textContent = datos.cantidad_carrito;
                        } else if (enlaceCarrito) {
                            const nuevaInsignia = document.createElement('span');
                            nuevaInsignia.className = 'cart-badge';
                            nuevaInsignia.textContent = datos.cantidad_carrito;
                            enlaceCarrito.appendChild(nuevaInsignia);
                        }
                    } else if (insignia) {
                        insignia.remove();
                    }
                })
                .catch(function() {
                    // Si falla la solicitud, se conserva el comportamiento tradicional.
                    sessionStorage.setItem("tienda_scroll", window.scrollY);
                    window.location.href = form.action;
                });
            });
        });
    });
</script>

<?php
$mensajes_carrito = isset($_SESSION['toast_mensajes']) && is_array($_SESSION['toast_mensajes'])
    ? $_SESSION['toast_mensajes']
    : [];
?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const claveColaCarrito = 'toasts_carrito_pendientes';
            const mensajesNuevos = <?php echo json_encode($mensajes_carrito); ?>;
            let mensajesPendientes = [];

            try {
                mensajesPendientes = JSON.parse(sessionStorage.getItem(claveColaCarrito) || '[]');
                if (!Array.isArray(mensajesPendientes)) mensajesPendientes = [];
            } catch (error) {
                mensajesPendientes = [];
            }

            mensajesNuevos.forEach(function(mensajeData) {
                mensajesPendientes.push({
                    id: Date.now() + '-' + Math.random().toString(36).slice(2),
                    mensaje: mensajeData
                });
            });

            try {
                sessionStorage.setItem(claveColaCarrito, JSON.stringify(mensajesPendientes));
            } catch (error) {
                // Si el almacenamiento no está disponible, se muestran los avisos igualmente.
            }

            mensajesPendientes.forEach(function(item, indice) {
                setTimeout(function() {
                    const mensajeData = item.mensaje;
                    if (mensajeData.tipo === 'exito') {
                        mostrarToastAgregado(mensajeData.producto);
                    } else if (mensajeData.tipo === 'limite') {
                        mostrarToastLimite(mensajeData.producto);
                    } else if (mensajeData.tipo === 'agotado') {
                        mostrarToastAgotado(mensajeData.producto);
                    }

                    setTimeout(function() {
                        try {
                            const colaActualizada = JSON.parse(sessionStorage.getItem(claveColaCarrito) || '[]')
                                .filter(function(mensaje) { return mensaje.id !== item.id; });
                            sessionStorage.setItem(claveColaCarrito, JSON.stringify(colaActualizada));
                        } catch (error) {
                            // No interrumpir la visualización si el almacenamiento no está disponible.
                        }
                    }, 5500);
                }, indice * 250);
            });
        });
    </script>
    <?php unset($_SESSION['toast_mensajes']); // Limpiar la cola tras enviarla al navegador ?>


<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin'): ?>
<script>
let conteoAnterior = null;
const stocksBajosAvisados = new Set();

function mostrarToastPedidoNuevo(cantidad) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-admin-pedido';
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-title"><i class="fa-solid fa-bell"></i> Nuevo pedido</span>
            <span class="toast-desc">Un cliente realizó una compra. Hay ${cantidad} pedido(s) pendiente(s).</span>
        </div>
        <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => ocultarToast(toast), 6000);
}

function mostrarToastStockBajo(nombre, stock) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-stock-bajo';
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-title"><i class="fa-solid fa-boxes-stacked"></i> Stock bajo</span>
            <strong class="toast-nombre">${nombre}</strong>
            <span class="toast-desc">Quedan ${stock} unidad(es). Revisa el inventario.</span>
        </div>
        <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => ocultarToast(toast), 6000);
}

function mostrarToastStockAgotado(nombre) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-title"><i class="fa-solid fa-triangle-exclamation"></i> Stock agotado</span>
            <strong class="toast-nombre">${nombre}</strong>
            <span class="toast-desc">Este producto llegó a cero unidades.</span>
        </div>
        <button class="toast-close" onclick="cerrarToast(this)">&times;</button>
    `;

    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => ocultarToast(toast), 6000);
}

function verificarNuevosPedidos() {
        fetch('api/check_nuevos_pedidos.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const badge = document.getElementById('badge-notificaciones');
                const cantidad = data.nuevos;

                if (cantidad > 0) {
                    badge.innerText = cantidad;
                    badge.style.display = 'inline-block';

                    // Si hay más pedidos que en la última revisión, reproducir alerta sonora
                    if (conteoAnterior === null && cantidad > 0) {
                        mostrarToastPedidoNuevo(cantidad);
                    } else if (conteoAnterior !== null && cantidad > conteoAnterior) {
                        mostrarToastPedidoNuevo(cantidad);
                        reproducirSonidoNotificacion();
                    }
                } else {
                    badge.style.display = 'none';
                }
                conteoAnterior = cantidad;
            }
        })
        .catch(err => console.error('Error comprobando pedidos:', err));
}

function reproducirSonidoNotificacion() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioContext.createOscillator();
    const gain = audioContext.createGain();
    osc.connect(gain);
    gain.connect(audioContext.destination);
    osc.type = 'sine';
    osc.frequency.value = 587.33; // Tono D5
    gain.gain.setValueAtTime(0.1, audioContext.currentTime);
    osc.start();
    osc.stop(audioContext.currentTime + 0.3);
}

// Consultar cada 5 segundos
setInterval(verificarNuevosPedidos, 5000);
verificarNuevosPedidos();
</script>
<?php endif; ?>


<script>
function actualizarStockEnTiempoReal() {
        fetch('api/obtener_stock.php')
        .then(response => response.json())
        .then(productos => {
            productos.forEach(prod => {
                // Buscar la tarjeta del producto por su ID
                const tarjeta = document.querySelector(`.product-card[data-id="${prod.id}"]`);
                if (tarjeta) {
                    const stockElement = tarjeta.querySelector('.stock-cantidad');
                    const stockLabel = tarjeta.querySelector('.stock-admin');

                    if (stockElement) {
                        stockElement.innerText = prod.stock;
                    }

                    // Si el stock llega a 0, deshabilitar el botón
                    if (stockLabel) {
                        stockLabel.classList.toggle('stock-agotado', prod.stock <= 0);
                    }
                }
            });
        })
        .catch(err => console.error('Error actualizando stock:', err));
}

// Consultar stock cada 4 segundos
setInterval(actualizarStockEnTiempoReal, 4000);
</script>

<script>
function actualizarStockAdminEnTiempoReal() {
        fetch('api/obtener_stock.php')
        .then(response => response.json())
        .then(productos => {
            productos.forEach(prod => {
                // Busca la tarjeta del producto por su ID
                const tarjeta = document.querySelector(`[data-id="${prod.id}"]`);
                if (tarjeta) {
                    const stockElement = tarjeta.querySelector('.stock-cantidad');
                    const stockLabel = tarjeta.querySelector('.stock-admin');
                    const nombreProducto = tarjeta.querySelector('h3')?.textContent.trim() || 'Producto';
                    if (stockElement) {
                        // Actualiza el número de stock en tiempo real
                        stockElement.innerText = prod.stock;
                    }
                    if (stockLabel) {
                        stockLabel.classList.toggle('stock-agotado', prod.stock <= 0);
                        stockLabel.classList.toggle('stock-bajo', prod.stock > 0 && prod.stock <= 5);
                    }
                    if (prod.stock <= 0) {
                        stocksBajosAvisados.delete(prod.id);
                        mostrarToastStockAgotado(nombreProducto);
                    } else if (prod.stock <= 5) {
                        if (!stocksBajosAvisados.has(prod.id)) {
                            mostrarToastStockBajo(nombreProducto, prod.stock);
                            stocksBajosAvisados.add(prod.id);
                        }
                    } else {
                        stocksBajosAvisados.delete(prod.id);
                    }
                }
            });
        })
        .catch(err => console.error('Error al actualizar el stock:', err));
}

// Ejecutar la actualización cada 5 segundos en segundo plano
setInterval(actualizarStockAdminEnTiempoReal, 5000);
</script>


</body>
</html>
