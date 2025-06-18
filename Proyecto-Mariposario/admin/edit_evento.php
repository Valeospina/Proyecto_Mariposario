<?php
session_start();
include '../DB.php'; // Incluye tu archivo de conexión a la base de datos

// Protección de la página de administración
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html'); // Redirige si no está logueado o no es admin
    exit;
}

$evento = null;
$message = '';
$message_type = '';

// Obtener el ID del evento de la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $evento_id = intval($_GET['id']); // Asegúrate de sanitizar el ID

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
                // "ssdsi" - string, string, double, string, integer
                $stmt->bind_param("ssdsi", $nombre, $descripcion, $precio, $imagen_url, $id_evento_edit);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Evento actualizado exitosamente.";
                    $message_type = "success";
                    // Recargar los datos del evento para mostrar los cambios (si no se redirecciona)
                    $evento['Nombre'] = $nombre;
                    $evento['Descripcion'] = $descripcion;
                    $evento['Precio'] = $precio;
                    $evento['Imagen_URL'] = $imagen_url;

                    // Opcional: Redirigir a la lista de eventos después de la actualización
                    // header('Location: eventoAdmin.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    // exit;
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

// Define el título de la página actual
$page_title = 'Editar Evento';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css" /> </head>
<body>

    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </aside>

        <div class="main-panel">
            <header class="main-panel-header">
                <div class="header-left">
                    <h2><?php echo $page_title; ?></h2>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <input type="text" placeholder="Buscar...">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="user-profile">
                        <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <img src="../images/user-avatar.png" alt="User Avatar">
                    </div>
                </div>
            </header>

            <main class="content-area">
                <div class="admin-content">
                    <h2>Editar Evento</h2>
                    <p>Modifica los detalles del evento.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($evento): ?>
                        <div class="form-container"> <h3>Información del Evento</h3>
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
                                    <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                                    <a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de eventos</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>No se pudo cargar el evento para editar. Por favor, asegúrese de que el ID es válido.</p>
                        <p><a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de eventos</a></p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

</body>
</html>

<?php
// Cierra la conexión a la base de datos si está abierta y es MySQLi
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
