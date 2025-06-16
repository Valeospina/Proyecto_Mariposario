<?php
session_start();
include '../DB.php'; 

// **Protección de la página de administración:**
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if ($_SESSION['user_role'] != 1) {
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
    echo "<p style='color: red;'>Error al cargar los productos: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin_styles.css">

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
            <li><a href="products.php">Gestionar Productos</a></li>
            <li><a href="eventoAdmin.php">Gestionar Eventos</a></li>
            <li><a href="reports.php">Ver Reportes</a></li>
            <li><a href="../logout.php">Cerrar Sesión</a></li>
        </ul>
    </nav>

    <main class="admin-content">
        <h2>Gestionar Productos</h2>
        <p>Aquí puedes ver, editar o eliminar productos (mariposas y orquídeas) del sistema.</p>

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
                            <td><?php echo htmlspecialchars($product['ID_Producto']); ?></td>
                            <td><?php echo htmlspecialchars($product['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($product['Categoria']); ?></td>
                            <td><?php echo htmlspecialchars($product['Precio']); ?></td>
                            <td><?php echo htmlspecialchars($product['Stock']); ?></td>
                            <td class="action-links">
                                <a class="btn-edit" href="edit_product.php?id=<?php echo $product['ID_Producto']; ?>">Editar</a>
                                <a class="btn-delete" href="delete_product.php?id=<?php echo $product['ID_Producto']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay productos registrados.</p>
        <?php endif; ?>

        <p><a href="add_product.php">Añadir Nuevo Producto</a></p>

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