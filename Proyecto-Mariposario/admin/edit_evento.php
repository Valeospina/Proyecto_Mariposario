<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}

$evento = null;
$message = '';
$message_type = '';

// Obtener el ID del evento de la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $evento_id = $_GET['id'];

    // Cargar los datos del evento existente
    $select_query = "SELECT ID_Evento, Nombre, Descripcion, Precio, Imagen_URL FROM Evento WHERE ID_Evento = ?";

    try {
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare($select_query);
            $stmt->bind_param("i", $evento_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $evento = $result->fetch_assoc();
            } else {
                $message = "Evento no encontrado.";
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
        }
    } catch (Exception $e) {
        error_log("Error al cargar datos del evento (ID: $evento_id): " . $e->getMessage());
        $message = "Error al cargar datos del evento: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
} else {
    $message = "ID de evento no proporcionado para editar.";
    $message_type = "danger";
}

// Procesar el formulario cuando se envía (actualizar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $evento) {
    // Recopilar y sanear los datos del formulario
    $id_evento_edit = intval($_POST['id_evento']); // Asegúrate de que el ID viene del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $precio = floatval($_POST['precio']);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url']));

    // Validación básica
    if (empty($nombre) || empty($descripcion) || $precio <= 0) {
        $message = "Todos los campos obligatorios deben ser completados correctamente.";
        $message_type = "danger";
    } else if ($id_evento_edit != $evento['ID_Evento']) {
        // Seguridad: verificar que el ID no se manipule
        $message = "Error de seguridad: ID de evento no coincide.";
        $message_type = "danger";
    }
    else {
        // Prepara la consulta para actualizar el evento
        $update_query = "UPDATE Evento SET Nombre = ?, Descripcion = ?, Precio = ?, Imagen_URL = ? WHERE ID_Evento = ?";

        try {
            if (isset($conn) && $conn instanceof mysqli) {
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen_url, $id_evento_edit);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Evento actualizado exitosamente.";
                    $message_type = "success";
                    // Recargar los datos del evento para mostrar los cambios
                    $evento['Nombre'] = $nombre;
                    $evento['Descripcion'] = $descripcion;
                    $evento['Precio'] = $precio;
                    $evento['Imagen_URL'] = $imagen_url;
                } else {
                    $message = "No se realizaron cambios en el evento o no se pudo actualizar.";
                    $message_type = "info";
                }
                $stmt->close();
            } else {
                throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
            }
        } catch (Exception $e) {
            error_log("Error al actualizar evento (ID: $id_evento_edit): " . $e->getMessage());
            $message = "Error al actualizar el evento: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Evento - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin_styles.css" />
</head>
<body>
    <header class="admin-header">
        <h1>Panel de Administración</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Desconocido'); ?>)</p>
    </header>

    <nav class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="users.php">Gestionar Usuarios</a></li>
            <li><a href="eventos.php">Gestionar Eventos</a></li>
            <li><a href="reports.php">Ver Reportes</a></li>
            <li><a href="../logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main class="admin-content">
        <h2>Editar Evento</h2>
        <p>Modifica los detalles del evento.</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($evento): ?>
        <form action="edit_evento.php?id=<?php echo htmlspecialchars($evento['ID_Evento']); ?>" method="POST">
            <input type="hidden" name="id_evento" value="<?php echo htmlspecialchars($evento['ID_Evento']); ?>" />

            <div class="form-group">
                <label for="nombre">Nombre del Evento:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($evento['Nombre'] ?? ''); ?>" required />
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($evento['Descripcion'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?php echo htmlspecialchars($evento['Precio'] ?? ''); ?>" required />
            </div>

            <div class="form-group">
                <label for="imagen_url">URL de la Imagen (opcional):</label>
                <input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($evento['Imagen_URL'] ?? ''); ?>" />
            </div>

            <div class="button-group">
                <button type="submit" class="btn-submit">Guardar Cambios</button>
                <p><a href="eventoadmin.php" class="btn-returnProducto">Volver a la lista de eventos</a></p>
            </div>
        </form>
        <?php endif; ?>
    </main>

    <footer>
        <p style="text-align: center; margin-top: 30px; color: #ffffff;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
    </footer>
</body>
</html>

<?php
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
