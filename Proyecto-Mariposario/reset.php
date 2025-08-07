<?php
require 'DB.php';

if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];
$stmt = $conn->prepare("SELECT ID_Usuario FROM Recuperacion_Contrasena WHERE Token = ? AND Expiracion > NOW() AND Usado = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Token inválido o expirado.");
}

$user = $result->fetch_assoc();
$userId = $user['ID_Usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE Usuario SET Contrasena = ? WHERE ID_Usuario = ?");
    $update->bind_param("si", $pass, $userId);
    $update->execute();

    $markUsed = $conn->prepare("UPDATE Recuperacion_Contrasena SET Usado = 1 WHERE Token = ?");
    $markUsed->bind_param("s", $token);
    $markUsed->execute();

    echo "<script>alert('¡Contraseña actualizada correctamente!'); window.location.href='logind.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer Contraseña</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #ffe6f0, #e0f7fa);
      height: 100vh;
      overflow: hidden;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      position: relative;
    }

    .form-wrapper {
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
      position: relative;
    }

    .form-card {
      background: #ffffffdd;
      border-radius: 15px;
      padding: 2rem;
      max-width: 450px;
      width: 100%;
      box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }

    .form-card h2 {
      color: #6f42c1;
      margin-bottom: 1rem;
    }

    .form-card .btn-success {
      background-color: #28a745;
      border: none;
    }

    .form-card .btn-success:hover {
      background-color: #218838;
    }

    .butterfly {
      position: absolute;
      top: 10%;
      left: -100px;
      width: 100px;
      animation: fly 20s linear infinite;
      z-index: 1;
    }

    @keyframes fly {
      0% { transform: translateX(0) rotate(0deg); }
      50% { transform: translateX(100vw) translateY(-100px) rotate(180deg); }
      100% { transform: translateX(0) rotate(360deg); }
    }
  </style>
</head>
<body>




<div class="form-wrapper">
  <div class="form-card">
    <h2 class="text-center">Restablecer Contraseña</h2>
    <form method="POST" id="resetForm">
      <div class="mb-3">
        <label for="password" class="form-label">Nueva contraseña</label>
        <input type="password" class="form-control" name="password" id="password" required minlength="6" placeholder="********">
      </div>
      <div class="d-grid">
        <button type="submit" class="btn btn-success">Actualizar contraseña</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
