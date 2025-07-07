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
        
        // Nuevo: Captura el método de pago
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

        // Lógica de canje de puntos
        if ($canjear_puntos) {
            // ... (tu código de canje de puntos actual) ...
            $stmt_puntos = $conn->prepare("SELECT Puntos_Actuales FROM Puntos_Usuario WHERE ID_Usuario = ?");
            $stmt_puntos->bind_param("i", $id_usuario);
            $stmt_puntos->execute();
            $result_puntos = $stmt_puntos->get_result();
            $user_points_data = $result_puntos->fetch_assoc();
            $stmt_puntos->close();

            $puntos_disponibles = $user_points_data['Puntos_Actuales'] ?? 0;
            
            $valor_punto = 10; // 1 punto = ₡10 (Ajusta esto según tu sistema de puntos)
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
        
        // Nuevo: Calcular fecha de vencimiento de la proforma (ej. 72 horas)
        $fecha_vencimiento_proforma = date('Y-m-d H:i:s', strtotime('+72 hours'));

        // Iniciar Transacción de Base de Datos
        $conn->begin_transaction();

        try {
            // Insertar Pedido en la tabla 'Pedido' (incluyendo Metodo_Pago y Fecha_Vencimiento_Proforma, Estado_Envio)
            $stmt = $conn->prepare("INSERT INTO Pedido (ID_Usuario, Fecha_Pedido, Total_Pedido, Estado_Pedido, Numero_Proforma, Observaciones, Puntos_Canjeados, Monto_Canjeado, Metodo_Pago, Estado_Envio, Fecha_Vencimiento_Proforma) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $estado_inicial_pedido = 'Pendiente de Pago';
            $estado_inicial_envio = 'Pedido Recibido'; // Estado inicial de envío

            $stmt->bind_param("idsssiisss", $id_usuario, $total_pedido, $estado_inicial_pedido, $numero_proforma, $observaciones, $puntos_canjeados, $monto_canjeado, $metodo_pago, $estado_inicial_envio, $fecha_vencimiento_proforma);
            $stmt->execute();
            $id_pedido = $conn->insert_id;
            $stmt->close();

            // Insertar Detalles del Pedido en 'Pedido_Producto'
            $stmt_detalle = $conn->prepare("INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario, Descuento_Aplicado) VALUES (?, ?, ?, ?, ?)");
            foreach ($productos_pedido as $item) {
                $descuento_por_item = 0; 
                $stmt_detalle->bind_param("iiidd", $id_pedido, $item['id_producto'], $item['cantidad'], $item['precio_unitario'], $descuento_por_item);
                $stmt_detalle->execute();
            }
            $stmt_detalle->close();

            // Si se canjearon puntos, actualizarlos y registrar en historial
            if ($puntos_canjeados > 0) {
                $stmt_update_puntos = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales - ? WHERE ID_Usuario = ?");
                $stmt_update_puntos->bind_param("ii", $puntos_canjeados, $id_usuario);
                $stmt_update_puntos->execute();
                $stmt_update_puntos->close();

                $stmt_historial_puntos = $conn->prepare("INSERT INTO Historial_Puntos (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
                $accion_puntos = 'Canjeado';
                $descripcion_puntos = "Puntos canjeados en el pedido " . $numero_proforma;
                $stmt_historial_puntos->bind_param("isisis", $id_usuario, $accion_puntos, $puntos_canjeados, $descripcion_puntos, $id_pedido, 'Pedido');
                $stmt_historial_puntos->execute();
                $stmt_historial_puntos->close();
            }
            
            // Nuevo: Registro en Bitácora
            $stmt_bitacora = $conn->prepare("INSERT INTO Bitacora (ID_Usuario, Tipo_Evento, Descripcion, ID_Referencia, Tabla_Referencia) VALUES (?, ?, ?, ?, ?)");
            $tipo_evento_bitacora = 'Pedido Creado';
            $descripcion_bitacora = "Nuevo pedido #{$numero_proforma} creado por el usuario {$user_name} ({$user_email}) con método de pago: {$metodo_pago}. Total: {$total_pedido}";
            $stmt_bitacora->bind_param("isiss", $id_usuario, $tipo_evento_bitacora, $descripcion_bitacora, $id_pedido, 'Pedido');
            $stmt_bitacora->execute();
            $stmt_bitacora->close();

            // Vaciar Carrito de la sesión
            unset($_SESSION['carrito']);
            $_SESSION['carrito'] = [];

            // Confirmar Transacción de Base de Datos
            $conn->commit();

            // --- ENVÍO DE CORREO DE CONFIRMACIÓN (PROFORMA) ---
            $mail = new PHPMailer(true);

            try {
                // Configuración SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'tucorreo@gmail.com'; // ¡CAMBIA ESTO!
                $mail->Password   = ''; // ¡CAMBIA ESTO!
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                // **PARA DEPURAR EL ENVÍO DE CORREO - DESCOMENTA SOLO PARA PRUEBAS**
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; 

                $mail->setFrom('tucorreo@gmail.com', 'Eco Mariposas'); // ¡CAMBIA ESTO!
                $mail->addAddress($user_email, $user_name);
                $mail->isHTML(true);
                $mail->Subject = 'Confirmación de Pedido - Proforma ' . $numero_proforma . ' - Eco Mariposas';
                
                // Contenido del correo adaptado al método de pago
                $paymentInstructions = '';
                if ($metodo_pago === 'Efectivo Tienda' || $metodo_pago === 'Tarjeta Tienda') {
                    $paymentInstructions = "
                        <h3>Instrucciones para el Pago y Recogida:</h3>
                        <p>Para completar tu pedido, por favor acércate a nuestra tienda física con el número de Proforma <strong>{$numero_proforma}</strong>.</p>
                        <p>Allí podrás realizar el pago en {$metodo_pago} y recoger tu pedido.</p>
                    ";
                } elseif ($metodo_pago === 'SINPE Movil') {
                    $paymentInstructions = "
                        <h3>Instrucciones para el Pago y Recogida:</h3>
                        <p>Para completar tu pedido, por favor realiza una transferencia por SINPE Móvil al siguiente número:</p>
                        <ul><li><strong>SINPE Móvil:</strong> [Tu número de teléfono SINPE Móvil]</li></ul>
                        <p>Una vez realizado el pago, por favor, envíanos el comprobante por WhatsApp a [Tu Número de WhatsApp] o responde a este correo para que podamos confirmar tu pago.</p>
                        <p>Cuando el pago sea confirmado, podrás recoger tu pedido en nuestra tienda física presentando el número de Proforma <strong>{$numero_proforma}</strong>.</p>
                    ";
                } elseif ($metodo_pago === 'Transferencia Bancaria') {
                    $paymentInstructions = "
                        <h3>Instrucciones para el Pago y Recogida:</h3>
                        <p>Para completar tu pedido, por favor realiza una transferencia bancaria a la siguiente cuenta:</p>
                        <ul>
                            <li><strong>Banco:</strong> [Nombre del Banco]</li>
                            <li><strong>Cuenta IBAN:</strong> [Tu IBAN]</li>
                            <li><strong>Cédula Jurídica/Identificación:</strong> [Tu Cédula/Identificación]</li>
                            <li><strong>Nombre del Beneficiario:</strong> [Tu Nombre/Nombre Empresa]</li>
                        </ul>
                        <p>Una vez realizado el pago, por favor, envíanos el comprobante por WhatsApp a [Tu Número de WhatsApp] o responde a este correo para que podamos confirmar tu pago.</p>
                        <p>Cuando el pago sea confirmado, podrás recoger tu pedido en nuestra tienda física presentando el número de Proforma <strong>{$numero_proforma}</strong>.</p>
                    ";
                }

                $mailContent = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { width: 80%; margin: 20px auto; border: 1px solid #ddd; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                            h2 { color: #28a745; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                            th { background-color: #f2f2f2; }
                            .total { font-size: 1.2em; font-weight: bold; text-align: right; margin-top: 20px; }
                            .footer { margin-top: 30px; font-size: 0.9em; color: #777; text-align: center; }
                            .info-box { background-color: #f9f9f9; border: 1px solid #eee; padding: 10px; margin-top: 15px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h2>¡Gracias por tu pedido, {$user_name}!</h2>
                            <p>Tu pedido ha sido recibido con éxito y está pendiente de pago.</p>
                            <div class='info-box'>
                                <p><strong>Número de Proforma:</strong> {$numero_proforma}</p>
                                <p><strong>Fecha del Pedido:</strong> " . date('d/m/Y H:i') . "</p>
                                <p><strong>Método de Pago Seleccionado:</strong> {$metodo_pago}</p>
                                <p><strong>Vigencia de Proforma:</strong> Hasta el " . date('d/m/Y H:i', strtotime($fecha_vencimiento_proforma)) . ".</p>
                            </div>
                            <p><strong>Observaciones:</strong> " . (empty($observaciones) ? 'Ninguna' : htmlspecialchars($observaciones)) . "</p>
                            
                            <h3>Detalles del Pedido:</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>";
                                foreach ($productos_pedido as $item) {
                                    $mailContent .= "
                                        <tr>
                                            <td>" . htmlspecialchars($item['nombre_producto']) . "</td>
                                            <td>" . htmlspecialchars($item['cantidad']) . "</td>
                                            <td>₡" . number_format($item['precio_unitario'], 2, ',', '.') . "</td>
                                            <td>₡" . number_format($item['subtotal'], 2, ',', '.') . "</td>
                                        </tr>";
                                }
                $mailContent .= "
                                </tbody>
                            </table>";
                if ($monto_canjeado > 0) {
                    $mailContent .= "<p class='total'>Descuento por Puntos Canjeados: -₡" . number_format($monto_canjeado, 2, ',', '.') . "</p>";
                }
                $mailContent .= "
                            <p class='total'>Total a Pagar: ₡" . number_format($total_pedido, 2, ',', '.') . "</p>

                            {$paymentInstructions}

                            <p>Una vez que tu pago sea confirmado, recibirás tu factura electrónica oficial por correo.</p>
                            
                            <div class='footer'>
                                <p>Atentamente,<br>El equipo de Eco Mariposas</p>
                                <p>Visítanos en: [Tu Dirección Física Completa]</p>
                                <p>Teléfono: [Tu Número de Teléfono de Contacto]</p>
                                <p>Correo: [Tu Correo de Contacto]</p>
                            </div>
                        </div>
                    </body>
                    </html>";

                $mail->Body = $mailContent;
                $mail->AltBody = 'Tu pedido ha sido recibido. Número de Proforma: ' . $numero_proforma . '. Total a pagar: ₡' . number_format($total_pedido, 2, ',', '.') . '. Por favor, sigue las instrucciones de pago en el cuerpo del correo.';

                $mail->send();
                $response['email_sent'] = true;
            } catch (Exception $e) {
                $response['email_sent'] = false;
                $response['email_error'] = "El mensaje no pudo ser enviado. Error de Mailer: {$mail->ErrorInfo}";
                error_log("Error enviando email de proforma: " . $mail->ErrorInfo);
            }

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