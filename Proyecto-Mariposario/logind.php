

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Jardín de Mariposas – Login</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap"
    rel="stylesheet"
  />
  <!-- Ruta RELATIVA a tu CSS -->
  <link rel="stylesheet" href="css/login.css" />
</head>
<body>
  <div class="container">

    <!-- PANEL IZQUIERDO: rutas relativas -->
    <div
      class="panel panel-image"
      data-img-signin="img/el-hermoso-ala-de-mariposa-de-cerca.jpg"
      data-img-signup="img/Fondo.jpg"
    >
      <img
        id="bg-image"
        src="img/el-hermoso-ala-de-mariposa-de-cerca.jpg"
        alt="Fondo mariposas"
      />
      <div class="overlay">
        <h2 id="overlay-title">Bienvenido a Jardín de Mariposas La Paz</h2>
        <p id="overlay-text">
          Disfruta de nuestra comunidad: inicia sesión o regístrate para acceder.
        </p>
        <button id="overlay-btn">Crear Cuenta</button>
      </div>
    </div>

    <!-- PANEL DERECHO (Formularios) -->
    <div class="panel panel-form">
      <!-- Iniciar Sesión -->
      <form
        id="form-signin"
        class="form active"
        action="login.php"
        method="POST"
      >
        <h2>Iniciar Sesión</h2>
        <div class="social-icons">
          <i class="bx bxl-google"></i>
          <i class="bx bxl-facebook-circle"></i>
          <i class="bx bxl-instagram"></i>
        </div>
        <input
          type="email"
          name="email"
          placeholder="Correo electrónico"
          required
          autocomplete="email"
        />
        <input
          type="password"
          name="password"
          placeholder="Contraseña"
          required
          autocomplete="current-password"
        />
        <button type="submit" class="btn">Entrar</button>
        <a href="recuperacion.php" class="forgot">¿Olvidaste tu contraseña?</a>
        <p class="switch">
          ¿No tienes cuenta? <a href="#" id="to-signup">Regístrate</a>
        </p>
      </form>

      <!-- Crear Cuenta -->
      <form
        id="form-signup"
        class="form"
        action="registro.php"
        method="POST"
      >
        <h2>Crear Cuenta</h2>
        <div class="social-icons">
          <i class="bx bxl-google"></i>
          <i class="bx bxl-facebook-circle"></i>
          <i class="bx bxl-instagram"></i>
        </div>
        <input
          type="text"
          name="nombre"
          placeholder="Nombre completo"
          required
        />
        <input
          type="email"
          name="email"
          placeholder="Correo electrónico"
          required
          autocomplete="email"
        />
        <input
          type="password"
          name="password"
          placeholder="Contraseña"
          required
          autocomplete="new-password"
        />
        <button type="submit" class="btn">Registrarse</button>
        <p class="switch">
          ¿Ya tienes cuenta? <a href="#" id="to-signin">Inicia Sesión</a>
        </p>
      </form>
    </div>
  </div>

  <!-- Boxicons (puede quedar en body) -->
  <link
    href="https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css"
    rel="stylesheet"
  />
  <!-- Ruta RELATIVA a tu JS -->
  <script src="js/login.js"></script>
</body>
</html>
