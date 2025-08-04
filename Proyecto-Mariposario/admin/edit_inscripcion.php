<?php
include '../DB.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}
$id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantidad = intval($_POST['cantidad']);
    $telefono  = $_POST['telefono'];
    $correo    = $_POST['correo'];
    $estado    = $_POST['estado'];
    $asistio   = isset($_POST['asistio']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE Reserva 
        SET Cantidad_Personas = ?, 
            Telefono          = ?, 
            Correo            = ?, 
            Estado            = ?, 
            Asistio           = ?
        WHERE ID_Reserva = ?
    ");
    $stmt->bind_param("isssii", $cantidad, $telefono, $correo, $estado, $asistio, $id);

    if ($stmt->execute()) {
        header("Location: InsEventoAdmin.php?msg=Inscripción actualizada");
        exit;
    } else {
        $error = "Error al guardar cambios: " . $stmt->error;
    }
}

// Traer datos actuales
$stmt = $conn->prepare("SELECT *, Asistio FROM Reserva WHERE ID_Reserva = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$page_title = 'Editar Reserva';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?> - Panel de Administración</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">

<style>
/* Contenedor Principal */
.admin-content {
    max-width: 600px;
    margin: 40px auto;
    background: #fff;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.admin-content h2 {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: #2c3e50;
}
.admin-content p {
    margin-bottom: 25px;
    color: #7f8c8d;
}

/* Alertas */
.alert {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 6px;
    font-size: 0.95rem;
}
.alert-danger {
    background-color: #f8d7da;
    color: #842029;
}

/* Campos del Formulario */
form .form-group {
    margin-bottom: 20px;
}
form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #34495e;
}
form input[type="text"],
form input[type="number"],
form input[type="email"],
form select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}
form input:focus,
form select:focus {
    border-color: #8BC34A;
    outline: none;
    background-color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(139,195,74,0.25);
}

/* Checkbox */
form .form-group label input[type="checkbox"] {
    margin-right: 10px;
}

/* Botones */
.button-group {
    display: flex;
    gap: 15px;
    justify-content: flex-start;
    margin-top: 20px;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 1rem;
    font-weight: 500;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-submit {
    background-color: #28a745;
}
.btn-submit:hover {
    background-color: #218838;
    transform: translateY(-2px);
}
.btn-secondary {
    background-color: #6c757d;
}
.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

/* Responsive */
@media(max-width: 576px) {
    .admin-content {
        margin: 20px;
        padding: 20px;
    }
    .button-group {
        flex-direction: column;
    }
}
</style>
</head>
<body>
<div class="admin-dashboard-layout">
    <aside class="sidebar">
           <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                    <ul>
                        <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li><a href="gestion_empleados.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['gestion_empleados.php', 'add_empleado.php', 'edit_empleado.php'])) ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                        <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                        <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                        <li><a href="inventarioAdmin.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['inventarioAdmin.php', 'add_inventario.php', 'edit_inventario.php'])) ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                        <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                        <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Gestionar Reservas</a></li>
                        <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Gestionar Asistencia</a></li>
                        <li><a href="pedidos.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['pedidos.php', 'edit_pedido.php'])) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                        <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
                        <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                        <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
                        <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Soporte</a></li>  
                    </ul>
            </nav>
    </aside>

    <div class="main-panel">
        <header class="main-panel-header">
            <div class="header-left"><h2><?php echo htmlspecialchars($page_title); ?></h2></div>
            <div class="header-right">
                <div class="user-profile">
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <img src="../images/user-avatar.png" alt="User Avatar">
                </div>
            </div>
        </header>

        <main class="content-area">
            <div class="admin-content">
                <h2><?php echo htmlspecialchars($page_title); ?></h2>
                <p>Modifica los detalles de la inscripción.</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="cantidad">Cantidad de Personas:</label>
                        <input type="number" id="cantidad" name="cantidad" value="<?= intval($res['Cantidad_Personas']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($res['Telefono']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo:</label>
                        <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($res['Correo']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado">
                            <option value="Pendiente" <?= ($res['Estado']=='Pendiente')?'selected':'' ?>>Pendiente</option>
                            <option value="Aprobada" <?= ($res['Estado']=='Aprobada')?'selected':'' ?>>Aprobada</option>
                            <option value="Cancelada" <?= ($res['Estado']=='Cancelada')?'selected':'' ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="asistio" <?= !empty($res['Asistio']) ? 'checked' : '' ?>> Asistió al evento
                        </label>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                        <a href="InsEventoAdmin.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
