<?php
session_start();

require_once __DIR__ . '/../includes/autenticacion_admin.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../index.html");
    exit();
}

include __DIR__ . "/../config/conexion.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    // 1. Obtener el nombre de la imagen para borrarla del servidor
    $sql_img = "SELECT imagen FROM productos WHERE id = ?";
    $stmt_img = $conexion->prepare($sql_img);
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $res_img = $stmt_img->get_result();

    if ($res_img->num_rows == 1) {
        $prod = $res_img->fetch_assoc();
        if ($prod["imagen"] != "default.jpg" && file_exists("uploads/" . $prod["imagen"])) {
            unlink("uploads/" . $prod["imagen"]);
        }
    }
    $stmt_img->close();

    // 2. Eliminar el producto de MySQL
    $sql = "DELETE FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "
        <script>
            alert('Producto eliminado correctamente');
            window.location.href = 'tienda.php';
        </script>
        ";
    } else {
        echo "Error al eliminar: " . $conexion->error;
    }

    $stmt->close();
}

$conexion->close();
?>
