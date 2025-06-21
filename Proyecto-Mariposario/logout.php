<?php
session_start();
session_unset();   
session_destroy(); 
header('Location: looginD.php'); // Redirige al looginD.php después de cerrar sesión
exit;
?>