<?php
class ReservaManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function crearReserva($evento_id, $fecha, $usuario, $cantidad) {
        try {
            $sql = "INSERT INTO Reserva (evento_id, fecha_reserva, usuario, cantidad_personas) 
                    VALUES (:ID_Evento, :Fecha_Reserva, :ID_Usuario, :cantidad_personas)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':ID_Evento' => $evento_id,
                ':Fecha_Reserva' => $fecha,
                ':ID_Usuario' => $usuario,
                ':cantidad_personas' => $cantidad
            ]);

            return ['success' => true, 'message' => 'Reserva creada exitosamente'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
