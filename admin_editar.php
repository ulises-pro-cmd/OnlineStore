<?php
session_start();

require_once __DIR__ . '/includes/autenticacion_admin.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.html");
    exit();
}

include __DIR__ . "/config/conexion.php";

$mensaje = "";
$id = $_GET["id"] ?? null;

if (!$id) {
    header("Location: tienda.php");
    exit();
}

// Obtener datos actuales del producto
$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    header("Location: tienda.php");
    exit();
}

// Procesar actualización al enviar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $categoria = $_POST["categoria"];
    $descripcion = $_POST["descripcion"];
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $nombre_imagen = $producto["imagen"];

    // Si sube una nueva imagen
    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] == 0) {
        $directorio = "uploads/";
        $nombre_archivo = time() . "_" . basename($_FILES["imagen"]["name"]);
        $ruta_completa = $directorio . $nombre_archivo;

        if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta_completa)) {
            // Eliminar imagen anterior si no es la por defecto
            if ($nombre_imagen != "default.jpg" && file_exists("uploads/" . $nombre_imagen)) {
                unlink("uploads/" . $nombre_imagen);
            }
            $nombre_imagen = $nombre_archivo;
        }
    }

    // Actualizar registro en MySQL
    $sql_update = "UPDATE productos SET nombre=?, categoria=?, descripcion=?, precio=?, imagen=?, stock=? WHERE id=?";
    $stmt_up = $conexion->prepare($sql_update);
    $stmt_up->bind_param("sssdsii", $nombre, $categoria, $descripcion, $precio, $nombre_imagen, $stock, $id);

    if ($stmt_up->execute()) {
        echo "
        <script>
            alert('Producto actualizado correctamente');
            window.location.href = 'tienda.php';
        </script>
        ";
    } else {
        $mensaje = "<p style='color: red;'>Error al actualizar: " . $conexion->error . "</p>";
    }
    $stmt_up->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - OnlineStore</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<style>
    .edit-page { min-height: 100vh; padding: 42px 20px; background: linear-gradient(135deg, #f4f7fb, #e9eef7); }
    .edit-card { width: min(100%, 760px); margin: 0 auto; padding: 38px 42px; background: #fff; border: 1px solid rgba(30,64,175,.08); border-radius: 22px; box-shadow: 0 18px 45px rgba(30,41,59,.12); }
    .edit-header { display: flex; align-items: center; gap: 16px; margin-bottom: 30px; padding-bottom: 22px; border-bottom: 1px solid #edf0f5; }
    .edit-header-icon { display: grid; place-items: center; width: 52px; height: 52px; color: #fff; background: linear-gradient(135deg,#2563eb,#1d4ed8); border-radius: 15px; font-size: 1.35rem; box-shadow: 0 8px 18px rgba(37,99,235,.25); }
    .edit-header h2 { margin: 0; color: #172033; font-size: 1.65rem; }
    .edit-title { display: flex; align-items: center; gap: 10px; margin: 0 0 8px; color: #172033; font-size: 1.65rem; }
    .edit-title i { color: #2563eb; }
    .edit-header p { margin: 5px 0 0; color: #718096; font-size: .92rem; }
    .edit-form .input-group { margin-bottom: 20px; }
    .edit-form label { display: block; margin-bottom: 8px; color: #344054; font-size: .88rem; font-weight: 700; }
    .edit-form .input-box { min-height: 48px; border: 1px solid #d9e0eb; border-radius: 11px; background: #fbfcfe; transition: .2s ease; }
    .edit-form .input-box:focus-within { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 4px rgba(37,99,235,.1); }
    .edit-form .input-box i { color: #2563eb; }
    .edit-form input, .edit-form select { color: #1f2937; }
    .edit-fields { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; }
    .current-image { display: flex; align-items: center; gap: 16px; padding: 12px; margin-bottom: 10px; border: 1px solid #e7ebf2; border-radius: 12px; background: #f8fafc; }
    .current-image img { width: 86px; height: 86px; object-fit: cover; border: 1px solid #dbe3ef; border-radius: 10px; }
    .current-image span { color: #667085; font-size: .85rem; }
    .edit-form input[type=file] { padding: 12px 10px; font-size: .88rem; }
    .edit-actions { display: flex; align-items: center; gap: 14px; margin-top: 28px; }
    .edit-submit, .edit-cancel { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; border-radius: 11px; font-weight: 700; text-decoration: none; transition: .2s ease; }
    .edit-submit { flex: 1; border: 0; color: #fff; background: linear-gradient(135deg,#2563eb,#1d4ed8); cursor: pointer; }
    .edit-cancel { padding: 0 20px; color: #475467; background: #f2f4f7; }
    .edit-submit:hover, .edit-cancel:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(30,41,59,.14); }
    @media (max-width: 620px) { .edit-page { padding: 20px 12px; } .edit-card { padding: 26px 18px; border-radius: 16px; } .edit-fields { grid-template-columns: 1fr; gap: 0; } .edit-actions { flex-direction: column-reverse; } .edit-submit, .edit-cancel { width: 100%; } }
</style>

    <main class="edit-page">
        <div class="edit-card">

            <h2 class="edit-title"><i class="fa-solid fa-pen-to-square"></i> Editar producto</h2>
            <p class="register-subtitle">Modifica la información o la imagen del producto</p>

            <?php echo $mensaje; ?>

            <form class="edit-form" action="admin_editar.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">

                <!-- Nombre -->
                <div class="input-group">
                    <label>Nombre del Producto</label>
                    <div class="input-box">
                        <i class="fa-solid fa-basket-shopping"></i>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>
                </div>

                <!-- Categoría -->
                <div class="input-group">
                    <label>Categoría</label>
                    <div class="input-box">
                        <i class="fa-solid fa-list"></i>
                        <select name="categoria" required style="width:100%; border:none; outline:none; font-size:15px; background:transparent;">
                            <?php 
                            $categorias = ["Bebidas", "Lácteos", "Almacén", "Frutas y Verduras", "Carnes", "Limpieza"];
                            foreach ($categorias as $cat) {
                                $selected = ($producto['categoria'] == $cat) ? 'selected' : '';
                                echo "<option value='$cat' $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="input-group">
                    <label>Descripción</label>
                    <div class="input-box">
                        <i class="fa-regular fa-file-lines"></i>
                        <input type="text" name="descripcion" value="<?php echo htmlspecialchars($producto['descripcion']); ?>">
                    </div>
                </div>

                <!-- Precio y Stock -->
                <div class="edit-fields">
                    <div class="input-group">
                        <label>Precio ($)</label>
                        <div class="input-box">
                            <i class="fa-solid fa-dollar-sign"></i>
                            <input type="number" step="0.01" name="precio" value="<?php echo $producto['precio']; ?>" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Stock (Unidades)</label>
                        <div class="input-box">
                            <i class="fa-solid fa-cubes"></i>
                            <input type="number" name="stock" value="<?php echo $producto['stock']; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Imagen Actual e Input para cambiarla -->
                <div class="input-group">
                    <label>Imagen Actual / Nueva Imagen</label>
                    <div class="current-image">
                        <img src="uploads/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="Imagen actual del producto">
                        <span>Imagen actual del producto</span>
                    </div>
                    <div class="input-box">
                        <i class="fa-regular fa-image"></i>
                        <input type="file" name="imagen" accept="image/*" style="padding-top: 15px;">
                    </div>
                </div>

                <div class="edit-actions">
                    <a href="tienda.php" class="edit-cancel">Cancelar</a>
                    <button type="submit" class="edit-submit"><i class="fa-solid fa-floppy-disk"></i>&nbsp; Guardar cambios</button>
                </div>

            </form>

        </div>
    </main>

<?php include __DIR__ . '/includes/admin_notificaciones.php'; ?>
</body>
</html>
