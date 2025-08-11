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
$ig_30 = 0;

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
/* ======= Overrides de look & feel solicitados ======= */
:root{
  --bg-main:#ffffff;
  --text:#1f2937;
  --muted:#6b7280;
  --primary:#2563eb;
  --primary-600:#1d4ed8;
  --ok:#16a34a;
  --warn:#f59e0b;
  --danger:#ef4444;
  --card:#ffffff;
  --border:#e5e7eb;
  --shadow:0 6px 18px rgba(0,0,0,.08);
}

/* Fondo general blanco */
body{ background:#f7f8fa; font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial; color:var(--text); }

/* Sidebar gris claro como antes */
.sidebar{
  background:#f0f2f5 !important;
  color:#111;
  border-right:1px solid #e3e5ea;
}
.sidebar .sidebar-header h3{ color:#111; }
.sidebar-nav ul li a{
  color:#222;
  background:transparent;
  border-radius:10px;
  padding:.85rem 1rem;
  display:flex; align-items:center; gap:.65rem;
}
.sidebar-nav ul li a:hover{
  background:#e7eaf1;
}
.sidebar-nav ul li a.active{
  background:#28a745; color:#fff;
}
.sidebar-footer a{ color:#0f172a; }
.menu-scroll{ max-height:calc(100vh - 180px); overflow:auto; }

/* Main panel blanco */
.main-panel{ background:var(--bg-main); }

/* Header */
.main-panel-header{
  background:#fff; border-bottom:1px solid var(--border);
}

/* KPI cards */
.kpis{ display:grid; grid-template-columns:repeat(4, minmax(200px,1fr)); gap:16px; margin-bottom:22px;}
.kpi{
  background:var(--card); border:1px solid var(--border); border-radius:14px; padding:16px; box-shadow:var(--shadow);
}
.kpi h6{ margin:0 0 6px; font-weight:600; color:var(--muted); font-size:.9rem;}
.kpi .val{ font-size:1.8rem; font-weight:700; }

/* Filtros */
.filters{
  display:grid; grid-template-columns: repeat(6, minmax(140px,1fr)); gap:12px;
  margin-bottom:18px;
}
.input, select{
  width:100%; padding:.75rem .9rem; border:1px solid var(--border); border-radius:10px; background:#fff; outline:none;
}
select:focus, .input:focus{ border-color:var(--primary); box-shadow:0 0 0 3px rgba(37,99,235,.15); }

/* === Card Agregar Lote (mejorada) === */
.card-add{
  background:linear-gradient(#fff, #fff) padding-box,
             linear-gradient(135deg, #e2e8f0, #cbd5e1) border-box;
  border:2px solid transparent;
  border-radius:16px; padding:18px; box-shadow:var(--shadow); margin-bottom:22px;
}
.card-add h4{ margin:0 0 14px; font-size:1.1rem; }
.add-grid{
  display:grid; grid-template-columns: 1.2fr 1.6fr 1.1fr 1fr 140px; gap:14px; align-items:end;
}
.form-group label{ display:block; font-size:.9rem; color:var(--muted); margin-bottom:6px;}
.btn{
  display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
  padding:.8rem 1rem; border-radius:10px; border:1px solid transparent; cursor:pointer; font-weight:600;
}
.btn-primary{ background:var(--primary); color:#fff; }
.btn-primary:hover{ background:var(--primary-600); }
.badge{ font-size:.72rem; padding:.3rem .5rem; border-radius:999px; border:1px solid var(--border); color:#334155; background:#f8fafc; }

/* Tabla cohortes */
.table{
  width:100%; border-collapse:separate; border-spacing:0 10px;
}
.table thead th{
  text-align:left; font-size:.85rem; color:#64748b; font-weight:600; padding:0 14px 6px;
}
.table tbody tr{
  background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow);
}
.table tbody td{
  padding:14px; vertical-align:middle;
}
.row-flex{ display:flex; align-items:center; gap:12px; }

/* Alertas */
.alert{ padding:.9rem 1rem; border-radius:10px; margin-bottom:16px; border:1px solid;}
.alert-success{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0;}
.alert-danger{ background:#fef2f2; color:#7f1d1d; border-color:#fecaca;}

/* Chips etapas */
.chip{ font-size:.75rem; padding:.3rem .55rem; border-radius:999px; }
.chip.recien{ background:#e0f2fe; color:#075985; }
.chip.juv{ background:#dcfce7; color:#065f46; }
.chip.adul{ background:#fef9c3; color:#92400e; }
.chip.pupa{ background:#fae8ff; color:#6b21a8; }

/* Utilidades */
.card{ background:#fff; border:1px solid var(--border); border-radius:14px; padding:16px; box-shadow:var(--shadow);}
.section-title{ font-size:1.05rem; font-weight:700; margin:0 0 10px; }

/* Responsive */
@media (max-width:1100px){
  .kpis{ grid-template-columns:repeat(2,1fr); }
  .filters{ grid-template-columns: repeat(2,1fr); }
  .add-grid{ grid-template-columns:1fr; }
}
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

          <!-- NUEVO: Gestión de Mariposas -->
          <li><a href="gestionM.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'gestionM.php') ? 'active' : ''; ?>"><i class="fa-solid fa-butterfly"></i> Gestión de Mariposas</a></li>

          <li><a href="eventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'eventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Gestionar Eventos</a></li>
          <li><a href="ReservaAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'ReservaAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Gestionar Reservas</a></li>
          <li><a href="InsEventoAdmin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'InsEventoAdmin.php') ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Gestionar Asistencia</a></li>
          <li><a href="pedidos.php" class="<?php echo (in_array(basename($_SERVER['PHP_SELF']), ['pedidos.php', 'edit_pedido.php'])) ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Gestionar Pedidos</a></li>
          <li><a href="reporte_ventas.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reporte_ventas.php') ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i> Reporte de Ventas</a></li>
          <li><a href="reports.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Ver Reportes</a></li>
          <li><a href="reportAsis.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Reportes Asistencia</a></li>
          <li><a href="admin-chats.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin-chats.php') ? 'active' : ''; ?>"><i class="fas fa-headset"></i> Soporte</a></li>  
        </ul>
      </div>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></div>
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
          <div class="alert alert-<?php echo h($message_type); ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="kpis">
          <div class="kpi"><h6>Total de especies</h6><div class="val"><?php echo $total_especies ?></div></div>
          <div class="kpi"><h6>Mariposas actuales</h6><div class="val"><?php echo $total_mariposas ?></div></div>
          <div class="kpi"><h6>Pupas actuales</h6><div class="val"><?php echo $total_pupas ?></div></div>
          <div class="kpi"><h6>Ingresos 7d / 30d</h6><div class="val"><?php echo $ing_7 ?> <span class="badge">/ <?php echo $ing_30 ?></span></div></div>
        </div>

        <!-- Filtros -->
        <form method="get" class="card">
          <div class="section-title">Filtros</div>
          <div class="filters">
            <div>
              <label class="form-group">
                <span>Mariposario</span>
                <select name="f_mariposario">
                  <option value="0">Todos</option>
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
                <span>Especie</span>
                <select name="f_especie">
                  <option value="0">Todas</option>
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
                <span>Etapa</span>
                <select name="f_etapa">
                  <option value="">Todas</option>
                  <?php foreach($etapas as $et): ?>
                    <option value="<?php echo $et ?>" <?php echo ($f_etapa===$et?'selected':''); ?>><?php echo $et ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
            <div>
              <label class="form-group">
                <span>Desde</span>
                <input type="date" name="f_desde" class="input" value="<?php echo h($f_desde) ?>">
              </label>
            </div>
            <div>
              <label class="form-group">
                <span>Hasta</span>
                <input type="date" name="f_hasta" class="input" value="<?php echo h($f_hasta) ?>">
              </label>
            </div>
            <div style="display:flex; align-items:end; gap:8px;">
              <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
              <a class="btn" href="gestionM.php"><i class="fa-solid fa-eraser"></i> Limpiar</a>
            </div>
          </div>
        </form>

        <!-- Agregar lote (DISEÑO MEJORADO) -->
        <form method="post" class="card-add">
          <h4><i class="fa-solid fa-layer-group"></i> Agregar lote</h4>
          <input type="hidden" name="accion" value="agregar_lote">
          <div class="add-grid">
            <div class="form-group">
              <label>Mariposario</label>
              <select name="id_mariposario" required>
                <option value="">Seleccione…</option>
                <?php foreach($mariposarios as $m): ?>
                  <option value="<?php echo $m['ID_Mariposario'] ?>">
                    #<?php echo $m['ID_Mariposario'] ?> — <?php echo h($m['Nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Especie</label>
              <select name="id_especie" required>
                <option value="">Seleccione…</option>
                <?php foreach($especies as $e): ?>
                  <option value="<?php echo $e['ID_Especie'] ?>">
                    <?php echo h($e['Nombre_Cientifico']) ?><?php echo $e['Nombre_Comun']?(' — '.h($e['Nombre_Comun'])):''; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Etapa</label>
              <select name="etapa" id="etapaSelect" required>
                <option value="">Seleccione…</option>
                <?php foreach($etapas as $et): ?>
                  <option value="<?php echo $et ?>"><?php echo $et ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group" id="pupaEdadGroup" style="display:none;">
              <label>Edad de pupa</label>
              <select name="pupa_edad" id="pupaEdad">
                <option value="">Seleccione…</option>
                <?php foreach($pupasEdad as $pe): ?>
                  <option value="<?php echo $pe ?>"><?php echo ucfirst($pe) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Cantidad</label>
              <input type="number" min="1" class="input" name="cantidad" required placeholder="Ej. 100">
            </div>

            <div style="grid-column: 1/-1; display:flex; gap:10px;">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
              <span class="badge"><i class="fa-regular fa-lightbulb"></i> Si eliges “Pupa”, especifica la edad (tierna/joven/vieja).</span>
            </div>
          </div>
        </form>

        <!-- Cohortes -->
        <div class="card">
          <div class="section-title"><i class="fa-solid fa-list-ul"></i> Cohortes (lotes)</div>
          <?php if(!$cohortes): ?>
            <p style="color:var(--muted); margin:0;">No hay lotes con los filtros aplicados.</p>
          <?php else: ?>
            <table class="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Mariposario</th>
                  <th>Especie</th>
                  <th>Etapa</th>
                  <th>Cantidad</th>
                  <th>Ingreso</th>
                  <th>Próx. transición</th>
                  <th>Cuenta regresiva</th>
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
                <tr>
                  <td>#<?php echo $c['ID_Lote'] ?></td>
                  <td><?php echo h($c['Mariposario']) ?></td>
                  <td>
                    <div class="row-flex">
                      <img src="<?php echo h($c['Imagen_URL'] ?: 'https://via.placeholder.com/40x40?text=') ?>" style="width:40px;height:40px;border-radius:8px;object-fit:cover;border:1px solid var(--border);" alt="">
                      <div>
                        <div style="font-weight:600;"><?php echo h($c['Nombre_Cientifico']) ?></div>
                        <div style="font-size:.8rem; color:var(--muted);"><?php echo h($c['Nombre_Comun']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="chip <?php echo $cls ?>">
                      <?php echo h($c['Etapa']) ?>
                      <?php if($c['Etapa']==='Pupa' && $c['Pupa_Edad']): ?>
                        · <?php echo ucfirst(h($c['Pupa_Edad'])) ?>
                      <?php endif; ?>
                    </span>
                  </td>
                  <td><strong><?php echo (int)$c['Cantidad'] ?></strong></td>
                  <td><?php echo h($c['Fecha_Ingreso']) ?></td>
                  <td><?php echo h($c['Fecha_Siguiente_Transicion']) ?></td>
                  <td>
                    <span class="badge" data-countdown="<?php echo h($c['Fecha_Siguiente_Transicion']) ?>">—</span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
// Mostrar/ocultar edad de pupa
const etapaSel = document.getElementById('etapaSelect');
const pupaGroup = document.getElementById('pupaEdadGroup');
if (etapaSel){
  etapaSel.addEventListener('change', ()=>{
    pupaGroup.style.display = (etapaSel.value==='Pupa') ? 'block' : 'none';
    if (etapaSel.value!=='Pupa') document.getElementById('pupaEdad').value='';
  });
}

// Cuenta regresiva vivo
function startCountdowns(){
  const items = document.querySelectorAll('[data-countdown]');
  function fmt(n){ return n.toString().padStart(2,'0'); }
  function tick(){
    const now = new Date().getTime();
    items.forEach(el=>{
      const target = new Date(el.dataset.countdown.replace(' ','T')).getTime();
      let diff = Math.max(0, target - now);
      const d = Math.floor(diff / (1000*60*60*24)); diff -= d*24*60*60*1000;
      const h = Math.floor(diff / (1000*60*60)); diff -= h*60*60*1000;
      const m = Math.floor(diff / (1000*60)); diff -= m*60*1000;
      const s = Math.floor(diff / 1000);
      el.textContent = `${d}d ${fmt(h)}h ${fmt(m)}m ${fmt(s)}s`;
    });
  }
  tick(); setInterval(tick, 1000);
}
startCountdowns();
</script>

<?php
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
?>
