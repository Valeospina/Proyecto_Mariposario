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
                    cartItemsBody.empty();

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

    // Increase quantity
    $(document).on('click', '.btn-increase-quantity', function() {
        let productId = $(this).data('id');
        let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
        let currentQuantity = parseInt(quantityInput.val());
        let newQuantity = currentQuantity + 1;
        updateCartItemQuantity(productId, newQuantity);
    });

    // Decrease quantity
    $(document).on('click', '.btn-decrease-quantity', function() {
        let productId = $(this).data('id');
        let quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
        let currentQuantity = parseInt(quantityInput.val());
        let newQuantity = currentQuantity - 1;
        if (newQuantity >= 0) {
            updateCartItemQuantity(productId, newQuantity);
        }
    });

    // Remove item
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
                    updateCartDisplay();
                    updateHeaderCartCount(response.total_items);
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
                    updateCartDisplay();
                    updateHeaderCartCount(response.total_items);
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

    function updateHeaderCartCount(count) {
        $('#cart-item-count').text(count);
    }

    // Inicializar la vista del carrito
    updateCartDisplay();
});
