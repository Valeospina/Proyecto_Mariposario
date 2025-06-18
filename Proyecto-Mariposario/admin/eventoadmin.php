<?php
session_start();
include '../DB.php'; 

// Inicializar variables de mensaje para evitar warnings (buena práctica)
$message = '';
$message_type = '';

// Protección de la página de administración
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) { // Añadido isset para user_role
    header('Location: ../index.php');
    exit;
}

// Consulta para obtener los eventos
$eventos_query = "SELECT ID_Evento, Nombre, Descripcion, Precio, Imagen_URL FROM Evento";
$eventos_result = null;
$eventos = [];

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $eventos_result = $conn->query($eventos_query);
        if ($eventos_result) {
            while ($row = $eventos_result->fetch_assoc()) {
                $eventos[] = $row;
            }
        } else {
            throw new Exception("Error en la consulta SQL: " . $conn->error);
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al obtener eventos: " . $e->getMessage());
    // Muestra un mensaje de error legible al usuario
    $message = "Error al cargar los eventos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección (ej. después de un ADD, EDIT, DELETE)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Define el título de la página actual
$page_title = 'Gestionar Eventos';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css"> </head>
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
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h2>Gestionar Eventos</h2>
                    <p>Aquí puedes ver, editar o eliminar eventos del sistema.</p>

                    <p style="margin-bottom: 25px;"><a href="add_evento.php" class="btn btn-add-product"><i class="fas fa-plus"></i> Añadir Nuevo Evento</a></p>

                    <?php if (!empty($eventos)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventos as $evento): ?>
                                    <tr>
                                        <td data-label="ID:"><?php echo htmlspecialchars($evento['ID_Evento']); ?></td>
                                        <td data-label="Nombre:"><?php echo htmlspecialchars($evento['Nombre']); ?></td>
                                        <td data-label="Descripción:"><?php echo htmlspecialchars($evento['Descripcion']); ?></td>
                                        <td data-label="Precio:">₡<?php echo number_format($evento['Precio'], 2); ?></td>
                                        <td data-label="Acciones:" class="action-links">
                                            <a class="btn btn-action-edit" href="edit_evento.php?id=<?php echo htmlspecialchars($evento['ID_Evento']); ?>"><i class="fas fa-edit"></i> Editar</a>
                                            <a class="btn btn-action-delete" href="delete_evento.php?id=<?php echo htmlspecialchars($evento['ID_Evento']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este evento?');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay eventos registrados.</p>
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
