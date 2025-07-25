<?php
session_start(); // Inicia la sesión al principio de todo

// --- Simulación de datos de usuario (para testing si no hay login real)
if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true; // Set to true for testing dashboard features
}
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'EcoMariposa User'; // Default name
}
// Using the uploaded image for the user avatar by default
if (!isset($_SESSION['user_avatar'])) {
    $_SESSION['user_avatar'] = 'img/user-profile.jpg'; // Assuming 'img/' is the correct path
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

// Function to convert Colones to Dollars
function convertirColonesADolares($colones) {
    $tipoCambio = 500; // Example exchange rate
    return round($colones / $tipoCambio, 2);
}

// --- Lógica para Peticiones AJAX (POST y GET para el carrito) ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json'); // Ensure JSON response for AJAX calls

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_quantity') {
            if (isset($_POST['id'], $_POST['cantidad'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);
                $cantidad = filter_var($_POST['cantidad'], FILTER_SANITIZE_NUMBER_INT);

                if ($id !== false && $id !== '' && $cantidad !== false) {
                    $found = false;
                    foreach ($_SESSION['carrito'] as $key => &$item) {
                        if ($item['id'] == $id) {
                            if ($cantidad > 0) {
                                $item['cantidad'] = $cantidad;
                                $response['success'] = true;
                                $response['message'] = 'Cantidad actualizada.';
                            } else {
                                unset($_SESSION['carrito'][$key]);
                                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
                                $response['success'] = true;
                                $response['message'] = 'Producto eliminado del carrito.';
                            }
                            $found = true;
                            break;
                        }
                    }
                    unset($item);

                    if (!$found) {
                        $response['message'] = 'Producto no encontrado en el carrito.';
                    }
                } else {
                    $response['message'] = 'Datos de actualización de cantidad inválidos.';
                }
            } else {
                $response['message'] = 'Faltan datos para actualizar la cantidad.';
            }
        } elseif ($action === 'remove') {
            if (isset($_POST['id'])) {
                $id = filter_var($_POST['id'], FILTER_SANITIZE_STRING);

                if ($id !== false && $id !== '') {
                    $found = false;
                    foreach ($_SESSION['carrito'] as $key => $item) {
                        if ($item['id'] == $id) {
                            unset($_SESSION['carrito'][$key]);
                            $_SESSION['carrito'] = array_values($_SESSION['carrito']);
                            $response['success'] = true;
                            $response['message'] = 'Producto eliminado del carrito.';
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $response['message'] = 'Producto no encontrado en el carrito.';
                    }
                } else {
                    $response['message'] = 'ID de producto inválido para eliminar.';
                }
            } else {
                $response['message'] = 'Falta el ID del producto para eliminar.';
            }
        } else {
            $response['message'] = 'Acción POST no reconocida.';
        }
    }
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get_count') {
            $response['success'] = true;
            $response['total_items'] = getTotalCartItems();
            $response['message'] = 'Cantidad de items obtenida.';
        } elseif ($action === 'get_cart_data') {
            $response['success'] = true;
            $response['carrito'] = array_values($_SESSION['carrito'] ?? []);
            $response['total_items'] = getTotalCartItems();
            $response['cart_total_amount'] = getCartTotalAmount();
            $response['user_points'] = $_SESSION['user_points'] ?? 0;
        } else {
            $response['message'] = 'Acción GET no reconocida.';
        }
    }
    else {
        $response['message'] = 'Método de solicitud no permitido para AJAX.';
    }

    $response['total_items'] = getTotalCartItems();
    $response['cart_total_amount'] = getCartTotalAmount();
    $response['total_usd'] = convertirColonesADolares($response['cart_total_amount']);

    echo json_encode($response);
    exit();
}

// --- Lógica para la carga inicial de la página (NO es una petición AJAX) ---
$carrito_actual = $_SESSION['carrito'] ?? [];
$total_carrito_final = getCartTotalAmount();
$puntosUsuario = $_SESSION['user_points'] ?? 0;

$total_usd = convertirColonesADolares($total_carrito_final);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Eco Mariposas, tienda, productos naturales, ecología, sostenibilidad">
    <meta name="description" content="Tu Carrito de Compras en Eco Mariposas. Finaliza tu pedido y paga de forma segura.">
    <meta name='copyright' content='Eco Mariposas'>
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="css/carrito.css">

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=ATaviM4-qfB_deZSXciXwtIalyjoNEseNB0FsCJ2riwp6fLYZzaVKTe4jjoY53IjHJx6UWQy48APsJ_H&currency=USD"></script>
</head>

<body>
    <?php include 'layout/nav2.php'; ?>

    <section class="user-panel section">
    <div class="container">
        <div class="row">
            <!-- User Sidebar -->
            <div class="col-lg-3 col-md-4 col-12">
                <div class="user-sidebar">
                    <div class="profile-info">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user-profile.jpg'); ?>" alt="Foto de perfil">
                        <h3>Hola, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></h3>
                    </div>
                    <ul class="sidebar-menu">
                        <li><a href="usuario.php"><i class="fas fa-user"></i> Perfil</a></li>
                        <li><a href="MisPedidos.php"><i class="fas fa-shopping-bag"></i> Mis Pedidos</a></li>
                    </ul>
                </div>
            </div>

            <!-- Main Cart Content -->
            <div class="col-lg-9 col-md-8 col-12">
                <div class="user-main-content">
                    <h2 class="text-center mb-4">Tu Carrito de Compras</h2>

                    <!-- Points Section -->
                    <div class="carrito-puntos-wrapper mb-4 p-3">
                        <div class="puntos-texto">
                            <h5>¡Hola <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Invitado'); ?>! Tienes 
                                <span class="puntos-numero" id="user-points-display"><?php echo htmlspecialchars($puntosUsuario ?? 0); ?></span> Puntos.
                            </h5>
                            <p>¡Canjea tus puntos por descuentos en tu próxima compra!</p>
                        </div>
                        <div class="puntos-acciones d-flex align-items-center">
                            <div class="form-check ms-3">
                                <input class="form-check-input" type="checkbox" id="checkboxCanjearPuntos">
                                <label class="form-check-label" for="checkboxCanjearPuntos">
                                    Canjear mis puntos en este pedido
                                </label>

                                <?php if ($puntosUsuario < 1000): ?>
                                    <small class="text-danger d-block mt-1">Necesitas al menos 1000 puntos para canjear.</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (empty($carrito_actual)): ?>
                        <!-- Empty Cart Message -->
                        <div class="alert alert-info text-center empty-cart-message" role="alert">
                            Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!
                            <br><a href="tienda.php" class="btn btn-primary mt-3">Ir a la Tienda</a>
                        </div>
                    <?php else: ?>
                        <!-- Cart Items Table -->
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
                                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                        <td id="cart-total-amount" class="text-right"><strong>₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr id="discount-row" style="display:none;">
                                        <td colspan="4" class="text-right text-success"><strong>Descuento por puntos:</strong></td>
                                        <td class="text-right text-success"><strong id="discount-amount">₡0.00</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total con descuento:</strong></td>
                                        <td class="text-right"><strong id="final-total-amount">₡<?php echo number_format($total_carrito_final, 2, ',', '.'); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Payment Method Selection -->
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
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="pagoTarjeta" value="Tarjeta PayPal">
                                    <div class="payment-card-content">
                                        <i class="fab fa-paypal"></i>
                                        <span>Pagar con PayPal</span>
                                    </div>
                                </label>
                                <label class="payment-card">
                                    <input class="form-check-input" type="radio" name="metodo_pago" id="pagoSinpe" value="SINPE Movil">
                                    <div class="payment-card-content">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>SINPE Móvil</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Payment Instructions and PayPal Button Container -->
                        <div id="instruccionesPago" class="alert alert-info mt-3" style="display: none;">
                            <h6 class="alert-heading">Instrucciones para el Pago:</h6>
                            <p id="efectivo-instructions" style="display:none;">
                                Tu pedido estará listo para ser retirado y pagado en efectivo en nuestra tienda física. Te enviaremos un correo de confirmación con los detalles de la dirección y horario de retiro.
                            </p>

                            <p id="sinpe-instructions" style="display:none;">
                                <strong>Número SINPE Móvil:</strong> **8888-8888**<br>
                                (A nombre de: EcoMariposa S.A. - Cédula Jurídica: 3-101-123456)<br>
                                Por favor, realiza el pago a este número y guarda tu comprobante. Sube una imagen o PDF del comprobante a continuación:
                                <br><br>
                                <label for="comprobanteSinpe">Subir Comprobante de Pago:</label>
                                <input type="file" id="comprobanteSinpe" name="comprobanteSinpe" accept="image/*,application/pdf" class="form-control-file mt-2">
                                <small class="form-text text-muted">Formatos permitidos: JPG, PNG o PDF.</small>
                            </p>
                        </div>

                        <div class="payment-options mt-4" id="paypal-payment-section" style="display: none;">
                            <div class="alert alert-info">
                                <h5>Total a pagar:</h5>
                                <p><strong>₡<span id="display-total-colones-paypal"><?php echo number_format($total_carrito_final, 2); ?></span></strong></p>
                                <p>Se convertirá aproximadamente a <strong>$<span id="display-total-usd-paypal"><?php echo number_format($total_usd, 2); ?></span></strong> para el pago con PayPal.</p>
                            </div>
                            <h3>Pagar con PayPal</h3>
                            <div id="paypal-button-container" class="mt-3"></div>
                        </div>

                        <div class="mt-4 text-center">
                            <button type="button" id="confirmOrderBtn" class="btn btn-success btn-lg btn-proceed-to-checkout" style="display:none;">Confirmar Pedido</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- JavaScript includes -->
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
    <script src="js/tienda.js"></script>

<script>
$(document).ready(function() {
    // Función para aplicar descuento dinámico
    function applyPointsDiscount() {
        let cartTotalText = $('#cart-total-amount strong').text().replace(/[₡\s]/g, '').replace(/\./g, '').replace(',', '.');
        let cartTotal = parseFloat(cartTotalText) || 0;
        let userPoints = parseInt($('#user-points-display').text()) || 0;
        let discount = 0;

        if ($('#checkboxCanjearPuntos').is(':checked') && userPoints >= 1000) {
            discount = Math.min(cartTotal, userPoints); // No excede el total
            $('#discount-row').show();
            $('#discount-amount').text('₡' + discount.toLocaleString('es-CR', { minimumFractionDigits: 2 }));
        } else {
            $('#discount-row').hide();
        }

        let finalTotal = cartTotal - discount;
        $('#final-total-amount').text('₡' + finalTotal.toLocaleString('es-CR', { minimumFractionDigits: 2 }));

        return discount;
    }

    // Function to update the cart display using AJAX
    function updateCartDisplay() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { action: 'get_cart_data' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-items-body').empty(); // Clear existing cart items

                    if (response.carrito.length === 0) {
                        // Show empty cart message and hide other elements
                        $('.empty-cart-message').show();
                        $('.table-responsive, .payment-options, .form-group.mt-4, #instruccionesPago, #paypal-payment-section, #confirmOrderBtn').hide();
                    } else {
                        $('.empty-cart-message').hide();
                        $('.table-responsive, .form-group.mt-4').show();

                        $('input[name="metodo_pago"]:checked').trigger('change');

                        $.each(response.carrito, function(index, item) {
                            var row = `
                                <tr class="carrito-item-row" data-id="${item.id}">
                                    <td data-label="Producto:" class="d-flex align-items-center product-cell">
                                        ${item.imagen_url ? `
                                            <div class="carrito-producto-imagen me-3">
                                                <img src="${item.imagen_url}" alt="${item.nombre}">
                                            </div>
                                        ` : ''}
                                        <span class="carrito-producto-nombre">${item.nombre}</span>
                                    </td>
                                    <td data-label="Precio Unitario:">
                                        <span class="carrito-precio">
                                            ₡${parseFloat(item.precio).toLocaleString('es-CR', { minimumFractionDigits: 2 })}
                                        </span>
                                    </td>
                                    <td data-label="Cantidad:">
                                        <div class="input-group input-group-sm quantity-control">
                                            <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="${item.id}">-</button>
                                            <input type="text" class="form-control text-center product-quantity"
                                                   value="${item.cantidad}" min="1" data-id="${item.id}"
                                                   data-price="${item.precio}" readonly>
                                            <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="${item.id}">+</button>
                                        </div>
                                    </td>
                                    <td data-label="Subtotal:">
                                        <span class="item-subtotal">
                                            ₡${(parseFloat(item.precio) * parseInt(item.cantidad)).toLocaleString('es-CR', { minimumFractionDigits: 2 })}
                                        </span>
                                    </td>
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

                    // Update totals and points display
                    $('#cart-total-amount').html(`
                        <strong>
                            ₡${parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2 })}
                        </strong>
                    `);
                    $('#display-total-colones-paypal').text(parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2 }));
                    $('#display-total-usd-paypal').text(parseFloat(response.total_usd).toLocaleString('en-US', { minimumFractionDigits: 2 }));

                    $('#user-points-display').text(response.user_points);
                    $('.total-count').text(response.total_items);

                    // Aplicar descuento si el checkbox está activo
                    applyPointsDiscount();
                } else {
                    console.error('Error al obtener datos del carrito:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al obtener datos del carrito:', status, error, xhr.responseText);
            }
        });
    }

    // Initial cart display update on page load
    updateCartDisplay();

    // Checkbox para aplicar puntos
    $('#checkboxCanjearPuntos').on('change', function() {
        if (this.checked && parseInt($('#user-points-display').text()) < 1000) {
            alert('Necesitas al menos 1000 puntos para poder canjear.');
            this.checked = false;
        }
        applyPointsDiscount();
    });

    // Event listeners for quantity changes and item removal
    $(document).on('click', '.btn-increase-quantity, .btn-decrease-quantity, .btn-remove-item', function() {
        var productId = $(this).data('id');
        var currentQuantityInput = $('.product-quantity[data-id="' + productId + '"]');
        var currentQuantity = parseInt(currentQuantityInput.val());
        var newQuantity;
        var actionType;

        if ($(this).hasClass('btn-increase-quantity')) {
            newQuantity = currentQuantity + 1;
            actionType = 'update_quantity';
        } else if ($(this).hasClass('btn-decrease-quantity')) {
            newQuantity = currentQuantity - 1;
            if (newQuantity < 0) newQuantity = 0;
            actionType = 'update_quantity';
        } else {
            newQuantity = 0;
            actionType = 'remove';
        }

        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            data: {
                action: actionType,
                id: productId,
                cantidad: newQuantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateCartDisplay();
                } else {
                    console.error('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', status, error, xhr.responseText);
            }
        });
    });

    // Handle payment method selection
    $('input[name="metodo_pago"]').on('change', function() {
        var selectedMethod = $(this).val();

        $('#instruccionesPago, #efectivo-instructions, #sinpe-instructions, #paypal-payment-section, #confirmOrderBtn').hide();

        if (selectedMethod === 'Tarjeta PayPal') {
            $('#paypal-payment-section').show();
        } else if (selectedMethod === 'Efectivo Tienda') {
            $('#instruccionesPago, #efectivo-instructions, #confirmOrderBtn').show();
        } else if (selectedMethod === 'SINPE Movil') {
            $('#instruccionesPago, #sinpe-instructions, #confirmOrderBtn').show();
        }
    });

    $('input[name="metodo_pago"]:checked').trigger('change');

    // PayPal Button Integration (igual que antes)
    paypal.Buttons({
        createOrder: function(data, actions) {
            return fetch('create_order.php', { method: 'post' })
                .then(res => res.json())
                .then(orderData => orderData.id);
        },
        onApprove: function(data, actions) {
            return fetch('crearOrden.php', {
                method: 'post',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderID: data.orderID })
            })
            .then(res => res.json())
            .then(details => {
                if (details.success) {
                    window.location.href = 'confirmacion.php';
                } else {
                    alert('Error al procesar el pedido: ' + (details.error || 'Error desconocido.'));
                }
            });
        }
    }).render('#paypal-button-container');

    // Botón "Confirmar Pedido" para métodos no PayPal
    $('#confirmOrderBtn').on('click', function() {
        var selectedMethod = $('input[name="metodo_pago"]:checked').val();
        var observaciones = $('#observacionesPedido').val();
        var canjearPuntos = $('#checkboxCanjearPuntos').is(':checked');

        if (selectedMethod === 'SINPE Movil') {
            const comprobanteFile = document.getElementById('comprobanteSinpe').files[0];

            if (!comprobanteFile) {
                alert('Por favor, sube el comprobante de pago para completar el pedido.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'manual_order_complete');
            formData.append('metodo_pago_final', selectedMethod);
            formData.append('canjearPuntos', canjearPuntos ? '1' : '0');
            formData.append('observaciones', observaciones);
            formData.append('comprobanteSinpe', comprobanteFile);

            $.ajax({
                url: 'procesar_pedido.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'confirmacion.php';
                    } else {
                        alert('Error al procesar el pedido: ' + response.message);
                    }
                }
            });
        } else if (selectedMethod === 'Efectivo Tienda') {
            $.ajax({
                url: 'procesar_pedido.php',
                method: 'POST',
                data: {
                    action: 'manual_order_complete',
                    metodo_pago_final: selectedMethod,
                    canjearPuntos: canjearPuntos ? '1' : '0',
                    observaciones: observaciones
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'confirmacion.php';
                    } else {
                        alert('Error al procesar el pedido: ' + response.message);
                    }
                }
            });
        } else {
            alert('Por favor, selecciona un método de pago válido.');
        }
    });
});
</script>


</body>
</html>  