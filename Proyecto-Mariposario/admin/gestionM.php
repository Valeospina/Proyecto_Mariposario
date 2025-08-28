<?php
// gestionM.php
session_start();
include '../DB.php'; // conexión mysqli en $conn

// ======= Seguridad (solo admin) =======
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logind.php'); exit;
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: ../index.php'); exit;
}

$page_title = 'Gestión de Mariposas';

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ========= Cargar combos =========
$mariposarios = [];
$especies     = [];
$etapas       = ["Recién Nacida","Juvenil","Adulta","Pupa"];
$pupasEdad    = ["tierna","joven","vieja"];

if ($conn instanceof mysqli) {
    // mariposarios
    $q1 = $conn->query("SELECT ID_Mariposario, Nombre, Capacidad_Especies, Capacidad_Pupas, Activo FROM Mariposario ORDER BY ID_Mariposario ASC");
    while($r=$q1->fetch_assoc()) $mariposarios[]=$r;

    // especies
    $q2 = $conn->query("SELECT ID_Especie, Nombre_Cientifico, COALESCE(Nombre_Comun,'') AS Nombre_Comun, Imagen_URL FROM Especie WHERE Activa=1 ORDER BY Nombre_Cientifico");
    while($r=$q2->fetch_assoc()) $especies[]=$r;
}

// ========= Manejo POST: Agregar lote =========
$message=''; $message_type='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion']) && $_POST['accion']==='agregar_lote') {
    $p_mariposario = intval($_POST['id_mariposario'] ?? 0);
    $p_especie     = intval($_POST['id_especie'] ?? 0);
    $p_etapa       = trim($_POST['etapa'] ?? '');
    $p_pupa_edad   = ($p_etapa==='Pupa') ? (trim($_POST['pupa_edad'] ?? '')) : null;
    $p_cantidad    = intval($_POST['cantidad'] ?? 0);

    if ($p_mariposario<=0 || $p_especie<=0 || !in_array($p_etapa,$etapas,true) || $p_cantidad<=0 || ($p_etapa==='Pupa' && !in_array($p_pupa_edad,$pupasEdad,true))) {
        $message="Completa todos los campos del formulario correctamente."; $message_type='danger';
    } else {
        try{
            // Usamos el SP que calcula la próxima transición y valida capacidades
            $stmt = $conn->prepare("CALL sp_agregar_lote(?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iissi", $p_mariposario, $p_especie, $p_etapa, $p_pupa_edad, $p_cantidad);
            $stmt->execute();
            // Liberar conjuntos de resultados de CALL
            while($conn->more_results() && $conn->next_result()){/* limpiar */}
            $message="Lote agregado correctamente."; $message_type='success';
        }catch(mysqli_sql_exception $e){
            $message="Error al agregar lote: ".h($e->getMessage()); $message_type='danger';
        }
    }
}

// ========= Filtros GET =========
$f_mariposario = intval($_GET['f_mariposario'] ?? 0);
$f_especie     = intval($_GET['f_especie'] ?? 0);
$f_etapa       = trim($_GET['f_etapa'] ?? '');
$f_desde       = trim($_GET['f_desde'] ?? '');
$f_hasta       = trim($_GET['f_hasta'] ?? '');

// ========= KPIs =========
$total_especies = 0;
$total_mariposas = 0;
$total_pupas = 0;
$ing_7 = 0;
$ing_30 = 0;

if ($conn instanceof mysqli) {
    // total especies activas (con cantidad > 0 en lotes)
    $sql = "SELECT COUNT(DISTINCT ID_Especie) AS c FROM Lote_Mariposa WHERE Cantidad>0";
    $total_especies = (int)($conn->query($sql)->fetch_assoc()['c'] ?? 0);

    // mariposas actuales (no pupas)
    $sql = "SELECT COALESCE(SUM(Cantidad),0) AS s FROM Lote_Mariposa WHERE Etapa <> 'Pupa'";
    $total_mariposas = (int)($conn->query($sql)->fetch_assoc()['s'] ?? 0);

    // pupas actuales
    $sql = "SELECT COALESCE(SUM(Cantidad),0) AS s FROM Lote_Mariposa WHERE Etapa = 'Pupa'";
    $total_pupas = (int)($conn->query($sql)->fetch_assoc()['s'] ?? 0);

    // ingresos últimos 7 y 30 días
    $sql = "SELECT 
              SUM(CASE WHEN Fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN Cantidad ELSE 0 END) ing7,
              SUM(CASE WHEN Fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN Cantidad ELSE 0 END) ing30
            FROM Historial_Ingresos
            WHERE Tipo_Accion='Ingreso'";
    $row = $conn->query($sql)->fetch_assoc();
    $ing_7 = (int)($row['ing7'] ?? 0);
    $ing_30 = (int)($row['ing30'] ?? 0);
}

// ========= Tabla de cohortes (lotes) =========
$cohortes = [];
if ($conn instanceof mysqli) {
    $where = [];
    if ($f_mariposario>0) $where[] = "l.ID_Mariposario=".$f_mariposario;
    if ($f_especie>0)     $where[] = "l.ID_Especie=".$f_especie;
    if ($f_etapa!=='' && in_array($f_etapa,$etapas,true)) $where[] = "l.Etapa='".$conn->real_escape_string($f_etapa)."'";
    if ($f_desde!=='')    $where[] = "DATE(l.Fecha_Ingreso) >= '".$conn->real_escape_string($f_desde)."'";
    if ($f_hasta!=='')    $where[] = "DATE(l.Fecha_Ingreso) <= '".$conn->real_escape_string($f_hasta)."'";
    $w = $where ? ('WHERE '.implode(' AND ',$where)) : '';

    $sql = "
      SELECT l.ID_Lote, l.ID_Mariposario, m.Nombre AS Mariposario,
             l.ID_Especie, e.Nombre_Cientifico, e.Nombre_Comun, e.Imagen_URL,
             l.Etapa, l.Pupa_Edad, l.Cantidad,
             l.Fecha_Ingreso, l.Fecha_Siguiente_Transicion
      FROM Lote_Mariposa l
      JOIN Mariposario m ON m.ID_Mariposario = l.ID_Mariposario
      JOIN Especie e ON e.ID_Especie = l.ID_Especie
      $w
      ORDER BY l.Fecha_Ingreso DESC, l.ID_Lote DESC
    ";
    $res = $conn->query($sql);
    while($r=$res->fetch_assoc()) $cohortes[]=$r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo h($page_title) ?> - Panel de Administración</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../css/admin.css"><!-- tu css base -->
<style>
/* ===== Paleta base ===== */
:root {
  --bg-main:#ffffff;
  --text:#1f2937;
  --muted:#6b7280;
  --primary:#2563eb;       
  --primary-600:#1d4ed8;   
  --primary-light:#dbeafe;
  --ok:#16a34a;            
  --ok-light:#dcfce7;
  --warn:#f59e0b;          
  --warn-light:#fef3c7;
  --danger:#ef4444;        
  --danger-600:#dc2626;    
  --danger-light:#fef2f2;
  --card:#ffffff;
  --border:#e5e7eb;
  --shadow:0 6px 18px rgba(0,0,0,.08);
  --shadow-hover:0 12px 28px rgba(0,0,0,.15);
  --radius:12px;
  --radius-sm:8px;
}

/* ===== MEJORAS GENERALES ===== */
body {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.admin-content {
  padding: 24px;
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.2);
}

/* ===== KPIs MEJORADOS ===== */
.kpis {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.kpi {
  background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
  border-radius: var(--radius);
  padding: 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.kpi:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-hover);
  border-color: var(--primary);
}

.kpi:before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--ok) 100%);
  border-radius: var(--radius) var(--radius) 0 0;
}

.kpi h6 {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--muted);
  margin: 0 0 12px 0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kpi .val {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.kpi .badge {
  font-size: 0.75rem;
  font-weight: 500;
  background: var(--primary-light);
  color: var(--primary-600);
  padding: 4px 8px;
  border-radius: 20px;
  border: 1px solid var(--primary);
}

/* ===== TARJETAS MEJORADAS ===== */
.card {
  background: #fff;
  border-radius: var(--radius);
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  transition: all 0.3s ease;
}

.card:hover {
  box-shadow: var(--shadow-hover);
}

.card-add {
  background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
  border-radius: var(--radius);
  padding: 28px;
  margin-bottom: 30px;
  box-shadow: var(--shadow);
  border: 2px solid var(--primary-light);
  position: relative;
  overflow: hidden;
}

.card-add:before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  background: linear-gradient(90deg, var(--primary) 0%, var(--ok) 50%, var(--warn) 100%);
}

.card-add h4 {
  color: var(--text);
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-add h4 i {
  color: var(--primary);
  font-size: 1.1rem;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--border);
}

.section-title i {
  color: var(--primary);
}

/* ===== BOTONES MEJORADOS ===== */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: var(--radius-sm);
  font-weight: 500;
  font-size: 0.9rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.btn:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.btn:hover:before {
  left: 100%;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-600) 100%);
  color: #fff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
}

.btn {
  background: #f8fafc;
  color: var(--text);
  border: 1px solid var(--border);
}

.btn:hover {
  background: #e2e8f0;
  transform: translateY(-1px);
}

.btn i {
  font-size: 0.85rem;
}

/* ===== FORMULARIOS MEJORADOS ===== */
.filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  align-items: end;
}

.add-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  align-items: end;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label,
.form-group span {
  font-weight: 500;
  color: var(--text);
  font-size: 0.9rem;
}

.input,
select {
  padding: 12px 16px;
  border: 2px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  transition: all 0.3s ease;
  background: #fff;
  color: var(--text);
}

.input:focus,
select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  transform: translateY(-1px);
}

.input:hover,
select:hover {
  border-color: var(--primary);
}

/* ===== CHIPS Y BADGES MEJORADOS ===== */
.chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  border: 1px solid;
}

.chip.recien {
  background: var(--ok-light);
  color: var(--ok);
  border-color: var(--ok);
}

.chip.juv {
  background: var(--warn-light);
  color: var(--warn);
  border-color: var(--warn);
}

.chip.adul {
  background: var(--primary-light);
  color: var(--primary-600);
  border-color: var(--primary);
}

.chip.pupa {
  background: var(--danger-light);
  color: var(--danger);
  border-color: var(--danger);
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  background: var(--primary-light);
  color: var(--primary-600);
  border-radius: 16px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid var(--primary);
}

/* ===== TABLA MEJORADA ===== */
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
  border-radius: var(--radius-sm);
  overflow: hidden;
  box-shadow: var(--shadow);
}

.table th {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-600) 100%);
  color: #fff;
  padding: 16px;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
}

.table td {
  padding: 16px;
  border-bottom: 1px solid var(--border);
  background: #fff;
  transition: background 0.2s ease;
}

.table tbody tr:hover td {
  background: #f8fafc;
}

.table tbody tr:nth-child(even) td {
  background: #fafbfc;
}

.table tbody tr:nth-child(even):hover td {
  background: #f1f5f9;
}

.row-flex {
  display: flex;
  align-items: center;
  gap: 12px;
}

.row-flex img {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  border: 2px solid var(--border);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.row-flex > div > div:first-child {
  font-weight: 600;
  color: var(--text);
  font-size: 0.9rem;
}

.row-flex > div > div:last-child {
  font-size: 0.8rem;
  color: var(--muted);
}

/* ===== ALERTAS MEJORADAS ===== */
.alert {
  padding: 16px 20px;
  border-radius: var(--radius-sm);
  margin-bottom: 24px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: slideInFromTop 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.9rem;
  border-left: 4px solid;
}

@keyframes slideInFromTop {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert i { 
  font-size: 1.2rem; 
  flex-shrink: 0;
}

.alert-success {
  background: var(--ok-light);
  color: var(--ok);
  border-left-color: var(--ok);
  border: 1px solid rgba(22, 163, 74, 0.2);
}

.alert-danger {
  background: var(--danger-light);
  color: var(--danger);
  border-left-color: var(--danger);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-warning {
  background: var(--warn-light);
  color: var(--warn);
  border-left-color: var(--warn);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

/* ===== ANIMACIONES ADICIONALES ===== */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.card, .kpi {
  animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.2s; }
.card:nth-child(4) { animation-delay: 0.3s; }

/* ===== RESPONSIVE MEJORADO ===== */
@media (max-width: 768px) {
  .admin-content { padding: 16px; }
  .card, .card-add { padding: 16px; }
  .kpi { padding: 16px; }
  
  .filters, .add-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .kpis {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .kpi .val { font-size: 1.5rem; }
  
  .btn { padding: 10px 16px; font-size: 0.85rem; }
  
  /* Tabla responsive mejorada */
  .table, thead, tbody, th, td, tr { display: block; }
  thead tr { position: absolute; top: -9999px; left: -9999px; }
  
  tr {
    border: 1px solid var(--border);
    margin-bottom: 12px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    background: #fff;
    box-shadow: var(--shadow);
  }
  
  td {
    border: none;
    border-bottom: 1px solid #f0f0f0;
    position: relative;
    padding: 12px 16px 12px 50%;
    text-align: right;
  }
  
  td:before {
    content: attr(data-label);
    position: absolute;
    left: 16px;
    width: 45%;
    padding-right: 10px;
    white-space: nowrap;
    text-align: left;
    font-weight: 600;
    color: var(--muted);
    font-size: 0.8rem;
    text-transform: uppercase;
  }
  
  .row-flex { justify-content: flex-end; }
}

/* ===== LOADING STATES ===== */
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn:disabled:before { display: none; }

/* ===== TOOLTIP STYLES ===== */
[data-tooltip] {
  position: relative;
}

[data-tooltip]:hover:after {
  content: attr(data-tooltip);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: var(--text);
  color: #fff;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 0.75rem;
  white-space: nowrap;
  z-index: 1000;
  margin-bottom: 5px;
}

[data-tooltip]:hover:before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: var(--text);
  z-index: 1000;
}
</style>
</head>
<body>

<div class="admin-dashboard-layout">
  <aside class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
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
                        <li><a href="gestionM.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestionM.php') ? 'active' : ''; ?>"><i class="fas fa-butterfly"></i> Gestión Mariposas</a></li> 
                    </ul>
                </div>
                        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
            </nav>

    </aside>

  <div class="main-panel">
    <header class="main-panel-header">
      <div class="header-left"><h2><?php echo h($page_title) ?></h2></div>
      <div class="header-right">
        <div class="search-bar">
          <input type="text" placeholder="Buscar..."><i class="fas fa-search"></i>
        </div>
        <div class="user-profile">
          <span><?php echo h($_SESSION['user_name'] ?? 'Admin'); ?></span>
          <img src="../images/user-avatar.png" alt="User Avatar">
        </div>
      </div>
    </header>

    <main class="content-area">
      <div class="admin-content">

        <?php if($message): ?>
          <div class="alert alert-<?php echo h($message_type); ?>">
            <i class="fas fa-<?php echo ($message_type === 'success') ? 'check-circle' : (($message_type === 'danger') ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
            <?php echo h($message); ?>
          </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="kpis">
          <div class="kpi" data-tooltip="Especies diferentes con mariposas activas">
            <h6><i class="fas fa-dna"></i> Total de especies</h6>
            <div class="val"><?php echo number_format($total_especies) ?></div>
          </div>
          <div class="kpi" data-tooltip="Mariposas en todas las etapas excepto pupa">
            <h6><i class="fas fa-butterfly"></i> Mariposas actuales</h6>
            <div class="val"><?php echo number_format($total_mariposas) ?></div>
          </div>
          <div class="kpi" data-tooltip="Pupas en proceso de transformación">
            <h6><i class="fas fa-egg"></i> Pupas actuales</h6>
            <div class="val"><?php echo number_format($total_pupas) ?></div>
          </div>
          <div class="kpi" data-tooltip="Ingresos recientes al mariposario">
            <h6><i class="fas fa-chart-line"></i> Ingresos recientes</h6>
            <div class="val">
              <?php echo number_format($ing_7) ?> 
              <span class="badge"><i class="fas fa-calendar-week"></i> 7d / <?php echo number_format($ing_30) ?> <i class="fas fa-calendar-month"></i> 30d</span>
            </div>
          </div>
        </div>

        <!-- Filtros -->
        <form method="get" class="card">
          <div class="section-title">
            <i class="fas fa-filter"></i>
            Filtros de búsqueda
          </div>
          <div class="filters">
            <div>
              <label class="form-group">
                <span><i class="fas fa-home"></i> Mariposario</span>
                <select name="f_mariposario">
                  <option value="0">Todos los mariposarios</option>
                  <?php foreach($mariposarios as $m): ?>
                    <option value="<?php echo $m['ID_Mariposario'] ?>" <?php echo ($f_mariposario==$m['ID_Mariposario']?'selected':''); ?>>
                      #<?php echo $m['ID_Mariposario'] ?> — <?php echo h($m['Nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <div>
              <label class="form-group">
                <span><i class="fas fa-dna"></i> Especie</span>
                <select name="f_especie">
                  <option value="0">Todas las especies</option>
                  <?php foreach($especies as $e): ?>
                    <option value="<?php echo $e['ID_Especie'] ?>" <?php echo ($f_especie==$e['ID_Especie']?'selected':''); ?>>
                      <?php echo h($e['Nombre_Cientifico']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <div>
              <label class="form-group">
                <span><i class="fas fa-layer-group"></i> Etapa</span>
                <select name="f_etapa">
                  <option value="">Todas las etapas</option>
                  <?php foreach($etapas as $et): ?>
                    <option value="<?php echo $et ?>" <?php echo ($f_etapa===$et?'selected':''); ?>><?php echo $et ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <div>
              <label class="form-group">
                <span><i class="fas fa-calendar-alt"></i> Fecha desde</span>
                <input type="date" name="f_desde" class="input" value="<?php echo h($f_desde) ?>">
              </label>
            </div>
            <div>
              <label class="form-group">
                <span><i class="fas fa-calendar-alt"></i> Fecha hasta</span>
                <input type="date" name="f_hasta" class="input" value="<?php echo h($f_hasta) ?>">
              </label>
            </div>
            <div style="display:flex; align-items:end; gap:12px;">
              <button class="btn btn-primary" type="submit">
                <i class="fas fa-filter"></i> Aplicar filtros
              </button>
              <a class="btn" href="gestionM.php">
                <i class="fas fa-eraser"></i> Limpiar
              </a>
            </div>
          </div>
        </form>

        <!-- Agregar lote -->
        <form method="post" class="card-add">
          <h4><i class="fas fa-plus-circle"></i> Agregar nuevo lote</h4>
          <input type="hidden" name="accion" value="agregar_lote">
          <div class="add-grid">
            <div class="form-group">
              <label><i class="fas fa-home"></i> Mariposario *</label>
              <select name="id_mariposario" required>
                <option value="">Seleccione un mariposario</option>
                <?php foreach($mariposarios as $m): ?>
                  <option value="<?php echo $m['ID_Mariposario'] ?>">
                    #<?php echo $m['ID_Mariposario'] ?> — <?php echo h($m['Nombre']) ?>
                    <?php if(!$m['Activo']): ?> (Inactivo)<?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label><i class="fas fa-dna"></i> Especie *</label>
              <select name="id_especie" required>
                <option value="">Seleccione una especie</option>
                <?php foreach($especies as $e): ?>
                  <option value="<?php echo $e['ID_Especie'] ?>" data-image="<?php echo h($e['Imagen_URL']) ?>">
                    <?php echo h($e['Nombre_Cientifico']) ?><?php echo $e['Nombre_Comun']?(' — '.h($e['Nombre_Comun'])):''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label><i class="fas fa-layer-group"></i> Etapa de desarrollo *</label>
              <select name="etapa" id="etapaSelect" required>
                <option value="">Seleccione una etapa</option>
                <?php foreach($etapas as $et): ?>
                  <option value="<?php echo $et ?>"><?php echo $et ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group" id="pupaEdadGroup" style="display:none;">
              <label><i class="fas fa-hourglass-half"></i> Edad de la pupa *</label>
              <select name="pupa_edad" id="pupaEdad">
                <option value="">Seleccione la edad</option>
                <?php foreach($pupasEdad as $pe): ?>
                  <option value="<?php echo $pe ?>"><?php echo ucfirst($pe) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label><i class="fas fa-calculator"></i> Cantidad *</label>
              <input type="number" min="1" max="10000" class="input" name="cantidad" required placeholder="Ej: 150" data-tooltip="Ingrese la cantidad de individuos">
            </div>

            <div style="grid-column: 1/-1; display:flex; gap:12px; align-items:center; margin-top:12px;">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Agregar lote
              </button>
              <div class="badge" style="background: var(--warn-light); color: var(--warn); border-color: var(--warn);">
                <i class="fas fa-info-circle"></i> Si eliges "Pupa", debes especificar la edad (tierna/joven/vieja)
              </div>
            </div>
          </div>
        </form>

        <!-- Cohortes -->
        <div class="card">
          <div class="section-title">
            <i class="fas fa-table"></i> 
            Cohortes actuales (<?php echo count($cohortes) ?> lotes)
          </div>
          <?php if(!$cohortes): ?>
            <div style="text-align:center; padding:40px; color:var(--muted);">
              <i class="fas fa-search" style="font-size:3rem; margin-bottom:16px; opacity:0.5;"></i>
              <p style="margin:0; font-size:1.1rem;">No hay lotes que coincidan con los filtros aplicados</p>
              <p style="margin:8px 0 0 0; font-size:0.9rem;">Intenta ajustar los criterios de búsqueda</p>
            </div>
          <?php else: ?>
            <div style="overflow-x: auto;">
              <table class="table">
                <thead>
                  <tr>
                    <th><i class="fas fa-hashtag"></i> ID Lote</th>
                    <th><i class="fas fa-home"></i> Mariposario</th>
                    <th><i class="fas fa-dna"></i> Especie</th>
                    <th><i class="fas fa-layer-group"></i> Etapa</th>
                    <th><i class="fas fa-calculator"></i> Cantidad</th>
                    <th><i class="fas fa-calendar-plus"></i> Fecha ingreso</th>
                    <th><i class="fas fa-clock"></i> Próx. transición</th>
                    <th><i class="fas fa-stopwatch"></i> Cuenta regresiva</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($cohortes as $c): 
                    $chip=''; $cls='';
                    switch($c['Etapa']){
                      case 'Recién Nacida': $cls='recien'; break;
                      case 'Juvenil': $cls='juv'; break;
                      case 'Adulta': $cls='adul'; break;
                      case 'Pupa': $cls='pupa'; break;
                    }
                  ?>
                  <tr data-lote-id="<?php echo $c['ID_Lote'] ?>">
                    <td data-label="ID:">#<?php echo $c['ID_Lote'] ?></td>
                    <td data-label="Mariposario:"><?php echo h($c['Mariposario']) ?></td>
                    <td data-label="Especie:">
                      <div class="row-flex">
                        <img src="<?php echo h($c['Imagen_URL'] ?: 'https://via.placeholder.com/48x48/e2e8f0/64748b?text=🦋') ?>" alt="<?php echo h($c['Nombre_Cientifico']) ?>">
                        <div>
                          <div><?php echo h($c['Nombre_Cientifico']) ?></div>
                          <div><?php echo h($c['Nombre_Comun'] ?: 'Sin nombre común') ?></div>
                        </div>
                      </div>
                    </td>
                    <td data-label="Etapa:">
                      <span class="chip <?php echo $cls ?>">
                        <?php echo h($c['Etapa']) ?>
                        <?php if($c['Etapa']==='Pupa' && $c['Pupa_Edad']): ?>
                          <i class="fas fa-circle" style="font-size:0.5rem;"></i>
                          <?php echo ucfirst(h($c['Pupa_Edad'])) ?>
                        <?php endif; ?>
                      </span>
                    </td>
                    <td data-label="Cantidad:">
                      <strong style="font-size:1.1rem; color:var(--primary);"><?php echo number_format((int)$c['Cantidad']) ?></strong>
                    </td>
                    <td data-label="Ingreso:">
                      <div>
                        <div><?php echo date('d/m/Y', strtotime($c['Fecha_Ingreso'])) ?></div>
                        <div style="font-size:0.8rem; color:var(--muted);"><?php echo date('H:i', strtotime($c['Fecha_Ingreso'])) ?></div>
                      </div>
                    </td>
                    <td data-label="Próx. transición:">
                      <div>
                        <div><?php echo date('d/m/Y', strtotime($c['Fecha_Siguiente_Transicion'])) ?></div>
                        <div style="font-size:0.8rem; color:var(--muted);"><?php echo date('H:i', strtotime($c['Fecha_Siguiente_Transicion'])) ?></div>
                      </div>
                    </td>
                    <td data-label="Cuenta regresiva:">
                      <span class="badge" data-countdown="<?php echo h($c['Fecha_Siguiente_Transicion']) ?>" style="font-family: monospace;">
                        <i class="fas fa-clock"></i> Calculando...
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Estadísticas rápidas de la tabla -->
            <div style="margin-top:20px; padding:16px; background:var(--primary-light); border-radius:var(--radius-sm); display:flex; gap:20px; flex-wrap:wrap; font-size:0.9rem;">
              <div><strong>Total mostrado:</strong> <?php echo count($cohortes) ?> lotes</div>
              <div><strong>Suma cantidad:</strong> <?php echo number_format(array_sum(array_column($cohortes, 'Cantidad'))) ?> individuos</div>
              <div><strong>Mariposarios únicos:</strong> <?php echo count(array_unique(array_column($cohortes, 'ID_Mariposario'))) ?></div>
              <div><strong>Especies únicas:</strong> <?php echo count(array_unique(array_column($cohortes, 'ID_Especie'))) ?></div>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
// Mostrar/ocultar edad de pupa con animación suave
const etapaSel = document.getElementById('etapaSelect');
const pupaGroup = document.getElementById('pupaEdadGroup');
const pupaSelect = document.getElementById('pupaEdad');

if (etapaSel) {
  etapaSel.addEventListener('change', function() {
    if (this.value === 'Pupa') {
      pupaGroup.style.display = 'block';
      pupaGroup.style.animation = 'fadeIn 0.3s ease-out';
      pupaSelect.required = true;
    } else {
      pupaGroup.style.display = 'none';
      pupaSelect.value = '';
      pupaSelect.required = false;
    }
  });
}

// Cuenta regresiva en tiempo real mejorada
function startCountdowns() {
  const items = document.querySelectorAll('[data-countdown]');
  
  function formatTime(num) {
    return num.toString().padStart(2, '0');
  }
  
  function updateCountdowns() {
    const now = new Date().getTime();
    
    items.forEach(element => {
      const targetDate = element.dataset.countdown.replace(' ', 'T');
      const target = new Date(targetDate).getTime();
      let timeDiff = Math.max(0, target - now);
      
      // Calcular tiempo restante
      const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
      timeDiff -= days * 24 * 60 * 60 * 1000;
      
      const hours = Math.floor(timeDiff / (1000 * 60 * 60));
      timeDiff -= hours * 60 * 60 * 1000;
      
      const minutes = Math.floor(timeDiff / (1000 * 60));
      timeDiff -= minutes * 60 * 1000;
      
      const seconds = Math.floor(timeDiff / 1000);
      
      // Formatear y mostrar
      if (days > 0) {
        element.innerHTML = `<i class="fas fa-clock"></i> ${days}d ${formatTime(hours)}h ${formatTime(minutes)}m`;
      } else if (hours > 0) {
        element.innerHTML = `<i class="fas fa-clock"></i> ${formatTime(hours)}h ${formatTime(minutes)}m ${formatTime(seconds)}s`;
      } else if (minutes > 0) {
        element.innerHTML = `<i class="fas fa-clock"></i> ${formatTime(minutes)}m ${formatTime(seconds)}s`;
      } else if (seconds > 0) {
        element.innerHTML = `<i class="fas fa-clock"></i> ${formatTime(seconds)}s`;
      } else {
        element.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ¡Ya!';
        element.style.background = 'var(--danger-light)';
        element.style.color = 'var(--danger)';
        element.style.borderColor = 'var(--danger)';
        element.style.animation = 'pulse 2s infinite';
      }
      
      // Cambiar color según proximidad
      if (days === 0 && hours < 6) {
        element.style.background = 'var(--warn-light)';
        element.style.color = 'var(--warn)';
        element.style.borderColor = 'var(--warn)';
      } else if (days === 0) {
        element.style.background = 'var(--warn-light)';
        element.style.color = 'var(--warn)';
        element.style.borderColor = 'var(--warn)';
      }
    });
  }
  
  // Actualizar inmediatamente y luego cada segundo
  updateCountdowns();
  const interval = setInterval(updateCountdowns, 1000);
  
  // Limpiar intervalo si la página se oculta
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      clearInterval(interval);
    } else {
      startCountdowns();
    }
  });
}

// Añadir animación de pulso para elementos críticos
const style = document.createElement('style');
style.textContent = `
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }
`;
document.head.appendChild(style);

// Inicializar cuenta regresiva cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', startCountdowns);
} else {
  startCountdowns();
}

// Mejorar la experiencia de los formularios
document.addEventListener('DOMContentLoaded', function() {
  // Validación en tiempo real
  const requiredFields = document.querySelectorAll('[required]');
  requiredFields.forEach(field => {
    field.addEventListener('blur', function() {
      if (this.value.trim() === '') {
        this.style.borderColor = 'var(--danger)';
        this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
      } else {
        this.style.borderColor = 'var(--ok)';
        this.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.1)';
      }
    });
    
    field.addEventListener('input', function() {
      if (this.value.trim() !== '') {
        this.style.borderColor = 'var(--ok)';
        this.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.1)';
      }
    });
  });
  
  // Mejorar selects con búsqueda visual
  const selects = document.querySelectorAll('select');
  selects.forEach(select => {
    select.addEventListener('focus', function() {
      this.style.transform = 'translateY(-1px)';
      this.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.15)';
    });
    
    select.addEventListener('blur', function() {
      this.style.transform = '';
      this.style.boxShadow = '';
    });
  });
});

// Función para exportar datos (opcional)
function exportTableData() {
  const table = document.querySelector('.table');
  if (!table) return;
  
  let csv = [];
  const rows = table.querySelectorAll('tr');
  
  rows.forEach(row => {
    const cols = row.querySelectorAll('th, td');
    const rowData = [];
    cols.forEach(col => {
      rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
    });
    csv.push(rowData.join(','));
  });
  
  const csvContent = csv.join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', 'cohortes_mariposas.csv');
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
</script>

<?php
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>