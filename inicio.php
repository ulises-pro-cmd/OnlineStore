<?php

session_start();

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>OnlineStore</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

    <div class="contenedor-inicio">

        <div class="tarjeta-bienvenida">

            <div class="logo-carrito">
                🛒
            </div>

            <h1>
                Bienvenido a <span>OnlineStore</span>
            </h1>

            <h2>
                Hola, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
            </h2>

            <p class="mensaje">
                Has iniciado sesión correctamente.
            </p>

            <div class="acciones">

                <a href="tienda.php" class="btn-tienda">
                    🛍️ Ir a la tienda
                </a>

                <a href="cerrar_sesion.php" class="btn-cerrar">
                    Cerrar sesión
                </a>

            </div>

        </div>

    </div>

<?php if (isset($_SESSION['rol']) && strtolower($_SESSION['rol']) === 'admin') include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>

</html>
