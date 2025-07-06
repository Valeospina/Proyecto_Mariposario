<?php
session_start(); // Inicia la sesión al principio de todo

// --- Simulación de datos de usuario (para testing si no hay login real)
// Mantén esto para que el dashboard funcione, pero los puntos ya no aumentan al añadir al carrito.
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
    $_SESSION['user_points'] = 0; // Default points for testing. Estos puntos se gestionarán en la compra real.
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
                // Sanitizar ID como STRING
                $id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
                $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
                $precio = filter_var($_POST['precio'], FILTER_VALIDATE_FLOAT);
                $imagen_url = filter_var($_POST['imagen_url'] ?? '', FILTER_SANITIZE_URL);

                // Validar que los datos no sean vacíos o inválidos
                if ($id !== false && $id !== '' && $nombre !== false && $precio !== false && $precio >= 0) {
                    $found = false;
                    foreach ($_SESSION['carrito'] as &$item) {
                        if ($item['id'] == $id) {
                            $item['cantidad']++;
                            $found = true;
                            break;
                        }
                    }
                    unset($item); // Romper la referencia

                    if (!$found) {
                        $_SESSION['carrito'][] = [
                            'id' => $id,
                            'nombre' => $nombre,
                            'precio' => $precio,
                            'cantidad' => 1,
                            'imagen_url' => $imagen_url
                        ];
                    }

                    // *** MODIFICACIÓN: No agregar puntos aquí. Los puntos se gestionarán al momento de la compra. ***
                    // if ($_SESSION['user_logged_in']) {
                    //     $_SESSION['user_points'] += 10;
                    // }

                    $response['success'] = true;
                    $response['message'] = 'Producto agregado al carrito.';
                    $response['total_items'] = getTotalCartItems();
                    $response['user_points'] = $_SESSION['user_points']; // Aunque no se sumaron, enviamos los puntos actuales
                } else {
                    $response['message'] = 'Datos de producto inválidos.';
                }
            } else {
                $response['message'] = 'Faltan datos para agregar el producto.';
            }
        } elseif ($action === 'update_quantity') {
            // Lógica para ACTUALIZAR la cantidad de un producto o eliminarlo (cantidad 0)
            if (isset($_POST['id'], $_POST['cantidad'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
                $cantidad = filter_var($_POST['cantidad'], FILTER_SANITIZE_NUMBER_INT);

                if ($id !== false && $id !== '' && $cantidad !== false) {
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
        }
        else {
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
            $response['user_points'] = $_SESSION['user_points']; // Enviar los puntos actuales
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

// Ejemplo básico de cómo mostrar el carrito cuando no es una petición AJAX (HTML de la página)
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="css/carrito.css">
</head>

<body>
    <?php include 'layout/nav2.php'; ?>
    <section class="user-panel section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="user-sidebar">
                        <div class="profile-info">
                            <img src="img/user-profile.jpg" alt="Foto de perfil">
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
                            <div class="alert alert-info text-center empty-cart-message" role="alert">
                                Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody id="cart-items-body">
                                        <?php foreach ($carrito_actual as $item): ?>
                                            <tr class="carrito-item-row" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                                <td data-label="Producto:" class="d-flex align-items-center">
                                                    <?php if (!empty($item['imagen_url'])): ?>
                                                        <div class="carrito-producto-imagen me-3">
                                                            <img src="<?php echo htmlspecialchars($item['imagen_url']); ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                                        </div>
                                                    <?php endif; ?>
                                                    <span class="carrito-producto-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                                </td>
                                                <td data-label="Precio Unitario:"><span class="carrito-precio">₡<?php echo number_format($item['precio'], 2, ',', '.'); ?></span></td>
                                                <td data-label="Cantidad:">
                                                    <div class="input-group input-group-sm quantity-control">
                                                        <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">-</button>
                                                        <input type="text" class="form-control text-center product-quantity" value="<?php echo htmlspecialchars($item['cantidad']); ?>" min="1" data-id="<?php echo htmlspecialchars($item['id']); ?>" data-price="<?php echo htmlspecialchars($item['precio']); ?>" readonly>
                                                        <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="<?php echo htmlspecialchars($item['id']); ?>">+</button>
                                                    </div>
                                                </td>
                                                <td data-label="Subtotal:"><span class="item-subtotal">₡<?php echo number_format($item['precio'] * $item['cantidad'], 2, ',', '.'); ?></span></td>
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
                                            <td colspan="3"></td>
                                            <td>Total:</td>
                                            <td id="cart-total-amount" class="text-right"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="mt-3">
                                <label for="observacionesPedido" class="form-label">Observaciones para el pedido (opcional):</label>
                                <textarea class="form-control" id="observacionesPedido" rows="3" placeholder="Ej: Recoger el viernes por la tarde, empaquetar para regalo, etc."></textarea>
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
    <script src="js/owl-carousel.js"></script> <script src="js/magnific-popup.js"></script> <script src="js/waypoints.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script> 
    <script src="js/active.js"></script> 
    <script src="js/carrito.js"></script>
<script>
        $(document).ready(function() {
            // Function to update cart display by fetching fresh data from the server
            function updateCartDisplay() {
                console.log("updateCartDisplay: Obteniendo datos del carrito para refrescar UI...");
                $.ajax({
                    url: 'carrito.php?action=get_cart_data', // Request for ALL current cart data
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log("Datos del carrito recibidos para UI:", response);
                        if (response.success) {
                            let cartItemsBody = $('#cart-items-body');
                            
                            // Check if the cart table and tbody exist before manipulating
                            if (cartItemsBody.length === 0) {
                                console.error("Elemento #cart-items-body no encontrado en el DOM. La UI del carrito no puede ser actualizada.");
                                return;
                            }

                            cartItemsBody.empty(); // Clear existing items to redraw the cart

                            if (response.carrito.length === 0) {
                                // If cart is empty, show the empty cart message and hide the table elements
                                $('.table-responsive').hide(); // Hide the table container
                                $('.btn-proceed-to-checkout').hide(); // Hide checkout button
                                // If the empty message is not there, add it
                                if ($('.empty-cart-message').length === 0) {
                                    $('.user-main-content').append('<div class="alert alert-info text-center empty-cart-message" role="alert">Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!</div>');
                                } else {
                                    $('.empty-cart-message').show();
                                }
                                $('#cart-total-amount strong').text('₡0,00'); // Ensure total is 0
                            } else {
                                // If cart has items, ensure table and button are visible and iterate and append each row
                                $('.table-responsive').show();
                                $('.btn-proceed-to-checkout').show();
                                $('.empty-cart-message').hide(); // Hide empty cart message if it exists

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
                                // Update cart totals in the table footer
                                $('#cart-total-amount strong').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            }
                            // Always update the cart item count in the header
                            updateHeaderCartCount(response.total_items);
                            // Always update user points if provided in the response
                            if (response.user_points !== undefined) {
                                $('#user-points-display').text(response.user_points + ' Puntos'); // Modificado para siempre mostrar "Puntos"
                            }
                        } else {
                            console.error('Error al obtener datos del carrito:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error al obtener datos del carrito para UI:', status, error);
                    }
                });
            }

            // --- Event Listener para el botón "Proceder al Pago" ---
            $(document).on('click', '.btn-proceed-to-checkout', function() {
                let observaciones = $('#observacionesPedido').val();
                let canjearPuntos = $('#checkboxCanjearPuntos').is(':checked'); // Obtener si el checkbox está marcado

                $.ajax({
                    url: 'procesar_pedido.php', // El nuevo endpoint para el checkout
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'checkout', // Asegúrate de que el PHP maneje esta acción
                        observaciones: observaciones,
                        canjear_puntos: canjearPuntos
                    },
                    success: function(response) {
                        console.log("Respuesta de checkout:", response);
                        if (response.success) {
                            alert('Pedido realizado con éxito!\nNúmero de Proforma: ' + response.numero_proforma + '\nTotal a pagar: ₡' + response.total_a_pagar + '\n\nPor favor, acérquese a la tienda para completar el pago.');
                            // Redirigir a una página de confirmación
                            window.location.href = 'confirmacion_pedido.php?proforma=' + response.numero_proforma;
                        } else {
                            alert('Error al procesar el pedido: ' + response.message);
                            console.error('Error del servidor al procesar el pedido:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error al procesar checkout:', status, error, xhr.responseText);
                        alert('Error de comunicación con el servidor al procesar el pedido. Por favor, inténtelo de nuevo.');
                    }
                });
            });

            // --- Event Listeners for cart actions (update quantity, remove item) ---

            // Listener for increasing quantity button
            $(document).on('click', '.btn-increase-quantity', function() {
                let productId = $(this).data('id');
                let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
                let currentQuantity = parseInt(quantityInput.val());
                let newQuantity = currentQuantity + 1;
                updateCartItemQuantity(productId, newQuantity, quantityInput);
            });

            // Listener for decreasing quantity button
            $(document).on('click', '.btn-decrease-quantity', function() {
                let productId = $(this).data('id');
                let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
                let currentQuantity = parseInt(quantityInput.val());
                let newQuantity = currentQuantity - 1;
                if (newQuantity >= 0) {
                    updateCartItemQuantity(productId, newQuantity, quantityInput);
                }
            });

            // Listener for removing item button
            $(document).on('click', '.btn-remove-item', function() {
                let productId = $(this).data('id');
                updateCartItemQuantity(productId, 0);
            });

            // Function to send quantity update (or removal) to the server
            function updateCartItemQuantity(id, quantity, elementToUpdate = null) {
                console.log(`Enviando actualización para ID: ${id}, Cantidad: ${quantity}`);
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
                        console.log("Respuesta del servidor para actualización/eliminación:", response);
                        if (response.success) {
                            if (quantity > 0 && elementToUpdate) {
                                elementToUpdate.val(quantity);
                                let row = elementToUpdate.closest('.carrito-item-row');
                                let price = parseFloat(elementToUpdate.data('price'));
                                let newSubtotal = (price * quantity).toFixed(2);
                                row.find('.item-subtotal').text('₡' + parseFloat(newSubtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            } else if (quantity === 0) {
                                $(`tr[data-id="${id}"]`).remove();
                            }
                            updateCartDisplay(); 
                        } else {
                            alert('Error al actualizar el carrito: ' + (response.message || 'Error desconocido.'));
                            console.error('Error del servidor al actualizar el carrito:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error en updateCartItemQuantity:', status, error, xhr.responseText);
                        alert('Error de comunicación con el servidor. Por favor, inténtelo de nuevo.');
                    }
                });
            }

            // Function to update the cart item count in the header (navbar)
            function updateHeaderCartCount(count) {
                $('#cart-item-count').text(count);
            }

            // Initial load: Ensure cart display is correct when the page loads
            updateCartDisplay();
        });
    </script>
</body>
</html>