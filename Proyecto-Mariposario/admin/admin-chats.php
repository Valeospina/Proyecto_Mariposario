<?php
session_start();
include '../DB.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

$sql = "SELECT c.ID_Consulta, u.Nombre AS Nombre_Usuario, c.Estado, c.Tema, c.Mensajes, c.Fecha
        FROM Consulta c
        LEFT JOIN Usuario u ON c.ID_Usuario = u.ID_Usuario
        ORDER BY c.Fecha DESC";
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
    .chat-container { display:flex; background:#fff; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); margin:20px; overflow:hidden; }
    .chat-list { width:30%; border-right:1px solid #ddd; background:#f8f9fa; display:flex; flex-direction:column; }
    .filter-bar { display:flex; gap:5px; padding:10px; background:#fff; border-bottom:1px solid #ddd; }
    .filter-btn { flex:1; padding:8px; background:#e9ecef; border:none; border-radius:5px; cursor:pointer; font-weight:500; transition:all 0.2s; }
    .filter-btn.active { background:#28a745; color:#fff; }
    .chat-items { overflow-y:auto; flex:1; }
    .chat-item { display:flex; gap:10px; padding:12px; border-bottom:1px solid #eee; cursor:pointer; align-items:center; transition:background 0.2s; }
    .chat-item:hover { background:#e6f7e6; }
    .chat-item img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
    .chat-item h4 { margin:0; font-size:1rem; display:flex; align-items:center; gap:5px; }
    .chat-item p { margin:5px 0 0; font-size:0.9rem; color:#666; }
    .status-badge { padding:2px 6px; font-size:0.75rem; border-radius:5px; }
    .status-pendiente { background:#ffc107; color:#fff; }
    .status-respondido { background:#17a2b8; color:#fff; }
    .status-cerrado { background:#6c757d; color:#fff; }
    .chat-window { flex:1; display:flex; flex-direction:column; background:#f1f1f1; }
    .chat-header { background:#28a745; color:#fff; padding:15px; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
    .chat-body { flex:1; padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; }
    .message { max-width:75%; padding:12px; border-radius:10px; font-size:0.95rem; }
    .admin-msg { background:#dcf8c6; align-self:flex-end; }
    .client-msg { background:#fff; align-self:flex-start; }
    .chat-footer { display:flex; gap:10px; background:#fff; padding:12px 16px; border-top:1px solid #ddd; }
    .chat-footer input { flex:1; padding:10px 14px; border-radius:25px; border:1px solid #ccc; font-size:0.95rem; }
    .chat-footer button { background:#28a745; color:#fff; border:none; border-radius:50%; width:40px; height:40px; font-size:18px; display:flex; align-items:center; justify-content:center; cursor:pointer; }
    .chat-footer button:disabled { background:#ccc; cursor:not-allowed; }
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
                        <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
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
                        <button class="filter-btn" onclick="filtrar('Respondido',event)">Respondidos</button>
                        <button class="filter-btn" onclick="filtrar('Cerrado',event)">Cerrados</button>
                    </div>
                    <div class="chat-items" id="chatItems">
                        <?php foreach ($chats as $chat): ?>
                            <?php
                            $mensajes = $chat['Mensajes'] ? json_decode($chat['Mensajes'], true) : [];
                            $ultimo = !empty($mensajes) ? end($mensajes)['text'] : 'Sin mensajes';
                            ?>
                            <div class="chat-item" id="chat-<?= $chat['ID_Consulta']; ?>" data-estado="<?= $chat['Estado']; ?>" onclick="abrirChat(<?= $chat['ID_Consulta']; ?>,'<?= htmlspecialchars($chat['Nombre_Usuario']); ?>','<?= $chat['Estado']; ?>')">
                                <img src="../img/user-profile.jpg">
                                <div>
                                    <h4><?= htmlspecialchars($chat['Nombre_Usuario']); ?>
                                        <span class="status-badge status-<?= strtolower($chat['Estado']); ?>" id="estado-<?= $chat['ID_Consulta']; ?>"><?= $chat['Estado']; ?></span>
                                    </h4>
                                    <p><?= htmlspecialchars($ultimo); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="chat-window">
                    <div class="chat-header">
                        <span id="chatUser">Selecciona un chat</span>
                    </div>
                    <div class="chat-body" id="chatBody">
                        <div class="system-msg">Selecciona un chat para comenzar</div>
                    </div>
                    <div class="chat-footer">
                        <input type="text" id="adminMsg" placeholder="Escribe un mensaje..." disabled>
                        <button id="sendBtn" disabled><i class="fas fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let conn = new WebSocket('ws://localhost:8080/chat');
let currentChatId = null;
let chatEstado = '';

conn.onmessage = (e)=>{
    const data = JSON.parse(e.data);

    // Nuevo chat
    if(data.tipo === "nuevo_chat"){
        const chatList = document.getElementById('chatItems');
        const div = document.createElement('div');
        div.classList.add('chat-item');
        div.dataset.estado = "Pendiente";
        div.id = "chat-"+data.consultaId;
        div.setAttribute('onclick',`abrirChat(${data.consultaId},'${data.usuario}','Pendiente')`);
        div.innerHTML = `
            <img src="../img/user-profile.jpg">
            <div>
                <h4>${data.usuario}<span class="status-badge status-pendiente" id="estado-${data.consultaId}">Pendiente</span></h4>
                <p>Nuevo chat iniciado</p>
            </div>`;
        chatList.prepend(div);
        return;
    }

    // Mensajes normales
    if(data.consultaId === currentChatId && data.user !== 'Admin'){
        const div = document.createElement('div');
        div.classList.add('message','client-msg');
        div.textContent = data.message;
        document.getElementById('chatBody').appendChild(div);
    }
};

function filtrar(estado,e){
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
    e.target.classList.add('active');
    document.querySelectorAll('.chat-item').forEach(i=>{
        i.style.display=(estado==='Todos'||i.dataset.estado===estado)?'flex':'none';
    });
}

function abrirChat(id,userName,estado){
    currentChatId=id;
    chatEstado=estado;
    document.getElementById('chatUser').textContent=userName;

    fetch('./get_chat.php?id='+id)
    .then(r=>r.json())
    .then(data=>{
        const body=document.getElementById('chatBody');
        body.innerHTML='';
        if(data.length===0){
            body.innerHTML='<div class="system-msg">Sin mensajes en este chat</div>';
        }else{
            data.forEach(m=>{
                const d=document.createElement('div');
                d.classList.add('message',m.role==='Admin'?'admin-msg':'client-msg');
                d.textContent=m.text;
                body.appendChild(d);
            });
        }
    });

    document.getElementById('adminMsg').disabled = (estado==='Cerrado');
    document.getElementById('sendBtn').disabled = (estado==='Cerrado');
}

document.getElementById('sendBtn').addEventListener('click', enviarMensaje);
document.getElementById('adminMsg').addEventListener('keypress', e=>{
    if(e.key==='Enter') enviarMensaje();
});

function enviarMensaje(){
    const msg=document.getElementById('adminMsg').value.trim();
    if(!msg) return;

    conn.send(JSON.stringify({consultaId:currentChatId,user:'Admin',message:msg}));

    const div=document.createElement('div');
    div.classList.add('message','admin-msg');
    div.textContent=msg;
    document.getElementById('chatBody').appendChild(div);
    document.getElementById('adminMsg').value='';

    fetch('update_chat.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${currentChatId}&role=Admin&text=${encodeURIComponent(msg)}`
    });

    if(chatEstado==='Pendiente'){
        fetch('cambiar_estado.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`id=${currentChatId}&estado=Respondido`
        }).then(()=>{
            // Actualizar estado visual
            document.getElementById('estado-'+currentChatId).textContent = 'Respondido';
            document.getElementById('estado-'+currentChatId).className = 'status-badge status-respondido';
            document.getElementById('chat-'+currentChatId).dataset.estado = 'Respondido';
        });
        chatEstado='Respondido';
    }
}

function cerrarChat(){
    if(!currentChatId) return;
    fetch('cambiar_estado.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${currentChatId}&estado=Cerrado`
    }).then(()=>{
        alert('Chat cerrado');
        document.getElementById('adminMsg').disabled=true;
        document.getElementById('sendBtn').disabled=true;
        // Actualizar estado visual
        document.getElementById('estado-'+currentChatId).textContent = 'Cerrado';
        document.getElementById('estado-'+currentChatId).className = 'status-badge status-cerrado';
        document.getElementById('chat-'+currentChatId).dataset.estado = 'Cerrado';
    });
}
</script>
</body>
</html>
