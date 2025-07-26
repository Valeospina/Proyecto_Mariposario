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
    ID_Usuario INT NULL, -- Puede ser NULL si es invitado
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación del ticket
    Tema ENUM('Consulta','Reclamo','Sugerencia') NOT NULL, -- Tipo de consulta
    Estado ENUM('Pendiente','Respondido','Cerrado') DEFAULT 'Pendiente', -- Estado del ticket
    Canal ENUM('Chat','Correo') DEFAULT 'Chat', -- Medio de soporte
    Mensajes JSON NULL, -- Historial de mensajes en formato JSON
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
('Hamadrias laodamia', 'Mariposa', 'Mariposa de colores vibrantes, común en áreas tropicales.', 8215.00, 10, 'https://static.inaturalist.org/photos/337837645/medium.jpg', '2025-07-15', TRUE),
('Mircelia cisniris', 'Mariposa', 'Mariposa con patrones únicos en las alas, encontrada en zonas de bosques.', 9940.00, 5, 'https://southcoastbotanicgarden.org/wp-content/uploads/2023/07/Shady-Spots-4.jpg', '2025-07-18', TRUE),
('Morpho', 'Mariposa', 'Mariposa azul brillante, conocida por su impresionante coloración metálica.', 10600.00, 12, 'https://contexto.udlap.mx/wp-content/uploads/2021/11/mariposa.jpg', '2025-07-20', TRUE),
('Igna', 'Mariposa', 'Igna es una mariposa exótica con alas de colores variados, habitante de zonas tropicales.', 7420.00, 8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Agraulis_vanillae_at_Isla_Margarita.jpg/250px-Agraulis_vanillae_at_Isla_Margarita.jpg', '2025-08-20', TRUE),
('Catonephele mexicana', 'Mariposa', 'Mariposa tropical mexicana con un diseño particular en sus alas.', 9220.00, 8, 'https://pictureinsect.com/wiki-image/1080/153820706105196555.jpeg', '2025-07-25', TRUE),
('Siproeta stelenis', 'Mariposa', 'Mariposa tropical de colores vibrantes, conocida por su tamaño y belleza.', 11925.00, 6, 'https://static.inaturalist.org/photos/33050984/medium.jpeg', '2025-07-27', TRUE),
('Archaeprepona', 'Mariposa', 'Mariposa tropical, especialmente conocida por su coloración verde.', 10070.00, 9, 'https://i.redd.it/a-one-spot-prepona-archaeoprepona-demophon-aka-banded-king-v0-23ufry6af6je1.jpg?width=8688&format=pjpg&auto=webp&s=1d012cc0bc98e278574d7a40aaf8e9ad76a1a75b', '2025-07-30', TRUE),
('Cónsul fabius', 'Mariposa', 'Mariposa color marrón, con patrones llamativos en sus alas.', 8612.50, 7, 'https://inaturalist-open-data.s3.amazonaws.com/photos/60274029/original.jpeg', '2025-08-05', TRUE);

-- Insertar Orquídeas a la tabla productos
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Guaria Morada', 'Orquídea', 'La Guaria Morada es una orquídea nativa de América Central, famosa por sus flores de color morado brillante.', 13250.00, 15, 'https://static.wixstatic.com/media/cdfea7_41bf369fee304c6687a2a41513851c6c~mv2.jpg/v1/fill/w_568,h_378,al_c,q_80,usm_0.66_1.00_0.01,enc_avif,quality_auto/cdfea7_41bf369fee304c6687a2a41513851c6c~mv2.jpg', '2025-08-01', TRUE),
('Cattleya trianae', 'Orquídea', 'La Cattleya trianae es conocida como la orquídea nacional de Colombia, con flores grandes y coloridas.', 15900.00, 10, 'https://media.istockphoto.com/id/1479201464/es/foto/cattleya-trianae-u-orqu%C3%ADdea-flor-de-mayo-con-punta-dentada-p%C3%A9talos-blancos-en-medio-de-los.jpg?s=170667a&w=0&k=20&c=hzpxAbT31sGZ1GUgsmoPnsuC9PLinD2WNX2igRGfqmU=', '2025-08-10', TRUE),
('Oncidium sphacelatum', 'Orquídea', 'Oncidium sphacelatum es una orquídea conocida por sus pequeñas flores que se asemejan a una mariposa.', 11925.00, 12, 'https://www.picturethisai.com/wiki-image/1080/154113820443279364.jpeg', '2025-08-15', TRUE),
('Psychopsis papilio', 'Orquídea', 'La Psychopsis papilio, o Orquídea mariposa, es famosa por sus flores que parecen alas de mariposa.', 14840.00, 8, 'https://www.picturethisai.com/wiki-image/1080/218296531776962560.jpeg', '2025-08-20', TRUE),
('Dendrobium', 'Orquídea', 'Dendrobium es un género que agrupa varias especies de orquídeas, conocidas por sus flores en racimo.', 10600.00, 18, 'https://www.interflora.es/blog/wp-content/uploads/orquidea-dendrobium.jpg', '2025-08-25', TRUE),
('Brassavola nodosa', 'Orquídea', 'La Brassavola nodosa es una orquídea nocturna, apreciada por su fragancia durante la noche.', 14575.00, 10, 'https://www.shutterstock.com/image-photo/brassavola-small-white-tough-species-600nw-2548499641.jpg', '2025-09-01', TRUE),
('Miltonia spectabilis', 'Orquídea', 'Miltonia spectabilis, conocida como la orquídea del pensamiento, es famosa por sus flores grandes y coloridas.', 18550.00, 7, 'https://png.pngtree.com/thumb_back/fh260/background/20220913/pngtree-miltonia-maui-orchid-plant-white-photo-image_19805376.jpg', '2025-09-05', TRUE),
('Epidendrum radicans', 'Orquídea', 'Epidendrum radicans es una orquídea que se caracteriza por sus raíces rojas y flores vibrantes.', 12720.00, 20, 'https://www.picturethisai.com/wiki-image/1080/154019752069562384.jpeg', '2025-09-10', TRUE);

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