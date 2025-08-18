<?php
require __DIR__ . '/vendor/autoload.php'; // Autoload Composer

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;

        // Conexión a la base de datos
        $this->db = new \mysqli("localhost", "root", "", "mariposariodb");
        if ($this->db->connect_error) {
            echo "Error de conexión a MySQL: " . $this->db->connect_error . "\n";
            exit;
        }

        echo "Servidor WebSocket iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión: ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // ✅ Caso 1: Notificación de nuevo chat
        if (isset($data['tipo']) && $data['tipo'] === 'nuevo_chat') {
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    "tipo" => "nuevo_chat",
                    "consultaId" => $data['consultaId'],
                    "tema" => $data['tema'],
                    "usuario" => $data['user']
                ], JSON_UNESCAPED_UNICODE));
            }
            echo "Notificación de nuevo chat enviada: ID {$data['consultaId']}\n";
            return;
        }

        // ✅ Caso 2: Mensaje normal
        if (!isset($data['consultaId'], $data['message'], $data['user'])) {
            echo "Mensaje inválido recibido.\n";
            return;
        }

        $consultaId = (int)$data['consultaId'];
        $mensaje = $this->db->real_escape_string($data['message']);
        $user = $this->db->real_escape_string($data['user']);

        // Obtener historial actual
        $sql = "SELECT Mensajes FROM Consulta WHERE ID_Consulta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $consultaId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $mensajes = $result && $result['Mensajes'] ? json_decode($result['Mensajes'], true) : [];
        $mensajes[] = [
            "role" => $user,
            "text" => $mensaje,
            "time" => date('H:i')
        ];

        // Guardar el mensaje actualizado en BD
        $update = $this->db->prepare("UPDATE Consulta SET Mensajes = ? WHERE ID_Consulta = ?");
        $jsonMensajes = json_encode($mensajes, JSON_UNESCAPED_UNICODE);
        $update->bind_param("si", $jsonMensajes, $consultaId);
        $update->execute();

        // Difundir mensaje a todos los clientes conectados
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                "consultaId" => $consultaId,
                "user" => $user,
                "message" => $mensaje,
                "time" => date('H:i')
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Conexión cerrada: ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Iniciar servidor WebSocket en puerto 8080
$app = new Ratchet\App('localhost', 8080);
$app->route('/chat', new Chat, ['*']);
$app->run();
