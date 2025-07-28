<?php
session_start();
include 'DB.php';

// Mostrar errores en desarrollo (puedes quitar esto en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Validar campos vacíos
    if (empty($email) || empty($password)) {
        echo "<script>
                alert('Por favor, ingresa correo y contraseña.');
                window.location.href = './logind.php';
              </script>";
        exit;
    }

    // Consultar usuario
    $login_query = "SELECT u.*, r.Nombre AS NombreRol 
                    FROM Usuario u
                    LEFT JOIN Rol r ON u.ID_Rol = r.ID_Rol
                    WHERE u.Correo = ?";
    $login_stmt = $conn->prepare($login_query);

    if ($login_stmt) {
        $login_stmt->bind_param("s", $email);
        $login_stmt->execute();
        $result = $login_stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<script>
                    alert('El correo ingresado no está registrado. Por favor, verifica tu correo.');
                    window.location.href = './logind.php';
                  </script>";
            exit;
        }

        $user = $result->fetch_assoc();

        // Validar contraseña
        if (password_verify($password, $user['Contrasena'])) {
            // Guardar datos en sesión
            $_SESSION['user_id'] = $user['ID_Usuario'];
            $_SESSION['user_name'] = $user['Nombre'];
            $_SESSION['user_email'] = $user['Correo'];
            $_SESSION['user_role'] = $user['ID_Rol'];
            $_SESSION['role_name'] = $user['NombreRol'];

            // Comprobar si el usuario tiene registro en Puntos_Usuario
            $puntos_stmt = $conn->prepare("SELECT COUNT(*) FROM Puntos_Usuario WHERE ID_Usuario = ?");
            $puntos_stmt->bind_param("i", $_SESSION['user_id']);
            $puntos_stmt->execute();
            $puntos_stmt->bind_result($puntos_exist);
            $puntos_stmt->fetch();
            $puntos_stmt->close();

            if ($puntos_exist == 0) {
                $crear_puntos_stmt = $conn->prepare("INSERT INTO Puntos_Usuario (ID_Usuario, Puntos_Actuales) VALUES (?, 0)");
                $crear_puntos_stmt->bind_param("i", $_SESSION['user_id']);
                $crear_puntos_stmt->execute();
                $crear_puntos_stmt->close();
            }

            // Registrar actividad si es Admin
            if ($user['ID_Rol'] == 1) {
                $emp_stmt = $conn->prepare("SELECT ID_Empleado FROM Empleado WHERE ID_Usuario = ?");
                $emp_stmt->bind_param("i", $user['ID_Usuario']);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();

                if ($emp_result->num_rows > 0) {
                    $empleado = $emp_result->fetch_assoc();
                    $act_stmt = $conn->prepare("INSERT INTO Registro_Actividad (ID_Empleado, Fecha_Hora, Accion, Detalle) VALUES (?, NOW(), 'Login', 'Usuario inició sesión exitosamente')");
                    $act_stmt->bind_param("i", $empleado['ID_Empleado']);
                    $act_stmt->execute();
                    $act_stmt->close();
                }
                $emp_stmt->close();
            }

            // Redirección según el rol
            if ($_SESSION['user_role'] == 1) { 
                echo "<script>
                        alert('¡Bienvenido Administrador " . htmlspecialchars($user['Nombre']) . "!');
                        window.location.href = './admin/dashboard.php'; 
                      </script>";
            } else { 
                echo "<script>
                        alert('¡Bienvenido " . htmlspecialchars($user['Nombre']) . "!');
                        window.location.href = './index.php'; 
                      </script>";
            }
            exit;
        } else {
            echo "<script>
                    alert('Contraseña incorrecta. Por favor, intenta nuevamente.');
                    window.location.href = './logind.php';
                  </script>";
            exit;
        }

        $login_stmt->close();
    } else {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $conn->close();
}
?>
