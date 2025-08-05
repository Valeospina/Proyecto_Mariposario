<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'DB.php';

// Validar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Datos del usuario
$stmt = $conn->prepare("SELECT Nombre, Apellido, Correo, Telefono, Direccion, Foto_Perfil FROM Usuario WHERE ID_Usuario = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    $fotoPerfil = $user['Foto_Perfil'];
    if (!empty($_FILES['foto']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
            $fotoPerfil = $targetFile;
        }
    }

    $update = $conn->prepare("UPDATE Usuario SET Nombre=?, Apellido=?, Telefono=?, Direccion=?, Foto_Perfil=? WHERE ID_Usuario=?");
    $update->bind_param("sssssi", $nombre, $apellido, $telefono, $direccion, $fotoPerfil, $userId);
    if ($update->execute()) {
        $_SESSION['user_name'] = $nombre;
        $user['Foto_Perfil'] = $fotoPerfil;
        $mensaje = "Perfil actualizado correctamente.";
    } else {
        $mensaje = "Error al actualizar perfil.";
    }
}

// Eventos
$eventos = [];
$eventQuery = $conn->query("SELECT ID_Evento, Nombre, Fecha, Imagen_URL, Descripcion FROM Evento WHERE Fecha >= CURDATE() ORDER BY Fecha ASC LIMIT 3");
while ($row = $eventQuery->fetch_assoc()) {
    $eventos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Perfil</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 0;
}
.container {
    display: flex;
    max-width: 1200px;
    margin: 30px auto;
    gap: 20px;
    padding: 0 15px;
}
.sidebar {
    width: 220px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    padding: 20px;
}
.sidebar h3 {
    font-size: 18px;
    margin-bottom: 15px;
}
.sidebar ul {
    list-style: none;
    padding: 0;
}
.sidebar ul li {
    margin-bottom: 10px;
}
.sidebar ul li a {
    text-decoration: none;
    color: #333;
    display: block;
    padding: 10px;
    border-radius: 8px;
    transition: background 0.3s;
}
.sidebar ul li a:hover {
    background: #8BC34A;
    color: #fff;
}
.profile-section {
    flex: 1;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    padding: 20px;
}
.profile-section h2 {
    text-align: center;
    margin-bottom: 20px;
}
.profile-photo {
    text-align: center;
    margin-bottom: 15px;
}
.profile-photo img {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #8BC34A;
    transition: transform 0.3s;
}
.profile-photo img:hover {
    transform: scale(1.05);
}
.change-photo-btn {
    margin-top: 10px;
    display: inline-block;
    background: #8BC34A;
    color: #fff;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}
.change-photo-btn:hover {
    background: #6fa12d;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    font-weight: 500;
    display: block;
    margin-bottom: 5px;
}
.form-group input, .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.btn {
    background: #8BC34A;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    display: block;
    margin: 0 auto;
}
.btn:hover {
    background: #6fa12d;
}
.event-section {
    width: 320px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    padding: 20px;
}
.event-section h3 {
    margin-bottom: 15px;
    font-size: 18px;
}
.event-card {
    background: #f9f9f9;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: transform 0.3s;
}
.event-card:hover {
    transform: scale(1.02);
}
.event-card img {
    width: 100%;
    border-radius: 6px;
    margin-bottom: 8px;
}
.event-card h4 {
    margin: 0 0 5px;
    font-size: 16px;
}
.event-card p {
    margin: 0 0 10px;
    font-size: 14px;
    color: #555;
}
.event-card a {
    background: #8BC34A;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
}
#sabiasQue {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    background: #f1f8e9;
    padding: 15px;
    margin: 20px auto;
    border-radius: 8px;
    color: #4e342e;
    width: 90%;
}
.back-btn {
    background: #ccc;
    color: #333;
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 15px;
}
</style>
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Mi Panel</h3>
        <ul>
            <li><a href="usuario.php">Perfil</a></li>
            <li><a href="MisPedidos.php">Mis Pedidos</a></li>
            <li><a href="eventosReservados.php">Mis Eventos</a></li>
            <li><a href="#" onclick="history.back()">← Regresar</a></li>
        </ul>
    </div>

    <!-- Formulario Perfil -->
    <div class="profile-section">
        <a href="#" class="back-btn" onclick="history.back()">← Volver</a>
        <h2>Editar Perfil</h2>
        <?php if (!empty($mensaje)): ?>
            <p style="text-align:center;color:green;"><?php echo $mensaje; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="profile-photo">
                <img src="<?php echo htmlspecialchars($user['Foto_Perfil']); ?>" id="previewImg" alt="Foto de perfil">
                <label for="foto" class="change-photo-btn">Cambiar Foto</label>
                <input type="file" name="foto" id="foto" accept="image/*" style="display:none;" onchange="previewImage(event)">
            </div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($user['Nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" value="<?php echo htmlspecialchars($user['Apellido']); ?>">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" value="<?php echo htmlspecialchars($user['Correo']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($user['Telefono']); ?>">
            </div>
            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion"><?php echo htmlspecialchars($user['Direccion']); ?></textarea>
            </div>
            <button type="submit" class="btn">Guardar Cambios</button>
        </form>
    </div>

    <!-- Eventos Próximos -->
    <div class="event-section">
        <h3>Próximos Eventos</h3>
        <?php if (count($eventos) > 0): ?>
            <?php foreach ($eventos as $evento): ?>
                <div class="event-card">
                    <img src="<?php echo htmlspecialchars($evento['Imagen_URL']); ?>" alt="Evento">
                    <h4><?php echo htmlspecialchars($evento['Nombre']); ?></h4>
                    <p><?php echo date("d/m/Y", strtotime($evento['Fecha'])); ?></p>
                    <p><?php echo substr(htmlspecialchars($evento['Descripcion']), 0, 60); ?>...</p>
                    <a href="evento_info.php?id=<?php echo $evento['ID_Evento']; ?>">Inscribirme</a>
                    
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay eventos próximos.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Sabías que -->
<div id="sabiasQue">¿Sabías que...? Cargando frase...</div>

<script>
// ✅ Frases reales sobre mariposas, orquídeas y Costa Rica
const frases = [
    "¿Sabías que Costa Rica alberga más de 1,200 especies de mariposas?",
    "¿Sabías que las orquídeas pueden vivir más de 100 años?",
    "¿Sabías que el 6% de la biodiversidad mundial está en Costa Rica?",
    "¿Sabías que las mariposas usan sus patas para saborear?",
    "¿Sabías que las orquídeas son las plantas con más especies en el mundo?",
    "¿Sabías que las mariposas no pueden volar si hace frío?",
    "¿Sabías que Costa Rica protege más del 25% de su territorio en parques?",
    "¿Sabías que el ala de una mariposa está cubierta por escamas microscópicas?",
    "¿Sabías que algunas mariposas pueden migrar más de 3,000 km?",
    "¿Sabías que el azul brillante de las Morpho no es pigmento, es luz?",
    "¿Sabías que Costa Rica tiene más de 1400 especies de orquídeas?",
    "¿Sabías que las mariposas son polinizadores esenciales para la vida?",
    "¿Sabías que Costa Rica es uno de los países más biodiversos del planeta?",
    "¿Sabías que las orquídeas crecen en selvas, montañas y hasta desiertos?",
    "¿Sabías que las mariposas tienen cuatro alas, no dos?",
    "¿Sabías que la mariposa Monarca viaja hasta 4,000 km en migración?",
    "¿Sabías que Costa Rica genera casi toda su energía con fuentes renovables?",
    "¿Sabías que las mariposas perciben colores que los humanos no ven?",
    "¿Sabías que algunas orquídeas florecen solo una vez al año?",
    "¿Sabías que Costa Rica tiene más de 500,000 especies registradas?"
];
function cambiarFrase() {
    const randomIndex = Math.floor(Math.random() * frases.length);
    document.getElementById('sabiasQue').textContent = frases[randomIndex];
}
cambiarFrase();
setInterval(cambiarFrase, 60000);

// Preview foto
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('previewImg').src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>

</body>
</html>
