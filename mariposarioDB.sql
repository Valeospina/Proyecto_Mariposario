Create database mariposarioDB;
use mariposarioDB;

CREATE TABLE Rol (
    ID_Rol INT PRIMARY KEY,
    Nombre VARCHAR(100),
    Tipo_Notificacion VARCHAR(100),
    Descripcion VARCHAR(300)
);

CREATE TABLE Usuario (
    ID_Usuario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Rol INT,
    Nombre VARCHAR(100),
    Correo VARCHAR(100),
    Contrasena VARCHAR(100),
    Telefono VARCHAR(20),
    Direccion VARCHAR(255),
    FOREIGN KEY (ID_Rol) REFERENCES Rol(ID_Rol)
);

CREATE TABLE Notificacion (
    ID_Notificacion INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Tipo_Notificacion VARCHAR(100),
    Fecha_Notificacion DATETIME,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Empleado (
    ID_Empleado INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Nombre VARCHAR(100),
    Correo VARCHAR(100),
    Salario DECIMAL(10,2),
    Rol VARCHAR(50),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Registro_Actividad (
    ID_Registro INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT,
    Fecha_Hora DATETIME,
    Accion VARCHAR(255),
    Detalle VARCHAR(300),
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

CREATE TABLE Horario (
    ID_Horario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT,
    Dia_Semana VARCHAR(20),
    Hora_Entrada TIME,
    Hora_Salida TIME,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

CREATE TABLE Pago_Empleado (
    ID_Pago INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT,
    Fecha_Pago DATE,
    Monto DECIMAL(10,2),
    Metodo_Pago VARCHAR(50),
    Detalle VARCHAR(300),
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

CREATE TABLE Asistencia (
    ID_Asistencia INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT,
    Fecha DATE,
    Hora_Entrada DATETIME,
    Hora_Salida DATETIME,
    Observaciones TEXT,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
);

CREATE TABLE Evento (
    ID_Evento INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Evento VARCHAR(100)
);

CREATE TABLE Reserva (
    ID_Reserva INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    ID_Evento INT,
    cantidad_personas INT NOT NULL,
    Fecha_Reserva DATETIME,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario),
    FOREIGN KEY (ID_Evento) REFERENCES Evento(ID_Evento)
);

CREATE TABLE Consulta (
    ID_Consulta INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Fecha DATETIME,
    Tema VARCHAR(100),
    Mensaje TEXT,
    Respuesta TEXT,
    Fecha_Respuesta DATETIME,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Pedido (
    ID_Pedido INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Fecha_Pedido DATETIME,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Estado_Pedido (
    ID_Estado INT PRIMARY KEY AUTO_INCREMENT,
    ID_Pedido INT,
    Estado VARCHAR(50),
    Fecha DATETIME,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
);

CREATE TABLE Factura (
    ID_Factura INT PRIMARY KEY AUTO_INCREMENT,
    ID_Pedido INT,
    Fecha_Factura DATETIME,
    Subtotal DECIMAL (10,2),
    Total DECIMAL(10,2),
    Metodo_de_pago VARCHAR(100),
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
);

CREATE TABLE Auditoria_Factura (
    ID_Auditoria INT PRIMARY KEY AUTO_INCREMENT,
    ID_Factura INT,
    Usuario_Responsable VARCHAR(100),
    Fecha DATETIME,
    Accion VARCHAR(255),
    Detalles VARCHAR(300),
    FOREIGN KEY (ID_Factura) REFERENCES Factura(ID_Factura)
);

CREATE TABLE Venta (
    ID_Venta INT PRIMARY KEY AUTO_INCREMENT,
    ID_Pedido INT,
    ID_Usuario INT,
    Fecha datetime,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Producto (
    ID_Producto INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100),
    Categoria VARCHAR(100),
    Descripcion VARCHAR(300),
    Precio DECIMAL(10,2),
    Stock INT,
    Imagen_URL TEXT,
    Fecha_Reposicion DATE,
    Notificar_Disponibilidad BOOLEAN
);

INSERT INTO Producto (ID_Producto, Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Notificar_Disponibilidad) VALUES
(1, 'Mariposa Morpho Azul', 'Mariposas', 'Mariposa tropical de color azul metálico brillante.', 15000.00, 10, 'img/morpho_azul.jpg', '2025-06-10', FALSE),
(2, 'Mariposa Monarca', 'Mariposas', 'Famosa por su migración anual entre Canadá y México.', 12000.00, 8, 'img/monarca.jpg', '2025-06-15', TRUE),
(3, 'Mariposa Alas de Vidrio', 'Mariposas', 'Tiene alas transparentes que la hacen única.', 18000.00, 5, 'img/alas_vidrio.jpg', '2025-06-20', FALSE),
(4, 'Orquídea Phalaenopsis', 'Orquídeas', 'Conocida como orquídea mariposa por la forma de sus pétalos.', 20000.00, 6, 'img/orquidea_phalaenopsis.jpg', '2025-06-05', TRUE),
(5, 'Orquídea Cattleya', 'Orquídeas', 'Flor nacional de Costa Rica, muy apreciada por su belleza.', 22000.00, 4, 'img/orquidea_cattleya.jpg', '2025-06-12', TRUE);


CREATE TABLE Pedido_Producto (
    ID_Pedido INT AUTO_INCREMENT,
    ID_Producto INT,
    Cantidad INT,
    Precio_Unitario DECIMAL(10,2),
    Descuento_Aplicado DECIMAL(10,2),
    PRIMARY KEY (ID_Pedido, ID_Producto),
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido),
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

CREATE TABLE Ciclo_Mariposa (
    ID_Mariposa INT PRIMARY KEY AUTO_INCREMENT,
    ID_Producto INT,
    Fecha_Nacimiento DATE,
    Etapa_Actual ENUM('Huevo','Larva','Pupa','Adulto'),
    Fecha_Actualizacion DATETIME,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

CREATE TABLE Puntos_Usuario (
    ID_Usuario INT,
    Puntos_Actuales INT,
    Fecha_Expiracion DATE,
    Notificado BOOLEAN,
    PRIMARY KEY (ID_Usuario),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Historial_Puntos (
    ID INT PRIMARY KEY,
    ID_Usuario INT,
    Accion ENUM('Acumulado','Usado'),
    Monto INT,
    Descripcion VARCHAR(300),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Carrito (
    ID_Carrito INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','finalizado','cancelado') DEFAULT 'activo',
    Imagen_URL TEXT,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
);

CREATE TABLE Carrito_Producto (
    ID_Carrito INT,
    ID_Producto INT,
    Cantidad INT DEFAULT 1,
    Imagen_URL TEXT,
    PRIMARY KEY (ID_Carrito, ID_Producto),
    FOREIGN KEY (ID_Carrito) REFERENCES Carrito(ID_Carrito) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
);

ALTER TABLE Reserva ADD Estado ENUM('Pendiente', 'Aprobada') DEFAULT 'Pendiente';


-- Insertar roles básicos para que funcione el sistema de login
INSERT INTO Rol (ID_Rol, Nombre, Tipo_Notificacion, Descripcion) VALUES
(1, 'Administrador', 'Email y SMS', 'Usuario con acceso completo al sistema'),
(2, 'Cliente', 'Email', 'Usuario cliente que puede hacer compras y reservas'),
(3, 'Empleado', 'Email', 'Empleado del mariposario con acceso limitado');






-- Insertar usuarios de prueba
-- Contraseña para todos los usuarios: "123456"

-- ADMINISTRADORES (ID_Rol = 1)
INSERT INTO Usuario (ID_Usuario, ID_Rol, Nombre, Correo, Contrasena, Telefono, Direccion) VALUES
(1, 1, 'Carlos Rodríguez', 'admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2234-5678', 'San José, Costa Rica'),
(2, 1, 'María González', 'maria.admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2345-6789', 'Cartago, Costa Rica'),
(3, 1, 'José Hernández', 'jose.admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2456-7890', 'Alajuela, Costa Rica');

-- EMPLEADOS (ID_Rol = 3)
INSERT INTO Usuario (ID_Usuario, ID_Rol, Nombre, Correo, Contrasena, Telefono, Direccion) VALUES
(4, 3, 'Ana Jiménez', 'ana.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2567-8901', 'Heredia, Costa Rica'),
(5, 3, 'Pedro Vargas', 'pedro.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2678-9012', 'Puntarenas, Costa Rica'),
(6, 3, 'Sofía Mora', 'sofia.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2789-0123', 'Guanacaste, Costa Rica');

-- CLIENTES/USUARIOS NORMALES (ID_Rol = 2)
INSERT INTO Usuario (ID_Usuario, ID_Rol, Nombre, Correo, Contrasena, Telefono, Direccion) VALUES
(7, 2, 'Laura Castillo', 'laura.cliente@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2890-1234', 'San José, Escazú'),
(8, 2, 'Diego Ramírez', 'diego.cliente@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2901-2345', 'Cartago, Paraíso'),
(9, 2, 'Valeria Solano', 'valeria.cliente@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2012-3456', 'Alajuela, Atenas');

-- EVENTOS ACTUALES
INSERT INTO Evento (Nombre_Evento) VALUES
('Taller de Mariposas'),
('Visita Guiada'),
('Charla de Orquídeas');
