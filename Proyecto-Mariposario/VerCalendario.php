

<?php
require_once 'Calendario.php';
include 'DB.php'; // tu conexión mysqli

$cal = new Calendario($conn);
$cal->mostrar();
?>


