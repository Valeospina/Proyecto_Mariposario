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

        // Verificar reservas para esos eventos
        if (!empty($eventos)) {
            $fechasMarcadas = array_keys($eventos);
            $placeholders = implode(',', array_fill(0, count($fechasMarcadas), '?'));
            $types = str_repeat('s', count($fechasMarcadas));

            $sqlReservas = "SELECT DATE(Fecha_Reserva) AS fecha, SUM(cantidad_personas) AS total FROM Reserva WHERE Fecha_Reserva IN ($placeholders) GROUP BY Fecha_Reserva";
            $stmtReservas = $this->conn->prepare($sqlReservas);

            // bind_param necesita variables por referencia, hacemos un truco para eso
            $refs = [];
            foreach ($fechasMarcadas as $key => $value) {
                $refs[$key] = &$fechasMarcadas[$key];
            }
            array_unshift($refs, $types);
            call_user_func_array([$stmtReservas, 'bind_param'], $refs);

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
        // Si la petición es AJAX para traer fechas
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

        // Variables para el menú activo en el header (puedes modificar según tu lógica)
        $currentPage = basename($_SERVER['PHP_SELF']);

        // Imprime todo el HTML con echo, sin cerrar PHP para evitar errores
        echo '
<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name="copyright" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Jardin De Mariposas - Mariposas</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="./css/tienda.css">

    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/icofont.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/tienda.css">

    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">

    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
            color: #000000;
            padding: 0;
            margin: 0;
        }
        h1 {
            color: #d4ac0d;
            text-align: center;
            margin-bottom: 30px;
        }
       .controls {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        select {
            padding: 8px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #d4ac0d;
            background-color: #ffffff;
            color: #000000;
            margin: 0 5px;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            max-width: 800px;
            margin: 0 auto 40px auto;
        }
        .day {
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid #ccc;
            user-select: none;
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
<header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="top-link justify-content-end">
                        <li>
                            <a href="usuario.php" class="user-info-link">
                                <i class="fas fa-user"></i>
                                <span>'.htmlspecialchars($_SESSION["user_name"] ?? "Usuario").'</span>
                            </a>
                        </li>
                        <li class="separator">|</li>
                        <li>';
                        if(isset($_SESSION["user_id"])) {
                            echo '<a href="logout.php" class="btn-topbar-action">Cerrar Sesión</a>';
                        } else {
                            echo '<a href="logind.php" class="btn-topbar-action">Iniciar Sesión</a>';
                        }
                    echo '</li>
                        <li class="separator">|</li>
                        <li>
                            <a href="carrito.php" class="btn-carrito-topbar">
                                <i class="fa fa-shopping-cart"></i>
                                <span id="cart-item-count" class="badge badge-pill badge-danger">0</span>
                            </a>
                        </li>
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
                        <div class="logo">
                            <a href="index.php"><img src="img/logo.png" alt="Logo Mariposario"></a>
                        </div>
                        <div class="mobile-nav"></div>
                    </div>
                    <div class="col-lg-9 col-md-9 col-12"> <div class="main-menu">
                            <nav class="navigation">
                                <ul class="nav menu">
                                    <li '.(($currentPage == "index.php") ? "class=\'active\'" : "").'>
                                        <a href="index.php">Inicio</a>
                                    </li>
                                    <li '.(($currentPage == "tienda.php") ? "class=\'active\'" : "").'>
                                        <a href="tienda.php">Tienda</a>
                                    </li>
                                    <li '.(($currentPage == "eventos.php") ? "class=\'active\'" : "").'>
                                        <a href="eventos.php">Eventos</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<h1>Calendario de Eventos</h1>

<div class="controls">
    <select id="anio"></select>
    <select id="mes"></select>
</div>

<div class="calendar" id="calendar"></div>

<footer id="footer" class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-footer">
                        <h2>Sobre Nosotros</h2>
                        <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
                        <ul class="social">
                            <li><a href="#"><i class="icofont-facebook"></i></a></li>
                            <li><a href="#"><i class="icofont-instagram"></i></a></li>
                            <li><a href="#"><i class="icofont-twitter"></i></a></li>
                        </ul>
                    </div>
                </div>
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
</footer>

<!-- jquery Min JS -->
<script src="js/jquery.min.js"></script>
<!-- jquery Migrate JS -->
<script src="js/jquery-migrate-3.0.0.js"></script>
<!-- jquery Ui JS -->
<script src="js/jquery-ui.min.js"></script>
<!-- Easing JS -->
<script src="js/easing.js"></script>
<!-- Color JS -->
<script src="js/colors.js"></script>
<!-- Popper JS -->
<script src="js/popper.min.js"></script>
<!-- Bootstrap Datepicker JS -->
<script src="js/bootstrap-datepicker.js"></script>
<!-- Jquery Nav JS -->
<script src="js/jquery.nav.js"></script>
<!-- Slicknav JS -->
<script src="js/slicknav.min.js"></script>
<!-- ScrollUp JS -->
<script src="js/jquery.scrollUp.min.js"></script>
<!-- Niceselect JS -->
<script src="js/niceselect.js"></script>
<!-- Tilt Jquery JS -->
<script src="js/tilt.jquery.min.js"></script>
<!-- Owl Carousel JS -->
<script src="js/owl-carousel.js"></script>
<!-- counterup JS -->
<script src="js/jquery.counterup.min.js"></script>
<!-- Steller JS -->
<script src="js/steller.js"></script>
<!-- Wow JS -->
<script src="js/wow.min.js"></script>
<!-- Magnific Popup JS -->
<script src="js/jquery.magnific-popup.min.js"></script>
<!-- Counter Up CDN JS -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
<!-- Bootstrap JS -->
<script src="js/bootstrap.min.js"></script>
<!-- Main JS -->
<script src="js/main.js"></script>

<script>
    const calendar = document.getElementById("calendar");
    const selectMes = document.getElementById("mes");
    const selectAnio = document.getElementById("anio");

    const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

    
    meses.forEach((m, i) => {
        const option = document.createElement("option");
        option.value = i + 1;
        option.textContent = m;
        selectMes.appendChild(option);
    });

    const yearActual = new Date().getFullYear();
    for (let y = yearActual - 1; y <= yearActual + 2; y++) {
        const option = document.createElement("option");
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
        calendar.innerHTML = "";
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);

        // Rellenar espacios en blanco para el primer día (ajuste por si domingo es 0, lo convertimos a 7)
        let startDay = firstDay.getDay();
        if (startDay === 0) startDay = 7;

        for (let i = 1; i < startDay; i++) {
            const div = document.createElement("div");
            calendar.appendChild(div);
        }

        for (let dia = 1; dia <= lastDay.getDate(); dia++) {
            const dateStr = `${year}-${String(month).padStart(2, "0")}-${String(dia).padStart(2, "0")}`;
            const div = document.createElement("div");
            div.classList.add("day");

            if (fechas[dateStr]) {
                div.classList.add(fechas[dateStr]);
                div.textContent = dia;
            } else {
                div.classList.add("sin-evento");
                div.textContent = dia;
            }
            calendar.appendChild(div);
        }
    }

    cargarFechas(yearActual, new Date().getMonth() + 1);

    selectMes.addEventListener("change", () => {
        cargarFechas(parseInt(selectAnio.value), parseInt(selectMes.value));
    });

    selectAnio.addEventListener("change", () => {
        cargarFechas(parseInt(selectAnio.value), parseInt(selectMes.value));
    });
</script>

</body>
</html>
';
    }
}
