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
        // AJAX: devuelve JSON con estado de fechas
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

        // ----- Botón disparador con estilo de "Reservar" -----
        ?>

        <!-- Modal Bootstrap -->
        <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header" style="background:#8BC34A;color:#fff;">
                <h5 class="modal-title">Fechas disponibles</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                  &times;
                </button>
              </div>
              <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <button id="prevBtn" class="btn btn-light btn-sm">‹ Anterior</button>
                  <span id="monthYearLabel" class="font-weight-bold"></span>
                  <button id="nextBtn" class="btn btn-light btn-sm">Siguiente ›</button>
                </div>
                <div id="calendarTableContainer"></div>
              </div>
            </div>
          </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', () => {
          const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                              'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
          let year  = new Date().getFullYear(),
              month = new Date().getMonth() + 1;

          function loadCalendar() {
            fetch(`?accion=fechas&year=${year}&month=${month}`)
              .then(res => res.json())
              .then(data => {
                const firstDay    = new Date(year, month-1, 1).getDay();
                const offset      = (firstDay + 6) % 7;
                const daysInMonth = new Date(year, month, 0).getDate();
                let html = '<table class="table mb-0">';
                html += '<thead><tr>';
                ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']
                  .forEach(d => html += `<th>${d}</th>`);
                html += '</tr></thead><tbody><tr>';
                // vacíos
                for (let i=0;i<offset;i++) html += '<td></td>';
                // días
                for (let d=1;d<=daysInMonth;d++){
                  if ((d+offset-1)%7===0 && d!==1) html+='</tr><tr>';
                  const key = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                  const estado = data[key];
                  let cls = '';
                  if (estado==='disponible') cls='celeste';
                  if (estado==='lleno') cls='rojo';
                  const idEv = data[key+'_id']||'#';
                  html += `<td class="${cls}"><a href="detalle_evento.php?id=${idEv}">${d}</a></td>`;
                }
                html += '</tr></tbody></table>';
                document.getElementById('calendarTableContainer').innerHTML = html;
                document.getElementById('monthYearLabel').textContent =
                  `${monthNames[month-1]} ${year}`;
              });
          }

          // navegación
          document.getElementById('prevBtn').addEventListener('click', ()=>{ 
            month = month>1?month-1:12;
            if(month===12) year--;
            loadCalendar();
          });
          document.getElementById('nextBtn').addEventListener('click', ()=>{ 
            month = month<12?month+1:1;
            if(month===1) year++;
            loadCalendar();
          });

          // carga al abrir
          document.querySelectorAll('.btn-ver-fechas')
            .forEach(b=>b.addEventListener('click', loadCalendar));
        });
        </script>
        <?php
    }
}



