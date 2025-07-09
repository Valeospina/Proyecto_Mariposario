<?php
$page_title = "Inscripciones a Eventos";
include '../DB.php';

$sql_eventos = "SELECT ID_Evento, Nombre FROM Evento";
$eventos = $conn->query($sql_eventos)->fetch_all(MYSQLI_ASSOC);

$evento_id = $_GET['evento_id'] ?? '';
$inscritos = [];

if ($evento_id) {
    $sql = "SELECT r.*, u.Nombre AS Nombre_Usuario 
            FROM Reserva r 
            LEFT JOIN Usuario u ON r.ID_Usuario = u.ID_Usuario
            WHERE r.ID_Evento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $evento_id);
    $stmt->execute();
    $inscritos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Encabezado común -->
</head>
<body>
    <div class="admin-dashboard-layout">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <header class="main-panel-header">
                <h2>Inscripciones a Eventos</h2>
            </header>
            <main class="content-area">
                <form method="GET" class="filter-form">
                    <select name="evento_id">
                        <option value="">-- Selecciona un evento --</option>
                        <?php foreach ($eventos as $evento): ?>
                            <option value="<?= $evento['ID_Evento'] ?>" <?= ($evento_id == $evento['ID_Evento']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($evento['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Ver Inscritos</button>
                </form>

                <?php if (!empty($inscritos)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inscritos as $ins): ?>
                                <tr>
                                    <td><?= $ins['Nombre_Usuario'] ?? 'No registrado'; ?></td>
                                    <td><?= $ins['Telefono']; ?></td>
                                    <td><?= $ins['Correo']; ?></td>
                                    <td><?= $ins['Cantidad_Personas']; ?></td>
                                    <td><?= $ins['Estado']; ?></td>
                                    <td>
                                        <a href="edit_inscripcion.php?id=<?= $ins['ID_Reserva']; ?>" class="btn btn-action-edit">Editar</a>
                                        <a href="cancel_reserva.php?id=<?= $ins['ID_Reserva']; ?>" class="btn btn-action-delete" onclick="return confirm('¿Cancelar participación?')">Cancelar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($evento_id): ?>
                    <p>No hay inscripciones para este evento.</p>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
