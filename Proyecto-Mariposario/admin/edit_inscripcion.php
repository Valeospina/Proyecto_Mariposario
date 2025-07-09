<?php
include '../DB.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}
$id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cantidad = intval($_POST['cantidad']);
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $estado = $_POST['estado'];

    $stmt = $conn->prepare("UPDATE Reserva SET Cantidad_Personas=?, Telefono=?, Correo=?, Estado=? WHERE ID_Reserva=?");
    $stmt->bind_param("isssi", $cantidad, $telefono, $correo, $estado, $id);

    if ($stmt->execute()) {
        header("Location: InsEventoAdmin.php?msg=Inscripción actualizada");
    } else {
        $error = "Error al guardar cambios.";
    }
}

$stmt = $conn->prepare("SELECT * FROM Reserva WHERE ID_Reserva = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Inscripción</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="admin-dashboard-layout">
        <div class="main-panel">
            <main class="content-area">
                <h2>Editar Inscripción</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label>Cantidad de Personas:
                        <input type="number" name="cantidad" value="<?= $res['Cantidad_Personas'] ?>" required>
                    </label>
                    <label>Teléfono:
                        <input type="text" name="telefono" value="<?= htmlspecialchars($res['Telefono']) ?>">
                    </label>
                    <label>Correo:
                        <input type="email" name="correo" value="<?= htmlspecialchars($res['Correo']) ?>">
                    </label>
                    <label>Estado:
                        <select name="estado">
                            <option value="Pendiente" <?= ($res['Estado'] == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                            <option value="Aprobada" <?= ($res['Estado'] == 'Aprobada') ? 'selected' : '' ?>>Aprobada</option>
                            <option value="Cancelada" <?= ($res['Estado'] == 'Cancelada') ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </label>
                    <button type="submit" class="btn btn-action-edit">Guardar Cambios</button>
                    <a href="InsEventoAdmin.php" class="btn">Cancelar</a>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
