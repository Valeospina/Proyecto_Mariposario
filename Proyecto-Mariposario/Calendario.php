<?php
class Calendario {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerFechasEstado($year, $month) {
        $eventos = [];

        // Obtener eventos del mes
        $sqlEventos = "SELECT ID_Evento, DATE(Fecha) AS fecha FROM Evento WHERE YEAR(Fecha) = ? AND MONTH(Fecha) = ?";
        $stmtEventos = $this->conn->prepare($sqlEventos);
        $stmtEventos->bind_param("ii", $year, $month);
        $stmtEventos->execute();
        $resultEventos = $stmtEventos->get_result();

        while ($row = $resultEventos->fetch_assoc()) {
            $eventos[$row['fecha']] = [
                'id' => $row['ID_Evento'],
                'estado' => 'disponible' // por defecto
            ];
        }
        $stmtEventos->close();

        // Verificar si hay reservas para esos eventos
        if (!empty($eventos)) {
            $fechasMarcadas = array_keys($eventos);
            $placeholders = implode(',', array_fill(0, count($fechasMarcadas), '?'));
            $types = str_repeat('s', count($fechasMarcadas));

            $sqlReservas = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total FROM Reserva WHERE Fecha_Reserva IN ($placeholders) GROUP BY Fecha_Reserva";
            $stmtReservas = $this->conn->prepare($sqlReservas);
            $stmtReservas->bind_param($types, ...$fechasMarcadas);
            $stmtReservas->execute();
            $resultReservas = $stmtReservas->get_result();

            while ($row = $resultReservas->fetch_assoc()) {
                $fecha = $row['fecha'];
                $total = (int)$row['total'];
                if (isset($eventos[$fecha])) {
                    $eventos[$fecha]['estado'] = ($total >= 10) ? 'lleno' : 'disponible';
                }
            }
            $stmtReservas->close();
        }

        return $eventos;
    }

    public function mostrar() {
        if (isset($_GET['accion']) && $_GET['accion'] === 'fechas') {
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
            
            $eventos = $this->obtenerFechasEstado($year, $month);

            $fechas = [];
            foreach ($eventos as $fecha => $info) {
                $fechas[$fecha] = $info['estado'];
            }

            header('Content-Type: application/json');
            echo json_encode($fechas);
            exit;
        }

        $year = date('Y');
        $month = date('m');
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Calendario de Eventos</title>
            <style>
                body {
                    background-color: #f5f5f5;
                    font-family: Arial, sans-serif;
                    color: #000000;
                    padding: 20px;
                }

                h1 {
                    color: #d4ac0d;
                    text-align: center;
                    margin-bottom: 30px;
                }

                .controls {
                    text-align: center;
                    margin-bottom: 20px;
                }

                select {
                    padding: 8px;
                    font-size: 16px;
                    border-radius: 5px;
                    border: 1px solid #d4ac0d;
                    background-color: #ffffff;
                    color: #000000;
                }

                .calendar {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    gap: 5px;
                    max-width: 800px;
                    margin: 0 auto;
                }

                .day {
                    padding: 10px;
                    text-align: center;
                    border-radius: 5px;
                    cursor: pointer;
                    border: 1px solid #ccc;
                }

                .disponible {
                    background-color: #28a745;
                    color: #fff;
                    font-weight: bold;
                }

                .lleno {
                    background-color: #dc3545;
                    color: #fff;
                    font-weight: bold;
                }

                .sin-evento {
                    background-color: #e0e0e0;
                    color: #aaa;
                    cursor: default;
                }
            </style>
        </head>
        <body>
        <h1>Calendario de Eventos</h1>

        <div class="controls">
            <select id="anio"></select>
            <select id="mes"></select>
        </div>

        <div class="calendar" id="calendar"></div>

        <script>
            const calendar = document.getElementById('calendar');
            const selectMes = document.getElementById('mes');
            const selectAnio = document.getElementById('anio');

            const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

            meses.forEach((m, i) => {
                const option = document.createElement('option');
                option.value = i + 1;
                option.textContent = m;
                selectMes.appendChild(option);
            });

            const yearActual = new Date().getFullYear();
            for (let y = yearActual - 1; y <= yearActual + 2; y++) {
                const option = document.createElement('option');
                option.value = y;
                option.textContent = y;
                selectAnio.appendChild(option);
            }

            selectMes.value = new Date().getMonth() + 1;
            selectAnio.value = yearActual;

            async function cargarFechas(year, month) {
                const response = await fetch(`?accion=fechas&year=${year}&month=${month}`);
                const fechas = await response.json();

                dibujarCalendario(year, month, fechas);
            }

            function dibujarCalendario(year, month, fechas) {
                calendar.innerHTML = '';
                const firstDay = new Date(year, month - 1, 1);
                const lastDay = new Date(year, month, 0);

                for (let i = 0; i < firstDay.getDay(); i++) {
                    const div = document.createElement('div');
                    calendar.appendChild(div);
                }

                for (let dia = 1; dia <= lastDay.getDate(); dia++) {
                    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                    const div = document.createElement('div');
                    div.classList.add('day');

                    if (fechas[dateStr]) {
                        div.classList.add(fechas[dateStr]);
                        div.textContent = dia;
                    } else {
                        div.classList.add('sin-evento');
                        div.textContent = dia;
                    }
                    calendar.appendChild(div);
                }
            }

            cargarFechas(yearActual, new Date().getMonth() + 1);

            selectMes.addEventListener('change', () => {
                cargarFechas(parseInt(selectAnio.value), parseInt(selectMes.value));
            });

            selectAnio.addEventListener('change', () => {
                cargarFechas(parseInt(selectAnio.value), parseInt(selectMes.value));
            });
        </script>
        </body>
        </html>
        <?php
    }
}

