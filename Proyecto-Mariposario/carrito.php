<?php
session_start(); // Inicia la sesión al principio de todo

// --- Simulación de datos de usuario (reemplazar con tu lógica de inicio de sesión real) ---
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Si el usuario no está logueado, establece datos por defecto
    $_SESSION['user_name'] = 'Usuario'; // Nombre por defecto
    $_SESSION['user_avatar'] = 'img/default-avatar.png'; // Ruta a una imagen de avatar por defecto
    $_SESSION['user_points'] = 0;
    $_SESSION['user_logged_in'] = false; // Indica que el usuario no está realmente logueado
} else {
    // Si el usuario ya está logueado, puedes cargar sus datos reales desde la base de datos o donde los almacenes
    // Por ejemplo, si tienes un array $_SESSION['user_data'] con 'name', 'avatar', 'points'
    // $_SESSION['user_name'] = $_SESSION['user_data']['name'];
    // $_SESSION['user_avatar'] = $_SESSION['user_data']['avatar'];
    // $_SESSION['user_points'] = $_SESSION['user_data']['points'];
}

// Define la variable $response al inicio para evitar errores de referencia
$response = ['success' => false, 'message' => ''];

// --- Lógica para Peticiones AJAX (POST y GET para el carrito) ---
// Identificamos si la petición es AJAX y qué tipo de petición es
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    // Si es una solicitud POST, se asume que es para AGREGAR, ACTUALIZAR o ELIMINAR del carrito
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? ''; // Obtener la acción solicitada

        if ($action === 'add') {
            // Lógica para AGREGAR un producto (ya existente)
            if (isset($_POST['id'], $_POST['nombre'], $_POST['precio'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
                $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);

                if ($id !== false && $id > 0 && $nombre !== false && $precio !== false && $precio >= 0) {
                    if (!isset($_SESSION['carrito'])) {
                        $_SESSION['carrito'] = [];
                    }

                    $found = false;
                    foreach ($_SESSION['carrito'] as &$item) {
                        if ($item['id'] == $id) {
                            $item['cantidad']++;
                            $found = true;
                            break;
                        }
                    }
                    unset($item);

                    if (!$found) {
                        $_SESSION['carrito'][] = [
                            'id' => $id,
                            'nombre' => $nombre,
                            'precio' => $precio,
                            'cantidad' => 1
                        ];
                    }

                    // Acumular puntos solo cuando se agrega un producto al carrito
                    // Puedes definir una lógica más compleja para los puntos, por ejemplo, 10 puntos por cada ₡1000 gastados
                    if ($_SESSION['user_logged_in']) { // Solo acumula puntos si el usuario está "logueado"
                        $_SESSION['user_points'] += 10; // Ejemplo: 10 puntos por cada producto agregado
                    }


                    $response['success'] = true;
                    $response['message'] = 'Producto agregado al carrito.';
                    $response['total_items'] = array_sum(array_column($_SESSION['carrito'], 'cantidad'));
                    $response['user_points'] = $_SESSION['user_points']; // Enviar los puntos actualizados
                } else {
                    $response['message'] = 'Datos de producto inválidos.';
                }
            } else {
                $response['message'] = 'Faltan datos para agregar el producto.';
            }
        } elseif ($action === 'update_quantity') {
            // Lógica para ACTUALIZAR la cantidad de un producto
            if (isset($_POST['id'], $_POST['cantidad'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $cantidad = filter_var($_POST['cantidad'], FILTER_SANITIZE_NUMBER_INT);

                if ($id !== false && $id > 0 && $cantidad !== false && $cantidad >= 0) {
                    if (isset($_SESSION['carrito'])) {
                        foreach ($_SESSION['carrito'] as &$item) {
                            if ($item['id'] == $id) {
                                $item['cantidad'] = $cantidad;
                                $response['success'] = true;
                                $response['message'] = 'Cantidad actualizada.';
                                break;
                            }
                        }
                        unset($item); // Romper la referencia
                    }
                    if (!$response['success']) {
                        $response['message'] = 'Producto no encontrado en el carrito.';
                    }
                } else {
                    $response['message'] = 'Datos de actualización de cantidad inválidos.';
                }
            } else {
                $response['message'] = 'Faltan datos para actualizar la cantidad.';
            }
        } elseif ($action === 'remove') {
            // Lógica para ELIMINAR un producto
            if (isset($_POST['id'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);

                if ($id !== false && $id > 0) {
                    if (isset($_SESSION['carrito'])) {
                        $initial_count = count($_SESSION['carrito']);
                        $_SESSION['carrito'] = array_filter($_SESSION['carrito'], function($item) use ($id) {
                            return $item['id'] != $id;
                        });
                        // Reindexar el array para evitar problemas con foreach y empty()
                        $_SESSION['carrito'] = array_values($_SESSION['carrito']);

                        if (count($_SESSION['carrito']) < $initial_count) {
                            $response['success'] = true;
                            $response['message'] = 'Producto eliminado del carrito.';
                        } else {
                            $response['message'] = 'Producto no encontrado en el carrito.';
                        }
                    } else {
                        $response['message'] = 'El carrito está vacío.';
                    }
                } else {
                    $response['message'] = 'ID de producto inválido para eliminar.';
                }
            } else {
                $response['message'] = 'Falta ID del producto para eliminar.';
            }
        } else {
            $response['message'] = 'Acción POST no reconocida.';
        }
    }
    // Si es una solicitud GET y se pide la cantidad de ítems
    else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_count') {
        $total_items = 0;
        if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $item) {
                $total_items += $item['cantidad'];
            }
        }
        $response['success'] = true;
        $response['total_items'] = $total_items;
        $response['message'] = 'Cantidad de items obtenida.'; // Mensaje opcional
        $response['user_name'] = $_SESSION['user_name'];
        $response['user_avatar'] = $_SESSION['user_avatar'];
        $response['user_points'] = $_SESSION['user_points'];

    }
    // Si es una solicitud GET que no es para obtener la cantidad
    else {
        $response['message'] = 'Método de solicitud o acción no permitida para AJAX.';
    }

    // Calcular el total de ítems en el carrito después de la acción para enviarlo siempre
    $response['total_items'] = 0;
    if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $response['total_items'] += $item['cantidad'];
        }
    }
    // Calcular el nuevo subtotal para el item actualizado (si aplica)
    if ($action === 'update_quantity' && $response['success'] && isset($id)) {
        foreach ($_SESSION['carrito'] as $item) {
            if ($item['id'] == $id) {
                $response['new_subtotal'] = $item['precio'] * $item['cantidad'];
                break;
            }
        }
    }

    // Siempre que sea una petición AJAX, devolvemos JSON y salimos
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // ¡Muy importante para detener la ejecución y no enviar HTML!
}

// --- Lógica para la Visualización de la Página del Carrito (Petición HTTP normal) ---
// Si no es una petición AJAX, entonces mostramos la página completa del carrito
// (Este bloque no se ejecuta si la petición es AJAX, gracias al exit() de arriba)

// Ejemplo básico de cómo podrías mostrar el carrito
$carrito_actual = $_SESSION['carrito'] ?? []; // Obtener el carrito de la sesión
$total_carrito_final = 0; // Usar una variable diferente para evitar conflictos
foreach ($carrito_actual as $item) {
    $total_carrito_final += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="css/custom-cart-user.css">

</head>
<body>
    <header class="header">
        <div class="topbar">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-5 col-12">
                        <ul class="top-link">
                            <li>
                                <a href="usuario.html" class="user-profile-link">
                                    <img src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" alt="Avatar de Usuario" class="user-avatar">
                                    <div class="user-info-text">
                                        <span class="user-greeting">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                        <span class="user-points-display" id="user-points-display"><?php echo htmlspecialchars($_SESSION['user_points']); ?> Puntos</span>
                                    </div>
                                </a>
                            </li>
                            <li class="admin-link"><a href="admin.html">Admin</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-6 col-md-7 col-12">
                        <ul class="top-contact">
                            <li><i class="fa fa-phone"></i>+506 8888 8888</li>
                            <li><i class="fa fa-envelope"></i><a href="mailto:info@mariposario.com">info@mariposario.com</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-inner">
            <div class="container">
                <div class="inner">
                    <div class="row">
                        <div class="col-lg-3 col-md-3 col-12">
                            <div class="logo">
                                <a href="index.html"><img src="img/logo.png" alt="Logo Mariposario"></a>
                            </div>
                            <div class="mobile-nav"></div>
                        </div>
                        <div class="col-lg-7 col-md-9 col-12">
                            <div class="main-menu">
                                <nav class="navigation">
                                    <ul class="nav menu">
                                        <li><a href="index.html">Inicio</a></li>
                                        <li><a href="mariposas.php">Mariposas</a></li>
                                        <li><a href="orquideas.php">Orquideas</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="col-lg-2 col-12">
                            <div class="get-quote">
                                <a href="carrito.php" class="btn btn-primary btn-shopping-cart">
                                    <i class="fa fa-shopping-cart cart-icon"></i>
                                    <span class="cart-item-count" id="cart-item-count">
                                        <?php
                                            $total_items_header = 0;
                                            if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
                                                foreach ($_SESSION['carrito'] as $item) {
                                                    $total_items_header += $item['cantidad'];
                                                }
                                            }
                                            echo $total_items_header;
                                        ?>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-5">
        <h2 class="mb-4">Tu Carrito de Compras</h2>
        <?php if (empty($carrito_actual)): ?>
            <div class="alert alert-info">Tu carrito está vacío. ¡Agrega algunos productos!</div>
        <?php else: ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="cart-items-body"> <?php foreach ($carrito_actual as $item): ?>
                    <tr data-product-id="<?php echo htmlspecialchars($item['id']); ?>"> <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                        <td class="product-price" data-price="<?php echo htmlspecialchars($item['precio']); ?>">₡<?php echo number_format($item['precio'], 2, ',', '.'); ?></td>
                        <td>
                            <div class="input-group" style="width: 120px;">
                                <div class="input-group-prepend">
                                    <button class="btn btn-outline-secondary btn-sm update-quantity" type="button" data-action="decrease" data-id="<?php echo htmlspecialchars($item['id']); ?>">-</button>
                                </div>
                                <input type="text" class="form-control form-control-sm text-center product-quantity" value="<?php echo htmlspecialchars($item['cantidad']); ?>" data-id="<?php echo htmlspecialchars($item['id']); ?>" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm update-quantity" type="button" data-action="increase" data-id="<?php echo htmlspecialchars($item['id']); ?>">+</button>
                                </div>
                            </div>
                        </td>
                        <td class="product-subtotal" data-subtotal="<?php echo htmlspecialchars($item['precio'] * $item['cantidad']); ?>">₡<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger remove-cart-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">Eliminar</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Total del Carrito:</strong></td>
                        <td id="total-carrito-display"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div class="text-right">
                <a href="mariposas.php" class="btn btn-secondary">Seguir Comprando</a>
                <button class="btn btn-success">Proceder al Pago</button>
            </div>
        <?php endif; ?>
    </div>

    <footer id="footer" class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Sobre Nosotros</h2>
                            <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
                            <ul class="social">
                                <li><a href="#"><i class="icofont-facebook"></i></a></li>
                                <li><a href="#"><i class="icofont-instagram"></i></a></li>
                                <li><a href="#"><i class="icofont-twitter"></i></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer f-link">
                            <h2>Enlaces Rápidos</h2>
                            <div class="row">
                                <div class="col-12">
                                    <ul>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Reservaciones</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Eventos</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Contáctanos</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Horario de Atención</h2>
                            <p>Visítanos para vivir una experiencia rodeado de naturaleza y belleza.</p>
                            <ul class="time-sidual">
                                <li class="day">Lunes - Viernes <span>8:00 - 17:00</span></li>
                                <li class="day">Sábado <span>9:00 - 16:00</span></li>
                                <li class="day">Domingo <span>Cerrado</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Boletín</h2>
                            <p>Suscríbete para recibir noticias sobre nuestras mariposas, orquídeas y próximos eventos especiales.</p>
                            <form action="#" method="get" target="_blank" class="newsletter-inner">
                                <input name="email" placeholder="Tu correo electrónico" class="common-input" required type="email">
                                <button class="button"><i class="icofont icofont-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="copyright-content">
                            <p>© 2025 Mariposas y Orquídeas | Todos los derechos reservados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate-3.0.0.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/easing.js"></script>
    <script src="js/colors.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap-datepicker.js"></script>
    <script src="js/jquery.nav.js"></script>
    <script src="js/slicknav.min.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/niceselect.js"></script>
    <script src="js/tilt.jquery.min.js"></script>
    <script src="js/owl-carousel.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/steller.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/carrito.js"></script>
    <script src="js/user-cart-updates.js"></script>
</body>
</html>