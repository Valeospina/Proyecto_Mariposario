<?php
class Calendario {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // AHORA RECIBE $eventoID
    public function obtenerFechasEstado(int $year, int $month, int $eventoID): array {
        $eventos = [];

        // 1) Fechas del evento seleccionado (solo ese ID)
        $sql = "SELECT ID_Evento, DATE(Fecha) AS fecha
                FROM Evento
                WHERE YEAR(Fecha)=? AND MONTH(Fecha)=? AND ID_Evento=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $year, $month, $eventoID);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $eventos[$row['fecha']] = [
                'id'     => $row['ID_Evento'],
                'estado' => 'disponible'
            ];
        }
        $stmt->close();

        // 2) Si hay fechas del evento, ver ocupación SOLO de este evento
        if (!empty($eventos)) {
            $fechas = array_values(array_keys($eventos));
            $placeholders = implode(',', array_fill(0, count($fechas), '?'));
            $typesDates   = str_repeat('s', count($fechas));

            // IMPORTANTE: filtrar por ID_Evento también (la tabla Reserva debe tener ese campo)
            $sql2 = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total
                     FROM Reserva
                     WHERE ID_Evento = ? AND Fecha_Reserva IN ($placeholders)
                     GROUP BY Fecha_Reserva";
            $stmt2 = $this->conn->prepare($sql2);

            // bind_param dinámico: primero el eventoID (i) y luego las fechas (s...)
            $types = 'i' . $typesDates;
            $params = [$types, $eventoID];
            foreach ($fechas as $k => $v) {
                $params[] = &$fechas[$k];
            }
            call_user_func_array([$stmt2, 'bind_param'], $this->refValues($params));

            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r = $res2->fetch_assoc()) {
                // Pon rojo si alcanzó el cupo (ajusta 10 a tu capacidad real)
                $eventos[$r['fecha']]['estado'] = ((int)$r['total'] >= 10) ? 'lleno' : 'disponible';
            }
            $stmt2->close();
        }

        return $eventos;
    }

    // Helper para bind_param variable
    private function refValues(array $arr) {
        // PHP 8 ya no lo requiere, pero lo dejamos por compatibilidad
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    public function mostrar(): void {
        if (isset($_GET['accion']) && $_GET['accion'] === 'fechas') {
            $year     = isset($_GET['year'])     ? (int)$_GET['year']     : (int)date('Y');
            $month    = isset($_GET['month'])    ? (int)$_GET['month']    : (int)date('m');
            $eventoID = isset($_GET['eventoID']) ? (int)$_GET['eventoID'] : 0;

            // Sin evento => no pintamos nada
            if ($eventoID <= 0) {
                header('Content-Type: application/json');
                echo json_encode([]);
                exit;
            }

            $ev  = $this->obtenerFechasEstado($year, $month, $eventoID);
            $out = [];
            foreach ($ev as $d => $info) {
                $out[$d]         = $info['estado']; // 'disponible'|'lleno'
                $out[$d . '_id'] = $info['id'];
            }
            header('Content-Type: application/json');
            echo json_encode($out);
            exit;
        }
    }
}
