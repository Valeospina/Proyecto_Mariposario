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

    <style>
        /* ======= ESTILOS GENERALES ======= */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            margin: 0;
            color: #333;
        }

        .admin-content {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .admin-content h3 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            text-align: center;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }

        /* ======= FORMULARIO ======= */
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #34495e;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            padding: 12px 14px;
            font-size: 1rem;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            transition: 0.3s;
            background: #f9f9f9;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.2);
            outline: none;
            background: #fff;
        }

        .form-group small {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* ======= BOTONES ======= */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: #28a745;
            color: #fff;
        }

        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108,117,125,0.2);
        }

        .btn i {
            font-size: 1rem;
        }

        /* ======= ALERTAS ======= */
        .alert {
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        /* ======= RESPONSIVE ======= */
        @media (max-width: 768px) {
            .admin-content {
                margin: 20px;
                padding: 20px;
            }
            .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="admin-dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
            <nav class="sidebar-nav">
                <div class="menu-scroll">
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
                </div>
            </nav>
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </aside>

    <!-- Main Panel -->
    <div class="main-panel">
        <main class="content-area">
            <div class="admin-content">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <h3>Añadir Ítem de Inventario</h3>

                <form action="add_inventario.php" method="POST" class="admin-form">
                    <!-- Producto -->
                    <div class="form-group">
                        <label for="id_producto">Producto Asociado:</label>
                        <select id="id_producto" name="id_producto" required>
                            <option value="">Seleccione un producto</option>
                            <?php foreach ($products_list as $prod): ?>
                                <option value="<?php echo htmlspecialchars($prod['ID_Producto']); ?>"
                                    <?php echo (isset($_POST['id_producto']) && $_POST['id_producto'] == $prod['ID_Producto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- SKU -->
                    <div class="form-group">
                        <label for="sku">SKU:</label>
                        <input type="text" id="sku" name="sku" required value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                        <small>Identificador único para este ítem.</small>
                    </div>

                    <!-- Stock Actual -->
                    <div class="form-group">
                        <label for="stock_actual">Stock Actual:</label>
                        <input type="number" id="stock_actual" name="stock_actual" min="0" required value="<?php echo htmlspecialchars($_POST['stock_actual'] ?? ''); ?>">
                    </div>

                    <!-- Stock Mínimo -->
                    <div class="form-group">
                        <label for="stock_minimo">Stock Mínimo:</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" min="0" required value="<?php echo htmlspecialchars($_POST['stock_minimo'] ?? ''); ?>">
                        <small>Nivel mínimo para alertas de reabastecimiento.</small>
                    </div>

                    <!-- Ubicación -->
                    <div class="form-group">
                        <label for="ubicacion">Ubicación:</label>
                        <input type="text" id="ubicacion" name="ubicacion" required value="<?php echo htmlspecialchars($_POST['ubicacion'] ?? ''); ?>">
                        <small>Ej: Almacén A, Estantería 3.</small>
                    </div>

                    <!-- Activo -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="activo" name="activo" value="1" checked>
                        <label for="activo">Ítem Activo</label>
                    </div>

                    <!-- Botones -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Ítem</button>
                        <a href="inventarioAdmin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
