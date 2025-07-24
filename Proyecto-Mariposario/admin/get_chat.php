<?php
include '../DB.php';
$id=intval($_GET['id']);
$stmt=$conn->prepare("SELECT Mensajes FROM Consulta WHERE ID_Consulta=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res=$stmt->get_result()->fetch_assoc();
$mensajes=$res['Mensajes']?json_decode($res['Mensajes'],true):[];
echo json_encode($mensajes);
