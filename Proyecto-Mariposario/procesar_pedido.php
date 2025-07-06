<?php
session_start();
header('Content-Type: application/json');

// Incluir tu archivo de conexión a la base de datos
require_once 'DB.php'; // Asegúrate de tener este archivo con la conexión $conn

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'checkout') {
        // 1. Validar Sesión y Carrito
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $response['message'] = 'Debes iniciar sesión para realizar un pedido.';
            echo json_encode($response);
            exit();
        }

        if (empty($_SESSION['carrito'])) {
            $response['message'] = 'Tu carrito está vacío. Agrega productos antes de proceder.';
            echo json_encode($response);
            exit();
        }

        $id_usuario = $_SESSION['user_id'] ?? null; // Asegúrate de que user_id esté en tu sesión
        if (!$id_usuario) {
            $response['message'] = 'ID de usuario no encontrado en la sesión. Inicia sesión nuevamente.';
            echo json_encode($response);
            exit();
        }

        $observaciones = filter_var($_POST['observaciones'] ?? '', FILTER_SANITIZE_STRING);
        $canjear_puntos = filter_var($_POST['canjear_puntos'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        $total_pedido = 0;
        $productos_pedido = [];
        $puntos_canjeados = 0;
        $monto_canjeado = 0;

        // 2. Calcular Totales y preparar productos para inserción
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal_item = $item['precio'] * $item['cantidad'];
            $total_pedido += $subtotal_item;
            $productos_pedido[] = [
                'id_producto' => $item['id'],
                'nombre_producto' => $item['nombre'], // Guardar el nombre por si el producto cambia
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'subtotal' => $subtotal_item
            ];
        }

        // 3. Manejo de Puntos (canje)
        if ($canjear_puntos) {
            try {
                // Obtener puntos actuales del usuario
                $stmt = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_points_data = $result->fetch_assoc();
                $puntos_actuales = $user_points_data ? $user_points_data['Puntos_Actuales'] : 0;
                $stmt->close();

                // Definir la equivalencia de puntos (ej. 100 puntos = ₡1000)
                $puntos_por_descuento = 100; // Cuántos puntos para obtener un descuento
                $monto_por_puntos = 1000; // Cuánto descuento por esos puntos

                if ($puntos_actuales >= $puntos_por_descuento) {
                    $puntos_canjeables = floor($puntos_actuales / $puntos_por_descuento) * $puntos_por_descuento;
                    $monto_canjeado = ($puntos_canjeables / $puntos_por_descuento) * $monto_por_puntos;

                    if ($monto_canjeado > $total_pedido) {
                        $monto_canjeado = $total_pedido; // No canjear más de lo que cuesta el pedido
                        $puntos_canjeables = floor(($monto_canjeado / $monto_por_puntos) * $puntos_por_descuento);
                    }

                    $total_pedido -= $monto_canjeado;
                    $puntos_canjeados = $puntos_canjeables;

                    // Actualizar puntos del usuario en la DB (restar puntos canjeados)
                    $stmt = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales - ? WHERE ID_Usuario = ?");
                    $stmt->bind_param("ii", $puntos_canjeados, $id_usuario);
                    $stmt->execute();
                    $stmt->close();

                    // Registrar en historial de puntos
                    $stmt = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Accion, Monto, Descripcion) VALUES (?, ?, ?, ?)");
                    $accion_puntos = 'Usado';
                    $descripcion_puntos = "Canje de puntos en pedido " . date('Y-m-d H:i:s');
                    $stmt->bind_param("isis", $id_usuario, $accion_puntos, $puntos_canjeados, $descripcion_puntos);
                    $stmt->execute();
                    $stmt->close();

                    // Actualizar puntos en la sesión para el header
                    $_SESSION['user_points'] = $puntos_actuales - $puntos_canjeados;

                } else {
                    $response['message'] = 'No tienes suficientes puntos para canjear.';
                    // No es un error crítico, el pedido puede continuar sin canje
                    $canjear_puntos = false; // Desactivar el canje para el resto del proceso
                }
            } catch (mysqli_sql_exception $e) {
                error_log("Error al procesar puntos para usuario {$id_usuario}: " . $e->getMessage());
                $response['message'] = 'Error interno al procesar puntos.';
                echo json_encode($response);
                exit();
            }
        }
        
        // Generar un número de proforma (ej. PF-AAAA-MMDD-#####)
        $numero_proforma = 'PF-' . date('Ymd') . '-' . uniqid(); // uniqid para asegurar que sea único

        // 4. Iniciar Transacción de Base de Datos
        $conn->begin_transaction();

        try {
            // 5. Insertar Pedido en la tabla 'Pedido'
            $stmt = $conn->prepare("INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Puntos_Canjeados, Monto_Canjeado) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
            $estado_inicial = 'Pendiente de Pago';
            $stmt->bind_param("idssii", $id_usuario, $total_pedido, $estado_inicial, $numero_proforma, $puntos_canjeados, $monto_canjeado);
            $stmt->execute();
            $id_pedido = $conn->insert_id; // Obtener el ID del pedido recién insertado
            $stmt->close();

            // 6. Insertar Detalles del Pedido en 'Pedido_Producto' y actualizar stock
            $stmt_detalle = $conn->prepare("INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario, Descuento_Aplicado) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE Inventario SET Stock_Actual = Stock_Actual - ? WHERE ID_Producto = ? AND Ubicacion = 'Tienda Principal'"); // Asumimos una ubicación
            
            foreach ($productos_pedido as $item) {
                // Insertar detalle de pedido
                $descuento_por_item = 0; // Si no hay descuento individual, es 0
                $stmt_detalle->bind_param("iiidd", $id_pedido, $item['id_producto'], $item['cantidad'], $item['precio_unitario'], $descuento_por_item);
                $stmt_detalle->execute();

                // Actualizar stock (esto podría requerir más lógica si hay múltiples SKUs o ubicaciones)
                $stmt_stock->bind_param("ii", $item['cantidad'], $item['id_producto']);
                $stmt_stock->execute();
            }
            $stmt_detalle->close();
            $stmt_stock->close();

            // 7. Vaciar Carrito de la sesión
            unset($_SESSION['carrito']);
            $_SESSION['carrito'] = [];

            // 8. Confirmar Transacción
            $conn->commit();

            $response['success'] = true;
            $response['message'] = 'Pedido realizado con éxito. Por favor, acérquese a la tienda para completar el pago.';
            $response['numero_proforma'] = $numero_proforma;
            $response['total_a_pagar'] = number_format($total_pedido, 2, ',', '.'); // Formatear para mostrar en UI

        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Revertir cambios si algo falla
            $response['message'] = 'Error al procesar el pedido: ' . $e->getMessage();
            error_log("Error al procesar pedido: " . $e->getMessage()); // Log del error
        } finally {
            $conn->close();
        }

    } else {
        $response['message'] = 'Acción no reconocida.';
    }
} else {
    $response['message'] = 'Método de solicitud no permitido.';
}

echo json_encode($response);
exit();

?>