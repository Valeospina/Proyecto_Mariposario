<!doctype html>
<html class="no-js" lang="zxx">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="keywords" content="Site keywords here">
        <meta name="description" content="">
        <meta name='copyright' content=''>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <title>Jardin De Orquideas - Orquideas</title> <link rel="icon" href="img/favicon.png">
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
        <link rel="stylesheet" href="css/tienda.css"> <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="css/responsive.css">

    </head>

    <body class="user">

        <?php include 'layout/nav2.php'; ?>
        <body class="orquidea">

            <?php
                include 'DB.php'; // Include the database connection file
                $searchTerm = isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '';
                $selectedCategory = 'Orquidea'; // Fijo para esta página
            ?>
                <div id="Orquidea-facts" class="Orquidea-facts section">
                    <div class="overlay-form-container">
                        <form method="GET" action="Orquideas.php">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-md-8 col-lg-5">
                                    <div class="form-group">
                                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre de producto"
                                            style="height: 45px; padding: 6px 12px;" value="<?php echo $searchTerm; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <div class="form-group">
                                        <button class="btn btn-primary w-100" type="submit">
                                            <i class="fa fa-search"></i> <strong>Buscar</strong>
                                        </button>
                                     </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <section class="products section">
            <div class="container">
                <div class="row">
                    <?php
                    // Check if the connection is successful before proceeding
                    if ($conn && !$conn->connect_error) { // Ensure $conn is not null and connection is good
                        // Build the SQL query
                        $sql = "SELECT ID_Producto, Nombre, Descripcion, Precio, Imagen_URL FROM producto WHERE 1";

                        $param_types = ''; // Stores the types of parameters (e.g., 's' for string)
                        $param_values = []; // Stores the actual parameter values

                        $conditions = [];

                        // Add search term condition
                        if (!empty($searchTerm)) {
                            $conditions[] = "(Nombre LIKE ? OR Descripcion LIKE ?)";
                            $param_types .= 'ss'; // 's' for string
                            $param_values[] = '%' . $searchTerm . '%';
                            $param_values[] = '%' . $searchTerm . '%';
                        }

                        // Add category filter condition for 'Orquidea' (fixed for this page)
                        // It's already fixed in $selectedCategory, so just add the condition
                        $conditions[] = "Categoria = ?";
                        $param_types .= 's';
                        $param_values[] = $selectedCategory;
                        
                        // !!! IMPORTANTE: Añadir condición para mostrar solo productos activos en el catálogo !!!
                        $conditions[] = "Activo_Catalogo = 1";


                        // Append conditions to SQL query
                        if (!empty($conditions)) {
                            $sql .= " AND " . implode(" AND ", $conditions);
                        }

                        try {
                            // Prepare the statement
                            // This is line 170 where the Fatal error was occurring if $conn was null
                            $stmt = $conn->prepare($sql);

                            if ($stmt === false) {
                                throw new Exception("Error al preparar la consulta: " . $conn->error);
                            }

                            // Bind parameters if there are any
                            if (!empty($param_values)) {
                                // Use call_user_func_array for dynamic binding
                                // We need to create an array of references for bind_param
                                $bind_params_array = [];
                                $bind_params_array[] = $param_types;
                                foreach ($param_values as $key => $value) {
                                    $bind_params_array[] = &$param_values[$key]; // Pass by reference
                                }
                                call_user_func_array([$stmt, 'bind_param'], $bind_params_array);
                            }

                            // Execute the statement
                            $stmt->execute();

                            // Get the result set
                            $result = $stmt->get_result();

                            // Fetch all rows into an array
                            $productos = [];
                            if ($result) { // Check if get_result returned a valid result object
                                while ($row = $result->fetch_assoc()) {
                                    $productos[] = $row;
                                }
                            }
                            
                            // Close the statement
                            $stmt->close();

                            if (empty($productos)) {
                                echo '<div class="col-12 text-center py-5">';
                                echo '<h3>No se encontraron productos que coincidan con tu búsqueda.</h3>';
                                echo '</div>';
                            } else {
                                foreach ($productos as $producto) {
                                    ?>
                                    <div class="col-lg-3 col-md-4 col-12 mb-4">
                                        <div class="single-product shadow rounded p-3 h-100">
                                            <div class="product-img">
                                                <?php
                                                $display_image_src = htmlspecialchars($producto['Imagen_URL']);
                                                // Si la imagen es una ruta local guardada como 'uploads/productos/...',
                                                // y Orquideas.php está en la raíz, la ruta ya es correcta.
                                                // Solo se necesita ajustar si la estructura cambia o la URL no es externa.
                                                // La lógica de `str_replace('../', '', $destination_path)` en add/edit_product.php
                                                // asegura que se guarda 'uploads/productos/...' en la DB, lo cual es directamente usable desde la raíz.
                                                ?>
                                                <img src="<?php echo $display_image_src; ?>" alt="<?php echo htmlspecialchars($producto['Nombre']); ?>" class="img-fluid">
                                            </div>
                                            <div class="product-content">
                                                <h4><strong><?php echo htmlspecialchars($producto['Nombre']); ?></strong></h4>
                                                <p class="text-muted"><?php echo htmlspecialchars($producto['Descripcion']); ?></p>
                                                <div class="product-price"><span><strong>₡<?php echo number_format($producto['Precio'], 2, ',', '.'); ?></strong></span></div>
                                                <button type="button" class="btn btn-primary agregar-carrito mt-2"
                                                    data-id="<?php echo htmlspecialchars($producto['ID_Producto']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($producto['Nombre']); ?>"
                                                    data-precio="<?php echo htmlspecialchars($producto['Precio']); ?>"
                                                    data-imagen-url="<?php echo htmlspecialchars($producto['Imagen_URL']); ?>"> <i class="fa fa-cart-plus"></i> Agregar al carrito
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
                        // Display a message if the database connection failed
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
                                <p>Somos un proyecto dedicado a la conservación y apreciación de Orquideas y orquídeas en Costa Rica. 
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
                                <p>Suscríbete para recibir noticias sobre nuestras Orquideas, orquídeas y próximos eventos especiales.</p>
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
                                <p>© 2025 Orquideas y Orquídeas | Todos los derechos reservados</p>
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
    </body>
</html>