<?php
include 'DB.php';

if ($_POST) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>
                alert('Por favor, completa todos los campos correctamente.');
                window.location.href = 'login.html';
              </script>";
        exit;
    }

    // Verificar si el email ya existe
    $check_query = "SELECT * FROM Usuario WHERE Correo = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Este correo electrónico ya está registrado. Por favor, utiliza otro.');
                window.location.href = 'login.html';
              </script>";
        exit;
    }

    // Encriptar la contraseña
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $insert_query = "INSERT INTO Usuario (ID_Rol, Nombre, Correo, Contrasena) VALUES (2, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("sss", $nombre, $email, $hashed_password);

    if ($insert_stmt->execute()) {
        $new_user_id = $conn->insert_id;

        // Crear carrito
        $carrito_query = "INSERT INTO Carrito (ID_Usuario, Estado) VALUES (?, 'activo')";
        $carrito_stmt = $conn->prepare($carrito_query);
        $carrito_stmt->bind_param("i", $new_user_id);
        $carrito_stmt->execute();

        // Inicializar puntos
        $puntos_query = "INSERT INTO Puntos_Usuario (ID_Usuario, Puntos_Actuales, Fecha_Expiracion, Notificado) VALUES (?, 0, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), FALSE)";
        $puntos_stmt = $conn->prepare($puntos_query);
        $puntos_stmt->bind_param("i", $new_user_id);
        $puntos_stmt->execute();

        echo "<script>
                alert('Registro exitoso. Ahora puedes iniciar sesión.');
                window.location.href = './login.html';
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Error al registrar usuario. Por favor, intenta nuevamente.');
                window.location.href = './login.html';
              </script>";
        exit;
    }
}
?>
