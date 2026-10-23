<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no inició sesión o no es administrador, redirigir a la tienda
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: tienda.php");
    exit();
}
?>