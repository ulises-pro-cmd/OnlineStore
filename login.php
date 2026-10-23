<?php

session_start();

include __DIR__ . "/config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];


    // Buscar el usuario por email

    $sql = "SELECT id, nombre, email, password
            FROM usuarios
            WHERE email = ?";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $resultado = $stmt->get_result();


    // Comprobar si existe

    if ($resultado->num_rows == 1) {

        $usuario = $resultado->fetch_assoc();


        // Comprobar contraseña

        if (password_verify($password, $usuario["password"])) {

            // Crear sesión

            $_SESSION["carrito"] = [];
            $_SESSION["usuario_id"] = (int) $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["email"] = $usuario["email"];
            $_SESSION["rol"] = $usuario["rol"];

            // Ir a la página principal

            header("Location: inicio.php");

            exit();

        } else {

            echo "
            <script>
                alert('Contraseña incorrecta');
                window.location.href = 'index.html';
            </script>
            ";

        }

    } else {

        echo "
        <script>
            alert('El correo no está registrado');
            window.location.href = 'index.html';
        </script>
        ";

    }


    $stmt->close();

}

$conexion->close();

?>
