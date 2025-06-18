<?php
session_start();
include '../DB.php'; // Archivo de conexión

// Protección: solo admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''));
    $precio = floatval($_POST['precio'] ?? 0);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url'] ?? ''));

    if (empty($nombre) || empty($descripcion) || $precio <= 0) {
        $message = "Completa correctamente todos los campos obligatorios.";
        $message_type = "danger";
    } else {
        $query = "INSERT INTO Evento (Nombre, Descripcion, Precio, Imagen_URL) VALUES (?, ?, ?, ?)";
        try {
            if ($conn instanceof mysqli) {
                $stmt = $conn->prepare($query);
                // "ssds" - string, string, double, string
                $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen_url);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Evento agregado exitosamente.";
                    $message_type = "success";
                    // Limpiar el formulario después de un éxito (opcional, si quieres que se quede en la página para añadir más)
                    $_POST = array(); 
                    // Opcional: Redirigir a eventoAdmin.php con un mensaje de éxito
                    // header('Location: eventoAdmin.php?message=' . urlencode($message) . '&type=' . urlencode($message_type));
                    // exit;
                } else {
                    $message = "No se pudo agregar el evento.";
                    $message_type = "warning";
                }
                $stmt->close();
            } else {
                throw new Exception("Conexión a la base de datos no válida o no disponible.");
            }
        } catch (Exception $e) {
            error_log("Error al añadir evento: " . $e->getMessage());
            $message = "Error: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Define el título de la página actual
$page_title = 'Añadir Nuevo Evento';
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
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php' || basename($_SERVER['PHP_SELF']) == 'add_evento.php' || basename($_SERVER['PHP_SELF']) == 'edit_evento.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
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
                    <h2>Añadir Nuevo Evento</h2>
                    <p>Completa el formulario para agregar un nuevo evento.</p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-container"> <h3>Detalles del Nuevo Evento</h3>
                        <form action="add_evento.php" method="POST">
                            <div class="form-group">
                                <label for="nombre">Nombre del Evento:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required />
                            </div>

                            <div class="form-group">
                                <label for="descripcion">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="precio">Precio:</label>
                                <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" required />
                            </div>

                            <div class="form-group">
                                <label for="imagen_url">URL de la Imagen (opcional):</label>
                                <input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($_POST['imagen_url'] ?? ''); ?>" />
                            </div>

                            <div class="button-group">
                                <button type="submit" class="btn btn-submit"><i class="fas fa-plus-circle"></i> Añadir Evento</button>
                                <a href="eventoAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a la lista de eventos</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>