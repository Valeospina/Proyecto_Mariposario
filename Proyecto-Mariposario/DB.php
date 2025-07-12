<?php
$servername = "localhost";
$username = "root";
<<<<<<< HEAD
$password = "1234"; 
=======
$password = "12345"; 
>>>>>>> parent of 2874284 (Sistema de pagos)
$dbname = "mariposarioDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
} 


$conn->set_charset("utf8mb4");
/*else {
    echo "Conexión exitosa a la base de datos $dbname";
}*/
?>

