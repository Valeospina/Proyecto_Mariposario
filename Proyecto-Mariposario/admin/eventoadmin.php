<?php
session_start();
include '../DB.php'; 

// Protección de la página de administración
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

if ($_SESSION['user_role'] != 1) {
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
    echo "<p style='color: red;'>Error al cargar los eventos: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Eventos - Panel de Administración</title>
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
        <h2>Gestionar Eventos</h2>
        <p>Aquí puedes ver, editar o eliminar eventos del sistema.</p>

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
                            <td><?php echo htmlspecialchars($evento['ID_Evento']); ?></td>
                            <td><?php echo htmlspecialchars($evento['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($evento['Descripcion']); ?></td>
                            <td>₡<?php echo number_format($evento['Precio'], 2); ?></td>
                            <td class="action-links">
                                <a href="edit_evento.php?id=<?php echo $evento['ID_Evento']; ?>">Editar</a> |
                                <a href="delete_evento.php?id=<?php echo $evento['ID_Evento']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este evento?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay eventos registrados.</p>
        <?php endif; ?>

        <p><a href="add_evento.php">Añadir Nuevo Evento</a></p>
    </main>

    <footer>
        <p style="text-align: center; margin-top: 30px; color: #ffffff;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
    </footer>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
