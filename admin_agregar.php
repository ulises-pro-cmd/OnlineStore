<?php
session_start();

require_once __DIR__ . '/includes/autenticacion_admin.php';

// Verificar inicio de sesión
if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

include __DIR__ . "/config/conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $categoria = $_POST["categoria"];
    $descripcion = $_POST["descripcion"];
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];

    // Procesar la imagen
    $nombre_imagen = "default.jpg";

    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $directorio = "uploads/";

        // Crear la carpeta si no existe
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombre_archivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $ruta_completa = $directorio . $nombre_archivo;

        // Se usa tmp_name en lugar de tmp_path
        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta_completa)) {
            $nombre_imagen = $nombre_archivo;
        }
    }

    // Insertar en la BD
    $sql = "INSERT INTO productos (nombre, categoria, descripcion, precio, imagen, stock) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssdsi", $nombre, $categoria, $descripcion, $precio, $nombre_imagen, $stock);

    if ($stmt->execute()) {
        $mensaje = "<p style='color: green; font-weight: bold;'>¡Producto agregado exitosamente!</p>";
    } else {
        $mensaje = "<p style='color: red;'>Error al agregar el producto: " . $conexion->error . "</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Agregar Producto</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .add-page { min-height: 100vh; padding: 42px 20px; background: linear-gradient(135deg, #f4f7fb, #e9eef7); }
        .add-card { width: min(100%, 760px); margin: 0 auto; padding: 38px 42px; background: #fff; border: 1px solid rgba(30,64,175,.08); border-radius: 22px; box-shadow: 0 18px 45px rgba(30,41,59,.12); }
        .add-title { display: flex; align-items: center; gap: 14px; margin: 0 0 8px; color: #172033; font-size: 1.65rem; }
        .add-title-icon { display: grid; place-items: center; width: 52px; height: 52px; color: #fff; background: linear-gradient(135deg,#ff8c00,#1d4ed8); border-radius: 15px; font-size: 1.35rem; box-shadow: 0 8px 18px rgba(37,99,235,.25); }
        .add-subtitle { margin: 0 0 30px; padding-bottom: 22px; color: #718096; font-size: .92rem; border-bottom: 1px solid #edf0f5; }
        .add-form .input-group { margin-bottom: 20px; }
        .add-form label { display: block; margin-bottom: 8px; color: #344054; font-size: .88rem; font-weight: 700; }
        .add-form .input-box { min-height: 48px; border: 1px solid #d9e0eb; border-radius: 11px; background: #fbfcfe; transition: .2s ease; }
        .add-form .input-box:focus-within { border-color: #ff8c00; background: #fff; box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
        .add-form .input-box i { color: #ff8c00; }
        .add-form input, .add-form select { color: #1f2937; }
        .add-fields { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; }
        .add-form input[type=file] { padding: 12px 10px; font-size: .88rem; }
        .file-hint { margin: 7px 0 0; color: #98a2b3; font-size: .78rem; }
        .add-actions { display: flex; align-items: center; gap: 14px; margin-top: 28px; }
        .add-submit, .add-cancel { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; border-radius: 11px; font-weight: 700; text-decoration: none; transition: .2s ease; }
        .add-submit { flex: 1; border: 0; color: #fff; background: linear-gradient(135deg,#ff8c00,#1d4ed8); cursor: pointer; }
        .add-cancel { padding: 0 20px; color: #475467; background: #f2f4f7; }
        .add-submit:hover, .add-cancel:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(30,41,59,.14); }
        .add-message { margin: -12px 0 22px; padding: 12px 14px; border-radius: 10px; background: #f0fdf4; color: #15803d; }
        @media (max-width: 620px) { .add-page { padding: 20px 12px; } .add-card { padding: 26px 18px; border-radius: 16px; } .add-fields { grid-template-columns: 1fr; gap: 0; } .add-actions { flex-direction: column-reverse; } .add-submit, .add-cancel { width: 100%; } }
    </style>
</head>
<body>

    <main class="add-page">
        <div class="add-card">

            <h2 class="add-title"><span class="add-title-icon"><i class="fa-solid fa-box-open"></i></span> Agregar producto</h2>
            <p class="add-subtitle">Carga los detalles y la imagen del nuevo producto.</p>

            <?php if ($mensaje): ?><div class="add-message"><?php echo $mensaje; ?></div><?php endif; ?>

            <form class="add-form" action="admin_agregar.php" method="POST" enctype="multipart/form-data">

                <!-- Nombre -->
                <div class="input-group">
                    <label>Nombre del Producto</label>
                    <div class="input-box">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <input type="text" name="nombre" placeholder="Ej: Leche Entera 1L" required>
                    </div>
                </div>

                <!-- Categoría -->
                <div class="input-group">
                    <label>Categoría</label>
                    <div class="input-box">
                        <i class="fa-solid fa-list"></i>
                        <select name="categoria" required style="width:100%; border:none; outline:none; font-size:15px; background:transparent;">
                            <option value="Lácteos">Lácteos</option>
                            <option value="Bebidas">Bebidas</option>
                            <option value="Almacén">Almacén</option>
                            <option value="Limpieza">Limpieza</option>
                            <option value="Frutas y Verduras">Frutas y Verduras</option>
                            <option value="Carnes">Carnes</option>
                        </select>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="input-group">
                    <label>Descripción</label>
                    <div class="input-box">
                        <i class="fa-regular fa-file-lines"></i>
                        <input type="text" name="descripcion" placeholder="Ej: Marca La Serenísima, Sachet 1 Litro">
                    </div>
                </div>

                <!-- Precio y Stock -->
                <div class="add-fields">
                    <div class="input-group">
                        <label>Precio ($)</label>
                        <div class="input-box">
                            <i class="fa-solid fa-dollar-sign"></i>
                            <input type="number" step="0.01" name="precio" placeholder="1250.00" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Stock (Unidades)</label>
                        <div class="input-box">
                            <i class="fa-solid fa-cubes"></i>
                            <input type="number" name="stock" placeholder="50" required>
                        </div>
                    </div>
                </div>

                <!-- Imagen -->
                <div class="input-group">
                    <label>Imagen del Producto</label>
                    <div class="input-box">
                        <i class="fa-regular fa-image"></i>
                        <input type="file" name="imagen" accept="image/*" required>
                    </div>
                    <p class="file-hint">Formatos recomendados: JPG, PNG o WEBP.</p>
                </div>

                <!-- Botones -->
                <div class="add-actions">
                    <a href="tienda.php" class="add-cancel">Cancelar</a>
                    <button type="submit" class="add-submit"><i class="fa-solid fa-plus"></i>&nbsp; Guardar producto</button>
                </div>

            </form>

        </div>
    </main>

<?php include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
