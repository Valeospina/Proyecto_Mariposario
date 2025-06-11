<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="header">
	<div class="topbar">
		<div class="container">
			<div class="row">
				<div class="col-lg-6 col-md-5 col-12">
					<ul class="top-link">
						<li>
							<a href="usuario.html" style="text-decoration: none;">
								<i class="fas fa-user" style="font-size: 18px; color: #80B78D; padding: 6px;"></i>
								<span style="color: #2C2D3F;">Usuario</span>
							</a>
						</li>
						<li class="admin"><a href="admin.html">Admin</a></li>
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
									<li class="<?= ($currentPage == 'mariposas.php') ? 'active' : '' ?>">
										<a href="mariposas.php">Mariposas</a>
									</li>
									<li class="<?= ($currentPage == 'orquideas.php') ? 'active' : '' ?>">
										<a href="orquideas.php">Orquideas</a>
									</li>
									<li class="<?= ($currentPage == 'eventos.php') ? 'active' : '' ?>">
										<a href="eventos.php">Eventos</a>
									</li>
								</ul>
							</nav>
						</div>
					</div>
					<div class="col-lg-2 col-12">
						<div class="get-quote">
							<a href="carrito.php" class="btn btn-carrito" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: transparent; box-shadow: none;">
								<i class="fa fa-shopping-cart icono-carrito" style="color: #42764D; font-size: 20px;"></i>
								<span id="cart-item-count" class="badge badge-pill badge-danger" style="position: absolute; top: -5px; right: -5px; background-color: #dc3545; color: white; font-size: 10px; padding: 3px 6px; border-radius: 50%;">0</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>
