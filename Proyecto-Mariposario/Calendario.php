<?php
class Calendario {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Devuelve un array con las fechas reservadas para un mes y año dados en formato "YYYY-MM-DD"
    public function obtenerFechasReservadas($year, $month) {
        $fechas = [];

        $sql = "SELECT DATE(Fecha_Reserva) as fecha FROM Reserva 
                WHERE YEAR(Fecha_Reserva) = ? AND MONTH(Fecha_Reserva) = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            die("Error en prepare: " . $this->conn->error);
        }
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $fechas[] = $row['fecha'];
        }
        $stmt->close();

        return $fechas;
    }

    // Muestra la página del calendario y maneja la petición AJAX para fechas reservadas
   public function mostrar() {
    // Si es petición AJAX para obtener fechas reservadas
    if (isset($_GET['accion']) && $_GET['accion'] === 'fechas') {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $fechas = $this->obtenerFechasReservadas($year, $month);
        header('Content-Type: application/json');
        echo json_encode($fechas);
        exit;
    }

    // Definir año y mes por defecto para mostrar calendario inicial
    $year = date('Y');
    $month = date('m');


        // Si no, mostrar la página completa
 $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Calendario de Reservas</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f9f9f9;
      color: #333;
      margin: 0;
      padding: 20px;
    }

    h2 {
      text-align: center;
      margin-bottom: 10px;
      color: #2c3e50;
    }

    .controls {
      text-align: center;
      margin-bottom: 20px;
    }

    select {
      padding: 10px;
      font-size: 1rem;
      margin: 0 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      background-color: #fff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      transition: border 0.3s;
    }

    select:focus {
      border-color: #3498db;
      outline: none;
    }

    .calendar {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
      max-width: 700px;
      margin: 0 auto;
      background-color: #fff;
      padding: 15px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .header {
      font-weight: 600;
      background-color: #ecf0f1;
      padding: 12px 0;
      border-radius: 6px;
    }

    .day {
      padding: 15px 0;
      border-radius: 6px;
      background-color: #e8f5e9; /* verde claro */
      font-weight: 500;
      transition: transform 0.2s ease;
      cursor: pointer;
    }

    .day:hover {
      transform: scale(1.05);
    }

    .unavailable {
      background-color: #f8d7da;
      color: #721c24;
      cursor: not-allowed;
    }

    @media (max-width: 768px) {
      .calendar {
        grid-template-columns: repeat(7, 1fr);
        font-size: 14px;
      }
    }

    @media (max-width: 480px) {
      .calendar {
        grid-template-columns: repeat(7, 1fr);
        font-size: 12px;
        gap: 5px;
      }
    }
  </style>
</head>
<body>
  <h2>Calendario de Reservas</h2>

  <div class="controls">
    <label for="mes">Mes:</label>
    <select id="mes"></select>

    <label for="anio">Año:</label>
    <select id="anio"></select>
  </div>

  <div class="calendar" id="calendar"></div>

  <script>
    const calendar = document.getElementById('calendar');
    const selectMes = document.getElementById('mes');
    const selectAnio = document.getElementById('anio');

    const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", 
                   "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

    meses.forEach((m, i) => {
      const option = document.createElement('option');
      option.value = i + 1;
      option.textContent = m;
      selectMes.appendChild(option);
    });

    const yearActual = new Date().getFullYear();
    for (let y = yearActual - 1; y <= yearActual + 5; y++) {
      const option = document.createElement('option');
      option.value = y;
      option.textContent = y;
      selectAnio.appendChild(option);
    }

    selectMes.value = new Date().getMonth() + 1;
    selectAnio.value = yearActual;

    function dibujarCalendario(year, month, fechasReservadas) {
      calendar.innerHTML = '';
      const daysOfWeek = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];

      daysOfWeek.forEach(day => {
        const header = document.createElement("div");
        header.className = "header";
        header.textContent = day;
        calendar.appendChild(header);
      });

      const firstDay = new Date(year, month - 1, 1);
      const lastDay = new Date(year, month, 0);

      for (let i = 0; i < firstDay.getDay(); i++) {
        const empty = document.createElement("div");
        calendar.appendChild(empty);
      }

      for (let day = 1; day <= lastDay.getDate(); day++) {
        const dateStr = year + "-" + String(month).padStart(2, "0") + "-" + String(day).padStart(2, "0");
        const div = document.createElement("div");

        if (fechasReservadas.includes(dateStr)) {
          div.className = "day unavailable";
          div.title = "Fecha no disponible";
        } else {
          div.className = "day";
          div.title = "Fecha disponible";
        }

        div.textContent = day;
        calendar.appendChild(div);
      }
    }

    async function cargarFechas(year, month) {
      try {
        const response = await fetch(`?accion=fechas&year=${year}&month=${month}`);
        if (!response.ok) throw new Error("Error al obtener las fechas");
        const fechas = await response.json();
        dibujarCalendario(year, month, fechas);
      } catch (error) {
        console.error(error);
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
HTML;

        echo $html;
    }
}
