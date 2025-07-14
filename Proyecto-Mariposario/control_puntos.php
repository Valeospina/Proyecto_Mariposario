<?php
// control_puntos.php
// Script para expirar puntos no canjeados después de 2 meses

include 'DB.php';

// Fecha de expiración: puntos ganados hace más de 2 meses
$expiracion_limite = date('Y-m-d', strtotime('-2 months'));

// 1. Obtener puntos GANADOS hace más de 2 meses y que no hayan sido CANJEADOS ni EXPIRADOS
$sql = "
    SELECT hp.ID_Punto, hp.ID_Usuario, hp.Monto
    FROM Historial_Puntos hp
    WHERE hp.Accion = 'Ganado'
      AND hp.Fecha <= ?
      AND NOT EXISTS (
          SELECT 1
          FROM Historial_Puntos h2
          WHERE h2.ID_Referencia = hp.ID_Punto
            AND h2.Tipo_Referencia = 'Punto'
            AND (h2.Accion = 'Canjeado' OR h2.Accion = 'Expirado')
      )
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $expiracion_limite);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $id_punto   = $row['ID_Punto'];
    $id_usuario = $row['ID_Usuario'];
    $monto      = $row['Monto'];

    // 2. Restar puntos del saldo actual
    $update = $conn->prepare("UPDATE Puntos_Usuario SET Puntos_Actuales = Puntos_Actuales - ? WHERE ID_Usuario = ?");
    $update->bind_param("ii", $monto, $id_usuario);
    $update->execute();
    $update->close();

    // 3. Registrar la expiración en el historial
    $insert = $conn->prepare("
        INSERT INTO Historial_Puntos 
            (ID_Usuario, Fecha, Accion, Monto, Descripcion, ID_Referencia, Tipo_Referencia)
        VALUES 
            (?, NOW(), 'Expirado', ?, 'Puntos expirados automáticamente después de 2 meses sin uso', ?, 'Punto')
    ");
    $insert->bind_param("iii", $id_usuario, $monto, $id_punto);
    $insert->execute();
    $insert->close();
}

$stmt->close();
$conn->close();

echo "Proceso de expiración de puntos completado correctamente.\n";