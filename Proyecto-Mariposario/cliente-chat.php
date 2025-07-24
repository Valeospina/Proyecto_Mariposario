<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simula ID de consulta y datos del usuario
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

/* Layout */
.container {
    display: flex;
    max-width: 1200px;
    margin: 30px auto;
    gap: 20px;
    padding: 0 15px;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    text-align: center;
}
.sidebar img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid #8BC34A;
    margin-bottom: 10px;
}
.sidebar h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
}
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar ul li {
    margin-bottom: 12px;
}
.sidebar ul li a {
    display: block;
    padding: 12px;
    background: #f4f6f9;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
}
.sidebar ul li a:hover {
    background: #8BC34A;
    color: white;
}

/* Chat Box */
.chat-wrapper {
    flex: 1;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    height: 550px;
}
.chat-header {
    background: #8BC34A;
    color: #fff;
    padding: 15px;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-header button {
    background: #fff;
    color: #8BC34A;
    border: none;
    font-weight: bold;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
}
.chat-header button:hover {
    background: #f2f2f2;
}
.chat-body {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: #f8f9fa;
}
.message {
    max-width: 70%;
    padding: 10px 14px;
    margin-bottom: 12px;
    border-radius: 18px;
    font-size: 14px;
    animation: fadeIn 0.3s ease;
    word-wrap: break-word;
}
.cliente {
    background: #8BC34A;
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 0;
}
.admin {
    background: #198754;
    color: #fff;
    margin-right: auto;
    border-bottom-left-radius: 0;
}
.system {
    background: #e0e0e0;
    color: #333;
    margin: auto;
    font-size: 13px;
}
.chat-footer {
    display: flex;
    flex-direction: column;
    padding: 15px;
    border-top: 1px solid #ddd;
    gap: 10px;
}
.select-box, .tema-label {
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    background: #f1f1f1;
    border: 1px solid #ccc;
    text-align: center;
    font-weight: bold;
}
.input-box {
    display: flex;
    gap: 10px;
}
.input-box input {
    flex: 1;
    padding: 12px;
    border-radius: 25px;
    border: 1px solid #ccc;
    font-size: 14px;
}
.input-box button {
    background: #8BC34A;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 18px;
    cursor: pointer;
    transition: background 0.3s;
}
.input-box button:hover {
    background: #6fa12d;
}
#newChatBtn {
    display: none;
    background: #8BC34A;
    color: white;
    font-weight: bold;
    padding: 10px;
    border: none;
    border-radius: 8px;
    margin-top: 10px;
    cursor: pointer;
}
#newChatBtn:hover {
    background: #6fa12d;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
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
        <div class="chat-header">
            Soporte en Línea
            <button id="closeChat">Cerrar Chat</button>
        </div>
        <div class="chat-body" id="chatBox">
            <div class="message system">Seleccione un tema antes de escribir: Consulta, Queja o Sugerencia.</div>
        </div>
        <div class="chat-footer" id="chatFooter">
            <select id="tema" class="select-box">
                <option value="">-- Selecciona un tema --</option>
                <option value="Consulta">Consulta</option>
                <option value="Queja">Queja</option>
                <option value="Sugerencia">Sugerencia</option>
            </select>
            <div class="input-box">
                <input type="text" id="msgInput" placeholder="Escribe tu mensaje..." disabled>
                <button id="sendBtn" disabled>➤</button>
            </div>
            <button id="newChatBtn">Abrir Nuevo Chat</button>
        </div>
    </div>
</div>

<script>
let consultaId = <?php echo $consultaId; ?>;
const user = "Cliente";
const chatBox = document.getElementById('chatBox');
const temaSelect = document.getElementById('tema');
const msgInput = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const closeChatBtn = document.getElementById('closeChat');
const newChatBtn = document.getElementById('newChatBtn');
const chatFooter = document.getElementById('chatFooter');
let temaElegido = "";
let conn = new WebSocket('ws://localhost:8080/chat');

// Activar input cuando selecciona tema
temaSelect.addEventListener('change', () => {
    if (temaSelect.value) {
        temaElegido = temaSelect.value;

        // Crear el chat en la BD vía AJAX
        fetch('crear_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tema=${encodeURIComponent(temaElegido)}&consultaId=${consultaId}`
        })
        .then(res => res.json())
        .then(response => {
            if (response.status === 'success') {
                console.log("Chat creado en la BD");
            } else {
                console.error("Error al crear chat:", response.message);
            }
        });

        agregarMensaje("system", `Tema seleccionado: ${temaElegido}`);

        // Reemplazar select con texto fijo
        temaSelect.style.display = 'none';
        const label = document.createElement('div');
        label.classList.add('tema-label');
        label.id = 'temaLabel';
        label.textContent = `Tema: ${temaElegido}`;
        chatFooter.insertBefore(label, chatFooter.firstChild);

        // Activar input y botón
        msgInput.disabled = false;
        sendBtn.disabled = false;
    }
});

conn.onopen = () => {
    agregarMensaje("system", "¡Bienvenido! Un agente responderá en un máximo de 24 horas.");
};

conn.onmessage = (e) => {
    const data = JSON.parse(e.data);
    if (data.user !== user) {
        agregarMensaje('admin', data.message);
    }
};

sendBtn.addEventListener('click', enviarMensaje);
msgInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') enviarMensaje();
});

closeChatBtn.addEventListener('click', cerrarChat);
newChatBtn.addEventListener('click', abrirNuevoChat);

function enviarMensaje() {
    const msg = msgInput.value.trim();
    if (!msg) return;

    const mensajeFinal = `[${temaElegido}] ${msg}`;
    agregarMensaje('cliente', mensajeFinal);
    conn.send(JSON.stringify({ consultaId, user, message: mensajeFinal }));
    msgInput.value = '';
}

function agregarMensaje(tipo, texto) {
    const div = document.createElement('div');
    div.classList.add('message', tipo);
    div.textContent = texto;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function cerrarChat() {
    agregarMensaje("system", "Has cerrado el chat. No podrás enviar más mensajes.");
    msgInput.disabled = true;
    sendBtn.disabled = true;
    temaSelect.disabled = true;
    closeChatBtn.disabled = true;
    newChatBtn.style.display = 'block';

    conn.send(JSON.stringify({ consultaId, user, message: "El cliente ha cerrado el chat." }));
    conn.close();
}

function abrirNuevoChat() {
    // Limpiar historial
    chatBox.innerHTML = '<div class="message system">Seleccione un tema antes de escribir: Consulta, Queja o Sugerencia.</div>';

    // Restablecer UI
    if (document.getElementById('temaLabel')) {
        document.getElementById('temaLabel').remove();
    }
    temaSelect.style.display = 'block';
    temaSelect.disabled = false;
    temaSelect.value = "";
    msgInput.disabled = true;
    sendBtn.disabled = true;
    closeChatBtn.disabled = false;
    newChatBtn.style.display = 'none';

    // Reiniciar variables
    temaElegido = "";

    // Nuevo ID de consulta
    consultaId = Math.floor(Math.random() * 9000) + 1000;

    // Reconectar WebSocket
    conn = new WebSocket('ws://localhost:8080/chat');
    conn.onopen = () => agregarMensaje("system", "Nuevo chat iniciado. ¡Seleccione un tema!");
    conn.onmessage = (e) => {
        const data = JSON.parse(e.data);
        if (data.user !== user) agregarMensaje('admin', data.message);
    };
}
</script>

</body>
</html>
