<?php
include '../DB.php';
$id=intval($_POST['id']);
$estado=$_POST['estado'];
$conn->query("UPDATE Consulta SET Estado='$estado' WHERE ID_Consulta=$id");
echo "ok";
