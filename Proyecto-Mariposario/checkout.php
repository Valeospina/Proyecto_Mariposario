<?php
require 'vendor/autoload.php';
require 'DB.php';             

session_start();

if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    die("El carrito está vacío.");
}

$userId = $_SESSION['user_id']; // Asegúrate de que esto exista en sesión
$carrito = $_SESSION['carrito'];

// Crear número de proforma único
$numeroProforma = 'PROF-' . strtoupper(uniqid());

// Calcular total
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

try {
    $conn->begin_transaction();

    // Insertar Pedido
    $stmt = $conn->prepare("
        INSERT INTO Pedido (
            ID_Usuario, Total_Pedido, Estado_Pedido, Numero_Proforma, Metodo_Pago
        ) VALUES (?, ?, 'Pendiente de Pago', ?, 'Tarjeta Tienda')
    ");
    $stmt->execute([$userId, $total, $numeroProforma]);
    $pedidoId = $conn->insert_id;

    // Insertar productos y actualizar stock
    foreach ($carrito as $item) {
        $idProducto = $item['id'];
        $cantidad = $item['cantidad'];
        $precio = $item['precio'];

        // Validar stock
        $verificar = $conn->prepare("SELECT Stock FROM Producto WHERE ID_Producto = ?");
        $verificar->bind_param("i", $idProducto);
        $verificar->execute();
        $verificar->bind_result($stockActual);
        $verificar->fetch();
        $verificar->close();


        if ($stockActual < $cantidad) {
            throw new Exception("Stock insuficiente para el producto ID $idProducto.");
        }

        // Descontar stock
        $conn->prepare("UPDATE Producto SET Stock = Stock - ? WHERE ID_Producto = ?")
             ->execute([$cantidad, $idProducto]);

        // Insertar detalle
        $conn->prepare("
            INSERT INTO Pedido_Producto (ID_Pedido, ID_Producto, Cantidad, Precio_Unitario)
            VALUES (?, ?, ?, ?)
        ")->execute([$pedidoId, $idProducto, $cantidad, $precio]);
    }

    $conn->commit();

    // Crear sesión de pago en Stripe
    \Stripe\Stripe::setApiKey('sk_test_51RjRay2LHFQdQmmkk5xtgl78eJ983Al5poJSNKBwvoP47C2Y3oxcwjsPtsuW8YYh3vOUXFqtYNksEQxFrPV50TPu00A2ZJiLpq'); 

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'crc', // o 'mxn', según tu país
                'product_data' => ['name' => 'Pedido ' . $numeroProforma],
                'unit_amount' => intval($total * 100),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/success.php?pedido_id=' . $pedidoId,
        'cancel_url' => 'http://localhost/cancel.php?pedido_id=' . $pedidoId,
        'metadata' => ['pedido_id' => $pedidoId],
    ]);

    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    echo "Error en el pedido: " . $e->getMessage();
}
?>
