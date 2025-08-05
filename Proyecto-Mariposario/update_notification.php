<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'DB.php';

    $action = $_POST['action'] ?? null;
    $id = $_POST['id'] ?? null;

    if ($action === 'read' && $id) {
        $stmt = $conn->prepare("UPDATE Notificacion SET Leida = 1 WHERE ID_Notificacion = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo 'ok';
    }

    if ($action === 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM Notificacion WHERE ID_Notificacion = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo 'deleted';
    }

    if ($action === 'read_all') {
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $stmt = $conn->prepare("UPDATE Notificacion SET Leida = 1 WHERE ID_Usuario = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
        echo 'all_read';
    }
}
