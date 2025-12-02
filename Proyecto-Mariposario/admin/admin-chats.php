<?php
session_start();
include '../DB.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

// Obtener todas las consultas con información del usuario
$sql = "SELECT c.ID_Consulta, u.Nombre AS Nombre_Usuario, u.Foto_Perfil, 
               c.Estado, c.Tema, c.Fecha_Creacion, c.Fecha_Actualizacion,
               (SELECT COUNT(*) FROM chat_mensajes WHERE id_consulta = c.ID_Consulta) as total_mensajes
        FROM Consulta c
        LEFT JOIN Usuario u ON c.ID_Usuario = u.ID_Usuario
        ORDER BY c.Fecha_Actualizacion DESC, c.Fecha_Creacion DESC";

$result = $conn->query($sql);
$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = $row;
}

$page_title = 'Soporte (Chats)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/admin.css">
<style>
    .chat-container { display:flex; background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); margin:20px; overflow:hidden; height: calc(100vh - 180px); }
    .chat-list { width:30%; border-right:1px solid #ddd; background:#f8f9fa; display:flex; flex-direction:column; }
    .filter-bar { display:flex; gap:5px; padding:10px; background:#fff; border-bottom:1px solid #ddd; }
    .filter-btn { flex:1; padding:8px; background:#e9ecef; border:none; border-radius:5px; cursor:pointer; font-weight:500; transition:all 0.2s; }
    .filter-btn.active { background:#28a745; color:#fff; }
    .chat-items { overflow-y:auto; flex:1; }
    .chat-item { display:flex; gap:10px; padding:12px; border-bottom:1px solid #eee; cursor:pointer; align-items:center; transition:background 0.2s; position: relative; }
    .chat-item:hover { background:#e6f7e6; }
    .chat-item.active { background:#d4f1d4; border-left: 3px solid #28a745; }
    .chat-item img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
    .chat-item h4 { margin:0; font-size:1rem; display:flex; align-items:center; gap:5px; }
    .chat-item p { margin:5px 0 0; font-size:0.9rem; color:#666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-item .time { font-size: 0.75rem; color: #999; position: absolute; top: 12px; right: 12px; }
    .status-badge { padding:2px 6px; font-size:0.75rem; border-radius:5px; }
    .status-pendiente { background:#ffc107; color:#fff; }
    .status-en.proceso, .status-en_proceso { background:#17a2b8; color:#fff; }
    .status-respondido { background:#17a2b8; color:#fff; }
    .status-resuelto { background:#28a745; color:#fff; }
    .status-cerrado { background:#6c757d; color:#fff; }
    .unread-badge { background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; position: absolute; top: 8px; right: 8px; }
    .chat-window { flex:1; display:flex; flex-direction:column; background:#f1f1f1; }
    .chat-header { background:#28a745; color:#fff; padding:15px; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
    .chat-header-actions { display: flex; gap: 10px; }
    .chat-header-actions button { background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 12px; border-radius: 5px; cursor: pointer; transition: all 0.2s; }
    .chat-header-actions button:hover { background: rgba(255,255,255,0.3); }
    .chat-body { flex:1; padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; }
    .message { max-width:75%; padding:12px; border-radius:10px; font-size:0.95rem; animation: slideIn 0.3s ease; }
    @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .admin-msg { background:#dcf8c6; align-self:flex-end; border-bottom-right-radius: 2px; }
    .client-msg { background:#fff; align-self:flex-start; border-bottom-left-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .system-msg { background:#e3f2fd; color:#1976d2; margin: 0 auto; text-align: center; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; }
    .message-time { font-size: 0.75rem; color: #999; margin-top: 4px; }
    .chat-footer { display:flex; gap:10px; background:#fff; padding:12px 16px; border-top:1px solid #ddd; align-items: center; }
    .chat-footer-actions { display: flex; gap: 8px; }
    .chat-footer-actions button { background: white; border: 2px solid #e0e0e0; width: 38px; height: 38px; border-radius: 8px; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; color: #666; }
    .chat-footer-actions button:hover { background: #28a745; border-color: #28a745; color: white; }
    .chat-footer input { flex:1; padding:10px 14px; border-radius:25px; border:1px solid #ccc; font-size:0.95rem; }
    .chat-footer .send-btn { background:#28a745; color:#fff; border:none; border-radius:50%; width:40px; height:40px; font-size:18px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition: all 0.3s; }
    .chat-footer .send-btn:hover { background:#218838; transform: scale(1.05); }
    .chat-footer .send-btn:disabled { background:#ccc; cursor:not-allowed; transform: scale(1); }
    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #999; }
    .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
    .typing-indicator { padding: 10px; background: #fff; border-radius: 10px; align-self: flex-start; display: none; }
    .typing-indicator span { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #999; margin: 0 2px; animation: typing 1.4s infinite; }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-10px); } }
</style>
</head>
<body>
<div class="admin-dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>Admin Panel</h3></div>
        <nav class="sidebar-nav">
            <div class="menu-scroll">
                <ul>
                    <li><a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="gestion_empleados.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['gestion_empleados.php', 'add_empleado.php', 'edit_empleado.php'])) ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestionar Empleados</a></li>
                    <li><a href="users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestionar Usuarios</a></li>
                    <li><a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Gestionar Productos</a></li>
                    <li><a href="inventarioAdmin.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['inventarioAdmin.php', 'add_inventario.php', 'edit_inventario.php'])) ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Gestionar Inventario</a></li>
                    <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
                    <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Gestionar Reservas</a></li>
                    <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Gestionar Asistencia</a></li>
                    <li><a href="pedidos.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['pedidos.php', 'edit_pedido.php'])) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
                    <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
                    <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
                    <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reportAsis.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
                    <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Soporte</a></li>  
                    <li><a href="gestionM.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestionM.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Gestion Mariposas</a></li>
                </ul>
            </div>
            <div class="sidebar-footer"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
        </nav>
    </aside>

    <div class="main-panel">
        <header class="main-panel-header">
            <h2><?php echo $page_title; ?></h2>
        </header>

        <main class="content-area">
            <div class="chat-container">
                <div class="chat-list">
                    <div class="filter-bar">
                        <button class="filter-btn active" onclick="filtrar('Todos',event)">Todos</button>
                        <button class="filter-btn" onclick="filtrar('Pendiente',event)">Pendientes</button>
                        <button class="filter-btn" onclick="filtrar('En Proceso',event)">En Proceso</button>
                        <button class="filter-btn" onclick="filtrar('Cerrado',event)">Cerrados</button>
                    </div>
                    <div class="chat-items" id="chatItems">
                        <?php foreach ($chats as $chat): 
                            $fotoPerfil = !empty($chat['Foto_Perfil']) ? '../' . $chat['Foto_Perfil'] : '../img/default-user.png';
                            $estadoClass = strtolower(str_replace(' ', '_', $chat['Estado']));
                        ?>
                            <div class="chat-item" id="chat-<?= $chat['ID_Consulta']; ?>" 
                                 data-estado="<?= $chat['Estado']; ?>" 
                                 onclick="abrirChat(<?= $chat['ID_Consulta']; ?>,'<?= htmlspecialchars($chat['Nombre_Usuario']); ?>','<?= $chat['Estado']; ?>')">
                                <img src="<?= $fotoPerfil; ?>" alt="<?= htmlspecialchars($chat['Nombre_Usuario']); ?>">
                                <div style="flex: 1;">
                                    <h4>
                                        <?= htmlspecialchars($chat['Nombre_Usuario']); ?>
                                        <span class="status-badge status-<?= $estadoClass; ?>" id="estado-<?= $chat['ID_Consulta']; ?>">
                                            <?= $chat['Estado']; ?>
                                        </span>
                                    </h4>
                                    <p><?= htmlspecialchars($chat['Tema'] ?? 'Consulta'); ?></p>
                                </div>
                                <span class="time"><?= date('H:i', strtotime($chat['Fecha_Actualizacion'] ?? $chat['Fecha_Creacion'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="chat-window">
                    <div class="chat-header">
                        <span id="chatUser">Selecciona un chat</span>
                        <div class="chat-header-actions">
                            <button onclick="cambiarEstado('En Proceso')" id="btnEnProceso" style="display:none;" title="Marcar como En Proceso">
                                <i class="fas fa-clock"></i> En Proceso
                            </button>
                            <button onclick="cambiarEstado('Resuelto')" id="btnResuelto" style="display:none;" title="Marcar como Resuelto">
                                <i class="fas fa-check"></i> Resolver
                            </button>
                            <button onclick="cambiarEstado('Cerrado')" id="btnCerrar" style="display:none;" title="Cerrar Chat">
                                <i class="fas fa-times-circle"></i> Cerrar
                            </button>
                        </div>
                    </div>
                    
                    <div class="chat-body" id="chatBody">
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>Selecciona un chat para comenzar</p>
                        </div>
                    </div>
                    
                    <div class="chat-footer">
                        <input type="file" id="fileInput" style="display:none;" accept="image/*,.pdf,.doc,.docx">
                        
                        <div class="chat-footer-actions">
                            <button id="emojiBtn" title="Agregar emoji" disabled>
                                <i class="far fa-smile"></i>
                            </button>
                            <button id="fileBtn" title="Adjuntar archivo" disabled>
                                <i class="fas fa-paperclip"></i>
                            </button>
                        </div>
                        
                        <input type="text" id="adminMsg" placeholder="Escribe un mensaje..." disabled>
                        <button class="send-btn" id="sendBtn" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let currentChatId = null;
let chatEstado = '';
let ultimoMensajeId = 0;
let pollingInterval = null;

// Emojis disponibles
const emojis = ['😊', '😂', '❤️', '👍', '🎉', '✨', '🌸', '🦋', '🌿', '🌺', '💚', '🌼', '😍', '🤗', '👌', '🙏', '💐', '🌻'];

// Filtrar chats por estado
function filtrar(estado, e) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    e.target.classList.add('active');
    document.querySelectorAll('.chat-item').forEach(i => {
        i.style.display = (estado === 'Todos' || i.dataset.estado === estado) ? 'flex' : 'none';
    });
}

// Abrir chat
function abrirChat(id, userName, estado) {
    currentChatId = id;
    chatEstado = estado;
    
    // Marcar como activo en la lista
    document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
    document.getElementById('chat-' + id).classList.add('active');
    
    // Actualizar header
    document.getElementById('chatUser').textContent = userName;
    
    // Mostrar/ocultar botones según el estado
    mostrarBotonesEstado(estado);
    
    // Cargar mensajes
    fetch('../get_mensajes.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            const body = document.getElementById('chatBody');
            body.innerHTML = '';
            
            if (data.length === 0) {
                body.innerHTML = '<div class="system-msg">Sin mensajes en este chat</div>';
            } else {
                data.forEach(m => {
                    agregarMensaje(m.role === 'Admin' ? 'admin' : 'cliente', m.text, false);
                    if (m.id) ultimoMensajeId = m.id;
                });
                body.scrollTop = body.scrollHeight;
            }
        })
        .catch(err => {
            console.error('Error cargando mensajes:', err);
            document.getElementById('chatBody').innerHTML = '<div class="system-msg">Error al cargar mensajes</div>';
        });
    
    // Habilitar/deshabilitar inputs
    const disabled = (estado === 'Cerrado');
    document.getElementById('adminMsg').disabled = disabled;
    document.getElementById('sendBtn').disabled = disabled;
    document.getElementById('emojiBtn').disabled = disabled;
    document.getElementById('fileBtn').disabled = disabled;
    
    // Iniciar polling
    iniciarPolling();
}

// Mostrar botones según estado
function mostrarBotonesEstado(estado) {
    document.getElementById('btnEnProceso').style.display = (estado === 'Pendiente') ? 'block' : 'none';
    document.getElementById('btnResuelto').style.display = (estado === 'En Proceso' || estado === 'Pendiente') ? 'block' : 'none';
    document.getElementById('btnCerrar').style.display = (estado !== 'Cerrado') ? 'block' : 'none';
}

// Polling de nuevos mensajes
function iniciarPolling() {
    if (pollingInterval) clearInterval(pollingInterval);
    
    pollingInterval = setInterval(() => {
        if (currentChatId) {
            fetch(`../get_nuevos_mensajes.php?id_consulta=${currentChatId}&last_id=${ultimoMensajeId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.nuevos) {
                        data.mensajes.forEach(msg => {
                            agregarMensaje(msg.role === 'Admin' ? 'admin' : 'cliente', msg.text, true);
                            ultimoMensajeId = msg.id;
                        });
                        reproducirSonido();
                    }
                })
                .catch(err => console.error('Error en polling:', err));
        }
    }, 3000);
}

// Agregar mensaje a la interfaz
function agregarMensaje(tipo, texto, scroll = true) {
    const div = document.createElement('div');
    div.classList.add('message', tipo === 'admin' ? 'admin-msg' : 'client-msg');
    div.textContent = texto;
    document.getElementById('chatBody').appendChild(div);
    
    if (scroll) {
        document.getElementById('chatBody').scrollTop = document.getElementById('chatBody').scrollHeight;
    }
}

// Enviar mensaje
document.getElementById('sendBtn').addEventListener('click', enviarMensaje);
document.getElementById('adminMsg').addEventListener('keypress', e => {
    if (e.key === 'Enter') enviarMensaje();
});

function enviarMensaje() {
    const msg = document.getElementById('adminMsg').value.trim();
    if (!msg || !currentChatId) return;
    
    // Agregar mensaje inmediatamente
    agregarMensaje('admin', `Admin: ${msg}`, true);
    document.getElementById('adminMsg').value = '';
    
    // Enviar al servidor
    fetch('../enviar_mensaje.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${currentChatId}&role=Admin&text=${encodeURIComponent('Admin: ' + msg)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cambiar estado si es necesario
            if (chatEstado === 'Pendiente') {
                cambiarEstado('En Proceso');
            }
        }
    })
    .catch(err => console.error('Error al enviar:', err));
}

// Cambiar estado
function cambiarEstado(nuevoEstado) {
    if (!currentChatId) return;
    
    fetch('cambiar_estado.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${currentChatId}&estado=${nuevoEstado}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Actualizar estado visual
            const estadoClass = nuevoEstado.toLowerCase().replace(' ', '_');
            document.getElementById('estado-' + currentChatId).textContent = nuevoEstado;
            document.getElementById('estado-' + currentChatId).className = 'status-badge status-' + estadoClass;
            document.getElementById('chat-' + currentChatId).dataset.estado = nuevoEstado;
            
            chatEstado = nuevoEstado;
            mostrarBotonesEstado(nuevoEstado);
            
            // Si se cerró, deshabilitar inputs
            if (nuevoEstado === 'Cerrado') {
                document.getElementById('adminMsg').disabled = true;
                document.getElementById('sendBtn').disabled = true;
                document.getElementById('emojiBtn').disabled = true;
                document.getElementById('fileBtn').disabled = true;
            }
            
            alert('Estado actualizado a: ' + nuevoEstado);
        }
    })
    .catch(err => console.error('Error al cambiar estado:', err));
}

// Botón de emojis
document.getElementById('emojiBtn').addEventListener('click', (e) => {
    e.stopPropagation();
    
    // Crear selector de emojis
    const existingPicker = document.getElementById('emojiPicker');
    if (existingPicker) {
        existingPicker.remove();
        return;
    }
    
    const picker = document.createElement('div');
    picker.id = 'emojiPicker';
    picker.style.cssText = `
        position: absolute;
        bottom: 70px;
        right: 20px;
        background: white;
        border: 2px solid #28a745;
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 5px;
        z-index: 1000;
    `;
    
    emojis.forEach(emoji => {
        const btn = document.createElement('button');
        btn.textContent = emoji;
        btn.style.cssText = `
            border: none;
            background: transparent;
            font-size: 22px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.2s;
        `;
        btn.onmouseover = () => btn.style.background = '#f0f0f0';
        btn.onmouseout = () => btn.style.background = 'transparent';
        btn.onclick = () => {
            document.getElementById('adminMsg').value += emoji;
            document.getElementById('adminMsg').focus();
            picker.remove();
        };
        picker.appendChild(btn);
    });
    
    document.querySelector('.chat-footer').appendChild(picker);
    
    // Cerrar al hacer clic fuera
    setTimeout(() => {
        document.addEventListener('click', function closePicker(e) {
            if (!picker.contains(e.target) && e.target.id !== 'emojiBtn') {
                picker.remove();
                document.removeEventListener('click', closePicker);
            }
        });
    }, 100);
});

// Botón de archivos
document.getElementById('fileBtn').addEventListener('click', () => {
    document.getElementById('fileInput').click();
});

document.getElementById('fileInput').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        console.log('Archivo seleccionado:', file.name);
        // Aquí puedes implementar la subida de archivos
        agregarMensaje('system', `📎 Archivo adjunto: ${file.name}`, true);
    }
});

// Sonido de notificación
function reproducirSonido() {
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTcIGWi78OScTgwKUajk7aJiGgU7k9ryxnkrBSV9y/DajkAKFGG56OqnVRQKR6Hf8rljHwYqgM/x3YY4CBdquvLnn00NDFPH4/KJh');
    audio.play().catch(() => {});
}

// Actualizar lista de chats cada 30 segundos
setInterval(() => {
    location.reload();
}, 30000);
</script>
</body>
</html>