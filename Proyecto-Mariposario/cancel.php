<?php
require 'DB.php';

$pedido_id = $_GET['pedido_id'] ?? null;

if ($pedido_id) {
    $conn->beginTransaction();

    
    $stmt = $conn->prepare("SELECT ID_Producto, Cantidad FROM Pedido_Producto WHERE ID_Pedido = ?");
    $stmt->execute([$pedido_id]);

    while ($row = $stmt->fetch()) {
        $conn->prepare("UPDATE Producto SET Stock = Stock + ? WHERE ID_Producto = ?")
             ->execute([$row['Cantidad'], $row['ID_Producto']]);
    }

    $conn->prepare("UPDATE Pedido SET Estado_Pedido = 'Cancelado' WHERE ID_Pedido = ?")
         ->execute([$pedido_id]);

    $conn->commit();
}

echo "<h3> Pago cancelado. Tu pedido fue revertido.</h3>";
?>
