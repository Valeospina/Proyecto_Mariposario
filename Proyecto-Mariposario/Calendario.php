<?php
class Calendario {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerFechasEstado($year, $month) {
        $eventos = [];

        // Obtener eventos del mes
        $sql = "SELECT ID_Evento, DATE(Fecha) AS fecha FROM Evento WHERE YEAR(Fecha)=? AND MONTH(Fecha)=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $eventos[$row['fecha']] = ['id'=>$row['ID_Evento'], 'estado'=>'disponible'];
        }
        $stmt->close();

        if (!empty($eventos)) {
            $fechas = array_keys($eventos);
            $ph = implode(',', array_fill(0, count($fechas),'?'));
            $types = str_repeat('s', count($fechas));
            $sql2 = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total
                     FROM Reserva WHERE Fecha_Reserva IN ($ph) GROUP BY Fecha_Reserva";
            $stmt2 = $this->conn->prepare($sql2);
            // bind_param por referencia
            $refs = [];
            foreach ($fechas as $i=>$f) $refs[$i] = &$fechas[$i];
            array_unshift($refs, $types);
            call_user_func_array([$stmt2,'bind_param'], $refs);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($r = $res2->fetch_assoc()) {
                $f = $r['fecha']; $t = (int)$r['total'];
                $eventos[$f]['estado'] = ($t>=10 ? 'lleno' : 'disponible');
            }
            $stmt2->close();
        }
        return $eventos;
    }

    public function mostrar() {
        // AJAX para fechas
        if (isset($_GET['accion']) && $_GET['accion']==='fechas') {
            $year = isset($_GET['year'])?(int)$_GET['year']:date('Y');
            $month = isset($_GET['month'])?(int)$_GET['month']:date('m');
            $ev = $this->obtenerFechasEstado($year,$month);
            $out = [];
            foreach ($ev as $d=>$info) {
                $out[$d] = $info['estado'];
                $out[$d.'_id'] = $info['id'];
            }
            header('Content-Type: application/json');
            echo json_encode($out);
            exit;
        }

        $currentPage = basename($_SERVER['PHP_SELF']);
        ?>
<!doctype html>
<html class="no-js" lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Jardin De Mariposas - Calendario de Eventos</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="./css/tienda.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/icofont.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
      :root {--color-primary:#8BC34A;--color-success:#28a745;--color-danger:#e74c3c;--color-muted:#bdc3c7;--bg:#f8f9fa;--text:#2c3e50;}
      *{box-sizing:border-box;margin:0;padding:0;}
      body{background:var(--bg);font-family:'Poppins',Arial,sans-serif;color:var(--text);}
      h1{color:#333;text-align:center;margin:30px 0;}
      .calendar-container{max-width:800px;margin:0 auto 40px;bg:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.1);overflow:hidden;}
      .calendar-header{display:flex;justify-content:space-between;align-items:center;background:var(--color-primary);color:#fff;padding:16px;}
      .calendar-header button{background:transparent;border:none;color:#fff;font-size:1.2rem;cursor:pointer;transition:transform .2s;}
      .calendar-header button:hover{transform:scale(1.1);}
      .calendar-header h2{font-size:1.5rem;margin:0;}
      .calendar-weekdays{display:grid;grid-template-columns:repeat(7,1fr);background:var(--bg);padding:12px;text-align:center;font-weight:600;color:var(--color-muted);}
      .calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px;padding:12px;}
      .day{background:#fff;padding:12px;text-align:center;border-radius:8px;cursor:pointer;transition:all .2s;position:relative;box-shadow:0 2px 4px rgba(0,0,0,0.05);}
      .day:hover{transform:translateY(-2px);box-shadow:0 4px 8px rgba(0,0,0,0.1);}
      .day.sin-evento{background:var(--bg);color:var(--color-muted);cursor:default;}
      .day.disponible{background:var(--color-success);color:#fff;}
      .day.lleno{background:var(--color-danger);color:#fff;}
      .day .number{font-size:1.1rem;font-weight:500;}
      .day .badge{position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;}
      .day.disponible .badge{background:#2ecc71;}
      .day.lleno .badge{background:#e74c3c;}
    </style>
</head>
<body>
<header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="top-link justify-content-end">
                        <li><a href="usuario.php" class="user-info-link"><i class="fas fa-user"></i><span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></span></a></li>
                        <li class="separator">|</li>
                        <li><?php if(isset($_SESSION['user_id'])): ?><a href="logout.php" class="btn-topbar-action">Cerrar Sesión</a><?php else: ?><a href="logind.php" class="btn-topbar-action">Iniciar Sesión</a><?php endif; ?></li>
                        <li class="separator">|</li>
                        <li><a href="carrito.php" class="btn-carrito-topbar"><i class="fa fa-shopping-cart"></i><span id="cart-item-count" class="badge badge-pill badge-danger">0</span></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="header-inner">
        <div class="container">
            <div class="inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-12">
                        <div class="logo"><a href="index.php"><img src="img/logo.png" alt="Logo Mariposario"></a></div>
                        <div class="mobile-nav"></div>
                    </div>
                    <div class="col-lg-9 col-md-9 col-12">
                        <div class="main-menu"><nav class="navigation"><ul class="nav menu">
                            <li <?php if($currentPage==='index.php') echo 'class="active"'; ?>><a href="index.php">Inicio</a></li>
                            <li <?php if($currentPage==='tienda.php') echo 'class="active"'; ?>><a href="tienda.php">Tienda</a></li>
                            <li <?php if($currentPage==='eventos.php') echo 'class="active"'; ?>><a href="eventos.php">Eventos</a></li>
                        </ul></nav></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<h1>Calendario de Eventos</h1>
<div class="calendar-container">
  <div class="calendar-header">
    <button id="prev"><i class="fas fa-chevron-left"></i></button>
    <h2 id="monthYear"></h2>
    <button id="next"><i class="fas fa-chevron-right"></i></button>
  </div>
  <div class="calendar-weekdays">
    <div>Lun</div><div>Mar</div><div>Mié</div><div>Jue</div><div>Vie</div><div>Sáb</div><div>Dom</div>
  </div>
  <div id="calendar" class="calendar-grid"></div>
</div>

<footer id="footer" class="footer">
<footer id="footer" class="footer">
				<!-- Footer Top -->
				<div class="footer-top">
					<div class="container">
						<div class="row">
							<!-- Acerca del Proyecto -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Sobre Nosotros</h2>
									<p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
									<!-- Social -->
									<ul class="social">
										<li><a href="#"><i class="icofont-facebook"></i></a></li>
										<li><a href="#"><i class="icofont-instagram"></i></a></li>
										<li><a href="#"><i class="icofont-twitter"></i></a></li>
									</ul>
									<!-- End Social -->
								</div>
							</div>

							<!-- Enlaces Rápidos -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer f-link">
									<h2>Enlaces Rápidos</h2>
									<div class="row">
										<div class="col-12">
											<ul>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Reservaciones</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Eventos</a></li>
												<li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Contáctanos</a></li>
											</ul>
										</div>
									</div>
								</div>
							</div>

							<!-- Horarios -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Horario de Atención</h2>
									<p>Visítanos para vivir una experiencia rodeado de naturaleza y belleza.</p>
									<ul class="time-sidual">
										<li class="day">Lunes - Viernes <span>8:00 - 17:00</span></li>
										<li class="day">Sábado <span>9:00 - 16:00</span></li>
										<li class="day">Domingo <span>Cerrado</span></li>
									</ul>
								</div>
							</div>

							<!-- Newsletter -->
							<div class="col-lg-3 col-md-6 col-12">
								<div class="single-footer">
									<h2>Boletín</h2>
									<p>Suscríbete para recibir noticias sobre nuestras mariposas, orquídeas y próximos eventos especiales.</p>
									<form action="#" method="get" target="_blank" class="newsletter-inner">
										<input name="email" placeholder="Tu correo electrónico" class="common-input" required type="email">
										<button class="button"><i class="icofont icofont-paper-plane"></i></button>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Footer Top -->

				<!-- Copyright -->
				<div class="copyright">
					<div class="container">
						<div class="row">
							<div class="col-12">
								<div class="copyright-content">
									<p>© 2025 Mariposas y Orquídeas | Todos los derechos reservados</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- End Copyright -->
			</footer>
			<!-- End Footer Area -->

<!-- Scripts originales -->
<script src="js/jquery.min.js"></script>
<script src="js/jquery-migrate-3.0.0.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/easing.js"></script>
<script src="js/colors.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap-datepicker.js"></script>
<script src="js/jquery.nav.js"></script>
<script src="js/slicknav.min.js"></script>
<script src="js/jquery.scrollUp.min.js"></script>
<script src="js/niceselect.js"></script>
<script src="js/tilt.jquery.min.js"></script>
<script src="js/owl-carousel.js"></script>
<script src="js/jquery.counterup.min.js"></script>
<script src="js/steller.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/jquery.magnific-popup.min.js"></script>
<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<script>
  const monthNames=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  const calendarEl=document.getElementById('calendar');const monthYearEl=document.getElementById('monthYear');let today=new Date(),currYear=today.getFullYear(),currMonth=today.getMonth();
  document.getElementById('prev').addEventListener('click',()=>changeMonth(-1));
  document.getElementById('next').addEventListener('click',()=>changeMonth(1));
  function changeMonth(delta){currMonth+=delta;if(currMonth<0){currMonth=11;currYear--;}if(currMonth>11){currMonth=0;currYear++;}renderCalendar(currYear,currMonth);}  
  async function fetchEventStatuses(year,month){const resp=await fetch('?accion=fechas&year='+year+'&month='+(month+1));return resp.json();}
  async function renderCalendar(year,month){calendarEl.innerHTML='';monthYearEl.textContent=monthNames[month]+' '+year;const statuses=await fetchEventStatuses(year,month);const firstDay=new Date(year,month,1).getDay();const offset=(firstDay+6)%7;const daysInMonth=new Date(year,month+1,0).getDate();for(let i=0;i<offset;i++){const e=document.createElement('div');e.className='day sin-evento';e.innerHTML='&nbsp;';calendarEl.appendChild(e);}for(let d=1;d<=daysInMonth;d++){const key=year+'-'+String(month+1).padStart(2,'0')+'-'+String(d).padStart(2,'0'),st=statuses[key]||null;const c=document.createElement('div');c.classList.add('day');if(!st)c.classList.add('sin-evento');else{c.classList.add(st);c.addEventListener('click',()=>location.href='detalle_evento.php?id='+statuses[key+'_id']);}c.innerHTML='<div class="number">'+d+'</div><div class="badge"></div>';calendarEl.appendChild(c);} }
  renderCalendar(currYear,currMonth);
</script>
<script src="js/main.js"></script>
</body>
</html>
<?php
    }
}

