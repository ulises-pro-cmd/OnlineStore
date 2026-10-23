<?php

$config_local = __DIR__ . '/conexion.local.php';
if (is_file($config_local)) {
    require $config_local;
}

$servidor = $servidor ?? getenv('DB_HOST') ?: 'localhost';
$usuario = $usuario ?? getenv('DB_USER') ?: 'root';
$password = $password ?? getenv('DB_PASSWORD') ?: '';
$base_datos = $base_datos ?? getenv('DB_NAME') ?: 'onlinestore';

$conexion = new mysqli(
    $servidor,
    $usuario,
    $password,
    $base_datos
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");

?>
