<?php
session_start(); // Inicia la sesión al principio de todo

// --- Simulación de datos de usuario 
// Initialize session variables if they don't exist
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = false;
}
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuario';
}
if (!isset($_SESSION['user_avatar'])) {
    $_SESSION['user_avatar'] = 'img/default-avatar.png';
}
if (!isset($_SESSION['user_points'])) {
    $_SESSION['user_points'] = 0;
}
// Ensure $_SESSION['carrito'] is always an array
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Define la variable $response al inicio para evitar errores de referencia
$response = ['success' => false, 'message' => ''];

// Function to calculate total items in cart
function getTotalCartItems() {
    $total_items = 0;
    if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $total_items += $item['cantidad'];
        }
    }
    return $total_items;
}

// Function to calculate cart total amount
function getCartTotalAmount() {
    $total_amount = 0;
    if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $total_amount += $item['precio'] * $item['cantidad'];
        }
    }
    return $total_amount;
}

// --- Lógica para Peticiones AJAX (POST y GET para el carrito) ---
// Identificamos si la petición es AJAX y qué tipo de petición es
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    // Si es una solicitud POST, se asume que es para AGREGAR, ACTUALIZAR o ELIMINAR del carrito
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? ''; // Obtener la acción solicitada

        if ($action === 'add') {
            // Lógica para AGREGAR un producto
            if (isset($_POST['id'], $_POST['nombre'], $_POST['precio'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
                $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
                $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
                // Assuming imagen_url might be passed, if not, it will be an empty string
                $imagen_url = filter_var($_POST['imagen_url'] ?? '', FILTER_SANITIZE_URL); 

                if ($id !== false && $id > 0 && $nombre !== false && $precio !== false && $precio >= 0) {
                    $found = false;
                    foreach ($_SESSION['carrito'] as &$item) {
                        if ($item['id'] == $id) {
                            $item['cantidad']++;
                            $found = true;
                            break;
                        }
                    }
                    unset($item); // Break the reference

                    if (!$found) {
                        $_SESSION['carrito'][] = [
                            'id' => $id,
                            'nombre' => $nombre,
                            'precio' => $precio,
                            'cantidad' => 1,
                            'imagen_url' => $imagen_url // Add image URL to cart item
                        ];
                    }

                    // Acumular puntos solo cuando se agrega un producto al carrito
                    if ($_SESSION['user_logged_in']) {
                        $_SESSION['user_points'] += 10; // Example: 10 points per product added
                    }

                    $response['success'] = true;
                    $response['message'] = 'Producto agregado al carrito.';
                    $response['total_items'] = getTotalCartItems();
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

                if ($id !== false && $id > 0 && $cantidad !== false) { // Allow quantity to be 0 for removal
                    if (isset($_SESSION['carrito'])) {
                        $found = false;
                        foreach ($_SESSION['carrito'] as $key => &$item) {
                            if ($item['id'] == $id) {
                                if ($cantidad > 0) {
                                    $item['cantidad'] = $cantidad;
                                    $response['success'] = true;
                                    $response['message'] = 'Cantidad actualizada.';
                                } else {
                                    // If quantity is 0, remove the item
                                    unset($_SESSION['carrito'][$key]);
                                    $_SESSION['carrito'] = array_values($_SESSION['carrito']); // Reindex array
                                    $response['success'] = true;
                                    $response['message'] = 'Producto eliminado del carrito.';
                                }
                                $found = true;
                                break;
                            }
                        }
                        unset($item); // Romper la referencia

                        if (!$found) {
                            $response['message'] = 'Producto no encontrado en el carrito.';
                        }
                    } else {
                        $response['message'] = 'El carrito está vacío.';
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
    // Si es una solicitud GET y se pide la cantidad de ítems o el carrito completo (para actualizar)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get_count') {
            $response['success'] = true;
            $response['total_items'] = getTotalCartItems();
            $response['message'] = 'Cantidad de items obtenida.';
            $response['user_name'] = $_SESSION['user_name'];
            $response['user_avatar'] = $_SESSION['user_avatar'];
            $response['user_points'] = $_SESSION['user_points'];
        } elseif ($action === 'get_cart_data') {
            // Return the entire cart data for dynamic update on the cart page
            $response['success'] = true;
            $response['carrito'] = $_SESSION['carrito'] ?? [];
            $response['total_items'] = getTotalCartItems();
            $response['cart_total_amount'] = getCartTotalAmount();
        } else {
            $response['message'] = 'Acción GET no reconocida.';
        }
    }
    else {
        $response['message'] = 'Método de solicitud no permitido para AJAX.';
    }

    // Calcular el total de ítems en el carrito después de la acción para enviarlo siempre
    $response['total_items'] = getTotalCartItems();
    $response['cart_total_amount'] = getCartTotalAmount(); // Also send total amount

    // Siempre que sea una petición AJAX, devolvemos JSON y salimos
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // ¡Muy importante para detener la ejecución y no enviar HTML!
}


// Ejemplo básico de cómo mostrar el carrito
$carrito_actual = $_SESSION['carrito'] ?? []; // Obtener el carrito de la sesión
$total_carrito_final = getCartTotalAmount();
?>
<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="keywords" content="Site keywords here">
        <meta name="description" content="">
        <meta name='copyright' content=''>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Jardin De Mariposas - Mariposas</title> <link rel="icon" href="img/favicon.png">
        <link rel="stylesheet" href="./css/tienda.css">

       
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/nice-select.css">
        <link rel="stylesheet" href="css/font-awesome.min.css">
        <link rel="stylesheet" href="css/icofont.css">
        <link rel="stylesheet" href="css/slicknav.min.css">
        <link rel="stylesheet" href="css/owl-carousel.css">
        <link rel="stylesheet" href="css/datepicker.css">
        <link rel="stylesheet" href="css/animate.min.css">
        <link rel="stylesheet" href="css/magnific-popup.css">
        <link rel="stylesheet" href="css/tienda.css">

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="css/responsive.css">
        <link rel="stylesheet" href="css/carrito.css">

</head>
    <body>
        <header class="header">
            <div class="topbar">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6 col-md-5 col-12">
                            <ul class="top-link">
                                <li>
                                    <a href="usuario.html" style="text-decoration: none;">
                                        <i class="fas fa-user" style="font-size: 18px; color: #80B78D; padding: 6px;"></i>
                                        <span style="color: #2C2D3F;">Usuario</span>
                                    </a>
                                </li>
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
                                            <li><a href="tienda.html">Tienda</a></li>
                                            <li><a href="mariposas.php">Mariposas</a></li> 
                                            <li><a href="orquideas.php">Orquideas</a></li>
                                            <li><a href="eventos.html">Eventos</a></li>
                                    
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div class="col-lg-2 col-12">
                                <div class="get-quote">
                                    <a href="carrito.php" class="btn btn-carrito" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: transparent; box-shadow: none;">
                                        <i class="fa fa-shopping-cart icono-carrito" style="color: #42764D; font-size: 20px;"></i>
                                        <span id="cart-item-count" class="badge badge-pill badge-danger" style="position: absolute; top: -5px; right: -5px; background-color: #dc3545; color: white; font-size: 10px; padding: 3px 6px; border-radius: 50%;">
                                            <?php echo getTotalCartItems(); ?>
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
        <h2 class="text-center mb-4">Tu Carrito de Compras</h2>

        <!-- INICIO SECCIÓN DE PUNTOS DEL CLIENTE -->
            <div class="carrito-puntos-wrapper mb-4 p-4 shadow-sm">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="puntos-texto">
                        <h5 class="mb-1">¡Hola Usuario! Tienes <span class="puntos-numero">0</span> puntos disponibles </h5>
                        <p class="mb-0 text-muted">Canjéalos en tu próxima compra o acumula más para obtener mejores beneficios en tus proximas compras y reservas.</p>
                    </div>
                    <div class="puntos-acciones d-flex gap-2">
                        <button class="btn btn-primary btn-sm">Canjear Puntos</button>
                    </div>
                </div>
            </div>
            <!-- FIN SECCIÓN DE PUNTOS DEL CLIENTE -->

        <?php if (empty($carrito_actual)): ?>
            <div class="alert alert-info text-center" role="alert">
                Tu carrito está vacío. ¡Explora nuestros productos y agrega algunos!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-body">
                        <?php foreach ($carrito_actual as $item): ?>
                            <tr data-id="<?php echo htmlspecialchars($item['id']); ?>" class="carrito-item-row">
                                <td data-label="Producto:" class="d-flex align-items-center">
                                    <?php if (!empty($item['imagen_url'])): ?>
                                        <div class="carrito-producto-imagen me-3">
                                            <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <span class="carrito-producto-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                </td>
                                <td data-label="Precio Unitario:" class="carrito-precio">₡<?php echo number_format($item['precio'], 2, ',', '.'); ?></td>
                                <td data-label="Cantidad:">
                                    <div class="input-group input-group-sm quantity-control">
                                        <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">-</button>
                                        <input type="text" class="form-control text-center product-quantity" value="<?php echo htmlspecialchars($item['cantidad']); ?>" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-price="<?php echo htmlspecialchars($item['precio']); ?>" readonly>
                                        <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">+</button>
                                    </div>
                                </td>
                                <td data-label="Subtotal:" class="item-subtotal">₡<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?></td>
                                <td data-label="Acciones:" class="carrito-item-actions">
                                    <button class="btn btn-danger btn-sm btn-remove-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                        <i class="fa fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total del Carrito:</strong></td>
                            <td id="cart-total-amount"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <div class="text-right mt-3">
            <button class="btn btn-success btn-lg btn-proceed-to-checkout">Proceder al Pago</button>
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
                                <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. 
                                    Promovemos el turismo sostenible y la educación ambiental.</p>
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
        <script>
            $(document).ready(function() {
                // Function to update cart display
                function updateCartDisplay() {
                    $.ajax({
                        url: 'carrito.php?action=get_cart_data',
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                let cartItemsBody = $('#cart-items-body');
                                cartItemsBody.empty(); // Clear existing items

                                if (response.carrito.length === 0) {
                                    $('.container.my-5').html('<h2 class="text-center mb-4">Tu Carrito de Compras</h2><div class="alert alert-info text-center" role="alert">Tu carrito está vacío. ¡Explora nuestros productos y agrega algunos!</div>');
                                } else {
                                    response.carrito.forEach(function(item) {
                                        let subtotal = (item.precio * item.cantidad).toFixed(2);
                                    let newRow = `
                                        <tr data-id="${item.id}" class="carrito-item-row">
                                            <td data-label="Producto:" class="d-flex align-items-center">
                                                ${item.imagen_url ? `
                                                    <div class="carrito-producto-imagen me-3">
                                                        <img src="${item.imagen_url}" alt="${item.nombre}">
                                                    </div>
                                                ` : ''}
                                                <span class="carrito-producto-nombre">${item.nombre}</span>
                                            </td>
                                            <td data-label="Precio Unitario:" class="carrito-precio">₡${parseFloat(item.precio).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td data-label="Cantidad:">
                                                <div class="input-group input-group-sm quantity-control">
                                                    <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="${item.id}">-</button>
                                                    <input type="text" class="form-control text-center product-quantity" value="${item.cantidad}" data-id="${item.id}" data-price="${item.precio}" readonly>
                                                    <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="${item.id}">+</button>
                                                </div>
                                            </td>
                                            <td data-label="Subtotal:" class="item-subtotal">₡${parseFloat(subtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td data-label="Acciones:" class="carrito-item-actions">
                                                <button class="btn btn-danger btn-sm btn-remove-item" data-id="${item.id}">
                                                    <i class="fa fa-trash"></i> Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                        cartItemsBody.append(newRow);
                                    });
                                    $('#cart-total-amount strong').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                                    $('#cart-item-count').text(response.total_items);
                                }
                            } else {
                                console.error('Error al obtener datos del carrito:', response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                        }
                    });
                }

                // Event listener for increasing quantity
                $(document).on('click', '.btn-increase-quantity', function() {
                    let productId = $(this).data('id');
                    let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
                    let currentQuantity = parseInt(quantityInput.val());
                    let newQuantity = currentQuantity + 1;
                    updateCartItemQuantity(productId, newQuantity);
                });

                // Event listener for decreasing quantity
                $(document).on('click', '.btn-decrease-quantity', function() {
                    let productId = $(this).data('id');
                    let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
                    let currentQuantity = parseInt(quantityInput.val());
                    let newQuantity = currentQuantity - 1;
                    if (newQuantity >= 0) { // Allow decreasing to 0 to trigger removal
                        updateCartItemQuantity(productId, newQuantity);
                    }
                });

                // Event listener for removing item
                $(document).on('click', '.btn-remove-item', function() {
                    let productId = $(this).data('id');
                    removeCartItem(productId);
                });

                function updateCartItemQuantity(id, quantity) {
                    $.ajax({
                        url: 'carrito.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'update_quantity',
                            id: id,
                            cantidad: quantity
                        },
                        success: function(response) {
                            if (response.success) {
                                updateCartDisplay(); // Refresh the entire cart table
                                updateHeaderCartCount(response.total_items); // Update header count
                                // You might also want to update points here if points are tied to quantity changes
                                if (response.user_points !== undefined) {
                                    $('#user-points-display').text(response.user_points + ' Puntos');
                                }
                            } else {
                                alert('Error al actualizar la cantidad: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            alert('Error de comunicación con el servidor.');
                        }
                    });
                }

                function removeCartItem(id) {
                    $.ajax({
                        url: 'carrito.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remove',
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                updateCartDisplay(); // Refresh the entire cart table
                                updateHeaderCartCount(response.total_items); // Update header count
                                // You might also want to update points here if removal affects points
                            } else {
                                alert('Error al eliminar el producto: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            alert('Error de comunicación con el servidor.');
                        }
                    });
                }

                // Function to update the cart item count in the header
                function updateHeaderCartCount(count) {
                    $('#cart-item-count').text(count);
                }

                // Initial load: Ensure cart display is correct
                updateCartDisplay();
            });
        </script>
    </body>
</html>