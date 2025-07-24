<?php
include '../DB.php';
$id = intval($_POST['id']);
$conn->query("UPDATE Consulta SET Estado='Cerrado' WHERE ID_Consulta=$id");
echo "ok";
