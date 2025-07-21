<!doctype html>
<html class="no-js" lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="Site keywords here">
    <meta name="description" content="">
    <meta name='copyright' content=''>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Jardin De Mariposas - Mariposas</title>
    <link rel="icon" href="img/favicon.png">

    <link href="https://fonts.googleapis.com/css?family=Poppins:200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/nice-select.css"> 
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/icofont.css">
    <link rel="stylesheet" href="css/slicknav.min.css">
    <link rel="stylesheet" href="css/owl-carousel.css">
    <link rel="stylesheet" href="css/datepicker.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    
    <link rel="stylesheet" href="css/tienda.css"> 
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>

<body class="orquidea">
    <?php include 'layout/nav2.php'; ?>
    
    <?php
        include 'DB.php'; // Include the database connection file
        $searchTerm = isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '';
        $selectedCategory = 'Orquidea'; // Fijo para esta página
    ?>

    <section class="search-hero-section">
        <div class="search-container-custom">
            <form method="GET" action="Orquideas.php" class="search-form-custom">
                <input type="text" name="buscar" class="search-input-custom" 
                        placeholder="Ej. Orquídea Phalaenopsis, Orquídea Vanda..." 
                        aria-label="Buscar..." value="<?php echo $searchTerm; ?>">
                
                <button type="submit" class="search-button-custom">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </section>

    <section class="products-catalog-section">
        <div class="catalog-wrapper">
            <div class="row">
                <?php
                if ($conn && !$conn->connect_error) { 
                    $sql = "SELECT ID_Producto, Nombre, Descripcion, Precio, Imagen_URL FROM producto WHERE 1";
                    $param_types = '';
                    $param_values = [];
                    $conditions = [];

                    if (!empty($searchTerm)) {
                        $conditions[] = "(Nombre LIKE ? OR Descripcion LIKE ?)";
                        $param_types .= 'ss';
                        $param_values[] = '%' . $searchTerm . '%';
                        $param_values[] = '%' . $searchTerm . '%';
                    }

                    $conditions[] = "Categoria = ?";
                    $param_types .= 's';
                    $param_values[] = $selectedCategory;
                    
                    $conditions[] = "Activo_Catalogo = 1";

                    if (!empty($conditions)) {
                        $sql .= " AND " . implode(" AND ", $conditions);
                    }

                    try {
                        $stmt = $conn->prepare($sql);

                        if ($stmt === false) {
                            throw new Exception("Error al preparar la consulta: " . $conn->error);
                        }

                        if (!empty($param_values)) {
                            $bind_params_array = [];
                            $bind_params_array[] = $param_types;
                            foreach ($param_values as $key => $value) {
                                $bind_params_array[] = &$param_values[$key]; 
                            }
                            call_user_func_array([$stmt, 'bind_param'], $bind_params_array);
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();
                        $productos = [];
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                $productos[] = $row;
                            }
                        }
                        
                        $stmt->close();

                        if (empty($productos)) {
                            echo '<div class="col-12 text-center py-5">';
                            echo '<h3>No se encontraron productos que coincidan con tu búsqueda.</h3>';
                            echo '</div>';
                        } else {
                        foreach ($productos as $producto) {
                            ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
                                <div class="product-card-custom position-relative">
                                    <div class="product-image-container-custom">
                                        <?php $display_image_src = htmlspecialchars($producto['Imagen_URL']); ?>
                                        <img src="<?php echo $display_image_src; ?>" alt="<?php echo htmlspecialchars($producto['Nombre']); ?>" class="product-image-custom">
                                    </div>
                                    <div class="product-content-custom">
                                        <!-- Enlace sobre el nombre del producto -->
                                        <a href="producto.php?id=<?php echo $producto['ID_Producto']; ?>" class="stretched-link text-decoration-none">
                                            <h4 class="product-name-custom"><strong><?php echo htmlspecialchars($producto['Nombre']); ?></strong></h4>
                                        </a>
                                        <p class="product-description-custom"><?php echo htmlspecialchars($producto['Descripcion']); ?></p>
                                       <div class="product-link-custom">
                                            <a href="producto.php?id=<?php echo $producto['ID_Producto']; ?>" class="text-dark text-decoration-none small">
                                                 Ver detalles
                                            </a>
                                        </div>
                                        <!-- Precio a la izquierda, Ver detalles a la derecha -->
                                        <div class="product-details-custom d-flex justify-content-between align-items-center mt-2">
                                            <div class="product-price-custom">
                                                <span>₡<?php echo number_format($producto['Precio'], 2, ',', '.'); ?></span>
                                            </div>
                                        </div>

                                        <!-- Botón de agregar al carrito -->
                                        <button type="button" class="add-to-cart-button-custom agregar-carrito mt-2"
                                            data-id="<?php echo htmlspecialchars($producto['ID_Producto']); ?>"
                                            data-nombre="<?php echo htmlspecialchars($producto['Nombre']); ?>"
                                            data-precio="<?php echo htmlspecialchars($producto['Precio']); ?>"
                                            data-imagen-url="<?php echo htmlspecialchars($producto['Imagen_URL']); ?>">
                                            AÑADIR AL CARRITO
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }

                        }
                    } catch (Exception $e) {
                        echo '<div class="col-12 text-center py-5">';
                        echo '<h3>Error al cargar productos: ' . $e->getMessage() . '</h3>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="col-12 text-center py-5">';
                    echo '<h3>No se pudo establecer conexión con la base de datos. Por favor, inténtelo de nuevo más tarde.</h3>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

</body>

<footer id="footer" class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="single-footer">
                        <h2>Sobre Nosotros</h2>
                        <p>Somos un proyecto dedicado a la conservación y apreciación de orquídeas en Costa Rica. 
                            Promovemos el turismo sostenible y la educación ambiental.</p>
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
                        <p>Suscríbete para recibir noticias sobre nuestras orquídeas y próximos eventos especiales.</p>
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
                        <p>© 2025 Orquídeas | Todos los derechos reservados</p>
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
    <script src="js/cart_interaction.js"></script>
</html>