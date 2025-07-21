<?php
session_start();
require_once 'DB.php';
require_once 'Calendario.php';

$cal = new Calendario($conn);

// 1) Obtener el ID y sanitizar
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: eventos.php');
    exit;
}

// 2) Traer datos del evento
$stmt = $conn->prepare(
  "SELECT Nombre, Descripcion, Precio, Imagen_URL, Fecha, Hora, Ubicacion
   FROM Evento
   WHERE ID_Evento = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$evento = $stmt->get_result()->fetch_assoc();
if (!$evento) {
    echo "Evento no encontrado.";
    exit;
}

// 3) Procesar envío de nueva reseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (empty($_SESSION['user_name'])) {
        echo "<p class='alert alert-warning'>Debes iniciar sesión para dejar una reseña.</p>";
    } else {
        $username = $_SESSION['user_name'];
        $rating   = intval($_POST['rating']);
        $comment  = trim($_POST['comment']);

        // Insertar calificación y comentario
        $ins = $conn->prepare(
          "INSERT INTO resenas_evento (ID_Evento, Usuario, Calificacion, `Reseña`)
           VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param('isis', $id, $username, $rating, $comment);
        $ins->execute();

        header("Location: evento_info.php?id=$id");
        exit;
    }
}

// 4) Consultar reseñas existentes con calificación
$revStmt = $conn->prepare(
  "SELECT Usuario, Calificacion, `Reseña`, Fecha
   FROM resenas_evento
   WHERE ID_Evento = ?
   ORDER BY Fecha DESC"
);
$revStmt->bind_param('i', $id);
$revStmt->execute();
$reseñas = $revStmt->get_result();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name='copyright' content=''>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Jardin De Mariposas - Mariposas</title>
    <link rel="icon" href="img/favicon.png">

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
    .small-button-custom { width: auto !important; padding: 6px 12px; font-size: 0.85em; }
    .star-rating .star { font-size: 1.5em; cursor: pointer; color: #ccc; margin-right: 5px; transition: color 0.2s ease; }
    .star-rating .star.selected { color: #ffc107; }
    .rating-text { font-size: 0.9em; color: #555; margin-top: 5px; }
    textarea#comment { width: 100%; }
  </style>
</head>
<body class="mariposa">
<?php include 'layout/nav2.php'; ?>

<section class="products-catalog-section">
  <div class="catalog-wrapper">
    <!-- Detalle del evento -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-4">
        <img src="<?= htmlspecialchars($evento['Imagen_URL']) ?>"
             class="product-detail-image" alt="Imagen <?= htmlspecialchars($evento['Nombre']) ?>">
      </div>
      <div class="col-md-8">
        <h2><?= htmlspecialchars($evento['Nombre']) ?></h2>
        <p><?= nl2br(htmlspecialchars($evento['Descripcion'])) ?></p>
        <ul class="list-unstyled">
          <li><strong>Fecha:</strong> <?= date("d/m/Y", strtotime($evento['Fecha'])) ?></li>
          <li><strong>Hora:</strong> <?= date("H:i", strtotime($evento['Hora'])) ?></li>
          <li><strong>Ubicación:</strong> <?= htmlspecialchars($evento['Ubicacion']) ?></li>
          <li><strong>Precio:</strong> ₡<?= number_format($evento['Precio'], 2) ?></li>
        </ul>
        <div class="mt-3 d-flex flex-row align-items-center gap-2">
          <a href="ReservaForm.php?id=<?= $id ?>" class="add-to-cart-button-custom small-button-custom">Reservar</a>
          <button class="add-to-cart-button-custom small-button-custom" data-toggle="modal" data-target="#calendarModal">
            Ver calendario
          </button>
          <a href="#form-review" class="add-to-cart-button-custom small-button-custom">Dejar reseña</a>
        </div>
      </div>
    </div>

    <!-- Calendario (modal) -->
    <?php $cal->mostrar(); ?>

    <hr>

    <!-- Reseñas de usuarios -->
    <h3>Reseñas de otros usuarios</h3> <br>
    <?php if ($reseñas->num_rows): ?>
      <?php while ($r = $reseñas->fetch_assoc()): ?>
        <div class="product-card-custom mb-3 p-3">
          <div class="mb-1">
            <?php for ($i = 0; $i < intval($r['Calificacion']); $i++): ?>
              <i class="fa fa-star text-warning"></i>
            <?php endfor; ?>
          </div>
          <h5 class="product-name-custom">
            <?= htmlspecialchars($r['Usuario']) ?>
            <small class="text-muted"><?= date("d/m/Y H:i", strtotime($r['Fecha'])) ?></small>
          </h5>
          <p class="product-description-custom"><?= nl2br(htmlspecialchars($r['Reseña'])) ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-muted">Aún no hay reseñas para este evento.</p>
    <?php endif; ?>

    <hr>

    <!-- Formulario de reseña siempre visible -->
    <h4 id="form-review">Deja tu reseña</h4>
    <form method="post" class="mb-5">
      <div class="form-group mb-2">
        <div class="star-rating">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="star" data-rating="<?= $i ?>">★</span>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="rating" required>
        <div class="rating-text">Haz clic en las estrellas para calificar</div>
      </div>
      <div class="form-group mb-3">
        <textarea name="comment" id="comment" class="search-input-custom" rows="4" placeholder="Tu comentario..." required></textarea>
      </div>
      <button type="submit" name="submit_review" class="add-to-cart-button-custom small-button-custom">Enviar reseña</button>
    </form>

  </div>
</section>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('.star-rating .star').forEach(function(star) {
    star.addEventListener('click', function() {
      var rating = this.getAttribute('data-rating');
      document.getElementById('rating').value = rating;
      document.querySelector('.rating-text').textContent = rating + ' estrella' + (rating > 1 ? 's' : '') + ' seleccionada';
      document.querySelectorAll('.star-rating .star').forEach(function(s) {
        s.classList.toggle('selected', s.getAttribute('data-rating') <= rating);
      });
    });
  });
</script>
</body>
</html>
