<?php
include '../DB.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE Reserva SET Estado = 'Cancelada' WHERE ID_Reserva = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?msg=Reserva cancelada");
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?msg=Error al cancelar");
    }
    $stmt->close();
} else {
    header("Location: ReservaAdmin.php?msg=ID inválido");
}
$conn->close();
?>
