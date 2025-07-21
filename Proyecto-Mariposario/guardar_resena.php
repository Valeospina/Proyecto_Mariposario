<?php
include 'DB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = intval($_POST['id_producto']);
    $usuario = htmlspecialchars(trim($_POST['usuario']));
    $resena = htmlspecialchars(trim($_POST['resena']));

    if ($conn && !$conn->connect_error && $id_producto > 0 && !empty($usuario) && !empty($resena)) {
        $stmt = $conn->prepare("INSERT INTO reseñas (ID_Producto, Usuario, Reseña, Fecha) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iss", $id_producto, $usuario, $resena);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: producto.php?id=" . $id_producto);
    exit();
}
?>
