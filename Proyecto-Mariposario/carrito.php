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
// Using the uploaded image for the user avatar by default
if (!isset($_SESSION['user_avatar'])) {
    $_SESSION['user_avatar'] = 'img/image_151dea.png'; // Assuming 'img/' is the correct path
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
                // Use a default image if none provided, or the uploaded one
                $imagen_url = filter_var($_POST['imagen_url'] ?? 'img/image_150aa3.png', FILTER_SANITIZE_URL);

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
    <link rel="stylesheet" href="css/carrito.css"> <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <?php include 'layout/nav2.php'; ?>
    <section class="user-panel section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-4 col-12">
                    <div class="user-sidebar">
                        <div class="profile-info">
                            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" alt="Foto de perfil del usuario">
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
                        <h2 class="text-center mb-4">Tu Carrito de Compras</h2>
                        <div class="carrito-puntos-wrapper mb-4 p-3">
                            <div class="puntos-texto">
                                <h5>¡Hola <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Invitado'); ?>! Tienes <span class="puntos-numero" id="user-points-display"><?php echo htmlspecialchars($puntosUsuario ?? 0); ?></span> Puntos.</h5>
                                <p>¡Canjea tus puntos por descuentos en tu próxima compra!</p>
                            </div>
                            <div class="puntos-acciones d-flex align-items-center">
                                <div class="form-check ms-3">
                                    <input class="form-check-input" type="checkbox" id="checkboxCanjearPuntos">
                                    <label class="form-check-label" for="checkboxCanjearPuntos">
                                        Canjear mis puntos en este pedido
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php if (empty($carrito_actual)): ?>
                            <div class="alert alert-info text-center empty-cart-message" role="alert">
                                Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!
                                <br><a href="tienda.php" class="btn btn-primary mt-3">Ir a la Tienda</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table cart-table">
                                    <thead>
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
                                            <tr class="carrito-item-row" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                                                <td data-label="Producto:" class="d-flex align-items-center product-cell">
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
                                            <td colspan="3" class="d-none d-md-table-cell"></td>
                                            <td colspan="2" class="d-md-none text-right"></td>
                                            <td class="text-right"><strong>Total:</strong></td>
                                            <td id="cart-total-amount" class="text-right"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="form-group mt-4">
                                <label class="mb-3"><h5>Selecciona tu Método de Pago:</h5></label>
                                <div class="payment-methods-grid">
                                    <label class="payment-card">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoEfectivo" value="Efectivo Tienda" checked>
                                        <div class="payment-card-content">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Pagar en Efectivo (en tienda física)</span>
                                        </div>
                                    </label>
                                    <label class="payment-card">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoTarjeta" value="Tarjeta Tienda">
                                        <div class="payment-card-content">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Pagar con Tarjeta (en datáfono físico en tienda)</span>
                                        </div>
                                    </label>
                                    <label class="payment-card">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoSinpe" value="SINPE Movil">
                                        <div class="payment-card-content">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span>SINPE Móvil</span>
                                        </div>
                                    </label>
                                    <label class="payment-card">
                                        <input class="form-check-input" type="radio" name="metodo_pago" id="pagoTransferencia" value="Transferencia Bancaria">
                                        <div class="payment-card-content">
                                            <i class="fas fa-university"></i>
                                            <span>Transferencia Bancaria</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="instruccionesPago" class="alert alert-info mt-3" style="display: none;">
                                <h6 class="alert-heading">Instrucciones para el Pago:</h6>
                                <p id="sinpe-instructions" style="display:none;">
                                    <strong>Número SINPE Móvil:</strong> **8888-8888**<br>
                                    (A nombre de: EcoMariposa S.A. - Cédula Jurídica: 3-101-123456)
                                </p>
                                <p id="transferencia-instructions" style="display:none;">
                                    <strong>Detalles de Transferencia Bancaria:</strong><br>
                                    Banco: **Banco de Costa Rica (BCR)**<br>
                                    Cuenta IBAN: **CR0000000000000000000000**<br>
                                    Cédula Jurídica/Identificación: **3-101-123456**<br>
                                    Nombre Beneficiario: **EcoMariposa S.A.**
                                </p>
                                <p class="mb-0">Por favor, realiza el pago y guarda tu comprobante. En caso de SINPE/Transferencia, contactaremos contigo para la confirmación o se te indicará cómo subir el comprobante en tu perfil de usuario.</p>
                            </div>
                            
                            <div class="mt-4">
                                <label for="observacionesPedido" class="form-label">Observaciones para el pedido (opcional):</label>
                                <textarea class="form-control" id="observacionesPedido" rows="3" placeholder="Ej: Recoger el viernes por la tarde, empaquetar para regalo, etc."></textarea>
                            </div>
                            <div class="text-right mt-4">
                                <button class="btn btn-success btn-lg btn-proceed-to-checkout">Ver metodos de pago</button>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/slicknav.min.js"></script>
    <script src="js/owl-carousel.js"></script>
    <script src="js/magnific-popup.js"></script>
    <script src="js/waypoints.min.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/easing.js"></script>
    <script src="js/active.js"></script>
    <script src="js/tienda.js"></script> <script>
        $(document).ready(function() {
            // Function to update cart display
            function updateCartDisplay() {
                $.ajax({
                    url: 'carrito.php', // Current file
                    method: 'GET',
                    data: { action: 'get_cart_data' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#cart-items-body').empty(); // Clear existing items

                            if (response.carrito.length === 0) {
                                // Show empty cart message
                                $('.empty-cart-message').show();
                                $('.table-responsive, .form-group, #instruccionesPago, .mt-3, .mt-4 button').hide();
                            } else {
                                $('.empty-cart-message').hide();
                                $('.table-responsive, .form-group, .mt-3, .mt-4 button').show(); // Show cart elements

                                $.each(response.carrito, function(index, item) {
                                    var row = `
                                        <tr class="carrito-item-row" data-id="${item.id}">
                                            <td data-label="Producto:" class="d-flex align-items-center product-cell">
                                                ${item.imagen_url ? `<div class="carrito-producto-imagen me-3"><img src="${item.imagen_url}" alt="${item.nombre}"></div>` : ''}
                                                <span class="carrito-producto-nombre">${item.nombre}</span>
                                            </td>
                                            <td data-label="Precio Unitario:"><span class="carrito-precio">₡${parseFloat(item.precio).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></td>
                                            <td data-label="Cantidad:">
                                                <div class="input-group input-group-sm quantity-control">
                                                    <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="${item.id}">-</button>
                                                    <input type="text" class="form-control text-center product-quantity" value="${item.cantidad}" min="1" data-id="${item.id}" data-price="${item.precio}" readonly>
                                                    <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="${item.id}">+</button>
                                                </div>
                                            </td>
                                            <td data-label="Subtotal:"><span class="item-subtotal">₡${(parseFloat(item.precio) * parseInt(item.cantidad)).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></td>
                                            <td data-label="Acciones:" class="carrito-item-actions">
                                                <button class="btn btn-danger btn-sm btn-remove-item" data-id="${item.id}">
                                                    <i class="fa fa-trash"></i> Eliminar
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                    $('#cart-items-body').append(row);
                                });
                            }

                            $('#cart-total-amount').html(`<strong>₡${parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                            $('#user-points-display').text(response.user_points);
                            // Update total items in the main navigation (if you have an element for it)
                            $('.total-count').text(response.total_items);
                        } else {
                            console.error('Error al obtener datos del carrito:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX al obtener datos del carrito:', status, error);
                    }
                });
            }

            // Initial cart display on page load
            updateCartDisplay();

            // Handle quantity increase/decrease and remove
            $(document).on('click', '.btn-increase-quantity, .btn-decrease-quantity, .btn-remove-item', function() {
                var productId = $(this).data('id');
                var currentQuantityInput = $('.product-quantity[data-id="' + productId + '"]');
                var currentQuantity = parseInt(currentQuantityInput.val());
                var newQuantity;
                var actionType = 'update_quantity';

                if ($(this).hasClass('btn-increase-quantity')) {
                    newQuantity = currentQuantity + 1;
                } else if ($(this).hasClass('btn-decrease-quantity')) {
                    newQuantity = currentQuantity - 1;
                    if (newQuantity < 0) newQuantity = 0; // Prevent negative quantity
                } else if ($(this).hasClass('btn-remove-item')) {
                    newQuantity = 0; // Set quantity to 0 to trigger removal
                }

                $.ajax({
                    url: 'carrito.php', // Your PHP script
                    method: 'POST',
                    data: {
                        action: actionType,
                        id: productId,
                        cantidad: newQuantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            updateCartDisplay(); // Re-render the cart table
                            alert(response.message); // For demonstration, use a toast/modal in production
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error AJAX:', status, error);
                        alert('Hubo un error al actualizar el carrito.');
                    }
                });
            });

            // Payment method selection logic
            $('input[name="metodo_pago"]').on('change', function() {
                var selectedMethod = $(this).val();
                var instruccionesPago = $('#instruccionesPago');
                var sinpeInstructions = $('#sinpe-instructions');
                var transferenciaInstructions = $('#transferencia-instructions');

                // Hide all instructions first
                instruccionesPago.hide();
                sinpeInstructions.hide();
                transferenciaInstructions.hide();

                // Show instructions based on selection
                if (selectedMethod === 'SINPE Movil') {
                    instruccionesPago.slideDown();
                    sinpeInstructions.show();
                } else if (selectedMethod === 'Transferencia Bancaria') {
                    instruccionesPago.slideDown();
                    transferenciaInstructions.show();
                }
            });

            // Trigger initial check for payment method display in case a reload occurs
            $('input[name="metodo_pago"]:checked').trigger('change');

            // Example of "Proceder al Pago" button (you'd send cart data to a checkout page/API)
            $('.btn-proceed-to-checkout').on('click', function() {
                var selectedPaymentMethod = $('input[name="metodo_pago"]:checked').val();
                var observaciones = $('#observacionesPedido').val();
                var canjearPuntos = $('#checkboxCanjearPuntos').is(':checked');

                if (confirm('¿Confirmar tu pedido y proceder al pago con ' + selectedPaymentMethod + '?')) {
                    // Here you would typically send an AJAX request to a 'process_order.php'
                    // containing the final cart data, payment method, observations, and points redemption status.
                    console.log("Procediendo al pago...");
                    console.log("Método de Pago Seleccionado:", selectedPaymentMethod);
                    console.log("Observaciones:", observaciones);
                    console.log("Canjear Puntos:", canjearPuntos);
                    console.log("Carrito Actual:", <?php echo json_encode($carrito_actual); ?>);
                    console.log("Total Final:", <?php echo json_encode($total_carrito_final); ?>);
                    console.log("Puntos del Usuario:", <?php echo json_encode($puntosUsuario); ?>);

                    // Example: Simulate a successful order
                    alert('Pedido realizado con éxito (simulado). Gracias por tu compra con EcoMariposa!');
                    // Optionally, clear the cart after successful order
                    // $.ajax({
                    //     url: 'carrito.php',
                    //     method: 'POST',
                    //     data: { action: 'clear_cart' }, // You'd need to add this action to your PHP
                    //     success: function() {
                    //         updateCartDisplay();
                    //     }
                    // });
                    window.location.href = 'confirmation.php'; // Redirect to a confirmation page
                }
            });
        });
    </script>
</body>
</html>