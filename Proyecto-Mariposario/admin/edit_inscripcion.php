<?php
include '../DB.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}
$id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantidad = intval($_POST['cantidad']);
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $estado = $_POST['estado'];

    $stmt = $conn->prepare("UPDATE Reserva SET Cantidad_Personas=?, Telefono=?, Correo=?, Estado=? WHERE ID_Reserva=?");
    $stmt->bind_param("isssi", $cantidad, $telefono, $correo, $estado, $id);

    if ($stmt->execute()) {
        header("Location: InsEventoAdmin.php?msg=Inscripción actualizada");
    } else {
        $error = "Error al guardar cambios.";
    }
}

$stmt = $conn->prepare("SELECT * FROM Reserva WHERE ID_Reserva = ?");
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
    <title><?php echo $page_title; ?> - Panel de Administración</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='dashboard.php')?'active':''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='users.php')?'active':''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='products.php')?'active':''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li>
                        <a href="eventoAdmin.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['eventoAdmin.php','add_evento.php','edit_evento.php']) ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> Gestionar Eventos
                        </a>
                    </li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF'])=='reports.php')?'active':''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
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
                    <h2><?php echo $page_title; ?></h2>
                    <p>Modifica los detalles de la inscripción.</p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                            <div class="form-group">
                                <label for="cantidad">Cantidad de Personas:</label>
                                <input type="number" id="cantidad" name="cantidad" value="<?= $res['Cantidad_Personas'] ?>" required>
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

                            <div class="button-group">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="InsEventoAdmin.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
