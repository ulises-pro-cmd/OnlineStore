<div id="admin-global-toast-container" class="toast-container"></div>

<script>
(function() {
    const container = document.getElementById('admin-global-toast-container');
    if (!container) return;

    let pedidosAnteriores = null;
    const stocksAvisados = new Set();

    function mostrarNotificacionAdmin(titulo, nombre, descripcion, tipo) {
        const toast = document.createElement('div');
        toast.className = tipo === 'exito' ? 'toast' : 'toast toast-error';

        const contenido = document.createElement('div');
        contenido.className = 'toast-content';

        const tituloElemento = document.createElement('span');
        tituloElemento.className = 'toast-title';
        tituloElemento.innerHTML = tipo === 'stock'
            ? '<i class="fa-solid fa-boxes-stacked"></i> ' + titulo
            : '<i class="fa-solid fa-bell"></i> ' + titulo;

        const nombreElemento = document.createElement('strong');
        nombreElemento.className = 'toast-nombre';
        nombreElemento.textContent = nombre;

        const descripcionElemento = document.createElement('span');
        descripcionElemento.className = 'toast-desc';
        descripcionElemento.textContent = descripcion;

        contenido.append(tituloElemento, nombreElemento, descripcionElemento);
        toast.appendChild(contenido);
        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 6000);
    }

    function verificarPedidosAdmin() {
        fetch('api/check_nuevos_pedidos.php')
            .then(respuesta => respuesta.json())
            .then(datos => {
                if (datos.status !== 'success') return;
                if (pedidosAnteriores !== null && datos.nuevos > pedidosAnteriores) {
                    mostrarNotificacionAdmin(
                        'Nuevo pedido',
                        'Pedidos pendientes',
                        'Hay ' + datos.nuevos + ' pedido(s) pendiente(s) para revisar.',
                        'exito'
                    );
                }
                pedidosAnteriores = datos.nuevos;
            })
            .catch(error => console.error('Error comprobando pedidos:', error));
    }

    function verificarStockAdmin() {
        fetch('api/obtener_stock.php')
            .then(respuesta => respuesta.json())
            .then(productos => {
                productos.forEach(producto => {
                    const stock = parseInt(producto.stock, 10);
                    const id = String(producto.id);
                    if (stock <= 5 && !stocksAvisados.has(id)) {
                        mostrarNotificacionAdmin(
                            stock <= 0 ? 'Stock agotado' : 'Stock bajo',
                            producto.nombre || 'Producto',
                            stock <= 0 ? 'Este producto llegó a cero unidades.' : 'Quedan ' + stock + ' unidad(es).',
                            'stock'
                        );
                        stocksAvisados.add(id);
                    } else if (stock > 5) {
                        stocksAvisados.delete(id);
                    }
                });
            })
            .catch(error => console.error('Error comprobando stock:', error));
    }

    verificarPedidosAdmin();
    verificarStockAdmin();
    setInterval(verificarPedidosAdmin, 4000);
    setInterval(verificarStockAdmin, 4000);
})();
</script>
