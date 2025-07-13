<?php
// Asegúrate de que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtén el nombre de la página actual para el estado "active" del menú
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <ul class="top-link justify-content-end">
                        <li>
                            <a href="usuario.php" class="user-info-link">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></span>
                            </a>
                        </li>
                        <li class="separator">|</li>
                        <li>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="logout.php" class="btn-topbar-action">Cerrar Sesión</a>
                            <?php else: ?>
                                <a href="logind.php" class="btn-topbar-action">Iniciar Sesión</a>
                            <?php endif; ?>
                        </li>
                        <li class="separator">|</li>
                        <li>
                            <a href="carrito.php" class="btn-carrito-topbar">
                                <i class="fa fa-shopping-cart"></i>
                                <span id="cart-item-count" class="badge badge-pill badge-danger">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="header-inner">
        <div class="container">
            <div class="inner">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-12">
                        <div class="logo">
                            <a href="index.php"><img src="img/logo.png" alt="Logo Mariposario"></a>
                        </div>
                        <div class="mobile-nav"></div>
                    </div>
                    <div class="col-lg-9 col-md-9 col-12"> <div class="main-menu">
                            <nav class="navigation">
                                <ul class="nav menu">
                                    <li class="<?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                                        <a href="index.php">Inicio</a>
                                    </li>
                                    <li class="<?= ($currentPage == 'tienda.php') ? 'active' : '' ?>">
                                        <a href="tienda.php">Tienda</a>
                                    </li>
                                    <li class="<?= ($currentPage == 'contact.php') ? 'active' : '' ?>">
                                        <a href="contact.php">Contacto</a>
                                    </li>                                    
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>