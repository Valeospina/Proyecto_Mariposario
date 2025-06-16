<?php
session_start();

// include '../DB.php'; 

// **Protección de la página de administración:**
// 1. Verifica si el usuario ha iniciado sesión.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html'); // Redirige a login si no hay sesión
    exit;
}

// 2. Verifica si el rol del usuario es administrador (ID_Rol = 1).
if ($_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); // Redirige a la página principal si no es admin
    exit;
}

// Si llega aquí, el usuario es un administrador y puede ver el contenido
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Mi Sitio</title>
    <link rel="stylesheet" href="../css/admin_styles.css">
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
        <h2>Resumen del Sistema</h2>
        <p>Aquí puedes ver un resumen rápido de las actividades y estadísticas importantes.</p>
        <ul>
            <li>Número total de usuarios: [Aquí podrías mostrar un conteo de la DB]</li>
            <li>Productos en stock: [Aquí podrías mostrar un conteo de la DB]</li>
            <li>Últimas actividades de registro: [Aquí podrías mostrar datos del Registro_Actividad]</li>
        </ul>

        <h3>Acciones Rápidas</h3>
        <button onclick="location.href='users.php'">Gestionar Usuarios</button>
        <button onclick="location.href='products.php'">Gestionar Productos</button>
        <button onclick="location.href='eventoAdmin.php'">Gestionar Eventos</button>

        </main>

    <footer>
        <p style="text-align: center; margin-top: 30px; color: #ffffff;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
    </footer>
</body>
</html>