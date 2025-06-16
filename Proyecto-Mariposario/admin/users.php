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

// Lógica para obtener y mostrar usuarios (ejemplo simple)
$users_query = "SELECT u.ID_Usuario, u.Nombre, u.Correo, r.Nombre as NombreRol
                FROM Usuario u LEFT JOIN Rol r ON u.ID_Rol = r.ID_Rol";
$users_result = $conn->query($users_query);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Panel de Administración</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
        .admin-header { background-color: #333; color: white; padding: 1em; text-align: center; }
        .admin-nav { background-color: #555; padding: 0.5em; }
        .admin-nav ul { list-style: none; padding: 0; margin: 0; display: flex; justify-content: center; }
        .admin-nav ul li { margin: 0 15px; }
        .admin-nav ul li a { color: white; text-decoration: none; padding: 5px 10px; border-radius: 3px; }
        .admin-nav ul li a:hover { background-color: #777; }
        .admin-content { padding: 20px; max-width: 960px; margin: 20px auto; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Panel de Administración</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['role_name']); ?>)</p>
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
        <h2>Gestionar Usuarios</h2>
        <p>Aquí puedes ver, editar o eliminar usuarios del sistema.</p>

        <?php if ($users_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['ID_Usuario']); ?></td>
                            <td><?php echo htmlspecialchars($user['Nombre']); ?></td>
                            <td><?php echo htmlspecialchars($user['Correo']); ?></td>
                            <td><?php echo htmlspecialchars($user['NombreRol']); ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['ID_Usuario']; ?>">Editar</a> |
                                <a href="delete_user.php?id=<?php echo $user['ID_Usuario']; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay usuarios registrados.</p>
        <?php endif; ?>

        <p><a href="add_user.php">Añadir Nuevo Usuario</a></p>

    </main>

    <footer>
        <p style="text-align: center; margin-top: 30px; color: #777;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
    </footer>
</body>
</html>
<?php
$conn->close();
?>