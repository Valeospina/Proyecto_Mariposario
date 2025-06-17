<?php
session_start();
include '../DB.php'; // Archivo de conexión

// Protección: solo admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.html');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $precio = floatval($_POST['precio']);
    $imagen_url = htmlspecialchars(trim($_POST['imagen_url']));

    if (empty($nombre) || empty($descripcion) || $precio <= 0) {
        $message = "Completa correctamente todos los campos obligatorios.";
        $message_type = "danger";
    } else {
        $query = "INSERT INTO Evento (Nombre, Descripcion, Precio, Imagen_URL) VALUES (?, ?, ?, ?)";
        try {
            if ($conn instanceof mysqli) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssds", $nombre, $descripcion, $precio, $imagen_url);
                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $message = "Evento agregado exitosamente.";
                    $message_type = "success";
                    $_POST = array();
                } else {
                    $message = "No se pudo agregar el evento.";
                    $message_type = "warning";
                }
                $stmt->close();
            } else {
                throw new Exception("Conexión no válida.");
            }
        } catch (Exception $e) {
            $message = "Error: " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Añadir Evento - Panel Administración</title>
<link rel="stylesheet" href="../css/admin_styles.css" />
</head>
<body>
<header class="admin-header">
  <h1>Panel de Administración</h1>
  <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Rol: <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Desconocido'); ?>)</p>
</header>
<nav class="admin-nav">
  <ul>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="eventoadmin.php">Gestionar Eventos</a></li>
    <li><a href="users.php">Gestionar Usuarios</a></li>
    <li><a href="../logout.php">Cerrar Sesión</a></li>
  </ul>
</nav>
<main class="admin-content">
  <h2>Añadir Nuevo Evento</h2>
  <p>Completa el formulario para agregar un nuevo evento.</p>

  <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <form action="add_evento.php" method="POST">
    <div class="form-group">
      <label for="nombre">Nombre del Evento:</label>
      <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required />
    </div>

    <div class="form-group">
      <label for="descripcion">Descripción:</label>
      <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
      <label for="precio">Precio:</label>
      <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" required />
    </div>

    <div class="form-group">
      <label for="imagen_url">URL de la Imagen (opcional):</label>
      <input type="text" id="imagen_url" name="imagen_url" value="<?php echo htmlspecialchars($_POST['imagen_url'] ?? ''); ?>" />
    </div>

    <div class="button-group">
      <button type="submit" class="btn-submit">Añadir Evento</button>
      <p><a href="eventoadmin.php" class="btn-return">Volver a la lista de eventos</a></p>
    </div>
  </form>
</main>
<footer>
  <p style="text-align:center; margin-top: 30px;">&copy; <?php echo date("Y"); ?> Panel de Administración</p>
</footer>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>