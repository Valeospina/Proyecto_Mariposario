<?php
session_start();
session_unset();   
session_destroy(); 
header('Location: logind.php'); // Redirige al logind.php después de cerrar sesión
exit;
?>