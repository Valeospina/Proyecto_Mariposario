<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="./css/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <title>Login Jardin de Mariposas La paz</title>
    <link rel="icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/normalize.css">
</head>

<body>

<!-- Preloader -->
<div class="preloader">
    <div class="loader">
        <div class="loader-outter"></div>
        <div class="loader-inner"></div>
        <div class="indicator"> 
            <svg width="32px" height="32px" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <g>
                  <path d="M32 32 C22 20, 10 40, 28 40" fill="none" stroke="#ffffff" stroke-width="2"/>
                  <path d="M32 32 C42 20, 54 40, 36 40" fill="none" stroke="#ffffff" stroke-width="2"/>
                  <path d="M32 32 C18 14, 4 34, 24 36" fill="none" stroke="#80B78D" stroke-width="2">
                    <animate attributeName="d" dur="1s" repeatCount="indefinite"
                      values="
                        M32 32 C18 14, 4 34, 24 36;
                        M32 32 C16 16, 2 32, 22 36;
                        M32 32 C18 14, 4 34, 24 36"/>
                  </path>
                  <path d="M32 32 C46 14, 60 34, 40 36" fill="none" stroke="#80B78D" stroke-width="2">
                    <animate attributeName="d" dur="1s" repeatCount="indefinite"
                      values="
                        M32 32 C46 14, 60 34, 40 36;
                        M32 32 C48 16, 62 32, 42 36;
                        M32 32 C46 14, 60 34, 40 36"/>
                  </path>
                  <line x1="32" y1="30" x2="32" y2="40" stroke="#ffffff" stroke-width="2" />
                </g>
            </svg>
        </div>
    </div>
</div>
<!-- End Preloader -->

<div class="container-form sign-up">
    <div class="welcome-back">
        <div class="message">
            <h2>Bienvenido a Jardin de Mariposas La paz</h2>
            <p>Si ya tienes una cuenta por favor inicia sesion aqui</p>
            <button class="sign-up-btn">Iniciar Sesion</button>
        </div>
    </div>
    <form class="formulario" action="registro.php" method="POST">
        <h2 class="create-account">Crear una cuenta</h2>
        <div class="iconos">
            <div class="border-icon"><i class='bx bxl-instagram'></i></div>
            <div class="border-icon"><i class='bx bxl-google'></i></div>
            <div class="border-icon"><i class='bx bxl-facebook-circle'></i></div>
        </div>
        <p class="cuenta-gratis">Crear una cuenta gratis</p>
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="email" name="email" placeholder="Email" required autocomplete="email">
        <input type="password" name="password" placeholder="Contraseña" required autocomplete="new-password">
        <input type="submit" value="Registrarse">
    </form>
</div>

<div class="container-form sign-in">
    <form class="formulario" action="login.php" method="POST">
        <h2 class="create-account">Iniciar Sesion</h2>
        <div class="iconos">
            <div class="border-icon"><i class='bx bxl-instagram'></i></div>
            <div class="border-icon"><i class='bx bxl-google'></i></div>
            <div class="border-icon"><i class='bx bxl-facebook-circle'></i></div>
        </div>
        <p class="cuenta-gratis">¿Aun no tienes una cuenta?</p>
        <input type="email" name="email" placeholder="Email" required autocomplete="email">
        <input type="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
        <input type="submit" value="Iniciar Sesion">
        <p class="forgot-password">
            <a href="./recuperacion.php"><i class='bx bx-lock-open'></i> Recuperar contraseña</a>
        </p>
    </form>
    <div class="welcome-back">
        <div class="message">
            <h2>Bienvenido de nuevo</h2>
            <p>Si aun no tienes una cuenta por favor registrese aqui</p>
            <button class="sign-in-btn">Registrarse</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="./js/login.js"></script>
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
<script src="js/main.js"></script>
</body>

</html>
