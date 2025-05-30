$(document).ready(function() {

    // --- Funciones de Actualización de UI ---

    /**
     * @function updateCartAndUserUI
     * @description Fetches the latest cart item count, user points, and user info from the server
     * and updates the corresponding elements in the header.
     * This function centralizes all header UI updates.
     */
    function updateCartAndUserUI() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            dataType: 'json',
            data: { action: 'get_count' }, // Requesting cart count and user info
            success: function(response) {
                if (response.success) {
                    // Update cart item count in the header
                    $('#cart-item-count').text(response.total_items);

                    // Update user info in the header
                    // Assuming user_name, user_avatar, user_points are returned by PHP
                    $('.user-greeting').text('Hola, ' + response.user_name);
                    $('.user-avatar').attr('src', response.user_avatar);
                    $('#user-points-display').text(response.user_points + ' Puntos');

                    // If on the carrito.php page, update the main cart table total
                    if ($('#total-carrito-display').length) {
                        updateOverallCartTotal();
                    }

                } else {
                    console.error("Error al obtener datos del carrito y usuario:", response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error AJAX al obtener datos del carrito y usuario:", status, error, xhr.responseText);
            }
        });
    }

    /**
     * @function updateOverallCartTotal
     * @description Recalculates and updates the total price displayed in the cart table footer.
     * This function should only be called on the carrito.php page.
     */
    function updateOverallCartTotal() {
        let total = 0;
        $('#cart-items-body tr').each(function() {
            // Retrieve subtotal from data attribute for accuracy
            const subtotalValue = parseFloat($(this).find('.product-subtotal').data('subtotal'));
            if (!isNaN(subtotalValue)) {
                total += subtotalValue;
            }
        });
        // Format to Costa Rican Colón (₡) with 2 decimal places and thousands separator
        $('#total-carrito-display strong').text('₡' + total.toLocaleString('es-CR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    }

    // --- Initial Load ---

    // Load initial cart state and user info when the page is ready
    updateCartAndUserUI();

    // --- Event Handlers for Cart Actions ---

    // Handler for "Add to Cart" button (e.g., on mariposas.php or orquideas.php)
    // Using event delegation for potential dynamically added elements
    $(document).on('click', '.agregar-carrito', function() {
        const button = $(this);
        const productId = button.data('id');
        const productName = button.data('nombre');
        const productPrice = button.data('precio');

        button.prop('disabled', true).text('Agregando...');

        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'add',
                id: productId,
                nombre: productName,
                precio: productPrice
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    updateCartAndUserUI(); // Update header (cart count, user points)
                    // If on carrito.php, you might want to dynamically add the row
                    // For now, we'll assume a page reload or separate logic is handled
                    // if this button is on the cart page itself.
                } else {
                    alert('Error: ' + response.message);
                    console.error('Error al agregar producto (PHP):', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Hubo un error al agregar el producto. Inténtelo de nuevo.');
                console.error("Error AJAX al agregar producto:", status, error, xhr.responseText);
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fa fa-cart-plus"></i> Agregar al carrito');
            }
        });
    });

    // --- Event Handlers for the Cart Page (carrito.php) ---

    // Handle clicks on Quantity Increase/Decrease buttons
    $('#cart-items-body').on('click', '.update-quantity', function() {
        const button = $(this);
        const productId = button.data('id');
        const actionType = button.data('action'); // 'increase' or 'decrease'
        const quantityInput = $(`input.product-quantity[data-id="${productId}"]`);
        let currentQuantity = parseInt(quantityInput.val());

        if (isNaN(currentQuantity)) {
            currentQuantity = 1; // Default to 1 if not a valid number
        }

        let newQuantity = currentQuantity;
        if (actionType === 'increase') {
            newQuantity++;
        } else if (actionType === 'decrease') {
            newQuantity--;
        }

        // Prevent quantity from going below 1
        if (newQuantity < 1) {
            newQuantity = 1;
        }

        // If quantity hasn't changed, do nothing
        if (newQuantity === currentQuantity) {
            return;
        }

        // Disable buttons while processing request
        button.prop('disabled', true);
        quantityInput.prop('disabled', true);

        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'update_quantity',
                id: productId,
                cantidad: newQuantity
            },
            success: function(response) {
                if (response.success) {
                    quantityInput.val(newQuantity); // Update input value
                    updateCartAndUserUI(); // Update header (cart count, user points)

                    // Update row subtotal
                    const row = button.closest('tr');
                    const price = parseFloat(row.find('.product-price').data('price'));
                    const newSubtotal = price * newQuantity;
                    row.find('.product-subtotal').text('₡' + newSubtotal.toLocaleString('es-CR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    row.find('.product-subtotal').data('subtotal', newSubtotal); // Update data attribute

                    updateOverallCartTotal(); // Recalculate and display the overall cart total
                } else {
                    alert('Error al actualizar cantidad: ' + response.message);
                    console.error('Error al actualizar cantidad (PHP):', response.message);
                    // On error, revert quantity to original
                    quantityInput.val(currentQuantity);
                }
            },
            error: function(xhr, status, error) {
                alert('Hubo un error al actualizar la cantidad. Inténtelo de nuevo.');
                console.error("Error AJAX al actualizar cantidad:", status, error, xhr.responseText);
                // On error, revert quantity to original
                quantityInput.val(currentQuantity);
            },
            complete: function() {
                // Re-enable buttons
                button.prop('disabled', false);
                quantityInput.prop('disabled', false);
            }
        });
    });

    // Handle clicks on Remove button
    $('#cart-items-body').on('click', '.remove-cart-item', function() {
        if (!confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
            return; // Stop if user cancels
        }

        const button = $(this);
        const productId = button.data('id');
        const row = button.closest('tr');

        button.prop('disabled', true).text('Eliminando...');

        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'remove',
                id: productId
            },
            success: function(response) {
                if (response.success) {
                    row.remove(); // Remove product row from table
                    updateCartAndUserUI(); // Update header (cart count, user points)
                    updateOverallCartTotal(); // Recalculate and display the overall cart total

                    // If cart becomes empty, display the "empty cart" message
                    if (response.total_items === 0) {
                        $('.container.my-5 table').remove(); // Remove the table
                        $('.container.my-5 .text-right').remove(); // Remove the action buttons
                        $('.container.my-5').append('<div class="alert alert-info">Tu carrito está vacío. ¡Agrega algunos productos!</div>');
                    }
                    alert(response.message);
                } else {
                    alert('Error al eliminar producto: ' + response.message);
                    console.error('Error al eliminar producto (PHP):', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Hubo un error al eliminar el producto. Inténtelo de nuevo.');
                console.error("Error AJAX al eliminar producto:", status, error, xhr.responseText);
            },
            complete: function() {
                button.prop('disabled', false).text('Eliminar'); // Restore button text if error
            }
        });
    });
});