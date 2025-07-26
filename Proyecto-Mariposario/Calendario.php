<?php
class Calendario {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerFechasEstado(int $year, int $month): array {
        $eventos = [];
        $sql = "SELECT ID_Evento, DATE(Fecha) AS fecha
                FROM Evento
                WHERE YEAR(Fecha)=? AND MONTH(Fecha)=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $eventos[$row['fecha']] = [
                'id'     => $row['ID_Evento'],
                'estado' => 'disponible'
            ];
        }
        $stmt->close();

        if (!empty($eventos)) {
            $fechas = array_keys($eventos);
            $ph     = implode(',', array_fill(0, count($fechas), '?'));
            $types  = str_repeat('s', count($fechas));

            $sql2 = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total
                     FROM Reserva
                     WHERE Fecha_Reserva IN ($ph)
                     GROUP BY Fecha_Reserva";
            $stmt2 = $this->conn->prepare($sql2);

            // bind_param dinámico
            $refs = [];
            foreach ($fechas as $i => $f) {
                $refs[$i] = &$fechas[$i];
            }
            array_unshift($refs, $types);
            call_user_func_array([$stmt2, 'bind_param'], $refs);

            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r = $res2->fetch_assoc()) {
                $eventos[$r['fecha']]['estado'] = ((int)$r['total'] >= 10) ? 'lleno' : 'disponible';
            }
            $stmt2->close();
        }

        return $eventos;
    }

    public function mostrar(): void {
        // Si es petición AJAX para las fechas, devolvemos JSON y salimos
        if (isset($_GET['accion']) && $_GET['accion'] === 'fechas') {
            $year  = isset($_GET['year'])  ? (int)$_GET['year']  : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
            $ev    = $this->obtenerFechasEstado($year, $month);
            $out   = [];
            foreach ($ev as $d => $info) {
                $out[$d]         = $info['estado'];
                $out[$d . '_id'] = $info['id'];
            }
            header('Content-Type: application/json');
            echo json_encode($out);
            exit;
        }
    }
}




