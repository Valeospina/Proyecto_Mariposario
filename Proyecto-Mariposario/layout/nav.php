<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="preloader">
    <div class="loader">
        <div class="loader-outter"></div>
        <div class="loader-inner"></div>

        <div class="indicator"> 
            <svg width="32px" height="32px" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <path d="M32 32 C22 20, 10 40, 28 40" fill="none" stroke="#ffffff" stroke-width="2"/>
                
                    <path d="M32 32 C42 20, 54 40, 36 40" fill="none" stroke="#ffffff" stroke-width="2"/>
                
                    <path d="M32 32 C18 14, 4 34, 24 36" fill="none" stroke="#80B78D" stroke-width="2">
                        <animate attributeName="d" dur="1s" repeatCount="indefinite"
                            values="
                                M32 32 C18 14, 4 34, 24 36;
                                M32 32 C16 16, 2 32, 22 36;
                                M32 32 C18 14, 4 34, 24 36"/>
                    </path>
                
                    <path d="M32 32 C46 14, 60 34, 40 36" fill="none" stroke="#80B78D" stroke-width="2">
                        <animate attributeName="d" dur="1s" repeatCount="indefinite"
                            values="
                                M32 32 C46 14, 60 34, 40 36;
                                M32 32 C48 16, 62 32, 42 36;
                                M32 32 C46 14, 60 34, 40 36"/>
                    </path>
                
                    <line x1="32" y1="30" x2="32" y2="40" stroke="#ffffff" stroke-width="2" />
                </g>
            </svg>
        </div>
    </div>
</div>
<header class="header">
    <div class="topbar">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 col-md-5 col-12">
                    <ul class="top-link">
                        <li>
                            <a href="usuario.html" style="text-decoration: none;">
                                <i class="fas fa-user" style="font-size: 18px; color: #80B78D; padding: 6px;"></i>
                                <span style="color: #2C2D3F;">
                                    <?php 
                                    // Esta parte asume que $_SESSION['user_name'] se establece en login.php
                                    // y que 'usuario.html' es la página del perfil del usuario.
                                    echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); 
                                    ?>
                                </span>
                            </a>
                        </li>
                    </ul>
                    </div>
                <div class="col-lg-6 col-md-7 col-12">
                    <ul class="top-contact">
                        <li><i class="fa fa-phone"></i>+506 8888 8888</li>
                        <li><i class="fa fa-envelope"></i><a href="mailto:info@mariposario.com">info@mariposario.com</a></li>
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
                    <div class="col-lg-7 col-md-9 col-12">
                        <div class="main-menu">
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
                    <div class="col-lg-2 col-12">
                        <div class="get-quote">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="logout.php" class="btn">Cerrar Sesión</a>
                            <?php else: ?>
                                <a href="login.html" class="btn">Iniciar Sesión</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </header>