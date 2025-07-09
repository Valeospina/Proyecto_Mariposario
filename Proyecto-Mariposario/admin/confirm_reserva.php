<?php
include '../DB.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE Reserva SET Estado = 'Aprobada' WHERE ID_Reserva = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: ReservaAdmin.php?msg=Reserva confirmada");
    } else {
        header("Location: ReservaAdmin.php?msg=Error al confirmar");
    }
    $stmt->close();
} else {
    header("Location: ReservaAdmin.php?msg=ID inválido");
}
$conn->close();
?>
