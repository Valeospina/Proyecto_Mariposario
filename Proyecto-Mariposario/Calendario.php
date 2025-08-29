<?php
class Calendario {
    private $conn;
    public function __construct($conn) { $this->conn = $conn; }

    // capacidad por día
    private int $CAPACIDAD = 10;

    public function obtenerFechasEstado(int $year, int $month, int $eventoID): array {
        $eventos = [];

        // 1) Fechas del evento seleccionado
        $sql = "SELECT ID_Evento, DATE(Fecha) AS fecha
                FROM Evento
                WHERE YEAR(Fecha)=? AND MONTH(Fecha)=? AND ID_Evento=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $year, $month, $eventoID);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $eventos[$row['fecha']] = [
                'id'       => (int)$row['ID_Evento'],
                'estado'   => 'disponible',
                'ocupados' => 0
            ];
        }
        $stmt->close();

        // 2) Ocupación SOLO de este evento
        if (!empty($eventos)) {
            $fechas = array_keys($eventos);
            $ph     = implode(',', array_fill(0, count($fechas), '?'));
            $types  = 'i' . str_repeat('s', count($fechas));

            $sql2 = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total
                     FROM Reserva
                     WHERE ID_Evento = ? AND DATE(Fecha_Reserva) IN ($ph)
                     GROUP BY DATE(Fecha_Reserva)";
            $stmt2 = $this->conn->prepare($sql2);

            // bind dinámico: primero eventoID y luego fechas
            $params = [$types, $eventoID];
            foreach ($fechas as $k => $f) { $params[] = &$fechas[$k]; }
            call_user_func_array([$stmt2, 'bind_param'], $this->refValues($params));

            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r = $res2->fetch_assoc()) {
                $ocup = (int)$r['total'];
                $eventos[$r['fecha']]['ocupados'] = $ocup;
                $eventos[$r['fecha']]['estado']   = ($ocup >= $this->CAPACIDAD) ? 'lleno' : 'disponible';
            }
            $stmt2->close();
        }

        return $eventos;
    }

    private function refValues(array $arr) {
        $refs = [];
        foreach ($arr as $k => $v) { $refs[$k] = &$arr[$k]; }
        return $refs;
    }

    public function mostrar(): void {
        if (isset($_GET['accion']) && $_GET['accion'] === 'fechas') {
            $year     = isset($_GET['year'])     ? (int)$_GET['year']     : (int)date('Y');
            $month    = isset($_GET['month'])    ? (int)$_GET['month']    : (int)date('m');
            $eventoID = isset($_GET['eventoID']) ? (int)$_GET['eventoID'] : 0;

            header('Content-Type: application/json');

            if ($eventoID <= 0) { echo json_encode([]); exit; }

            $ev  = $this->obtenerFechasEstado($year, $month, $eventoID);
            $out = [];
            foreach ($ev as $d => $info) {
                $cupos = max(0, $this->CAPACIDAD - (int)$info['ocupados']);
                $out[$d]              = $info['estado'];      // 'disponible'|'lleno'
                $out[$d . '_id']      = (int)$info['id'];
                $out[$d . '_cupos']   = $cupos;               // <<< NECESARIO PARA EL TOOLTIP
            }
            echo json_encode($out);
            exit;
        }
    }
}

