<?php
session_start();
include '../DB.php';

// Verificación de rol administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php');
    exit;
}

// Obtener todos los chats
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#f4f4f4; }
.admin-layout { display:flex; height:100vh; }
.sidebar { width:250px; background:#2c3e50; color:white; }
.sidebar-header h3 { text-align:center; padding:15px; margin:0; background:#1a252f; }
.sidebar-nav a { color:white; display:block; padding:12px 15px; text-decoration:none; }
.sidebar-nav a.active { background:#8BC34A; }
.main-panel { flex:1; display:flex; flex-direction:column; }
.main-panel-header { background:#fff; padding:15px; font-weight:600; border-bottom:1px solid #ddd; }
.chat-container { flex:1; display:flex; margin:10px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
.chat-list { width:30%; border-right:1px solid #ddd; display:flex; flex-direction:column; }
.filter-bar { display:flex; justify-content:space-around; padding:10px; background:#fff; border-bottom:1px solid #ddd; }
.filter-btn { background:#eaeaea; border:none; border-radius:20px; padding:6px 12px; cursor:pointer; }
.filter-btn.active { background:#8BC34A; color:#fff; }
.chat-items { flex:1; overflow-y:auto; }
.chat-item { display:flex; padding:10px; border-bottom:1px solid #eee; cursor:pointer; }
.chat-item:hover { background:#e9f5e9; }
.chat-item img { width:40px; height:40px; border-radius:50%; margin-right:10px; }
.status-badge { padding:2px 6px; font-size:11px; border-radius:4px; margin-left:4px; }
.status-pendiente { background:#ffc107; color:#fff; }
.status-respondido { background:#17a2b8; color:#fff; }
.status-cerrado { background:#6c757d; color:#fff; }
.chat-window { flex:1; display:flex; flex-direction:column; background:#ece5dd; }
.chat-header { background:#8BC34A; color:white; padding:15px; display:flex; justify-content:space-between; }
.chat-body { flex:1; padding:15px; overflow-y:auto; display:flex; flex-direction:column; }
.message { max-width:70%; padding:10px; margin-bottom:10px; border-radius:8px; font-size:14px; }
.admin-msg { background:#dcf8c6; align-self:flex-end; }
.client-msg { background:#fff; align-self:flex-start; }
.chat-footer { background:#fff; padding:10px; display:flex; gap:10px; border-top:1px solid #ddd; }
.chat-footer input { flex:1; padding:10px; border-radius:25px; border:1px solid #ccc; }
.chat-footer button { background:#8BC34A; color:white; border:none; border-radius:50%; padding:10px; font-size:18px; cursor:pointer; }
.chat-footer button:disabled { background:#ccc; cursor:not-allowed; }
</style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header"><h3>Admin Panel</h3></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="gestion_empleados.php">Gestionar Empleados</a>
            <a href="users.php">Gestionar Usuarios</a>
            <a href="products.php">Gestionar Productos</a>
            <a href="./admin-chats.php" class="active">Soporte</a>
        </nav>
    </div>

    <!-- Main -->
    <div class="main-panel">
        <div class="main-panel-header"><?php echo $page_title; ?></div>
        <div class="chat-container">
            <!-- Lista -->
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

            <!-- Ventana -->
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
