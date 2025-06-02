$(document).ready(function() {
    // Function to update the cart item count in the header
    function updateCartItemCount() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { action: 'get_count' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#cart-item-count').text(response.total_items);
                    // Optionally update user points display if you have it in the header
                    // $('#user-points-display').text(response.user_points + ' Puntos');
                } else {
                    console.error('Error al obtener la cantidad del carrito: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error al obtener la cantidad del carrito:', status, error);
            }
        });
    }

    // Call updateCartItemCount on page load to initialize the count
    updateCartItemCount();

    // Event listener for "Add to Cart" buttons on products page
    $('.agregar-carrito').on('click', function() {
        const productId = $(this).data('id');
        const productName = $(this).data('nombre');
        const productPrice = $(this).data('precio');

        $.ajax({
            url: 'carrito.php', // The PHP script that handles cart operations
            method: 'POST',
            data: {
                action: 'add',
                id: productId,
                nombre: productName,
                precio: productPrice
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message); // Or a more subtle notification
                    updateCartItemCount(); // Update the count in the header
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Hubo un error al agregar el producto al carrito.');
            }
        });
    });

    // Function to update the cart display on the carrito.php page
    function updateCartDisplay() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { action: 'get_cart_data' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const cartItemsBody = $('#cart-items-body');
                    cartItemsBody.empty(); // Clear existing items

                    if (response.total_items === 0) {
                        $('.container.my-5').html('<div class="alert alert-info text-center" role="alert">Tu carrito está vacío. ¡Explora nuestros productos y agrega algunos!</div>');
                    } else {
                        response.carrito.forEach(item => {
                            const subtotal = (item.precio * item.cantidad).toFixed(2);
                            const row = `
                                <tr data-id="${item.id}">
                                    <td>${item.nombre}</td>
                                    <td>₡${parseFloat(item.precio).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td>
                                        <div class="input-group input-group-sm quantity-control">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-outline-secondary btn-decrease-quantity" type="button" data-id="${item.id}">-</button>
                                            </div>
                                            <input type="text" class="form-control text-center product-quantity" value="${item.cantidad}" data-id="${item.id}" data-price="${item.precio}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary btn-increase-quantity" type="button" data-id="${item.id}">+</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="item-subtotal">₡${parseFloat(subtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                    <td>
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
                    updateCartItemCount(); // Ensure the header count is also updated
                } else {
                    console.error('Error al obtener datos del carrito: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error al obtener datos del carrito:', status, error);
            }
        });
    }

    // Call updateCartDisplay if on the carrito.php page
    if ($('#cart-items-body').length) {
        updateCartDisplay();

        // Event listener for quantity increase button
        $(document).on('click', '.btn-increase-quantity', function() {
            const productId = $(this).data('id');
            const quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
            let currentQuantity = parseInt(quantityInput.val());
            currentQuantity++;
            updateQuantity(productId, currentQuantity);
        });

        // Event listener for quantity decrease button
        $(document).on('click', '.btn-decrease-quantity', function() {
            const productId = $(this).data('id');
            const quantityInput = $(this).closest('.quantity-control').find('.product-quantity');
            let currentQuantity = parseInt(quantityInput.val());
            if (currentQuantity > 0) { // Allow decreasing to 0 to trigger removal
                currentQuantity--;
                updateQuantity(productId, currentQuantity);
            }
        });

        // Event listener for remove item button
        $(document).on('click', '.btn-remove-item', function() {
            const productId = $(this).data('id');
            if (confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                removeItem(productId);
            }
        });
    }

    // Function to send AJAX request for quantity update
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
                        // If quantity becomes 0, remove the row from the display
                        $('tr[data-id="' + productId + '"]').remove();
                    } else {
                        // Otherwise, update the quantity input and subtotal
                        const row = $('tr[data-id="' + productId + '"]');
                        row.find('.product-quantity').val(newQuantity);
                        const itemPrice = parseFloat(row.find('.product-quantity').data('price'));
                        const newSubtotal = (itemPrice * newQuantity).toFixed(2);
                        row.find('.item-subtotal').text('₡' + parseFloat(newSubtotal).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    }
                    updateCartItemCount(); // Update the count in the header
                    $('#cart-total-amount').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    
                    if (response.total_items === 0) {
                        $('.container.my-5').html('<div class="alert alert-info text-center" role="alert">Tu carrito está vacío. ¡Explora nuestros productos y agrega algunos!</div>');
                    }
                } else {
                    alert('Error al actualizar la cantidad: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error al actualizar cantidad:', status, error);
                alert('Hubo un error al actualizar la cantidad del producto.');
            }
        });
    }

    // Function to send AJAX request for item removal
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
                    $('tr[data-id="' + productId + '"]').remove(); // Remove the item row from the table
                    updateCartItemCount(); // Update the count in the header
                    $('#cart-total-amount').text('₡' + parseFloat(response.cart_total_amount).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    
                    if (response.total_items === 0) {
                        $('.container.my-5').html('<div class="alert alert-info text-center" role="alert">Tu carrito está vacío. ¡Explora nuestros productos y agrega algunos!</div>');
                    }
                } else {
                    alert('Error al eliminar el producto: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error al eliminar producto:', status, error);
                alert('Hubo un error al eliminar el producto del carrito.');
            }
        });
    }
});