<?php

include __DIR__ . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = $_POST["nombre"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirmar_password = $_POST["confirmar_password"];

    if (strlen($password) < 10) {
        echo "
        <script>
            alert('La contrase\u00f1a debe tener al menos 10 caracteres.');
            window.location.href = 'registro.html';
        </script>
        ";
        exit();
    }


    // 1. Comprobar que las contraseñas coincidan
    if ($password !== $confirmar_password) {
        echo "
        <script>
            alert('Las contraseñas no coinciden.');
            window.location.href = 'registro.html';
        </script>
        ";
        exit();
    }


    // 2. Verificar si el correo ya existe en la base de datos
    $sql_check = "SELECT id FROM usuarios WHERE email = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    if ($resultado_check->num_rows > 0) {
        echo "
        <script>
            alert('Este correo electrónico ya está registrado. Por favor, usa otro o inicia sesión.');
            window.location.href = 'registro.html';
        </script>
        ";
        $stmt_check->close();
        $conexion->close();
        exit();
    }
    $stmt_check->close();


    // 3. Encriptar contraseña
    $password_segura = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // 4. Registrar el nuevo usuario
    $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $password_segura);

    if ($stmt->execute()) {
        echo "
        <script>
            alert('Cuenta creada correctamente');
            window.location.href = 'index.html';
        </script>
        ";
    } else {
        echo "Error al registrar el usuario: " . $conexion->error;
    }

    $stmt->close();
}

$conexion->close();

?>
