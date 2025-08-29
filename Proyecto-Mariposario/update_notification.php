<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once 'DB.php';                     
require_once 'notifications_helper.php';   


$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id']) ? (int)$_POST['id'] : null;

try {
    if ($action === 'read' && $id) {
        $sql = "UPDATE Notificacion SET Leida = 1 WHERE ID_Notificacion = ? AND ID_Usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        exit;
    }

    if ($action === 'delete' && $id) {
        $sql = "DELETE FROM Notificacion WHERE ID_Notificacion = ? AND ID_Usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        exit;
    }

    if ($action === 'read_all') {
        $sql = "UPDATE Notificacion SET Leida = 1 WHERE ID_Usuario = ? AND Leida = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => $ok]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción inválida']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Excepción: '.$e->getMessage()]);
}
