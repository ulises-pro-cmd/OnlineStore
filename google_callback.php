<?php
session_start();
require_once __DIR__ . '/config/google_config.php';
include __DIR__ . "/config/conexion.php";

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $nombre = $google_account_info->name;

        // Limpiar el carrito al iniciar sesiÃ³n con Google.
        $_SESSION["carrito"] = [];

        // Verificar si el usuario ya existe con ese google_id o por email
        $sql = "SELECT * FROM usuarios WHERE google_id = ? OR email = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $google_id, $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            
            // Si el usuario existía por email pero no tenía google_id, vincularlo
            if (empty($usuario['google_id'])) {
                $update_sql = "UPDATE usuarios SET google_id = ? WHERE id = ?";
                $stmt_up = $conexion->prepare($update_sql);
                $stmt_up->bind_param("si", $google_id, $usuario['id']);
                $stmt_up->execute();
                $stmt_up->close();
            }

            // Iniciar sesión
            $_SESSION["usuario_id"] = $usuario['id'];
            $_SESSION["nombre"] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
        } else {
            // Si no existe, registrar el nuevo usuario mediante Google
            $sql_insert = "INSERT INTO usuarios (nombre, email, google_id) VALUES (?, ?, ?)";
            $stmt_ins = $conexion->prepare($sql_insert);
            $stmt_ins->bind_param("sss", $nombre, $email, $google_id);
            
            if ($stmt_ins->execute()) {
                $_SESSION["usuario_id"] = $stmt_ins->insert_id;
                $_SESSION["nombre"] = $nombre;
            } else {
                die("Error al registrar con Google: " . $conexion->error);
            }
            $stmt_ins->close();
        }

        $stmt->close();
        header("Location: inicio.php");
        exit();
    }
}

// Si ocurre algún fallo vuelve a la página inicial
header("Location: index.html");
exit();
?>
