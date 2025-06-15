<?php
session_start();
session_unset();   
session_destroy(); 
header('Location: login.html'); // Redirige al login.html después de cerrar sesión
exit;
?>