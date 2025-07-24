<?php
include '../DB.php';
$id=intval($_POST['id']);
$role=$_POST['role'];
$text=$_POST['text'];

$stmt=$conn->prepare("SELECT Mensajes FROM Consulta WHERE ID_Consulta=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res=$stmt->get_result()->fetch_assoc();
$mensajes=$res['Mensajes']?json_decode($res['Mensajes'],true):[];
$mensajes[]=["role"=>$role,"text"=>$text,"time"=>date('H:i')];

$newJson=json_encode($mensajes);
$upd=$conn->prepare("UPDATE Consulta SET Mensajes=? WHERE ID_Consulta=?");
$upd->bind_param("si",$newJson,$id);
$upd->execute();
