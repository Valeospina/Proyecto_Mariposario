<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mantener el mismo ID de consulta en la sesión
if (!isset($_SESSION['consultaId'])) {
    $_SESSION['consultaId'] = rand(1000, 9999);
}
$consultaId = $_SESSION['consultaId'];
$userName = $_SESSION['user_name'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Soporte en Línea</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 0;
}
.container { display:flex; max-width:1200px; margin:30px auto; gap:20px; padding:0 15px; }
.sidebar { width:250px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05); text-align:center; }
.sidebar img { width:100px; height:100px; border-radius:50%; border:3px solid #8BC34A; margin-bottom:10px; }
.sidebar h3 { font-size:16px; font-weight:600; margin-bottom:15px; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li { margin-bottom:12px; }
.sidebar ul li a { display:block; padding:12px; background:#f4f6f9; color:#333; text-decoration:none; border-radius:8px; font-weight:bold; transition:0.3s; }
.sidebar ul li a:hover { background:#8BC34A; color:white; }
.chat-wrapper { flex:1; background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); display:flex; flex-direction:column; height:550px; }
.chat-header { background:#8BC34A; color:#fff; padding:15px; font-size:18px; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
.chat-header button { display:none; }
.chat-body { flex:1; padding:15px; overflow-y:auto; background:#f8f9fa; }
.message { max-width:70%; padding:10px 14px; margin-bottom:12px; border-radius:18px; font-size:14px; animation:fadeIn 0.3s ease; word-wrap:break-word; }
.cliente { background:#8BC34A; color:#fff; margin-left:auto; border-bottom-right-radius:0; }
.admin { background:#198754; color:#fff; margin-right:auto; border-bottom-left-radius:0; }
.system { background:#e0e0e0; color:#333; margin:auto; font-size:13px; text-align:center; }
.chat-footer { display:flex; flex-direction:column; padding:15px; border-top:1px solid #ddd; gap:10px; }
.input-box { display:flex; gap:10px; }
.input-box input { flex:1; padding:12px; border-radius:25px; border:1px solid #ccc; font-size:14px; }
.input-box button { background:#8BC34A; color:white; border:none; border-radius:50%; width:50px; height:50px; font-size:18px; cursor:pointer; transition:background 0.3s; }
.input-box button:hover { background:#6fa12d; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }
</style>
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="img/user-profile.jpg" alt="Usuario">
        <h3><?php echo htmlspecialchars($userName); ?></h3>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="MisPedidos.php">Mis Pedidos</a></li>
            <li><a href="#" onclick="history.back()">← Devolver</a></li>
        </ul>
    </div>

    <!-- Chat -->
    <div class="chat-wrapper">
        <div class="chat-header">Soporte en Línea</div>
        <div class="chat-body" id="chatBox">
            <div class="message system">Cargando historial...</div>
        </div>
        <div class="chat-footer">
            <div class="input-box">
                <input type="text" id="msgInput" placeholder="Escribe tu mensaje...">
                <button id="sendBtn">➤</button>
            </div>
        </div>
    </div>
</div>

<script>
let consultaId = <?php echo $consultaId; ?>;
const user = "<?php echo htmlspecialchars($userName); ?>";
const chatBox = document.getElementById('chatBox');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
let conn = new WebSocket('ws://localhost:8080/chat');

// ✅ Paso 1: Registrar el chat en BD automáticamente con tema fijo "Consulta"
fetch('crear_chat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `tema=Consulta&consultaId=${consultaId}`
});

// ✅ Cargar historial
window.onload = () => {
    fetch('./admin/get_chat.php?id=' + consultaId)
        .then(r => r.json())
        .then(data => {
            chatBox.innerHTML = '';
            if (data.length === 0) {
                chatBox.innerHTML = '<div class="message system">Escribe tu consulta para iniciar el chat.</div>';
            } else {
                data.forEach(m => {
                    const div = document.createElement('div');
                    div.classList.add('message', m.role === 'Admin' ? 'admin' : 'cliente');
                    div.textContent = m.text;
                    chatBox.appendChild(div);
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
};

// WebSocket: recibir mensajes
conn.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.consultaId === consultaId && data.user !== user) {
        agregarMensaje('admin', data.message);
    }
};

// Enviar mensaje
sendBtn.addEventListener('click', enviarMensaje);
msgInput.addEventListener('keypress', e => { if (e.key === 'Enter') enviarMensaje(); });

function enviarMensaje() {
    const msg = msgInput.value.trim();
    if (!msg) return;

    const mensajeFinal = `${user}: ${msg}`;
    agregarMensaje('cliente', mensajeFinal);
    conn.send(JSON.stringify({ consultaId, user, message: mensajeFinal }));

    // Guardar en BD
    fetch('update_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${consultaId}&role=Cliente&text=${encodeURIComponent(mensajeFinal)}`
    });

    msgInput.value = '';
}

function agregarMensaje(tipo, texto) {
    const div = document.createElement('div');
    div.classList.add('message', tipo);
    div.textContent = texto;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}
</script>
</body>
</html>
