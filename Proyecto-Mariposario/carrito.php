<?php
session_start(); // Start the session at the very beginning of the script

// Initialize the cart if it doesn't exist in the session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// --- Handle "Add to Cart" requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    $nombre = htmlspecialchars($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $cantidad = 1; // Default quantity when adding an item

    // Check if the product is already in the cart
    if (isset($_SESSION['cart'][$producto_id])) {
        // If it exists, just increase the quantity
        $_SESSION['cart'][$producto_id]['cantidad'] += $cantidad;
    } else {
        // If not, add the new product to the cart
        $_SESSION['cart'][$producto_id] = [
            'nombre' => $nombre,
            'precio' => $precio,
            'cantidad' => $cantidad
        ];
    }

    // Optional: Redirect back to the tienda.html or mariposas.html after adding
    // This prevents form resubmission on page refresh
    header('Location: carrito.php'); // Redirect to show the updated cart
    exit();
}

// --- Handle "Remove from Cart" requests ---
if (isset($_GET['remove_item'])) {
    $remove_id = intval($_GET['remove_item']);
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]); // Remove the item from the cart
    }
    header('Location: carrito.php'); // Redirect to show the updated cart
    exit();
}

// --- Handle "Update Quantity" requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $update_id = intval($_POST['update_quantity']);
    $new_quantity = intval($_POST['new_quantity']);

    if (isset($_SESSION['cart'][$update_id])) {
        if ($new_quantity > 0) {
            $_SESSION['cart'][$update_id]['cantidad'] = $new_quantity;
        } else {
            // If quantity is 0 or less, remove the item
            unset($_SESSION['cart'][$update_id]);
        }
    }
    header('Location: carrito.php'); // Redirect to show the updated cart
    exit();
}


// Calculate total cart value
$total_carrito = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_carrito += $item['precio'] * $item['cantidad'];
}

?>

<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="keywords" content="Site keywords here" />
    <meta name="description" content="" />
    <meta name="copyright" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <title>Carrito de Compras - Jardin De Mariposas</title>

    <link rel="icon" href="img/favicon.png" />
    <link rel="stylesheet" href="./css/tienda.css" />

    <link
        href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap"
        rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/nice-select.css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <link rel="stylesheet" href="css/icofont.css" />
    <link rel="stylesheet" href="css/slicknav.min.css" />
    <link rel="stylesheet" href="css/owl-carousel.css" />
    <link rel="stylesheet" href="css/datepicker.css" />
    <link rel="stylesheet" href="css/animate.min.css" />
    <link rel="stylesheet" href="css/magnific-popup.css" />
    <link rel="stylesheet" href="css/tienda.css" />

    <link rel="stylesheet" href="css/normalize.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/responsive.css" />

    <style>
        /* Estilos específicos para el carrito */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .cart-table th,
        .cart-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .cart-table th {
            background-color: #f2f2f2;
        }

        .cart-total {
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
        }

        .btn-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-remove:hover {
            background-color: #c82333;
        }

        .quantity-input {
            width: 60px;
            padding: 5px;
            text-align: center;
        }
    </style>
</head>

<body class="user">
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
                                <a href="index.html"><img src="img/logo.png" alt="Logo Mariposario" /></a>
                            </div>
                            <div class="mobile-nav"></div>
                            </div>
                        <div class="col-lg-7 col-md-9 col-12">
                            <div class="main-menu">
                                <nav class="navigation">
                                    <ul class="nav menu">
                                        <li><a href="index.html">Inicio</a></li>
                                        <li><a href="tienda.html">Volver a tienda</a></li>
                                        <li class="active"><a href="mariposas.html">Mariposas</a></li>
                                    </ul>
                                </nav>
                            </div>
                            </div>
                        <div class="col-lg-2 col-12">
                            <div class="get-quote">
                                <a href="carrito.php" class="btn btn-carrito"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: transparent; box-shadow: none;">
                                    <i class="fa fa-shopping-cart icono-carrito" style="color: #42764D; font-size: 20px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </header>
    <div class="breadcrumbs overlay">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-12">
                    <div class="breadcrumbs-content">
                        <h1 class="page-title">Carrito de Compras</h1>
                        <p>Revisa y gestiona los productos en tu carrito.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="shopping-cart section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="cart-main">
                        <div class="cart-table-wrapper">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Precio Unitario</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($_SESSION['cart'])): ?>
                                        <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                                            <tr>
                                                <td><?php echo $item['nombre']; ?></td>
                                                <td>$<?php echo number_format($item['precio'], 2); ?></td>
                                                <td>
                                                    <form method="POST" action="carrito.php" style="display:inline-block;">
                                                        <input type="hidden" name="update_quantity" value="<?php echo $id; ?>">
                                                        <input type="number" name="new_quantity" value="<?php echo $item['cantidad']; ?>" min="1" class="quantity-input" onchange="this.form.submit()">
                                                    </form>
                                                </td>
                                                <td>$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                                                <td>
                                                    <a href="carrito.php?remove_item=<?php echo $id; ?>" class="btn-remove">Eliminar</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center;">Tu carrito está vacío.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="cart-total">
                            Total: $<?php echo number_format($total_carrito, 2); ?>
                        </div>
                        <div class="shopping-cart-button">
                            <div class="row">
                                <div class="col-md-6 col-12 text-left">
                                    <a href="mariposas.php" class="btn btn-primary">Continuar Comprando</a>
                                </div>
                                <div class="col-md-6 col-12 text-right">
                                    <?php if (!empty($_SESSION['cart'])): ?>
                                        <a href="pago.html" class="btn btn-success">Proceder al Pago</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer id="footer" class="footer">
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Sobre Nosotros</h2>
                            <p>Somos un proyecto dedicado a la conservación y apreciación de mariposas y orquídeas en Costa Rica. Promovemos el turismo sostenible y la educación ambiental.</p>
                            <ul class="social">
                                <li><a href="#"><i class="icofont-facebook"></i></a></li>
                                <li><a href="#"><i class="icofont-instagram"></i></a></li>
                                <li><a href="#"><i class="icofont-twitter"></i></a></li>
                            </ul>
                            </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer f-link">
                            <h2>Enlaces Rápidos</h2>
                            <div class="row">
                                <div class="col-12">
                                    <ul>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Inicio</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Reservaciones</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Galería</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Eventos</a></li>
                                        <li><a href="#"><i class="fa fa-caret-right" aria-hidden="true"></i>Contáctanos</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Horario de Atención</h2>
                            <p>Visítanos para vivir una experiencia rodeado de naturaleza y belleza.</p>
                            <ul class="time-sidual">
                                <li class="day">Lunes - Viernes <span>8:00 - 17:00</span></li>
                                <li class="day">Sábado <span>9:00 - 16:00</span></li>
                                <li class="day">Domingo <span>Cerrado</span></li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-12">
                        <div class="single-footer">
                            <h2>Boletín</h2>
                            <p>Suscríbete para recibir noticias sobre nuestras mariposas, orquídeas y próximos eventos especiales.</p>
                            <form action="#" method="get" target="_blank" class="newsletter-inner">
                                <input name="email" placeholder="Tu correo electrónico" class="common-input" required type="email">
                                <button class="button"><i class="icofont icofont-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="copyright-content">
                            <p>© 2025 Mariposas y Orquídeas | Todos los derechos reservados</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </footer>
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate-3.0.0.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/easing.js"></script>
    <script src="js/colors.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap-datepicker.js"></script>
    <script src="js/jquery.nav.js"></script>
    <script src="js/slicknav.min.js"></script>
    <script src="js/jquery.scrollUp.min.js"></script>
    <script src="js/niceselect.js"></script>
    <script src="js/tilt.jquery.min.js"></script>
    <script src="js/owl-carousel.js"></script>
    <script src="js/jquery.counterup.min.js"></script>
    <script src="js/steller.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>