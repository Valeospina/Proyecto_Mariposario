<?php
class ReservaManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Verifica si la fecha está disponible para un evento dado
    public function fechaDisponible($evento_id, $fecha) {
        // Aquí puedes agregar lógica para límite de reservas por día o por evento
        $sql = "SELECT COUNT(*) FROM reservas WHERE evento_id = :evento_id AND fecha_reserva = :fecha_reserva";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':evento_id' => $evento_id,
            ':fecha_reserva' => $fecha
        ]);
        $count = $stmt->fetchColumn();

        // Por ejemplo, límite máximo 20 reservas por día para un evento
        $maxReservasPorDia = 20;

        return $count < $maxReservasPorDia;
    }

    // Crea una reserva si la fecha está disponible
    public function crearReserva($evento_id, $fecha, $usuario, $cantidad) {
        if (!$this->fechaDisponible($evento_id, $fecha)) {
            return ['success' => false, 'message' => 'Fecha no disponible para reserva.'];
        }

        $sql = "INSERT INTO reservas (evento_id, fecha_reserva, usuario, cantidad_personas) VALUES (:evento_id, :fecha_reserva, :usuario, :cantidad_personas)";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            ':evento_id' => $evento_id,
            ':fecha_reserva' => $fecha,
            ':usuario' => $usuario,
            ':cantidad_personas' => $cantidad
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'Reserva creada correctamente.'];
        } else {
            return ['success' => false, 'message' => 'Error al crear la reserva.'];
        }
    }

    // Obtiene fechas disponibles para un evento en un rango de fechas
    public function obtenerFechasDisponibles($evento_id, $fecha_inicio, $fecha_fin) {
        $fechasDisponibles = [];

        $periodo = new DatePeriod(
            new DateTime($fecha_inicio),
            new DateInterval('P1D'),
            (new DateTime($fecha_fin))->modify('+1 day')
        );

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');
            if ($this->fechaDisponible($evento_id, $fechaStr)) {
                $fechasDisponibles[] = $fechaStr;
            }
        }

        return $fechasDisponibles;
    }
}
?>

?>
