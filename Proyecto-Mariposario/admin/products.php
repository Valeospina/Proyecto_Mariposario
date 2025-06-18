<?php
session_start();
include '../DB.php'; 

// Inicializar variables de mensaje para evitar warnings (aunque no se usen aquí directamente, es buena práctica)
$message = '';
$message_type = '';

// **Protección de la página de administración:**
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

// Lógica para obtener y mostrar productos
// Consulta la tabla 'Producto' con los nombres de columnas de tu DB
$products_query = "SELECT ID_Producto, Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL
                   FROM Producto"; 

$products_result = null;
$products = []; 

try {
    if (isset($conn) && $conn instanceof mysqli) {
        $products_result = $conn->query($products_query);
        if ($products_result) {
            while ($row = $products_result->fetch_assoc()) {
                $products[] = $row;
            }
        } else {
            throw new Exception("Error en la consulta SQL: " . $conn->error);
        }
    } else {
        throw new Exception("Error: La conexión a la base de datos no está disponible o no es MySQLi.");
    }
} catch (Exception $e) {
    error_log("Error al obtener productos: " . $e->getMessage());
    // Muestra un mensaje de error legible al usuario
    $message = "Error al cargar los productos: " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

// Lógica para mostrar mensajes si vienen de una redirección (ej. después de un ADD, EDIT, DELETE)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Define el título de la página actual
$page_title = 'Gestionar Productos';

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

                    <h2>Gestionar Productos</h2>
                    <p>Aquí puedes ver, editar o eliminar productos (mariposas y orquídeas) del sistema.</p>

                    <p style="margin-bottom: 25px;"><a href="add_product.php" class="btn btn-add-product"><i class="fas fa-plus"></i> Añadir Nuevo Producto</a></p>

                    <?php if (!empty($products)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td data-label="ID:"><?php echo htmlspecialchars($product['ID_Producto']); ?></td>
                                        <td data-label="Nombre:"><?php echo htmlspecialchars($product['Nombre']); ?></td>
                                        <td data-label="Categoría:"><?php echo htmlspecialchars($product['Categoria']); ?></td>
                                        <td data-label="Precio:"><?php echo htmlspecialchars($product['Precio']); ?></td>
                                        <td data-label="Stock:"><?php echo htmlspecialchars($product['Stock']); ?></td>
                                        <td data-label="Acciones:" class="action-links">
                                            <a class="btn btn-action-edit" href="edit_product.php?id=<?php echo htmlspecialchars($product['ID_Producto']); ?>"><i class="fas fa-edit"></i> Editar</a>
                                            <a class="btn btn-action-delete" href="delete_product.php?id=<?php echo htmlspecialchars($product['ID_Producto']); ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');"><i class="fas fa-trash-alt"></i> Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay productos registrados.</p>
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