<?php
session_start();
include 'DB.php'; // Conexión a base de datos

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$conn || $conn->connect_error || $id <= 0) {
    die("Producto no encontrado.");
}

// 1) Procesar envío de nueva reseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (empty($_SESSION['user_name'])) {
        echo "<p class='alert alert-warning'>Debes iniciar sesión para dejar una reseña.</p>";
    } else {
        $username = $_SESSION['user_name'];
        $rating   = intval($_POST['rating']);
        $comment  = trim($_POST['comment']);

        // Insertar calificación y comentario
        $ins = $conn->prepare(
          "INSERT INTO reseñas (ID_Producto, Usuario, Calificacion, `Reseña`)
           VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param('isis', $id, $username, $rating, $comment);
        $ins->execute();
        $ins->close();

        header("Location: producto.php?id=$id");
        exit;
    }
}

// 2) Obtener datos del producto
$sql = "SELECT * FROM producto WHERE ID_Producto = ? AND Activo_Catalogo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 3) Obtener reseñas del producto
$sql_reviews = "SELECT Usuario, Calificacion, `Reseña`, Fecha FROM reseñas WHERE ID_Producto = ? ORDER BY Fecha DESC";
$stmt = $conn->prepare($sql_reviews);
$stmt->bind_param("i", $id);
$stmt->execute();
$reseñas = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($producto['Nombre']); ?> - Detalle</title>
    <link rel="icon" href="img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/tienda.css">
    <link rel="stylesheet" href="style.css">
    <style>
      .small-button-custom { width:auto!important; padding:6px 12px; font-size:0.85em; }
      .star-rating .star { font-size:1.5em; cursor:pointer; color:#ccc; margin-right:5px; transition:color 0.2s; }
      .star-rating .star.selected { color:#8BC34A; }
      .rating-text { font-size:0.9em; color:#555; margin-top:5px; }
      textarea#comment { width:100%; }
    </style>
</head>
<body class="mariposa">

<?php include 'layout/nav2.php'; ?>
<section class="products-catalog-section">
  <div class="catalog-wrapper container">

    <!-- Producto -->
    <div class="row align-items-center mb-5">
      <div class="col-md-6">
        <img src="<?= htmlspecialchars($producto['Imagen_URL']); ?>" class="product-image-custom product-detail-image" alt="Imagen del producto">
      </div>
      <div class="col-md-6">
        <h2 class="product-name-custom"><?= htmlspecialchars($producto['Nombre']); ?></h2>

        <!-- Descripción con botón "Leer más" -->
        <p class="product-description-custom"><?= htmlspecialchars($producto['Descripcion']); ?></p>
        <button class="toggle-description btn btn-link p-0" style="font-size: 0.85em; color:#8BC34A; background:none; border:none;">Leer más</button>

        <div class="product-price-custom mb-3">₡<?= number_format($producto['Precio'], 2, ',', '.'); ?></div>
        <button class="add-to-cart-button-custom agregar-carrito small-button-custom"
            data-id="<?= $producto['ID_Producto']; ?>"
            data-nombre="<?= htmlspecialchars($producto['Nombre']); ?>"
            data-precio="<?= $producto['Precio']; ?>"
            data-imagen-url="<?= htmlspecialchars($producto['Imagen_URL']); ?>">
            Añadir al carrito
        </button>
      </div>
    </div>

    <!-- Reseñas de usuarios -->
    <h3>Reseñas de otros usuarios</h3>  <br>
    <?php if ($reseñas->num_rows): ?>
      <?php while ($r = $reseñas->fetch_assoc()): ?>
        <div class="product-card-custom mb-3 p-3">
          <div class="mb-1">
            <?php for ($i=0; $i<intval($r['Calificacion']); $i++): ?>
              <i class="fa fa-star text-warning"></i>
            <?php endfor; ?>
          </div>
          <h5 class="product-name-custom">
            <?= htmlspecialchars($r['Usuario']); ?>
            <small class="text-muted"><?= date("d/m/Y H:i", strtotime($r['Fecha'])); ?></small>
          </h5>
          <p class="product-description-custom"><?= nl2br(htmlspecialchars($r['Reseña'])); ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-muted">Aún no hay reseñas para este producto.</p>
    <?php endif; ?>

    <hr>

    <!-- Formulario de reseña -->
    <h4 id="form-review">Deja tu reseña</h4>
    <form method="post" class="mb-5">
      <div class="form-group mb-2">
        <div class="star-rating">
          <?php for ($i=1; $i<=5; $i++): ?>
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
<script src="js/cart_interaction.js"></script>
<script>
  // Script para la calificación por estrellas
  document.querySelectorAll('.star-rating .star').forEach(star => {
    star.addEventListener('click', () => {
      let rating = star.dataset.rating;
      document.getElementById('rating').value = rating;
      document.querySelector('.rating-text').textContent = rating + ' estrella' + (rating > 1 ? 's' : '') + ' seleccionada';
      document.querySelectorAll('.star-rating .star').forEach(s => s.classList.toggle('selected', s.dataset.rating <= rating));
    });
  });

  // Script para "Leer más / Leer menos" en la descripción del producto
  const toggleBtn = document.querySelector('.toggle-description');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      const desc = document.querySelector('.product-description-custom');
      desc.classList.toggle('expanded');
      this.textContent = desc.classList.contains('expanded') ? 'Leer menos' : 'Leer más';
    });
  }
</script>
</body>
</html>
