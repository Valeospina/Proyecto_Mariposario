<?php
// notifications_helper.php – versión robusta para ambos esquemas

/**
 * Verifica si una columna existe en la tabla.
 */
function tableHasColumn(mysqli $conn, string $table, string $column): bool {
    // Detecta el schema actual
    $res = $conn->query("SELECT DATABASE() AS db");
    $row = $res ? $res->fetch_assoc() : null;
    $db  = $row ? $row['db'] : null;

    if (!$db) return false;

    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    $stmt->bind_param('sss', $db, $table, $column);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Inserta una notificación mínima:
 * - Si existe `Categoria`, inserta en (ID_Usuario, Categoria, Mensaje).
 * - Si NO existe `Categoria` pero sí `Tipo_Notificacion`, inserta en (ID_Usuario, Tipo_Notificacion, Mensaje).
 */
function addNotification(mysqli $conn, int $userId, string $tipoOCategoria, string $mensaje): bool {
    $table = 'Notificacion';

    $hasCategoria       = tableHasColumn($conn, $table, 'Categoria');
    $hasTipoNoti        = tableHasColumn($conn, $table, 'Tipo_Notificacion');

    if ($hasCategoria) {
        // Esquema nuevo (con Categoria; Tipo_Notificacion podría ser GENERADA)
        $sql = "INSERT INTO Notificacion (ID_Usuario, Categoria, Mensaje) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('iss', $userId, $tipoOCategoria, $mensaje);
    } elseif ($hasTipoNoti) {
        // Esquema antiguo (solo Tipo_Notificacion normal)
        $sql = "INSERT INTO Notificacion (ID_Usuario, Tipo_Notificacion, Mensaje) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('iss', $userId, $tipoOCategoria, $mensaje);
    } else {
        // Ninguna columna encontrada: evitar fallo silencioso
        return false;
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Plantillas de mensajes.
 */
function buildMessage(string $key, array $vars = []): string {
    $templates = [
        'Bienvenida'         => "¡Bienvenido a Eco Mariposas! 🦋 Gracias por unirte a nuestra comunidad",
        'PedidoRealizado'    => "Tu pedido #{{orden}} ha sido recibido y está en proceso",
        'PedidoPreparacion'  => "Tu pedido está siendo preparado con mucho cariño 🌱",
        'PedidoEnviado'      => "¡Tu pedido ya está en camino! Código de seguimiento: {{tracking}}",
        'PedidoEntregado'    => "Tu pedido ha sido entregado. ¿Qué te pareció tu compra?",
        'EventoProximo'      => "Tu evento '{{titulo}}' es {{cuando}}",
        'Recordatorio'       => "Te recordamos tu evento en 2 horas",
        'EventoCompletado'   => "Gracias por participar en nuestro evento. ¡Califícanos!",
        'ProductosNuevos'    => "Nuevos productos agregados 🛒",
        'Mantenimiento'      => "Mantenimiento programado el domingo de 2:00 AM a 4:00 AM",
        'Actualizacion'      => "Nuevas funciones disponibles en tu perfil",
    ];
    $msg = $templates[$key] ?? '';
    foreach ($vars as $k => $v) {
        $msg = str_replace('{{'.$k.'}}', $v, $msg);
    }
    return $msg;
}
