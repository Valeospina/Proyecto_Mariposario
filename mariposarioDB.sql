-- ====================================================================
-- SCRIPT DE BASE DE DATOS PARA EL SISTEMA DEL MARIPOSARIO
-- Creado: 07 de Julio de 2025
-- Este script consolida la estructura de tablas, relaciones,
-- y datos iniciales para soportar el sistema de gestión de pedidos,
-- pagos fuera de línea, facturación electrónica (simulada),
-- y seguimiento de estados.
-- ====================================================================

-- Eliminar la base de datos si existe (¡CUIDADO! Esto borrará todos los datos existentes)
DROP DATABASE IF EXISTS mariposarioDB;

-- Crear la base de datos
CREATE DATABASE mariposarioDB;

-- Seleccionar la base de datos para su uso
USE mariposarioDB;

-- ====================================================================
-- 1. ESTRUCTURA DE TABLAS
-- ====================================================================

-- Tabla: Rol
-- Define los diferentes roles de usuario en el sistema.
CREATE TABLE Rol (
    ID_Rol INT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Tipo_Notificacion VARCHAR(100), -- Ej: 'Email', 'SMS', 'Email y SMS'
    Descripcion VARCHAR(300)
);

-- Tabla: Usuario
-- Almacena la información general para todos los usuarios, incluyendo clientes, administradores y empleados.
CREATE TABLE Usuario (
    ID_Usuario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Rol INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100),
    Correo VARCHAR(255) UNIQUE NOT NULL,
    Contrasena VARCHAR(255) NOT NULL, -- Almacenar contraseñas hasheadas (ej: bcrypt)
    Telefono VARCHAR(20),
    Direccion TEXT,
    Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','inactivo') DEFAULT 'activo',
    FOREIGN KEY (ID_Rol) REFERENCES Rol(ID_Rol)
);

-- Tabla: Empleado
-- Detalles específicos para empleados, vinculados a la tabla Usuario.
CREATE TABLE Empleado (
    ID_Empleado INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100),
    Correo VARCHAR(100),
    Salario DECIMAL(10,2),
    Rol VARCHAR(50), -- Rol específico del empleado (ej: 'Gerente', 'Vendedor', 'Guía')
    Horario VARCHAR(255) DEFAULT 'No especificado', -- Horario de trabajo del empleado
    Fecha_Contratacion DATE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Horario
-- Horarios de trabajo detallados por empleado y día.
CREATE TABLE Horario (
    ID_Horario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Dia_Semana VARCHAR(20) NOT NULL, -- Ej: 'Lunes', 'Martes'
    Hora_Entrada TIME,
    Hora_Salida TIME,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Asistencia
-- Registros de asistencia de empleados.
CREATE TABLE Asistencia (
    ID_Asistencia INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha DATE NOT NULL,
    Hora_Entrada DATETIME,
    Hora_Salida DATETIME,
    Observaciones TEXT,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Pago_Empleado
-- Registros de pagos realizados a empleados.
CREATE TABLE Pago_Empleado (
    ID_Pago INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha_Pago DATE NOT NULL,
    Monto DECIMAL(10,2) NOT NULL,
    Metodo_Pago VARCHAR(50), -- Ej: 'Transferencia', 'Cheque', 'Efectivo'
    Detalle VARCHAR(300),
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Producto
-- Productos disponibles para la venta (mariposas, orquídeas, etc.).
CREATE TABLE Producto (
    ID_Producto INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(255) NOT NULL,
    Categoria VARCHAR(100),
    Descripcion TEXT,
    Precio DECIMAL(10, 2) NOT NULL,
    Stock INT,
    Imagen_URL TEXT,
    Fecha_Reposicion DATE,
    Activo_Catalogo BOOLEAN DEFAULT TRUE,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Inventario
-- Gestiona el stock de productos en diferentes ubicaciones/lotes.
CREATE TABLE Inventario (
    ID_Inventario INT AUTO_INCREMENT PRIMARY KEY,
    ID_Producto INT NOT NULL,
    SKU VARCHAR(100) UNIQUE, -- SKU para este ítem/lote específico de inventario
    Stock_Actual INT NOT NULL DEFAULT 0,
    Stock_Minimo INT DEFAULT 0, -- Para alertas de bajo stock para esta ubicación/lote
    Ubicacion VARCHAR(100), -- Ej: 'Mariposario 1', 'Tienda Principal', 'Online'
    Notas TEXT, -- Para cualquier nota específica del lote o ubicación
    Fecha_Creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto) ON DELETE CASCADE
);

-- Tabla: Carrito
-- Carrito de compras persistente en la base de datos.
CREATE TABLE Carrito (
    ID_Carrito INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','finalizado','cancelado') DEFAULT 'activo',
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Carrito_Producto
-- Productos dentro del carrito de compras persistente.
CREATE TABLE Carrito_Producto (
    ID_Carrito INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT DEFAULT 1,
    PRIMARY KEY (ID_Carrito, ID_Producto),
    FOREIGN KEY (ID_Carrito) REFERENCES Carrito(ID_Carrito) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Pagos
-- Registro centralizado de todos los pagos realizados.
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaccion VARCHAR(50) NOT NULL UNIQUE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    id_cliente VARCHAR(50), -- Considerar vincular a ID_Usuario si siempre se refiere a un usuario interno
    total DECIMAL(10,2) NOT NULL
);

-- Tabla: Pedido
-- Pedidos de productos realizados por los usuarios (considerados como proformas).
CREATE TABLE Pedido (
    ID_Pedido INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    Total_Pedido DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    Estado_Pedido ENUM(
        'Pendiente de Pago',
        'Pago Confirmado',
        'Completado',
        'Cancelado',
        'Reembolsado'
    ) DEFAULT 'Pendiente de Pago',
    Numero_Proforma VARCHAR(50) UNIQUE NOT NULL,
    Observaciones TEXT NULL DEFAULT NULL,
    Puntos_Canjeados INT DEFAULT 0,
    Monto_Canjeado DECIMAL(10, 2) DEFAULT 0.00,
    Metodo_Pago VARCHAR(50) NOT NULL DEFAULT 'PayPal',
    Estado_Envio ENUM(
        'Pedido Recibido',
        'En Preparacion',
        'En Transito',
        'Entregado',
        'Cancelado Envio'
    ) DEFAULT 'Pedido Recibido',
    Fecha_Vencimiento_Proforma DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Detalle_Pedido
-- Detalles de los productos en cada pedido.
-- Nota: Esta tabla parece ser un duplicado de Pedido_Producto. Se mantiene si hay una razón específica para tenerla separada,
-- de lo contrario, se recomienda usar solo una (Pedido_Producto).
CREATE TABLE Detalle_Pedido (
    ID_Detalle INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Pedido_Producto
-- Detalles de los productos en cada pedido - Usada como base para los detalles del pedido.
CREATE TABLE Pedido_Producto (
    ID_Pedido INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio_Unitario DECIMAL(10,2) NOT NULL,
    Descuento_Aplicado DECIMAL(10,2) DEFAULT 0.00,
    PRIMARY KEY (ID_Pedido, ID_Producto),
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Estado_Pedido
-- Historial de estados de un pedido.
CREATE TABLE Estado_Pedido (
    ID_Estado INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT NOT NULL,
    Estado VARCHAR(50) NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
);

-- Tabla: Detalle_Pago
-- Detalles de los productos incluidos en un pago.
CREATE TABLE detalle_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL,
    id_producto INT NOT NULL,
    nombre_producto VARCHAR(100),
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pago) REFERENCES pagos(id) ON DELETE CASCADE
);

-- Tabla: Factura
-- Facturas Electrónicas Oficiales.
CREATE TABLE Factura (
    ID_Factura INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT UNIQUE NOT NULL,
    id_pago INT, -- Añadido y vinculado a la tabla `pagos`
    Fecha_Factura DATETIME DEFAULT CURRENT_TIMESTAMP,
    Subtotal DECIMAL(10,2) NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    Metodo_Pago VARCHAR(100),
    Numero_Factura VARCHAR(100) UNIQUE,
    XML_Enviado TEXT,
    XML_Respuesta TEXT,
    Estado_Hacienda ENUM('Pendiente Validacion', 'Enviado', 'Aceptada', 'Rechazada', 'Anulada') DEFAULT 'Pendiente Validacion',
    Clave_Numerica VARCHAR(50) UNIQUE,
    Ruta_PDF_Factura VARCHAR(255),
    Fecha_Envio_Hacienda DATETIME,
    Fecha_Respuesta_Hacienda DATETIME,
    Referencia_Pago VARCHAR(255),
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido),
    FOREIGN KEY (id_pago) REFERENCES pagos(id)
);

-- Tabla: Factura_Producto
-- Detalles de los productos en cada factura.
CREATE TABLE Factura_Producto (
    ID_Factura INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio_Unitario DECIMAL(10, 2) NOT NULL,
    Descuento_Aplicado DECIMAL(10, 2) DEFAULT 0.00,
    PRIMARY KEY (ID_Factura, ID_Producto),
    FOREIGN KEY (ID_Factura) REFERENCES Factura(ID_Factura) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Puntos_Usuario
-- Puntos de lealtad actuales de cada usuario.
CREATE TABLE Puntos_Usuario (
    ID_Puntos INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT UNIQUE NOT NULL,
    Puntos_Actuales INT DEFAULT 0,
    Fecha_Expiracion DATE,
    Notificado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Historial_Puntos
-- Registro de transacciones de puntos.
CREATE TABLE Historial_Puntos (
    ID_Historial INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Accion ENUM('Ganado','Canjeado') NOT NULL,
    Monto INT NOT NULL,
    Descripcion TEXT,
    ID_Referencia INT,
    Tipo_Referencia VARCHAR(50),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Evento
-- Eventos o actividades del mariposario.
CREATE TABLE Evento (
    ID_Evento INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT NOT NULL,
    Precio DECIMAL(10, 2) NOT NULL,
    Fecha DATE NOT NULL,
    Hora TIME,
    Ubicacion VARCHAR(255),
    Imagen_URL VARCHAR(255) NOT NULL
);

-- Tabla: Reserva
-- Reservas para eventos.
CREATE TABLE Reserva (
    ID_Reserva INT PRIMARY KEY AUTO_INCREMENT,
    ID_Evento INT NOT NULL,
    ID_Usuario INT,
    Cantidad_Personas INT NOT NULL,
    Fecha_Reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('Pendiente', 'Aprobada', 'Cancelada') DEFAULT 'Pendiente',
    Telefono VARCHAR(20),
    Correo VARCHAR(100),
    Descripcion TEXT,
    Asistio TINYINT(1) NOT NULL DEFAULT 0, -- Columna para registrar asistencia
    FOREIGN KEY (ID_Evento) REFERENCES Evento(ID_Evento),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Consulta
-- Soporte al Cliente (Chat o Correo).
CREATE TABLE Consulta (
    ID_Consulta INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Tema ENUM('Consulta','Reclamo','Sugerencia') NOT NULL,
    Estado ENUM('Pendiente','Respondido','Cerrado') DEFAULT 'Pendiente',
    Canal ENUM('Chat','Correo') DEFAULT 'Chat',
    Mensajes JSON NULL,
    Ultima_Actividad DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);



-- Tabla: Notificacion
-- Notificaciones generales del sistema.
CREATE TABLE Notificacion (
    ID_Notificacion INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Tipo_Notificacion VARCHAR(100),
    Mensaje TEXT NOT NULL,
    Fecha_Notificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Leida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Bitacora
-- Registro centralizado de actividades del sistema y auditoría.
CREATE TABLE Bitacora (
    ID_Log INT AUTO_INCREMENT PRIMARY KEY,
    Fecha_Hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ID_Usuario INT,
    Tipo_Evento ENUM(
        'Pedido Creado',
        'Pedido Actualizado',
        'Pago Confirmado',
        'Factura Generada',
        'Factura Enviada Hacienda',
        'Factura Aceptada Hacienda',
        'Factura Rechazada Hacienda',
        'Stock Actualizado',
        'Usuario Logged In',
        'Usuario Logged Out',
        'Error Sistema',
        'Reserva Creada',
        'Reserva Aprobada',
        'Reserva Cancelada',
        'Producto Agregado',
        'Producto Actualizado',
        'Producto Eliminado',
        'Puntos Ganados',
        'Puntos Canjeados'
    ) NOT NULL,
    Descripcion TEXT NOT NULL,
    ID_Referencia INT,
    Tabla_Referencia VARCHAR(50),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Reseñas de Productos
-- Permite a los usuarios dejar reseñas y calificaciones para productos.
CREATE TABLE reseñas (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_Producto INT NOT NULL,
    Usuario VARCHAR(100) NOT NULL,
    Calificacion INT NOT NULL DEFAULT 0,
    Reseña TEXT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Producto) REFERENCES producto(ID_Producto)
);

-- Tabla: Reseñas de Eventos
-- Permite a los usuarios dejar reseñas y calificaciones para eventos.
CREATE TABLE resenas_evento (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_Evento INT NOT NULL,
    Usuario VARCHAR(100) NOT NULL,
    Calificacion INT NOT NULL DEFAULT 0,
    Reseña TEXT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Evento) REFERENCES Evento(ID_Evento)
);

-- ====================================================================
-- 2. INSERCIÓN DE DATOS INICIALES
-- ====================================================================

-- Insertar roles básicos
INSERT INTO Rol (ID_Rol, Nombre, Tipo_Notificacion, Descripcion) VALUES
(1, 'Administrador', 'Email y SMS', 'Usuario con acceso completo al sistema'),
(2, 'Cliente', 'Email', 'Usuario cliente que puede hacer compras y reservas'),
(3, 'Empleado', 'Email', 'Empleado del mariposario con acceso limitado');

-- Insertar usuarios de prueba
-- Contraseña para todos los usuarios: "123456" (hash bcrypt)
-- Puedes usar password_hash('123456', PASSWORD_DEFAULT) en PHP para generar estos hashes.
INSERT INTO Usuario (ID_Usuario, ID_Rol, Nombre, Apellido, Correo, Contrasena, Telefono, Direccion) VALUES
(1, 1, 'Carlos', 'Rodríguez', 'admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2234-5678', 'San José, Costa Rica'),
(2, 1, 'María', 'González', 'maria.admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2345-6789', 'Cartago, Costa Rica'),
(3, 3, 'Ana', 'Jiménez', 'ana.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2567-8901', 'Heredia, Costa Rica'),
(4, 3, 'Pedro', 'Vargas', 'pedro.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2678-9012', 'Puntarenas, Costa Rica'),
(5, 2, 'Laura', 'Castillo', 'laura.cliente@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2890-1234', 'San José, Escazú'),
(6, 2, 'Diego', 'Ramírez', 'diego.cliente@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2901-2345', 'Cartago, Paraíso'),
(7, 2, 'Valeria', 'Solano', 'valeria.cliente@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2012-3456', 'Alajuela, Atenas');

-- Insertar empleados (vinculados a los usuarios creados)
INSERT INTO Empleado (ID_Usuario, Nombre, Correo, Salario, Rol, Horario, Fecha_Contratacion) VALUES
(3, 'Ana Jiménez', 'ana.empleado@mariposario.com', 850000.00, 'Guía', 'L-V 8am-4pm', '2023-01-15'),
(4, 'Pedro Vargas', 'pedro.empleado@mariposario.com', 900000.00, 'Mantenimiento', 'M-S 7am-3pm', '2022-07-01');

-- Insertar mariposas a la tabla productos
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Hamadrias laodamia', 'Mariposa', 'La Hamadrias laodamia es conocida como "mariposa caracolera". Presenta un patrón de puntos y rayas en tonos negros y azules metálicos. Habita en zonas tropicales y subtropicales de América. Suele encontrarse cerca de árboles de Fabaceae, ya que sus orugas se alimentan de estas plantas. Posee un característico zumbido al volar.', 750.00, 10, 'https://static.inaturalist.org/photos/337837645/medium.jpg', '2025-07-15', TRUE),

('Mircelia cisniris', 'Mariposa', 'La Mircelia cisniris destaca por sus alas con patrones irregulares y tonos marrones y naranjas, que sirven como camuflaje. Es habitual en bosques húmedos y selvas tropicales, donde se alimenta del néctar de flores silvestres. Sus larvas suelen alimentarse de hojas tiernas de plantas trepadoras.', 990.00, 5, 'https://southcoastbotanicgarden.org/wp-content/uploads/2023/07/Shady-Spots-4.jpg', '2025-07-18', TRUE),

('Morpho', 'Mariposa', 'Las mariposas Morpho son reconocidas mundialmente por sus alas de un azul metálico brillante que reflejan la luz, lo que las hace únicas en el reino animal. Habitan selvas tropicales de América Central y del Sur, volando principalmente a nivel del sotobosque. Además de su belleza, desempeñan un papel importante en la polinización.', 600.00, 12, 'https://contexto.udlap.mx/wp-content/uploads/2021/11/mariposa.jpg', '2025-07-20', TRUE),

('Igna', 'Mariposa', 'La mariposa Igna es una especie exótica con alas decoradas con manchas y tonos vivos que van desde el naranja hasta el negro. Prefiere climas tropicales y es muy activa durante el día. Sus orugas se alimentan de plantas herbáceas, y los adultos se nutren principalmente del néctar de flores.', 750.00, 8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Agraulis_vanillae_at_Isla_Margarita.jpg/250px-Agraulis_vanillae_at_Isla_Margarita.jpg', '2025-08-20', TRUE),

('Catonephele mexicana', 'Mariposa', 'La Catonephele mexicana es una mariposa tropical de gran belleza, originaria de México y América Central. Sus alas negras presentan puntos anaranjados que forman un patrón distintivo. Prefiere áreas húmedas y sombreadas, y se alimenta de savia y frutas fermentadas en lugar de néctar.', 820.00, 8, 'https://pictureinsect.com/wiki-image/1080/153820706105196555.jpeg', '2025-07-25', TRUE),

('Siproeta stelenis', 'Mariposa', 'La Siproeta stelenis, conocida como mariposa "malaquita", debe su nombre a sus alas verdes con bordes negros, que imitan el color de la piedra semipreciosa. Es común en selvas y bosques húmedos, donde se alimenta del néctar y frutas en descomposición. Es muy apreciada por su elegante diseño natural.', 925.00, 6, 'https://static.inaturalist.org/photos/33050984/medium.jpeg', '2025-07-27', TRUE),

('Archaeprepona', 'Mariposa', 'El género Archaeprepona incluye mariposas tropicales muy llamativas, con alas de colores azules y verdes metálicos en la parte superior y marrón en la inferior, brindando camuflaje cuando reposan. Son rápidas en vuelo y suelen frecuentar frutas fermentadas y savia en bosques tropicales.', 870.00, 9, 'https://i.redd.it/a-one-spot-prepona-archaeoprepona-demophon-aka-banded-king-v0-23ufry6af6je1.jpg?width=8688&format=pjpg&auto=webp&s=1d012cc0bc98e278574d7a40aaf8e9ad76a1a75b', '2025-07-30', TRUE),

('Cónsul fabius', 'Mariposa', 'La mariposa Cónsul fabius se distingue por su color marrón con líneas y puntos que imitan hojas secas, lo que le otorga un camuflaje perfecto. Es frecuente en bosques húmedos y claros tropicales. Su comportamiento críptico la hace difícil de detectar en la naturaleza.', 650.00, 7, 'https://inaturalist-open-data.s3.amazonaws.com/photos/60274029/original.jpeg', '2025-08-05', TRUE);

-- Insertar Orquídeas a la tabla productos
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Copper Queen', 'Orquídea', 'La Copper Queen es una orquídea híbrida de gran elegancia, caracterizada por sus pétalos en tonos cobrizos y anaranjados que evocan el brillo metálico del cobre. Florece en climas cálidos y húmedos, ideal para cultivadores que buscan plantas vistosas. Sus flores pueden durar varias semanas y requieren luz brillante pero indirecta.', 16500, 10, 'https://cdn11.bigcommerce.com/s-pxrevx9n0f/images/stencil/1280x1280/products/4593/23281/Rby._copper_queen11__53919.1730150731.jpg?c=2', '2025-08-15', 1),

('Sagarik Wax', 'Orquídea', 'La Sagarik Wax es una orquídea tailandesa muy apreciada por la textura cerosa y brillante de sus pétalos, que suelen presentarse en colores vibrantes como el blanco, rosado y púrpura. Posee un aroma delicado y es conocida por su resistencia, lo que la convierte en una excelente opción para cultivadores principiantes y experimentados.', 15800, 12, 'https://static1.squarespace.com/static/55b8e840e4b0b3ab4e21b1dc/5e4daa9cee5bb23bd71cd226/5f491592401f346d88d01abe/1648083759123/IMG_5007.jpg?format=1500w', '2025-08-20', 1),

('Rungnapha Fancy', 'Orquídea', 'La Rungnapha Fancy destaca por su espectacular combinación de colores vivos, que pueden incluir tonos amarillos, rojos y púrpuras. Es una orquídea híbrida, muy popular en exposiciones por la elegancia de sus pétalos ondulados. Prefiere ambientes cálidos y húmedos, con luz filtrada para mantener la intensidad de sus colores.', 18750, 8, 'https://www.tropicalorchidsportugal.com/wp-content/uploads/2024/03/cattleya-rungnapha-warm-welcome-01.jpg', '2025-08-22', 1),

('Cattleya máxima', 'Orquídea', 'La Cattleya máxima es una especie originaria de Sudamérica, famosa por sus flores grandes y fragantes, que presentan tonalidades lilas con el labelo más oscuro. Es muy valorada en floristería por su belleza y perfume. Necesita alta luminosidad y buena ventilación para un desarrollo óptimo.', 20100, 15, 'https://www.shutterstock.com/image-photo/bright-purple-cattleya-maxima-orchid-600nw-1764983171.jpg', '2025-08-18', 1),

('Rlc Fort Waston', 'Orquídea', 'El híbrido Rlc Fort Waston combina la elegancia y tamaño de las cattleyas con colores intensos y vibrantes. Sus flores pueden medir más de 15 cm, lo que la hace ideal para exhibiciones y coleccionistas. Prefiere ambientes cálidos, con buena humedad y luz moderada.', 16500, 6, 'https://orchidroots.com/static/utils/images/hybrid/Rhyncholaeliocattleya_100071713_000006953.jpeg', '2025-08-25', 1),

('Cattleya tenebrosa', 'Orquídea', 'La Cattleya tenebrosa es una orquídea brasileña rara, apreciada por sus pétalos de tonos oscuros, entre marrón y burdeos, y su fragancia intensa. Florece en verano y requiere luz brillante con sombra parcial. Es considerada una joya para coleccionistas por su rareza y elegancia.', 17000, 5, 'https://http2.mlstatic.com/D_NQ_NP_642976-MLB75419045260_042024-O-cattleya-laelia-tenebrosa-orquidea-adulta.webp', '2025-08-30', 1),

('Rlc Liu''s Joyance', 'Orquídea', 'La Rlc Liu''s Joyance es un híbrido espectacular con flores grandes en tonalidades moradas y amarillas, con el centro intensamente contrastado. Es muy resistente y florece varias veces al año si recibe la luz adecuada. Ideal para coleccionistas que buscan variedades llamativas.', 16800, 7, 'https://orchidgarden.co.uk/wp-content/uploads/2023/09/Cattleya-Rlc.-Lius-Joyance-2.jpg', '2025-08-28', 1),

('Miltassia Shelob', 'Orquídea', 'La Miltassia Shelob es un híbrido entre Miltonia y Brassia, famosa por sus pétalos alargados y moteados en tonos púrpura y crema, lo que le da un aspecto exótico. Prefiere ambientes húmedos y frescos, con luz tenue, siendo ideal para cultivo en interiores.', 22150, 9, 'https://www.orquidariooriental.com.br/produtos/396.jpg', '2025-08-24', 1),

('Grammatophyllum ', 'Orquídea', 'Esta especie es una de las orquídeas más grandes, conocida por sus racimos de flores amarillas con manchas verdes. Puede alcanzar gran tamaño y florecer espectacularmente una vez al año. Es resistente y perfecta para espacios amplios y bien iluminados.', 17550, 4, 'https://orchideeen-shop.nl/cdn/shop/products/orchid_big325.jpg?v=1676552033', '2025-09-01', 1),

('Cymbidium cinderella', 'Orquídea', 'La Cymbidium cinderella es una variedad elegante y compacta, con flores en tonos pastel que duran varias semanas. Es muy apreciada para arreglos florales y decoración de interiores por su resistencia y facilidad de cuidado.', 19580, 10, 'https://media.istockphoto.com/id/1345528717/es/foto/flor-de-orqu%C3%ADdea-cymbidium.jpg?s=612x612&w=0&k=20&c=5xOLW8n5aiQ6zEEpazLcIBy-gW26XkXA_e-4XISQ3ZY=', '2025-08-27', 1),

('Cymbidium tracyanum', 'Orquídea', 'El Cymbidium tracyanum es una especie originaria de Asia, caracterizada por sus flores grandes, fragantes, en tonos verdosos con marcas marrones. Florece en invierno y es muy resistente a bajas temperaturas, lo que la hace ideal para climas fríos.', 16200, 8, 'https://inaturalist-open-data.s3.amazonaws.com/photos/10919961/original.jpg', '2025-08-26', 1),

('Cymbidium marco polo', 'Orquídea', 'El Cymbidium Marco Polo es un híbrido moderno con flores elegantes en tonos rosados y centros intensos. Florece en invierno y primavera, siendo muy valorado en arreglos ornamentales por su durabilidad y belleza.', 18400, 6, 'https://www.interflora.es/blog/wp-content/uploads/orquidea-cymbidium-1024x683.jpg.webp', '2025-08-29', 1);


-- Insertar Eventos en la tabla de eventos
INSERT INTO Evento (Nombre, Descripcion, Precio, Fecha, Hora, Ubicacion, Imagen_URL)
VALUES
('Tour al mariposario',
'Disfruta de un recorrido guiado por nuestro espectacular mariposario en La Paz de Alajuela, donde podrás observar diferentes especies tropicales en su hábitat natural. Conoce su ciclo de vida, aprende sobre su importancia ecológica y admira la belleza de sus colores y patrones. Ideal para toda la familia y amantes de la naturaleza.', 
3500, '2025-08-10', '09:00:00', 'La Paz, Alajuela', 'https://a.travel-assets.com/findyours-php/viewfinder/images/res70/116000/116350-Butterfly-Farm.jpg'),

('Tour orquídeas',
'Explora la fascinante colección de orquídeas exóticas en nuestro jardín especializado. Aprende sobre las especies más raras, sus cuidados y la historia detrás de estas hermosas flores. Un recorrido lleno de color y fragancias que cautivará tus sentidos, en un ambiente tranquilo rodeado de naturaleza.', 
3500, '2025-08-12', '10:00:00', 'La Paz, Alajuela', 'https://cdn-imgix.headout.com/tour/25577/TOUR-IMAGE/37f64084-d2ff-4f81-8b91-e399eaf665f2-NOG-4.JPG?auto=format&w=900&h=562.5&q=90&ar=16%3A10&crop=faces%2Ccenter&fit=crop'),

('Tour completo ', 
'Vive la experiencia más completa con nuestro tour premium que combina lo mejor de ambos mundos: un recorrido por diversos mariposarios y la exclusiva zona de orquídeas. Incluye guías expertos, actividades interactivas, tiempo para fotografías y acceso a áreas únicas donde podrás aprender y disfrutar de la biodiversidad tropical en todo su esplendor.', 
5000, '2025-08-15', '08:30:00', 'La Paz, Alajuela', 'https://media.istockphoto.com/id/849220498/photo/blue-tiger-butterfly-on-a-pink-zinnia-flower-with-green-background.jpg?b=1&s=612x612&w=0&k=20&c=vGMeC-fi_URdA3IhC_3MYb-068m1Jaxw9iwsuQcZmdo=');


-- Insertar datos de ejemplo para la tabla Consulta
INSERT INTO Consulta (ID_Usuario, Tema, Estado, Canal, Mensajes) VALUES
(5, 'Consulta', 'Pendiente', 'Chat', JSON_ARRAY(
    JSON_OBJECT('role', 'system', 'text', '¡Bienvenido! Un agente responderá en un máximo de 24 horas. Tema: Consulta', 'time', '10:00'),
    JSON_OBJECT('role', 'cliente', 'text', 'Hola, tengo una duda.', 'time', '10:02')
)),
(6, 'Reclamo', 'Respondido', 'Correo', JSON_ARRAY(
    JSON_OBJECT('role', 'cliente', 'text', 'Mi pedido llegó incompleto.', 'time', '11:10'),
    JSON_OBJECT('role', 'admin', 'text', 'Estamos revisando su caso, le avisaremos pronto.', 'time', '11:15')
));

--  Tienen que ejecutar este comando para que les funcione la parte de pagos
ALTER TABLE Pedido MODIFY Estado_Envio VARCHAR(50) NOT NULL DEFAULT 'Pedido Recibido';




-- ====================================================================
-- 3. EJEMPLOS DE CONSULTAS (OPCIONAL)
-- ====================================================================

-- Describir la estructura de la tabla Consulta
-- DESCRIBE Consulta;

-- Seleccionar todos los registros de la tabla Consulta
-- SELECT * FROM Consulta;

-- Seleccionar columnas específicas de la tabla Consulta, ordenadas por fecha descendente
-- SELECT ID_Consulta, ID_Usuario, Tema, Estado, Canal, Mensajes, Fecha
-- FROM Consulta
-- ORDER BY Fecha DESC;

-- Contar el número de consultas por estado
-- SELECT Estado, COUNT(*) AS Total
-- FROM Consulta
-- GROUP BY Estado;