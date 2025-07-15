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
CREATE DATABASE mariposarioDB;
USE mariposarioDB;

CREATE TABLE Rol (
    ID_Rol INT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Tipo_Notificacion VARCHAR(100), -- Ej: 'Email', 'SMS', 'Email y SMS'
    Descripcion VARCHAR(300)
);

-- Tabla: Usuario (Información de todos los usuarios: clientes, administradores, empleados)
CREATE TABLE Usuario (
    ID_Usuario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Rol INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100), -- Añadido para mayor completitud
    Correo VARCHAR(255) UNIQUE NOT NULL, -- Correo como UNIQUE y VARCHAR(255) para emails largos
    Contrasena VARCHAR(255) NOT NULL, -- VARCHAR(255) para almacenar hashes de contraseñas (ej. bcrypt)
    Telefono VARCHAR(20),
    Direccion TEXT, -- TEXT para direcciones más largas
    Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','inactivo') DEFAULT 'activo', -- Para activar/desactivar usuarios
    FOREIGN KEY (ID_Rol) REFERENCES Rol(ID_Rol)
);

-- Tabla: Empleado (Detalles específicos de los empleados, vinculados a la tabla Usuario)
CREATE TABLE Empleado (
    ID_Empleado INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT UNIQUE NOT NULL, -- UNIQUE porque un Usuario solo puede ser un Empleado
    Nombre VARCHAR(100), -- Redundante con Usuario.Nombre, pero mantenido si se usa para reportes internos de Empleado
    Correo VARCHAR(100), -- Redundante con Usuario.Correo, pero mantenido si se usa para reportes internos de Empleado
    Salario DECIMAL(10,2),
    Rol VARCHAR(50), -- Rol específico del empleado (ej: 'Gerente', 'Vendedor', 'Guía')
    Horario VARCHAR(255) DEFAULT 'No especificado', -- Nuevo: Horario de trabajo del empleado
    Fecha_Contratacion DATE, -- Nuevo: Fecha de contratación
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Horario (Horarios de trabajo detallados por empleado y día)
CREATE TABLE Horario (
    ID_Horario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Dia_Semana VARCHAR(20) NOT NULL, -- Ej: 'Lunes', 'Martes'
    Hora_Entrada TIME,
    Hora_Salida TIME,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Asistencia (Registro de asistencia de empleados)
CREATE TABLE Asistencia (
    ID_Asistencia INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha DATE NOT NULL,
    Hora_Entrada DATETIME,
    Hora_Salida DATETIME,
    Observaciones TEXT,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Pago_Empleado (Registro de pagos a empleados)
CREATE TABLE Pago_Empleado (
    ID_Pago INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha_Pago DATE NOT NULL,
    Monto DECIMAL(10,2) NOT NULL,
    Metodo_Pago VARCHAR(50), -- Ej: 'Transferencia', 'Cheque', 'Efectivo'
    Detalle VARCHAR(300),
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

-- Tabla: Producto (Productos disponibles para la venta)
CREATE TABLE Producto (
    ID_Producto INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(255) NOT NULL,
    Categoria VARCHAR(100),
    Descripcion TEXT,
    Precio DECIMAL(10, 2) NOT NULL,
    Stock INT,
    Imagen_URL TEXT,
    Fecha_Reposicion DATE,
    Activo_Catalogo BOOLEAN DEFAULT TRUE, -- Indica si el producto está visible en el catálogo
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: Inventario (Gestión de stock de productos en diferentes ubicaciones/lotes)
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

-- Tabla: Pedido (Pedidos de productos realizados por los usuarios - Proformas)
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
    Numero_Proforma VARCHAR(50) UNIQUE NOT NULL, -- Proforma para el cliente
    Observaciones TEXT NULL DEFAULT NULL,
    Puntos_Canjeados INT DEFAULT 0,
    Monto_Canjeado DECIMAL(10, 2) DEFAULT 0.00,
    Metodo_Pago ENUM(
        'Efectivo Tienda',
        'Tarjeta Tienda',
        'SINPE Movil',
        'Transferencia Bancaria'
    ) NOT NULL DEFAULT 'Efectivo Tienda', -- Método de pago elegido por el cliente
    Estado_Envio ENUM(
        'Pedido Recibido',
        'En Preparacion',
        'En Transito',
        'Entregado',
        'Cancelado Envio'
    ) DEFAULT 'Pedido Recibido', -- Estado de envío/entrega del pedido
    Fecha_Vencimiento_Proforma DATETIME NULL DEFAULT NULL, -- Vigencia de la proforma
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Pedido_Producto (Detalles de los productos en cada pedido)
CREATE TABLE Pedido_Producto (
    ID_Pedido INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio_Unitario DECIMAL(10,2) NOT NULL,
    Descuento_Aplicado DECIMAL(10,2) DEFAULT 0.00,
    PRIMARY KEY (ID_Pedido, ID_Producto), -- Clave primaria compuesta
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Factura (Facturas Electrónicas Oficiales)
CREATE TABLE Factura (
    ID_Factura INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT UNIQUE NOT NULL, -- Relación uno a uno con Pedido
    Fecha_Factura DATETIME DEFAULT CURRENT_TIMESTAMP,
    Subtotal DECIMAL(10,2) NOT NULL,
    Total DECIMAL(10,2) NOT NULL,
    Metodo_Pago VARCHAR(100), -- Método de pago registrado en la factura final
    Numero_Factura VARCHAR(100) UNIQUE, -- Número de factura oficial (consecutivo Hacienda)
    XML_Enviado TEXT, -- Contenido XML enviado a Hacienda
    XML_Respuesta TEXT, -- Contenido XML de la respuesta de Hacienda
    Estado_Hacienda ENUM('Pendiente Validacion', 'Enviado', 'Aceptada', 'Rechazada', 'Anulada') DEFAULT 'Pendiente Validacion',
    Clave_Numerica VARCHAR(50) UNIQUE, -- Clave Numérica de Hacienda (50 dígitos para CR)
    Ruta_PDF_Factura VARCHAR(255), -- Ruta o URL del PDF de la factura
    Fecha_Envio_Hacienda DATETIME,
    Fecha_Respuesta_Hacienda DATETIME,
    Referencia_Pago VARCHAR(255), -- Para SINPE/Transferencia
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
);

-- Tabla: Factura_Producto (Detalles de los productos en cada factura)
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

-- Tabla: Puntos_Usuario (Puntos de lealtad actuales de cada usuario)
CREATE TABLE Puntos_Usuario (
    ID_Puntos INT AUTO_INCREMENT PRIMARY KEY, -- Nueva PK para esta tabla
    ID_Usuario INT UNIQUE NOT NULL, -- UNIQUE para asegurar solo una entrada de puntos por usuario
    Puntos_Actuales INT DEFAULT 0,
    Fecha_Expiracion DATE,
    Notificado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Historial_Puntos (Registro de transacciones de puntos)
CREATE TABLE Historial_Puntos (
    ID_Historial INT AUTO_INCREMENT PRIMARY KEY, -- Renombrado de ID a ID_Historial para claridad
    ID_Usuario INT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Accion ENUM('Ganado','Canjeado') NOT NULL,
    Monto INT NOT NULL, -- Cantidad de puntos
    Descripcion TEXT,
    ID_Referencia INT, -- Opcional: ID de Pedido o Factura relacionada
    Tipo_Referencia VARCHAR(50), -- Opcional: 'Pedido', 'Factura'
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Carrito (Carrito de compras persistente en DB - Opcional si solo usas sesión)
CREATE TABLE Carrito (
    ID_Carrito INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','finalizado','cancelado') DEFAULT 'activo',
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Carrito_Producto (Productos dentro del carrito persistente)
CREATE TABLE Carrito_Producto (
    ID_Carrito INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT DEFAULT 1,
    PRIMARY KEY (ID_Carrito, ID_Producto),
    FOREIGN KEY (ID_Carrito) REFERENCES Carrito(ID_Carrito) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

-- Tabla: Evento (Eventos o actividades del mariposario)
CREATE TABLE Evento (
    ID_Evento INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT NOT NULL,
    Precio DECIMAL(10, 2) NOT NULL,
    Fecha DATE NOT NULL, -- Nuevo: Fecha del evento
    Hora TIME, -- Nuevo: Hora del evento
    Ubicacion VARCHAR(255), -- Nuevo: Ubicación del evento
    Imagen_URL VARCHAR(255) NOT NULL
);

-- Tabla: Reserva (Reservas para eventos)
CREATE TABLE Reserva (
    ID_Reserva INT PRIMARY KEY AUTO_INCREMENT,
    ID_Evento INT NOT NULL,
    ID_Usuario INT, -- Opcional: Si la reserva está ligada a un usuario registrado
    Cantidad_Personas INT NOT NULL,
    Fecha_Reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('Pendiente', 'Aprobada', 'Cancelada') DEFAULT 'Pendiente', -- Nuevo: Estado de la reserva
    Telefono VARCHAR(20), -- Nuevo: Teléfono de contacto para la reserva
    Correo VARCHAR(100), -- Nuevo: Correo de contacto para la reserva
    Descripcion TEXT, -- Nuevo: Notas o detalles adicionales de la reserva
    FOREIGN KEY (ID_Evento) REFERENCES Evento(ID_Evento),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario) -- Si ID_Usuario es NOT NULL, el usuario debe estar registrado.
);

-- Tabla: Consulta (Consultas o mensajes de usuarios)
CREATE TABLE Consulta (
    ID_Consulta INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT, -- Puede ser NULL si es una consulta de invitado
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Tema VARCHAR(100),
    Mensaje TEXT NOT NULL,
    Respuesta TEXT,
    Fecha_Respuesta DATETIME,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Notificacion (Notificaciones generales del sistema)
CREATE TABLE Notificacion (
    ID_Notificacion INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT, -- Puede ser NULL para notificaciones generales
    Tipo_Notificacion VARCHAR(100), -- Ej: 'Sistema', 'Promocion', 'Alerta'
    Mensaje TEXT NOT NULL, -- Contenido de la notificación
    Fecha_Notificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Leida BOOLEAN DEFAULT FALSE, -- Si el usuario ya la vio
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

-- Tabla: Bitacora (Registro centralizado de actividades del sistema y auditoría)
-- Reemplaza a Registro_Actividad y Auditoria_Factura
CREATE TABLE Bitacora (
    ID_Log INT AUTO_INCREMENT PRIMARY KEY,
    Fecha_Hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    ID_Usuario INT, -- El usuario que realizó la acción (NULL para acciones del sistema)
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
    ID_Referencia INT, -- ID del registro afectado (Pedido, Factura, Usuario, etc.)
    Tabla_Referencia VARCHAR(50), -- Nombre de la tabla afectada (ej: 'Pedido', 'Factura', 'Usuario')
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
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
-- Puedes usar password_hash('123456', PASSWORD_DEFAULT) en PHP para generar estos hashes
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
('Hamadrias laodamia', 'Mariposa', 'Mariposa de colores vibrantes, común en áreas tropicales.', 15.50, 10, 'https://static.inaturalist.org/photos/337837645/medium.jpg', '2025-07-15', TRUE),
('Mircelia cisniris', 'Mariposa', 'Mariposa con patrones únicos en las alas, encontrada en zonas de bosques.', 18.75, 5, 'https://southcoastbotanicgarden.org/wp-content/uploads/2023/07/Shady-Spots-4.jpg', '2025-07-18', TRUE),
('Morpho', 'Mariposa', 'Mariposa azul brillante, conocida por su impresionante coloración metálica.', 20.00, 12, 'https://contexto.udlap.mx/wp-content/uploads/2021/11/mariposa.jpg', '2025-07-20', TRUE),
('Igna', 'Mariposa', 'Igna es una mariposa exótica con alas de colores variados, habitante de zonas tropicales.', 14.00, 8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Agraulis_vanillae_at_Isla_Margarita.jpg/250px-Agraulis_vanillae_at_Isla_Margarita.jpg', '2025-08-20', TRUE),
('Catonephele mexicana', 'Mariposa', 'Mariposa tropical mexicana con un diseño particular en sus alas.', 17.40, 8, 'https://pictureinsect.com/wiki-image/1080/153820706105196555.jpeg', '2025-07-25', TRUE),
('Siproeta stelenis', 'Mariposa', 'Mariposa tropical de colores vibrantes, conocida por su tamaño y belleza.', 22.50, 6, 'https://static.inaturalist.org/photos/33050984/medium.jpeg', '2025-07-27', TRUE),
('Archaeprepona', 'Mariposa', 'Mariposa tropical, especialmente conocida por su coloración verde.', 19.00, 9, 'https://i.redd.it/a-one-spot-prepona-archaeoprepona-demophon-aka-banded-king-v0-23ufry6af6je1.jpg?width=8688&format=pjpg&auto=webp&s=1d012cc0bc98e278574d7a40aaf8e9ad76a1a75b', '2025-07-30', TRUE),
('Cónsul fabius', 'Mariposa', 'Mariposa color marrón, con patrones llamativos en sus alas.', 16.25, 7, 'https://inaturalist-open-data.s3.amazonaws.com/photos/60274029/original.jpeg', '2025-08-05', TRUE);

-- Insertar Orquideas a la tabla productos
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Guaria Morada', 'Orquídea', 'La Guaria Morada es una orquídea nativa de América Central, famosa por sus flores de color morado brillante.', 25.00, 15, 'https://static.wixstatic.com/media/cdfea7_41bf369fee304c6687a2a41513851c6c~mv2.jpg/v1/fill/w_568,h_378,al_c,q_80,usm_0.66_1.00_0.01,enc_avif,quality_auto/cdfea7_41bf369fee304c6687a2a41513851c6c~mv2.jpg', '2025-08-01', TRUE),
('Cattleya trianae', 'Orquídea', 'La Cattleya trianae es conocida como la orquídea nacional de Colombia, con flores grandes y coloridas.', 30.00, 10, 'https://media.istockphoto.com/id/1479201464/es/foto/cattleya-trianae-u-orqu%C3%ADdea-flor-de-mayo-con-punta-dentada-p%C3%A9talos-blancos-en-medio-de-los.jpg?s=170667a&w=0&k=20&c=hzpxAbT31sGZ1GUgsmoPnsuC9PLinD2WNX2igRGfqmU=', '2025-08-10', TRUE),
('Oncidium sphacelatum', 'Orquídea', 'Oncidium sphacelatum es una orquídea conocida por sus pequeñas flores que se asemejan a una mariposa.', 22.50, 12, 'https://www.picturethisai.com/wiki-image/1080/154113820443279364.jpeg', '2025-08-15', TRUE),
('Psychopsis papilio', 'Orquídea', 'La Psychopsis papilio, o Orquídea mariposa, es famosa por sus flores que parecen alas de mariposa.', 28.00, 8, 'https://www.picturethisai.com/wiki-image/1080/218296531776962560.jpeg', '2025-08-20', TRUE),
('Dendrobium', 'Orquídea', 'Dendrobium es un género que agrupa varias especies de orquídeas, conocidas por sus flores en racimo.', 20.00, 18, 'https://www.interflora.es/blog/wp-content/uploads/orquidea-dendrobium.jpg', '2025-08-25', TRUE),
('Brassavola nodosa', 'Orquídea', 'La Brassavola nodosa es una orquídea nocturna, apreciada por su fragancia durante la noche.', 27.50, 10, 'https://www.shutterstock.com/image-photo/brassavola-small-white-tough-species-600nw-2548499641.jpg', '2025-09-01', TRUE),
('Miltonia spectabilis', 'Orquídea', 'Miltonia spectabilis, conocida como la orquídea del pensamiento, es famosa por sus flores grandes y coloridas.', 35.00, 7, 'https://png.pngtree.com/thumb_back/fh260/background/20220913/pngtree-miltonia-maui-orchid-plant-white-photo-image_19805376.jpg', '2025-09-05', TRUE),
('Epidendrum radicans', 'Orquídea', 'Epidendrum radicans es una orquídea que se caracteriza por sus raíces rojas y flores vibrantes.', 24.00, 20, 'https://www.picturethisai.com/wiki-image/1080/154019752069562384.jpeg', '2025-09-10', TRUE);

-- Nuevas tablas para la parte de pagos

USE mariposarioDB;

CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaccion VARCHAR(50) NOT NULL UNIQUE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    id_cliente VARCHAR(50),
    total DECIMAL(10,2) NOT NULL
);


CREATE TABLE detalle_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL,
    id_producto INT NOT NULL,
    nombre_producto VARCHAR(100),
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pago) REFERENCES pagos(id) ON DELETE CASCADE
);


ALTER TABLE Factura
ADD COLUMN id_pago INT,
ADD FOREIGN KEY (id_pago) REFERENCES pagos(id);

ALTER TABLE Reserva 
ADD COLUMN Asistio TINYINT(1) NOT NULL DEFAULT 0;



CREATE TABLE Estado_Pedido (
    ID_Estado INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT NOT NULL,
    Estado VARCHAR(50) NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
);



-- Como recomendacion, ejecuten desde 0 la base de datos y de ultimo ejecutan esta linea de codigo
ALTER TABLE Pedido MODIFY metodo_pago VARCHAR(50) NOT NULL DEFAULT 'PayPal';