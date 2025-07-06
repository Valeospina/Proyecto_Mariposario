<?php
session_start(); // Inicia la sesión al principio de todo

// --- Simulación de datos de usuario (para testing si no hay login real)
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true; // Set to true for testing dashboard features
}
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'EcoMariposa User'; // Default name
}
if (!isset($_SESSION['user_avatar'])) {
    $_SESSION['user_avatar'] = 'https://via.placeholder.com/100/CCCCCC/888888?text=Usuario'; // Default avatar or your local path
}
if (!isset($_SESSION['user_points'])) {
    $_SESSION['user_points'] = 150; // Default points for testing
}

// Ensure $_SESSION['carrito'] is always an array
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
    // Add some sample products for testing the layout if the cart is empty
    /*
    $_SESSION['carrito'] = [
        'prod_001' => [
            'id' => 'prod_001',
            'nombre' => 'Semillas de Girasol Gigante',
            'precio' => 8.50,
            'cantidad' => 2,
            'imagen_url' => 'https://via.placeholder.com/150/A4C639/FFFFFF?text=Girasol'
        ],
        'prod_002' => [
            'id' => 'prod_002',
            'nombre' => 'Kit de Cultivo de Orquídeas',
            'precio' => 35.00,
            'cantidad' => 1,
            'imagen_url' => 'https://via.placeholder.com/150/8BC34A/FFFFFF?text=Orquidea'
        ],
        'prod_003' => [
            'id' => 'prod_003',
            'nombre' => 'Guía de Mariposas Nativas',
            'precio' => 12.99,
            'cantidad' => 3,
            'imagen_url' => 'https://via.placeholder.com/150/66BB6A/FFFFFF?text=Guia'
        ]
    ];
    */
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

// Ejemplo básico de cómo mostrar el carrito cuando no es una petición AJAX
$carrito_actual = $_SESSION['carrito'] ?? []; // Obtener el carrito de la sesión
$total_carrito_final = getCartTotalAmount();
$puntosUsuario = $_SESSION['user_points']; // Obtener los puntos del usuario de la sesión

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

    <title>Tu Carrito de Compras - Eco Mariposas</title>
    <link rel="icon" href="img/favicon.png">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&family=Poppins:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/icofont.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css"> <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/tienda.css"> <link rel="stylesheet" href="css/carrito.css">
</head>

<body>
    <?php include 'layout/nav2.php'; ?>
    <section class="user-panel section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="user-sidebar">
                        <div class="profile-info">
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" alt="Foto de perfil">
                            <h3>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                            <p>Miembro desde: Abril 2023</p>
                            <a href="user-settings.php" class="btn btn-sm btn-primary">Editar Perfil</a>
                        </div>
                        <ul class="sidebar-menu">
                            <li><a href="user-profile.php"><i class="fas fa-user"></i> Perfil</a></li>
                            <li><a href="MisPedidos.php"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                            <li><a href="eventosReservados.php"><i class="fas fa-calendar-alt"></i> Eventos</a></li>
                            <li><a href="notificaciones.php"><i class="fas fa-bell"></i> Notificaciones <span class="badge badge-primary">3</span></a></li>
                            <li><a href="user-favorites.php"><i class="fas fa-heart"></i> Favoritos</a></li>
                            <li><a href="user-settings.php"><i class="fas fa-cog"></i> Configuración</a></li>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9 col-md-8 col-12">
                    <div class="user-main-content">
                        <h2 class="text-center">Tu Carrito de Compras</h2>

                        <div class="carrito-puntos-wrapper">
                            <div class="puntos-texto">
                                <h5>¡Hola <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Tienes <span class="puntos-numero" id="user-points-display"><?php echo htmlspecialchars($puntosUsuario); ?></span> puntos.</h5>
                                <p>¡Canjea tus puntos por descuentos en tu próxima compra!</p>
                            </div>
                            <div class="puntos-acciones d-flex align-items-center">
                                <button class="btn btn-primary btn-sm" id="canjear-puntos-btn">Canjear Puntos</button>
                            </div>
                        </div>
                        <?php if (empty($carrito_actual)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody id="carrito-items-body">
                                        <?php foreach ($carrito_actual as $item): ?>
                                            <tr class="carrito-item-row" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                                <td data-label="Producto:">
                                                    <?php if (!empty($item['imagen_url'])): ?>
                                                        <div class="carrito-producto-imagen">
                                                            <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="carrito-producto-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                                </td>
                                                <td data-label="Precio Unitario:"><span class="carrito-precio">₡<?php echo number_format($item['precio'], 2, ',', '.'); ?></span></td>
                                                <td data-label="Cantidad:">
                                                    <div class="quantity-control">
                                                        <button class="btn btn-sm btn-outline-secondary btn-decrease-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">-</button>
                                                        <input type="text" class="form-control product-quantity" value="<?php echo htmlspecialchars($item['cantidad']); ?>" min="1" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-price="<?php echo htmlspecialchars($item['precio']); ?>" readonly>
                                                        <button class="btn btn-sm btn-outline-secondary btn-increase-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">+</button>
                                                    </div>
                                                </td>
                                                <td data-label="Subtotal:"><span class="item-subtotal">₡<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?></span></td>
                                                <td data-label="Acciones:">
                                                    <button class="btn btn-danger btn-sm btn-remove-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                                        <i class="fas fa-trash-alt"></i> Eliminar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-responsive">
                                <table class="table">
                                    <tfoot>
                                        <tr>
                                            <td>Total:</td>
                                            <td id="cart-total-amount" class="text-right"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="text-right mt-4">
                                <button class="btn btn-success btn-proceed-to-checkout">Proceder al Pago</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    <script src="js/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script src="js/jquery-ui.min.js"></script>
    <script src="js/easing.js"></script>
    <script src="js/colors.js"></script>
    <script src="js/bootstrap-datepicker.js"></script>
    <script src="js/jquery.nav.js"></script>
    <script src="js/slicknav.min.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/niceselect.js"></script>
    <script src="js/tilt.jquery.min.js"></script>
    <script src="js/owl-carousel.js"></script> <script src="js/magnific-popup.js"></script> <script src="js/waypoints.min.js"></script> <script src="js/jquery.counterup.min.js"></script> <script src="js/active.js"></script> <script src="js/carrito.js"></script>
    <script>
        // Ensure this function is called after the DOM is ready to update the total and points
        $(document).ready(function() {
            // Function to update the cart display, including total and points
            function updateCartDisplay() {
                $.ajax({
                    url: 'carrito.php?action=get_cart_data', // Request cart data
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let cartItemsBody = $('#carrito-items-body');
                            cartItemsBody.empty(); // Clear existing items

                            if (response.carrito && response.carrito.length > 0) {
                                // Re-render cart items
                                response.carrito.forEach(function(item) {
                                    let itemRow = `
                                        <tr class="carrito-item-row" data-id="${item.id}">
                                            <td data-label="Producto:" class="d-flex align-items-center">
                                                ${item.imagen_url ? `<div class="carrito-producto-imagen"><img src="${item.imagen_url}" alt="${item.nombre}"></div>` : ''}
                                                <span class="carrito-producto-nombre">${item.nombre}</span>
                                            </td>
                                            <td data-label="Precio Unitario:"><span class="carrito-precio">₡${(item.precio).toFixed(2).replace('.', ',')}</span></td>
                                            <td data-label="Cantidad:">
                                                <div class="quantity-control">
                                                    <button class="btn btn-sm btn-outline-secondary btn-decrease-quantity" type="button" data-id="${item.id}">-</button>
                                                    <input type="text" class="form-control text-center product-quantity" value="${item.cantidad}" min="1" data-id="${item.id}" data-price="${item.precio}" readonly>
                                                    <button class="btn btn-sm btn-outline-secondary btn-increase-quantity" type="button" data-id="${item.id}">+</button>
                                                </div>
                                            </td>
                                            <td data-label="Subtotal:"><span class="item-subtotal">₡${(item.precio * item.cantidad).toFixed(2).replace('.', ',')}</span></td>
                                            <td data-label="Acciones:">
                                                <button class="btn btn-danger btn-sm btn-remove-item" data-id="${item.id}">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                    cartItemsBody.append(itemRow);
                                });
                                $('#cart-total-amount strong').text(`₡${(response.cart_total_amount).toFixed(2).replace('.', ',')}`);
                                $('.alert-info').hide(); // Hide empty cart message
                                $('.table-responsive').show();
                                $('.text-right.mt-4').show(); // Show checkout button
                            } else {
                                $('.alert-info').show(); // Show empty cart message
                                $('.table-responsive').hide();
                                $('.text-right.mt-4').hide(); // Hide checkout button
                            }

                            // Update user points display
                            $('#user-points-display').text(response.user_points || 0);

                        } else {
                            console.error('Error fetching cart data:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                    }
                });
            }

            // Initial call to update cart display on page load
            updateCartDisplay();

            // Event listener for quantity decrease button (delegated)
            $(document).on('click', '.btn-decrease-quantity', function() {
                let productId = $(this).data('id');
                let quantityInput = $(this).siblings('.product-quantity');
                let currentQuantity = parseInt(quantityInput.val());
                if (currentQuantity > 0) { // Allow decreasing to 0 to trigger removal
                    updateCartItem(productId, currentQuantity - 1);
                }
            });

            // Event listener for quantity increase button (delegated)
            $(document).on('click', '.btn-increase-quantity', function() {
                let productId = $(this).data('id');
                let quantityInput = $(this).siblings('.product-quantity');
                let currentQuantity = parseInt(quantityInput.val());
                updateCartItem(productId, currentQuantity + 1);
            });

            // Event listener for remove item button (delegated)
            $(document).on('click', '.btn-remove-item', function() {
                let productId = $(this).data('id');
                removeCartItem(productId);
            });

            function updateCartItem(id, newQuantity) {
                $.ajax({
                    url: 'carrito.php',
                    method: 'POST',
                    data: {
                        action: 'update_quantity',
                        id: id,
                        cantidad: newQuantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateCartDisplay(); // Refresh the entire cart
                            // Optionally, show a small success message
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('Error al actualizar el carrito. Inténtalo de nuevo.');
                    }
                });
            }

            function removeCartItem(id) {
                $.ajax({
                    url: 'carrito.php',
                    method: 'POST',
                    data: {
                        action: 'remove',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateCartDisplay(); // Refresh the entire cart
                            // Optionally, show a small success message
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('Error al eliminar el producto. Inténtalo de nuevo.');
                    }
                });
            }

            // Example for "Canjear Puntos" button (you'll implement its specific logic)
            $('#canjear-puntos-btn').on('click', function() {
                alert('Funcionalidad para canjear puntos se implementará aquí.');
                // Here you would likely make another AJAX call to process points redemption
            });

            // Example for "Proceder al Pago" button (you'll implement its specific logic)
            $('.btn-proceed-to-checkout').on('click', function() {
                if (parseFloat($('#cart-total-amount strong').text().replace('₡', '').replace(',', '.')) > 0) {
                     alert('Redirigiendo a la página de pago...');
                     // window.location.href = 'checkout.php'; // Uncomment and change to your checkout page
                } else {
                     alert('Tu carrito está vacío. No se puede proceder al pago.');
                }
            });
        });
    </script>
</body>
</html>