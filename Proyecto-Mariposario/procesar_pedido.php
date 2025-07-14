<?php
session_start();
header('Content-Type: application/json');

require_once 'DB.php'; 
require 'vendor/autoload.php'; 

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'checkout') {
        $id_usuario = $_SESSION['user_id'] ?? null;
        $user_email = $_SESSION['user_email'] ?? null;
        $user_name = $_SESSION['user_name'] ?? 'Cliente';
        
        if (!$id_usuario || !$user_email) {
            $response['message'] = 'ID de usuario o correo no encontrado en la sesión. Inicia sesión nuevamente.';
            echo json_encode($response);
            exit();
        }

        if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
            $response['message'] = 'El carrito está vacío. No se puede procesar el pedido.';
            echo json_encode($response);
            exit();
        }

        $observaciones = filter_var($_POST['observaciones'] ?? '', FILTER_SANITIZE_STRING);
        $canjear_puntos = filter_var($_POST['canjear_puntos'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        
        $metodo_pago = $_POST['metodo_pago'] ?? null;
        if (!in_array($metodo_pago, ['Efectivo Tienda', 'Tarjeta Tienda', 'SINPE Movil', 'Transferencia Bancaria'])) {
            $response['message'] = 'Método de pago no válido.';
            echo json_encode($response);
            exit();
        }

        $total_pedido = 0;
        $productos_pedido = [];
        $puntos_canjeados = 0;
        $monto_canjeado = 0;

        foreach ($_SESSION['carrito'] as $key => $item) {
            $id_producto_real = (int)$item['id']; 
            $cantidad_int = (int)$item['cantidad'];

            $stmt = $conn->prepare("SELECT Nombre, Precio, Imagen_URL FROM Producto WHERE ID_Producto = ?");
            $stmt->bind_param("i", $id_producto_real);
            $stmt->execute();
            $result = $stmt->get_result();
            $producto_db = $result->fetch_assoc();
            $stmt->close();

            if ($producto_db) {
                $precio_unitario_real = (float)$producto_db['Precio'];
                $subtotal_item = $precio_unitario_real * $cantidad_int;
                $total_pedido += $subtotal_item;

                $productos_pedido[] = [
                    'id_producto' => $id_producto_real,
                    'nombre_producto' => $producto_db['Nombre'],
                    'imagen_url' => $producto_db['Imagen_URL'],
                    'cantidad' => $cantidad_int,
                    'precio_unitario' => $precio_unitario_real,
                    'subtotal' => $subtotal_item
                ];
            } else {
                $response['message'] = 'Error: Uno o más productos en el carrito no son válidos.';
                echo json_encode($response);
                exit();
            }
        }

        if ($canjear_puntos) {
            $stmt_puntos = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
            $stmt_puntos->bind_param("i", $id_usuario);
            $stmt_puntos->execute();
            $result_puntos = $stmt_puntos->get_result();
            $user_points_data = $result_puntos->fetch_assoc();
            $stmt_puntos->close();

            $puntos_disponibles = $user_points_data['Puntos_Actuales'] ?? 0;
            $valor_punto = 10;
            $max_puntos_canjear = floor($total_pedido / $valor_punto); 

            $puntos_a_canjear = min($puntos_disponibles, $max_puntos_canjear);
            $monto_descuento_por_puntos = $puntos_a_canjear * $valor_punto;

            if ($monto_descuento_por_puntos > 0) {
                $total_pedido -= $monto_descuento_por_puntos;
                $puntos_canjeados = $puntos_a_canjear;
                $monto_canjeado = $monto_descuento_por_puntos;
            }
        }

        $total_pedido = max(0, $total_pedido); 
        $numero_proforma = 'PF-' . date('Ymd') . '-' . substr(uniqid(), -5); 
        $fecha_vencimiento_proforma = date('Y-m-d H:i:s', strtotime('+72 hours'));

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Puntos_Canjeados, Monto_Canjeado, Metodo_Pago, Estado_Envio, Fecha_Vencimiento_Proforma) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $estado_inicial_pedido = 'Pendiente de Pago';
            $estado_inicial_envio = 'Pedido Recibido';

            $stmt->bind_param("idsssiisss", $id_usuario, $total_pedido, $estado_inicial_pedido, $numero_proforma, $observaciones, $puntos_canjeados, $monto_canjeado, $metodo_pago, $estado_inicial_envio, $fecha_vencimiento_proforma);
            $stmt->execute();
            $id_pedido = $conn->insert_id;
            $stmt->close();

            $stmt_detalle = $conn->prepare("INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario, Descuento_Aplicado) VALUES (?, ?, ?, ?, ?)");
            foreach ($productos_pedido as $item) {
                $descuento_por_item = 0; 
                $stmt_detalle->bind_param("iiidd", $id_pedido, $item['id_producto'], $item['cantidad'], $item['precio_unitario'], $descuento_por_item);
                $stmt_detalle->execute();
            }
            $stmt_detalle->close();

            if ($puntos_canjeados > 0) {
                $stmt_update_puntos = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales - ? WHERE ID_Usuario = ?");
                $stmt_update_puntos->bind_param("ii", $puntos_canjeados, $id_usuario);
                $stmt_update_puntos->execute();
                $stmt_update_puntos->close();

                $stmt_historial_puntos = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
                $accion_puntos = 'Canjeado';
                $descripcion_puntos = "Puntos canjeados en el pedido " . $numero_proforma;
                $tipo_ref = 'Pedido';
                $stmt_historial_puntos->bind_param("isisis", $id_usuario, $accion_puntos, $puntos_canjeados, $descripcion_puntos, $id_pedido, $tipo_ref);
                $stmt_historial_puntos->execute();
                $stmt_historial_puntos->close();
            } else {
                $porcentaje_puntos = 0.05;
                $puntos_ganados = floor($total_pedido * $porcentaje_puntos);
                if ($puntos_ganados > 0) {
                    $stmt_sumar_puntos = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales + ? WHERE ID_Usuario = ?");
                    $stmt_sumar_puntos->bind_param("ii", $puntos_ganados, $id_usuario);
                    $stmt_sumar_puntos->execute();
                    $stmt_sumar_puntos->close();

                    $stmt_historial_ganado = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia) VALUES (?, NOW(), 'Ganado', ?, ?, ?, 'Pedido')");
                    $descripcion_ganados = "Puntos ganados por pedido " . $numero_proforma;
                    $stmt_historial_ganado->bind_param("iisi", $id_usuario, $puntos_ganados, $descripcion_ganados, $id_pedido);
                    $stmt_historial_ganado->execute();
                    $stmt_historial_ganado->close();
                }
            }

            $stmt_bitacora = $conn->prepare("INSERT INTO Bitacora (ID_Usuario, Tipo_Evento, Descripcion, ID_Referencia, Tabla_Referencia) VALUES (?, ?, ?, ?, ?)");
            $tipo_evento_bitacora = 'Pedido Creado';
            $descripcion_bitacora = "Nuevo pedido #{$numero_proforma} creado por el usuario {$user_name} ({$user_email}) con método de pago: {$metodo_pago}. Total: {$total_pedido}";
            $stmt_bitacora->bind_param("isiss", $id_usuario, $tipo_evento_bitacora, $descripcion_bitacora, $id_pedido, $tabla = 'Pedido');
            $stmt_bitacora->execute();
            $stmt_bitacora->close();

            unset($_SESSION['carrito']);
            $_SESSION['carrito'] = [];

            $conn->commit();

            // Aquí continúa el bloque de envío de correo (no mostrado por longitud)
            // ...

            $response['success'] = true;
            $response['message'] = 'Pedido realizado con éxito. Se ha enviado una proforma a tu correo.';
            $response['numero_proforma'] = $numero_proforma;
            $response['total_a_pagar'] = number_format($total_pedido, 2, ',', '.');

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $response['message'] = 'Error al procesar el pedido en la base de datos: ' . $e->getMessage();
            error_log("Error al procesar pedido (DB): " . $e->getMessage());
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
