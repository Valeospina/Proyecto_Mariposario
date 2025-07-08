$(document).ready(function() {
    // Función para actualizar el contador de ítems en el carrito en el encabezado
    function updateCartItemCount() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { action: 'get_count' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-item-count').text(response.total_items);
                    // Opcionalmente, actualiza la visualización de puntos del usuario si la tienes en el encabezado
                    // $('#user-points-display').text(response.user_points + ' Puntos');
                } else {
                    console.error('Error al obtener la cantidad del carrito: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al obtener la cantidad del carrito:', status, error);
            }
        });
    }

    // Llama a updateCartItemCount al cargar la página para inicializar el contador
    updateCartItemCount();

    // Listener de eventos para los botones "Agregar al Carrito" en la página de productos
    $('.agregar-carrito').on('click', function() {
        const productId = $(this).data('id');
        // No necesitamos enviar productName ni productPrice desde aquí,
        // el PHP lo obtendrá de la base de datos.

        $.ajax({
            url: 'agregar-carrito.php', // ¡CAMBIADO! Ahora apunta al script dedicado para añadir
            method: 'POST',
            data: {
                action: 'add',
                id: productId,
                quantity: 1 // Puedes ajustar esto si tienes un selector de cantidad en la vista del producto
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message); // O una notificación más sutil
                    updateCartItemCount(); // Actualiza el contador en el encabezado
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', status, error);
                alert('Hubo un error al agregar el producto al carrito.');
            }
        });
    });

    // Función para actualizar la visualización del carrito en la página carrito.php
    function updateCartDisplay() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { action: 'get_cart_data' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const cartItemsBody = $('#cart-items-body');
                    cartItemsBody.empty(); // Limpia los ítems existentes

                    // Encuentra los elementos que deseas ocultar/mostrar
                    const cartTableContainer = $('.table-responsive');
                    const paymentMethodsSection = $('.form-group.mt-4');
                    const instructionsSection = $('#instruccionesPago');
                    const observationsSection = $('.mt-4:has(#observacionesPedido)');
                    const checkoutButtonSection = $('.text-right.mt-4');
                    const emptyCartMessage = `
                        <div class="alert alert-info text-center empty-cart-message" role="alert">
                            Tu carrito está vacío. ¡Empieza a llenarlo con nuestros productos!
                            <br><a href="tienda.php" class="btn btn-primary mt-3">Ir a la Tienda</a>
                        </div>
                    `;

                    if (response.total_items === 0) {
                        // Si el carrito está vacío, oculta la tabla y las opciones de pago/checkout
                        cartTableContainer.hide();
                        paymentMethodsSection.hide();
                        instructionsSection.hide();
                        observationsSection.hide();
                        checkoutButtonSection.hide();
                        
                        // Muestra el mensaje de carrito vacío
                        $('.user-main-content').find('.alert.alert-info.text-center.empty-cart-message').remove(); // Evita duplicados
                        $('.user-main-content').append(emptyCartMessage);
                    } else {
                        // Si hay ítems, muestra la tabla y las opciones de pago/checkout
                        cartTableContainer.show();
                        paymentMethodsSection.show();
                        // instructionsSection y observationsSection se manejarán según el método de pago
                        checkoutButtonSection.show();

                        $('.user-main-content').find('.alert.alert-info.text-center.empty-cart-message').remove(); // Remueve el mensaje si estaba presente

                        response.carrito.forEach(item => {
                            const subtotal = (item.precio * item.cantidad).toFixed(2);
                            const row = `
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
                                    <td data-label="Subtotal:"><span class="item-subtotal">₡${parseFloat(subtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></td>
                                    <td data-label="Acciones:" class="carrito-item-actions">
                                        <button class="btn btn-danger btn-sm btn-remove-item" data-id="${item.id}">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            `;
                            cartItemsBody.append(row);
                        });
                        $('#cart-total-amount').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }
                    updateCartItemCount(); // Asegura que el contador del encabezado también se actualice
                } else {
                    console.error('Error al obtener datos del carrito: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al obtener datos del carrito:', status, error);
            }
        });
    }

    // Llama a updateCartDisplay si estás en la página carrito.php
    if ($('#cart-items-body').length) {
        updateCartDisplay();

        // Event listener para el botón de aumentar cantidad
        $(document).on('click', '.btn-increase-quantity', function() {
            const productId = $(this).data('id');
            const quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
            let currentQuantity = parseInt(quantityInput.val());
            currentQuantity++;
            updateQuantity(productId, currentQuantity);
        });

        // Event listener para el botón de disminuir cantidad
        $(document).on('click', '.btn-decrease-quantity', function() {
            const productId = $(this).data('id');
            const quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
            let currentQuantity = parseInt(quantityInput.val());
            if (currentQuantity > 0) { // Permite disminuir a 0 para activar la eliminación
                currentQuantity--;
                updateQuantity(productId, currentQuantity);
            }
        });

        // Event listener para el botón de eliminar ítem
        $(document).on('click', '.btn-remove-item', function() {
            const productId = $(this).data('id');
            if (confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                removeItem(productId);
            }
        });
        
        // Listener para los radio buttons de método de pago
        $('input[name="metodo_pago"]').on('change', function() {
            const selectedMethod = $(this).val();
            const instruccionesPago = $('#instruccionesPago');
            const sinpeInstructions = $('#sinpe-instructions');
            const transferenciaInstructions = $('#transferencia-instructions');

            // Oculta todas las instrucciones primero
            sinpeInstructions.hide();
            transferenciaInstructions.hide();
            instruccionesPago.hide(); // Oculta el contenedor por defecto

            // Muestra las instrucciones relevantes
            if (selectedMethod === 'SINPE Movil') {
                sinpeInstructions.show();
                instruccionesPago.show();
            } else if (selectedMethod === 'Transferencia Bancaria') {
                transferenciaInstructions.show();
                instruccionesPago.show();
            }
            // Para "Efectivo Tienda" y "Tarjeta Tienda", las instrucciones se mantienen ocultas
        });
    }

    // Función para enviar solicitud AJAX para actualizar cantidad
    function updateQuantity(productId, newQuantity) {
        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            data: {
                action: 'update_quantity',
                id: productId,
                cantidad: newQuantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (newQuantity === 0) {
                        // Si la cantidad llega a 0, elimina la fila de la visualización
                        $('tr[data-id="' + productId + '"]').remove();
                    } else {
                        // De lo contrario, actualiza la cantidad en el input y el subtotal
                        const row = $('tr[data-id="' + productId + '"]');
                        row.find('.product-quantity').val(newQuantity);
                        const itemPrice = parseFloat(row.find('.product-quantity').data('price'));
                        const newSubtotal = (itemPrice * newQuantity).toFixed(2);
                        row.find('.item-subtotal').text('₡' + parseFloat(newSubtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }
                    updateCartItemCount(); // Actualiza el contador en el encabezado
                    $('#cart-total-amount').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    // Vuelve a verificar si el carrito está vacío y actualiza la vista
                    if (response.total_items === 0) {
                         updateCartDisplay(); // Llama a la función para recargar y mostrar el mensaje de vacío
                    }
                } else {
                    alert('Error al actualizar la cantidad: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al actualizar cantidad:', status, error);
                alert('Hubo un error al actualizar la cantidad del producto.');
            }
        });
    }

    // Función para enviar solicitud AJAX para eliminar ítem
    function removeItem(productId) {
        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            data: {
                action: 'remove',
                id: productId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('tr[data-id="' + productId + '"]').remove(); // Elimina la fila del ítem de la tabla
                    updateCartItemCount(); // Actualiza el contador en el encabezado
                    $('#cart-total-amount').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                    // Vuelve a verificar si el carrito está vacío y actualiza la vista
                    if (response.total_items === 0) {
                        updateCartDisplay(); // Llama a la función para recargar y mostrar el mensaje de vacío
                    }
                } else {
                    alert('Error al eliminar el producto: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX al eliminar producto:', status, error);
                alert('Hubo un error al eliminar el producto del carrito.');
            }
        });
    }

    // Manejo del botón "Ver métodos de pago" (ahora llamado "Continuar al Pago")
    $('.btn-proceed-to-checkout').on('click', function() {
        // Redirige al usuario a la página de checkout (o realiza la lógica de envío de pedido)
        // Por ahora, solo mostraremos una alerta, pero aquí iría la lógica final.
        const metodoPago = $('input[name="metodo_pago"]:checked').val();
        const observaciones = $('#observacionesPedido').val();
        
        // Aquí podrías enviar los datos finales del pedido a un script de procesamiento de compra
        // Por ejemplo:
        // $.ajax({
        //     url: 'procesar_compra.php',
        //     method: 'POST',
        //     data: {
        //         metodo_pago: metodoPago,
        //         observaciones: observaciones,
        //         // Podrías enviar también los items del carrito o que el PHP los obtenga de la sesión
        //     },
        //     dataType: 'json',
        //     success: function(response) {
        //         if (response.success) {
        //             alert('Pedido realizado con éxito! Redirigiendo...');
        //             window.location.href = 'pagina_confirmacion.php';
        //         } else {
        //             alert('Error al procesar el pedido: ' + response.message);
        //         }
        //     }
        // });

        alert('Simulación de "Ver métodos de pago" / "Continuar al Pago":\n\nMétodo seleccionado: ' + metodoPago + '\nObservaciones: ' + observaciones);
        // Después de esta alerta, normalmente redirigirías al usuario a una página de confirmación
        // o a la pasarela de pago real, o procesarías el pedido en segundo plano.
    });

    // Listener para el checkbox de canjear puntos
    $('#checkboxCanjearPuntos').on('change', function() {
        if ($(this).is(':checked')) {
            alert('¡Canjeando puntos! La lógica de descuento se aplicaría aquí en el cálculo final.');
            // Aquí iría la lógica para recalcular el total del carrito aplicando el descuento por puntos
            // Esto implicaría otra llamada AJAX a carrito.php con una acción 'apply_points_discount'
            // o un cálculo directo si tienes todos los datos en JS.
        } else {
            alert('Puntos no canjeados. El total volverá a ser el original.');
            // Aquí iría la lógica para revertir el descuento si se desmarca la opción.
        }
        // En una aplicación real, probablemente harías una llamada AJAX para recalcular y actualizar los totales
        // updateCartDisplay(); // Para reflejar los cambios en el total
    });
});