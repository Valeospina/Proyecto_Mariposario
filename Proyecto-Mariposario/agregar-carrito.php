<?php
session_start(); // Inicia la sesión


require_once 'db.php'; 
// Asegúrate de que $_SESSION['carrito'] sea un array
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Define la respuesta por defecto
$response = ['success' => false, 'message' => ''];

// Verifica si la solicitud es AJAX y de tipo POST
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $productId = $_POST['id'] ?? '';
        $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_SANITIZE_NUMBER_INT);

        if (empty($productId)) {
            $response['message'] = 'ID de producto no proporcionado.';
        } elseif ($quantity <= 0) {
            $response['message'] = 'La cantidad debe ser al menos 1.';
        } else {
            // --- CONEXIÓN A LA BASE DE DATOS Y OBTENCIÓN DEL PRODUCTO ---

            try {
             

                $stmt = $conn->prepare("SELECT ID_Producto, Nombre, Precio, Imagen_URL FROM Producto WHERE ID_Producto = ?");
                $stmt->bind_param("i", $productId); // "i" para integer
                $stmt->execute();
                $result = $stmt->get_result();
                $product_data = $result->fetch_assoc(); // Obtiene los datos como array asociativo

                $stmt->close(); // Cierra el statement

            } catch (Exception $e) { // Captura cualquier tipo de excepción
                error_log("Error de base de datos al obtener el producto: " . $e->getMessage());
                $response['message'] = 'Error interno del servidor al buscar el producto.';
                header('Content-Type: application/json');
                echo json_encode($response);
                exit();
            }
            // --- FIN DE LA LÓGICA DE BASE DE DATOS ----

            if ($product_data) {
                $product_in_cart = false;
                foreach ($_SESSION['carrito'] as &$item) {
                    if ($item['id'] == $product_data['ID_Producto']) {
                        $item['cantidad'] += $quantity;
                        $product_in_cart = true;
                        break;
                    }
                }
                unset($item);

                if (!$product_in_cart) {
                    $_SESSION['carrito'][] = [
                        'id' => $product_data['ID_Producto'],
                        'nombre' => $product_data['Nombre'],
                        'precio' => $product_data['Precio'],
                        'cantidad' => $quantity,
                        'imagen_url' => $product_data['Imagen_URL']
                    ];
                }

                $response['success'] = true;
                $response['message'] = $product_data['Nombre'] . ' ha sido agregado al carrito.';

            } else {
                $response['message'] = 'Producto no encontrado en la base de datos.';
            }
        }
    } else {
        $response['message'] = 'Acción no válida.';
    }
} else {
    $response['message'] = 'Solicitud inválida.';
}

// Envía la respuesta JSON
header('Content-Type: application/json');
echo json_encode($response);


exit();
?>