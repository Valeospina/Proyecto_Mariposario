-- ====================================================================
-- RECUERDA QUE TODO ESTO ES REALIZADO EN MYSQL WORKBENCH 8.0 CE
-- ====================================================================
-- SCRIPT DE BASE DE DATOS PARA EL SISTEMA DEL MARIPOSARIO
-- Creado: 07 de Julio de 2025
-- Incluye: gestión de pedidos/pagos/facturación + Organización de Mariposas
-- ====================================================================

-- 0) RESET
DROP DATABASE IF EXISTS mariposarioDB;
CREATE DATABASE mariposarioDB;
USE mariposarioDB;

-- ====================================================================
-- 1. ESTRUCTURA DE TABLAS (CORE E-COMMERCE + EVENTOS)
-- ====================================================================

-- Rol
CREATE TABLE Rol (
    ID_Rol INT PRIMARY KEY,
    Nombre VARCHAR(100) NOT NULL,
    Tipo_Notificacion VARCHAR(100),
    Descripcion VARCHAR(300)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario
CREATE TABLE Usuario (
    ID_Usuario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Rol INT NOT NULL,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100),
    Correo VARCHAR(255) UNIQUE NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    Telefono VARCHAR(20),
    Direccion TEXT,
    Foto_Perfil VARCHAR(255) DEFAULT 'img/default-user.png',
    Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','inactivo') DEFAULT 'activo',
    FOREIGN KEY (ID_Rol) REFERENCES Rol(ID_Rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Empleado
CREATE TABLE Empleado (
    ID_Empleado INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT UNIQUE NOT NULL,
    Nombre VARCHAR(100),
    Correo VARCHAR(100),
    Salario DECIMAL(10,2),
    Rol VARCHAR(50),
    Horario VARCHAR(255) DEFAULT 'No especificado',
    Fecha_Contratacion DATE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Horario
CREATE TABLE Horario (
    ID_Horario INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Dia_Semana VARCHAR(20) NOT NULL,
    Hora_Entrada TIME,
    Hora_Salida TIME,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asistencia
CREATE TABLE Asistencia (
    ID_Asistencia INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha DATE NOT NULL,
    Hora_Entrada DATETIME,
    Hora_Salida DATETIME,
    Observaciones TEXT,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pago_Empleado
CREATE TABLE Pago_Empleado (
    ID_Pago INT PRIMARY KEY AUTO_INCREMENT,
    ID_Empleado INT NOT NULL,
    Fecha_Pago DATE NOT NULL,
    Monto DECIMAL(10,2) NOT NULL,
    Metodo_Pago VARCHAR(50),
    Detalle VARCHAR(300),
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Producto (ID_Especie se agrega luego en módulo de mariposas)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventario (ID_Mariposario se agrega luego)
CREATE TABLE Inventario (
    ID_Inventario INT AUTO_INCREMENT PRIMARY KEY,
    ID_Producto INT NOT NULL,
    SKU VARCHAR(100) UNIQUE,
    Stock_Actual INT NOT NULL DEFAULT 0,
    Stock_Minimo INT DEFAULT 0,
    Ubicacion VARCHAR(100),
    Notas TEXT,
    Fecha_Creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carrito
CREATE TABLE Carrito (
    ID_Carrito INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('activo','finalizado','cancelado') DEFAULT 'activo',
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carrito_Producto
CREATE TABLE Carrito_Producto (
    ID_Carrito INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT DEFAULT 1,
    PRIMARY KEY (ID_Carrito, ID_Producto),
    FOREIGN KEY (ID_Carrito) REFERENCES Carrito(ID_Carrito) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pagos (registro general de cobros externos, ej. PayPal/SINPE)
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaccion VARCHAR(50) NOT NULL UNIQUE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    id_cliente VARCHAR(50),
    total DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pedido (Estado_Pedido/Estado_Envio como VARCHAR para máxima compatibilidad)
CREATE TABLE Pedido (
    ID_Pedido INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Fecha_Pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    Total_Pedido DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    Estado_Pedido VARCHAR(100) DEFAULT 'Pendiente de Pago',
    Numero_Proforma VARCHAR(50) UNIQUE NOT NULL,
    Observaciones TEXT NULL DEFAULT NULL,
    Puntos_Canjeados INT DEFAULT 0,
    Monto_Canjeado DECIMAL(10, 2) DEFAULT 0.00,
    Metodo_Pago VARCHAR(50) NOT NULL DEFAULT 'PayPal',
    Estado_Envio VARCHAR(100) NOT NULL DEFAULT 'Pedido Recibido',
    Fecha_Vencimiento_Proforma DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle_Pedido (detalle por ítem; útil para reportes independientes)
CREATE TABLE Detalle_Pedido (
    ID_Detalle INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pedido_Producto (relación compuesta alternativa)
CREATE TABLE Pedido_Producto (
    ID_Pedido INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio_Unitario DECIMAL(10,2) NOT NULL,
    Descuento_Aplicado DECIMAL(10,2) DEFAULT 0.00,
    PRIMARY KEY (ID_Pedido, ID_Producto),
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estado_Pedido (historial)
CREATE TABLE Estado_Pedido (
    ID_Estado INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT NOT NULL,
    Estado VARCHAR(50) NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Pedido) REFERENCES Pedido(ID_Pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle_Pago (detalle por producto del pago)
CREATE TABLE detalle_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL,
    id_producto INT NOT NULL,
    nombre_producto VARCHAR(100),
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pago) REFERENCES pagos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factura
CREATE TABLE Factura (
    ID_Factura INT AUTO_INCREMENT PRIMARY KEY,
    ID_Pedido INT UNIQUE NOT NULL,
    id_pago INT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Factura_Producto
CREATE TABLE Factura_Producto (
    ID_Factura INT NOT NULL,
    ID_Producto INT NOT NULL,
    Cantidad INT NOT NULL,
    Precio_Unitario DECIMAL(10, 2) NOT NULL,
    Descuento_Aplicado DECIMAL(10, 2) DEFAULT 0.00,
    PRIMARY KEY (ID_Factura, ID_Producto),
    FOREIGN KEY (ID_Factura) REFERENCES Factura(ID_Factura) ON DELETE CASCADE,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Puntos_Usuario
CREATE TABLE Puntos_Usuario (
    ID_Puntos INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT UNIQUE NOT NULL,
    Puntos_Actuales INT DEFAULT 0,
    Fecha_Expiracion DATE,
    Notificado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial_Puntos
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Evento
CREATE TABLE Evento (
    ID_Evento INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Descripcion TEXT NOT NULL,
    Precio DECIMAL(10, 2) NOT NULL,
    Fecha DATE NOT NULL,
    Hora TIME,
    Ubicacion VARCHAR(255),
    Imagen_URL VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reserva
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
    Asistio TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (ID_Evento) REFERENCES Evento(ID_Evento),
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Consulta (Chat/Correo)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notificacion
CREATE TABLE Notificacion (
    ID_Notificacion INT PRIMARY KEY AUTO_INCREMENT,
    ID_Usuario INT,
    Tipo_Notificacion VARCHAR(100),
    Mensaje TEXT NOT NULL,
    Fecha_Notificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Leida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bitacora
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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


-- Reseñas de Productos
CREATE TABLE resenas_producto (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_Producto INT NOT NULL,
    Usuario VARCHAR(100) NOT NULL,
    Calificacion INT NOT NULL DEFAULT 0,
    Resena TEXT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Producto) REFERENCES Producto(ID_Producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



-- Recuperacion_Contrasena
CREATE TABLE Recuperacion_Contrasena (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ID_Usuario INT NOT NULL,
    Token VARCHAR(255) NOT NULL UNIQUE,
    Expiracion DATETIME NOT NULL,
    Usado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro_Actividad (log adicional)
CREATE TABLE Registro_Actividad (
    ID_Registro INT AUTO_INCREMENT PRIMARY KEY,
    ID_Empleado INT NULL,
    ID_Usuario INT NULL,
    Accion VARCHAR(50) NOT NULL,
    Detalle TEXT,
    IP VARCHAR(45),
    Navegador VARCHAR(255),
    Fecha_Hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Empleado) REFERENCES Empleado(ID_Empleado) ON DELETE SET NULL,
    FOREIGN KEY (ID_Usuario) REFERENCES Usuario(ID_Usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- 4. ORGANIZACIÓN DE LAS MARIPOSAS (NUEVO MÓDULO)
-- ====================================================================

-- 4.1 Mariposarios y Especies
CREATE TABLE Mariposario (
    ID_Mariposario INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(120) NOT NULL,
    Capacidad_Especies INT NOT NULL DEFAULT 4,
    Capacidad_Pupas INT NOT NULL DEFAULT 1000,
    Activo BOOLEAN NOT NULL DEFAULT TRUE,
    Notas TEXT,
    Fecha_Creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8 mariposarios iniciales
INSERT INTO Mariposario (Nombre) VALUES
('Mariposario 1'),('Mariposario 2'),('Mariposario 3'),('Mariposario 4'),
('Mariposario 5'),('Mariposario 6'),('Mariposario 7'),('Mariposario 8');

CREATE TABLE Especie (
    ID_Especie INT AUTO_INCREMENT PRIMARY KEY,
    Nombre_Cientifico VARCHAR(150) NOT NULL,
    Nombre_Comun VARCHAR(150),
    Descripcion TEXT,
    Imagen_URL TEXT,
    Activa BOOLEAN DEFAULT TRUE,
    UNIQUE KEY uq_especie_cientifico (Nombre_Cientifico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Especie_Param (
    ID_Especie INT PRIMARY KEY,
    Horas_ReciénNacida_A_Juvenil INT NOT NULL DEFAULT 240,
    Horas_Juvenil_A_Adulta INT NOT NULL DEFAULT 120,
    Horas_Adulta_A_Pupa INT NOT NULL DEFAULT 0,
    Horas_Pupa_A_Adulta INT NOT NULL DEFAULT 168,
    Mult_ReciénNacida DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    Mult_Juvenil DECIMAL(5,2) NOT NULL DEFAULT 0.75,
    Mult_Adulta DECIMAL(5,2) NOT NULL DEFAULT 0.10,
    Mult_Pupa_Tierna DECIMAL(5,2) NOT NULL DEFAULT 1.25,
    Mult_Pupa_Joven DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    Mult_Pupa_Vieja DECIMAL(5,2) NOT NULL DEFAULT 0.50,
    FOREIGN KEY (ID_Especie) REFERENCES Especie(ID_Especie) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enlazar Producto con Especie (opcional)
ALTER TABLE Producto
    ADD COLUMN ID_Especie INT NULL,
    ADD CONSTRAINT fk_producto_especie
        FOREIGN KEY (ID_Especie) REFERENCES Especie(ID_Especie)
        ON DELETE SET NULL;

-- Ubicar inventario físico en un mariposario (opcional)
ALTER TABLE Inventario
    ADD COLUMN ID_Mariposario INT NULL,
    ADD CONSTRAINT fk_inventario_mariposario
        FOREIGN KEY (ID_Mariposario) REFERENCES Mariposario(ID_Mariposario)
        ON DELETE SET NULL;

-- 4.2 Lotes, Historial y Alertas
CREATE TABLE Lote_Mariposa (
    ID_Lote BIGINT AUTO_INCREMENT PRIMARY KEY,
    ID_Mariposario INT NOT NULL,
    ID_Especie INT NOT NULL,
    Etapa ENUM('Recién Nacida','Juvenil','Adulta','Pupa') NOT NULL,
    Pupa_Edad ENUM('tierna','joven','vieja') NULL,
    Cantidad INT NOT NULL CHECK (Cantidad > 0),
    Fecha_Ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Fecha_Siguiente_Transicion DATETIME NULL,
    Notas TEXT,
    FOREIGN KEY (ID_Mariposario) REFERENCES Mariposario(ID_Mariposario) ON DELETE CASCADE,
    FOREIGN KEY (ID_Especie) REFERENCES Especie(ID_Especie) ON DELETE CASCADE,
    INDEX idx_lote_marip (ID_Mariposario, ID_Especie, Etapa, Fecha_Siguiente_Transicion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Historial_Ingresos (
    ID_Historial BIGINT AUTO_INCREMENT PRIMARY KEY,
    ID_Mariposario INT NOT NULL,
    ID_Especie INT NOT NULL,
    Etapa_Inicial ENUM('Recién Nacida','Juvenil','Adulta','Pupa') NOT NULL,
    Pupa_Edad_Inicial ENUM('tierna','joven','vieja') NULL,
    Cantidad INT NOT NULL,
    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Tipo_Accion ENUM('Ingreso','Egreso','Ajuste') NOT NULL DEFAULT 'Ingreso',
    FOREIGN KEY (ID_Mariposario) REFERENCES Mariposario(ID_Mariposario) ON DELETE CASCADE,
    FOREIGN KEY (ID_Especie) REFERENCES Especie(ID_Especie) ON DELETE CASCADE,
    INDEX idx_historial_fechas (Fecha),
    INDEX idx_historial_marip (ID_Mariposario, ID_Especie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE Alerta_Mariposario (
    ID_Alerta BIGINT AUTO_INCREMENT PRIMARY KEY,
    ID_Mariposario INT NOT NULL,
    ID_Especie INT NULL,
    Tipo ENUM('Pupa por eclosionar','Capacidad al límite','Ciclo por completarse') NOT NULL,
    Mensaje TEXT NOT NULL,
    Fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    Atendida BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (ID_Mariposario) REFERENCES Mariposario(ID_Mariposario) ON DELETE CASCADE,
    FOREIGN KEY (ID_Especie) REFERENCES Especie(ID_Especie) ON DELETE SET NULL,
    INDEX idx_alerta_estado (Atendida, Fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4.3 Vistas
CREATE OR REPLACE VIEW vw_dashboard_mariposarios AS
SELECT
    m.ID_Mariposario,
    m.Nombre AS Mariposario,
    COUNT(DISTINCT CASE WHEN l.Cantidad > 0 THEN l.ID_Especie END) AS Especies_Activas,
    SUM(CASE WHEN l.Etapa = 'Pupa' THEN l.Cantidad ELSE 0 END) AS Pupas_Actuales,
    SUM(CASE WHEN l.Etapa <> 'Pupa' THEN l.Cantidad ELSE 0 END) AS Mariposas_Actuales,
    MIN(l.Fecha_Siguiente_Transicion) AS Proxima_Transicion
FROM Mariposario m
LEFT JOIN Lote_Mariposa l ON l.ID_Mariposario = m.ID_Mariposario
GROUP BY m.ID_Mariposario, m.Nombre;

CREATE OR REPLACE VIEW vw_promedios_desarrollo_especie AS
SELECT
    e.ID_Especie,
    e.Nombre_Cientifico,
    e.Nombre_Comun,
    ep.Horas_ReciénNacida_A_Juvenil * ep.Mult_ReciénNacida AS H_ReciénNacida_A_Juvenil,
    ep.Horas_Juvenil_A_Adulta      * ep.Mult_Juvenil      AS H_Juvenil_A_Adulta,
    ep.Horas_Pupa_A_Adulta                                  AS H_Pupa_A_Adulta_Base
FROM Especie e
JOIN Especie_Param ep ON ep.ID_Especie = e.ID_Especie;

CREATE OR REPLACE VIEW vw_lotes_detalle AS
SELECT
    l.ID_Lote, l.ID_Mariposario, m.Nombre AS Mariposario,
    l.ID_Especie, e.Nombre_Cientifico, e.Nombre_Comun,
    l.Etapa, l.Pupa_Edad, l.Cantidad,
    l.Fecha_Ingreso, l.Fecha_Siguiente_Transicion
FROM Lote_Mariposa l
JOIN Mariposario m ON m.ID_Mariposario = l.ID_Mariposario
JOIN Especie e ON e.ID_Especie = l.ID_Especie;

-- 4.4 Índices de búsqueda
CREATE INDEX idx_especie_nombre ON Especie (Nombre_Cientifico, Nombre_Comun);

-- 4.5 Triggers de integridad y cálculo
DELIMITER $$

-- Capacidad de especies por mariposario
CREATE TRIGGER trg_lote_before_insert_capacidad_especies
BEFORE INSERT ON Lote_Mariposa
FOR EACH ROW
BEGIN
    DECLARE especies_activas INT DEFAULT 0;
    DECLARE cap INT DEFAULT 4;

    SELECT Capacidad_Especies INTO cap
    FROM Mariposario WHERE ID_Mariposario = NEW.ID_Mariposario;

    SELECT COUNT(DISTINCT ID_Especie) INTO especies_activas
    FROM Lote_Mariposa
    WHERE ID_Mariposario = NEW.ID_Mariposario
      AND Cantidad > 0;

    IF NOT EXISTS (
        SELECT 1 FROM Lote_Mariposa
        WHERE ID_Mariposario = NEW.ID_Mariposario
          AND ID_Especie = NEW.ID_Especie
          AND Cantidad > 0
    ) THEN
        IF especies_activas >= cap THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Capacidad de especies alcanzada para este mariposario';
        END IF;
    END IF;
END$$

-- Impedir eliminar mariposario con lotes
CREATE TRIGGER trg_mariposario_before_delete_no_vacio
BEFORE DELETE ON Mariposario
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM Lote_Mariposa
        WHERE ID_Mariposario = OLD.ID_Mariposario
          AND Cantidad > 0
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No se puede eliminar el mariposario: aún tiene individuos/pupas';
    END IF;
END$$

-- Calcular siguiente transición al insertar lote
CREATE TRIGGER trg_lote_before_insert_calculo_transicion
BEFORE INSERT ON Lote_Mariposa
FOR EACH ROW
BEGIN
    DECLARE h INT DEFAULT NULL;
    DECLARE mult DECIMAL(5,2) DEFAULT 1.00;

    IF NEW.Etapa = 'Recién Nacida' THEN
        SELECT Horas_ReciénNacida_A_Juvenil, Mult_ReciénNacida INTO h, mult
        FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        SET NEW.Fecha_Siguiente_Transicion = DATE_ADD(NEW.Fecha_Ingreso, INTERVAL ROUND(h * mult) HOUR);

    ELSEIF NEW.Etapa = 'Juvenil' THEN
        SELECT Horas_Juvenil_A_Adulta, Mult_Juvenil INTO h, mult
        FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        SET NEW.Fecha_Siguiente_Transicion = DATE_ADD(NEW.Fecha_Ingreso, INTERVAL ROUND(h * mult) HOUR);

    ELSEIF NEW.Etapa = 'Adulta' THEN
        SELECT Horas_Adulta_A_Pupa, Mult_Adulta INTO h, mult
        FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        IF h IS NULL OR h = 0 THEN
            SET NEW.Fecha_Siguiente_Transicion = DATE_ADD(NEW.Fecha_Ingreso, INTERVAL 1 HOUR);
        ELSE
            SET NEW.Fecha_Siguiente_Transicion = DATE_ADD(NEW.Fecha_Ingreso, INTERVAL ROUND(h * mult) HOUR);
        END IF;

    ELSEIF NEW.Etapa = 'Pupa' THEN
        SELECT Horas_Pupa_A_Adulta INTO h
        FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;

        IF NEW.Pupa_Edad = 'tierna' THEN
            SELECT Mult_Pupa_Tierna INTO mult FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        ELSEIF NEW.Pupa_Edad = 'joven' THEN
            SELECT Mult_Pupa_Joven INTO mult FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        ELSEIF NEW.Pupa_Edad = 'vieja' THEN
            SELECT Mult_Pupa_Vieja INTO mult FROM Especie_Param WHERE ID_Especie = NEW.ID_Especie;
        ELSE
            SET mult = 1.00;
        END IF;

        SET NEW.Fecha_Siguiente_Transicion = DATE_ADD(NEW.Fecha_Ingreso, INTERVAL ROUND(h * mult) HOUR);
    END IF;

    -- Guardar historial en el alta
    INSERT INTO Historial_Ingresos (
        ID_Mariposario, ID_Especie, Etapa_Inicial, Pupa_Edad_Inicial, Cantidad, Tipo_Accion
    ) VALUES (NEW.ID_Mariposario, NEW.ID_Especie, NEW.Etapa, NEW.Pupa_Edad, NEW.Cantidad, 'Ingreso');
END$$

-- Alerta por capacidad de pupas (80%)
CREATE TRIGGER trg_lote_after_insert_alerta_capacidad
AFTER INSERT ON Lote_Mariposa
FOR EACH ROW
BEGIN
    DECLARE cap INT DEFAULT 0;
    DECLARE total_pupas INT DEFAULT 0;

    SELECT Capacidad_Pupas INTO cap FROM Mariposario WHERE ID_Mariposario = NEW.ID_Mariposario;
    SELECT COALESCE(SUM(Cantidad),0) INTO total_pupas
    FROM Lote_Mariposa
    WHERE ID_Mariposario = NEW.ID_Mariposario AND Etapa = 'Pupa';

    IF cap > 0 AND total_pupas >= (cap * 0.80) THEN
        INSERT INTO Alerta_Mariposario (ID_Mariposario, ID_Especie, Tipo, Mensaje)
        VALUES (NEW.ID_Mariposario, NEW.ID_Especie, 'Capacidad al límite',
                CONCAT('Pupas al ', ROUND((total_pupas/cap)*100), '% de la capacidad.'));
        INSERT INTO Notificacion (ID_Usuario, Tipo_Notificacion, Mensaje)
        VALUES (NULL, 'Alerta', CONCAT('Mariposario #', NEW.ID_Mariposario, ': pupas al ', ROUND((total_pupas/cap)*100), '%'));
    END IF;
END$$
DELIMITER ;

-- 4.6 Procedimiento para alta de lotes
DELIMITER $$
CREATE PROCEDURE sp_agregar_lote (
    IN p_ID_Mariposario INT,
    IN p_ID_Especie INT,
    IN p_Etapa VARCHAR(20),
    IN p_Pupa_Edad VARCHAR(10),
    IN p_Cantidad INT,
    IN p_Fecha_Ingreso DATETIME
)
BEGIN
    IF p_Cantidad IS NULL OR p_Cantidad <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cantidad debe ser > 0';
    END IF;

    INSERT INTO Lote_Mariposa (ID_Mariposario, ID_Especie, Etapa, Pupa_Edad, Cantidad, Fecha_Ingreso)
    VALUES (p_ID_Mariposario, p_ID_Especie, p_Etapa, p_Pupa_Edad, p_Cantidad, IFNULL(p_Fecha_Ingreso, NOW()));
END$$
DELIMITER ;

-- ====================================================================
-- 2. INSERCIÓN DE DATOS INICIALES (CORE + CONSULTA + NOTIFICACIONES)
-- ====================================================================

-- Roles
INSERT INTO Rol (ID_Rol, Nombre, Tipo_Notificacion, Descripcion) VALUES
(1, 'Administrador', 'Email y SMS', 'Usuario con acceso completo al sistema'),
(2, 'Cliente', 'Email', 'Usuario cliente que puede hacer compras y reservas'),
(3, 'Empleado', 'Email', 'Empleado del mariposario con acceso limitado');

-- Usuarios (pass: 123456 bcrypt)
INSERT INTO Usuario (ID_Usuario, ID_Rol, Nombre, Apellido, Correo, Contrasena, Telefono, Direccion) VALUES
(1, 1, 'Carlos', 'Rodríguez', 'admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2234-5678', 'San José, Costa Rica'),
(2, 1, 'María', 'González', 'maria.admin@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2345-6789', 'Cartago, Costa Rica'),
(3, 3, 'Ana', 'Jiménez', 'ana.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2567-8901', 'Heredia, Costa Rica'),
(4, 3, 'Pedro', 'Vargas', 'pedro.empleado@mariposario.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2678-9012', 'Puntarenas, Costa Rica'),
(5, 2, 'Laura', 'Castillo', 'laura.cliente@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2890-1234', 'San José, Escazú'),
(6, 2, 'Diego', 'Ramírez', 'diego.cliente@hotmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2901-2345', 'Cartago, Paraíso'),
(7, 2, 'Valeria', 'Solano', 'valeria.cliente@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2012-3456', 'Alajuela, Atenas');

-- Empleados
INSERT INTO Empleado (ID_Usuario, Nombre, Correo, Salario, Rol, Horario, Fecha_Contratacion) VALUES
(3, 'Ana Jiménez', 'ana.empleado@mariposario.com', 850000.00, 'Guía', 'L-V 8am-4pm', '2023-01-15'),
(4, 'Pedro Vargas', 'pedro.empleado@mariposario.com', 900000.00, 'Mantenimiento', 'M-S 7am-3pm', '2022-07-01');

-- Productos (mariposas)
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Hamadrias laodamia', 'Mariposa', 'La Hamadrias laodamia es conocida como "mariposa caracolera". Presenta un patrón de puntos y rayas en tonos negros y azules metálicos.', 750.00, 10, 'https://static.inaturalist.org/photos/337837645/medium.jpg', '2025-07-15', TRUE),
('Mircelia cisniris', 'Mariposa', 'Patrones irregulares y tonos marrones/naranjas; bosques húmedos.', 990.00, 5, 'https://southcoastbotanicgarden.org/wp-content/uploads/2023/07/Shady-Spots-4.jpg', '2025-07-18', TRUE),
('Morpho', 'Mariposa', 'Azul metálico brillante; selvas tropicales.', 600.00, 12, 'https://contexto.udlap.mx/wp-content/uploads/2021/11/mariposa.jpg', '2025-07-20', TRUE),
('Igna', 'Mariposa', 'Exótica con manchas y tonos vivos; diurna.', 750.00, 8, 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/20/Agraulis_vanillae_at_Isla_Margarita.jpg/250px-Agraulis_vanillae_at_Isla_Margarita.jpg', '2025-08-20', TRUE),
('Catonephele mexicana', 'Mariposa', 'Alas negras con puntos anaranjados.', 820.00, 8, 'https://pictureinsect.com/wiki-image/1080/153820706105196555.jpeg', '2025-07-25', TRUE),
('Siproeta stelenis', 'Mariposa', 'Malaquita: verdes con bordes negros.', 925.00, 6, 'https://static.inaturalist.org/photos/33050984/medium.jpeg', '2025-07-27', TRUE),
('Archaeprepona', 'Mariposa', 'Género con colores metálicos llamativos.', 870.00, 9, 'https://i.redd.it/a-one-spot-prepona-archaeoprepona-demophon-aka-banded-king-v0-23ufry6af6je1.jpg?width=8688&format=pjpg&auto=webp', '2025-07-30', TRUE),
('Cónsul fabius', 'Mariposa', 'Camuflaje tipo hoja seca.', 650.00, 7, 'https://inaturalist-open-data.s3.amazonaws.com/photos/60274029/original.jpeg', '2025-08-05', TRUE);

-- Productos (orquídeas)
INSERT INTO Producto (Nombre, Categoria, Descripcion, Precio, Stock, Imagen_URL, Fecha_Reposicion, Activo_Catalogo)
VALUES
('Copper Queen', 'Orquídea', 'Híbrido elegante en tonos cobrizos.', 16500, 10, 'https://cdn11.bigcommerce.com/s-pxrevx9n0f/images/stencil/1280x1280/products/4593/23281/Rby._copper_queen11__53919.1730150731.jpg?c=2', '2025-08-15', 1),
('Sagarik Wax', 'Orquídea', 'Textura cerosa y colores vibrantes.', 15800, 12, 'https://static1.squarespace.com/static/55b8e840e4b0b3ab4e21b1dc/5e4daa9cee5bb23bd71cd226/5f491592401f346d88d01abe/1648083759123/IMG_5007.jpg?format=1500w', '2025-08-20', 1),
('Rungnapha Fancy', 'Orquídea', 'Combinación de colores vivos.', 18750, 8, 'https://www.tropicalorchidsportugal.com/wp-content/uploads/2024/03/cattleya-rungnapha-warm-welcome-01.jpg', '2025-08-22', 1),
('Cattleya máxima', 'Orquídea', 'Flores grandes y fragantes.', 20100, 15, 'https://www.shutterstock.com/image-photo/bright-purple-cattleya-maxima-orchid-600nw-1764983171.jpg', '2025-08-18', 1),
('Rlc Fort Waston', 'Orquídea', 'Colores intensos, flores grandes.', 16500, 6, 'https://orchidroots.com/static/utils/images/hybrid/Rhyncholaeliocattleya_100071713_000006953.jpeg', '2025-08-25', 1),
('Cattleya tenebrosa', 'Orquídea', 'Tonos oscuros y fragancia intensa.', 17000, 5, 'https://http2.mlstatic.com/D_NQ_NP_642976-MLB75419045260_042024-O-cattleya-laelia-tenebrosa-orquidea-adulta.webp', '2025-08-30', 1),
('Rlc Liu''s Joyance', 'Orquídea', 'Híbrido con flores grandes moradas/amarillas.', 16800, 7, 'https://orchidgarden.co.uk/wp-content/uploads/2023/09/Cattleya-Rlc.-Lius-Joyance-2.jpg', '2025-08-28', 1),
('Miltassia Shelob', 'Orquídea', 'Pétalos alargados moteados.', 22150, 9, 'https://www.orquidariooriental.com.br/produtos/396.jpg', '2025-08-24', 1),
('Grammatophyllum ', 'Orquídea', 'Racimos amarillos con manchas verdes.', 17550, 4, 'https://orchideeen-shop.nl/cdn/shop/products/orchid_big325.jpg?v=1676552033', '2025-09-01', 1),
('Cymbidium cinderella', 'Orquídea', 'Tonos pastel duraderos.', 19580, 10, 'https://media.istockphoto.com/id/1345528717/es/foto/flor-de-orqu%C3%ADdea-cymbidium.jpg?s=612x612', '2025-08-27', 1),
('Cymbidium tracyanum', 'Orquídea', 'Verdosas con marcas marrones.', 16200, 8, 'https://inaturalist-open-data.s3.amazonaws.com/photos/10919961/original.jpg', '2025-08-26', 1),
('Cymbidium marco polo', 'Orquídea', 'Elegantes en rosado.', 18400, 6, 'https://www.interflora.es/blog/wp-content/uploads/orquidea-cymbidium-1024x683.jpg.webp', '2025-08-29', 1);

-- Eventos
INSERT INTO Evento (Nombre, Descripcion, Precio, Fecha, Hora, Ubicacion, Imagen_URL)
VALUES
('Tour al mariposario',
'Disfruta de un recorrido guiado por nuestro espectacular mariposario en La Paz de Alajuela, donde podrás observar diferentes especies tropicales en su hábitat natural.', 
3500, '2025-08-10', '09:00:00', 'La Paz, Alajuela', 'https://a.travel-assets.com/findyours-php/viewfinder/images/res70/116000/116350-Butterfly-Farm.jpg'),
('Tour orquídeas',
'Explora la fascinante colección de orquídeas exóticas en nuestro jardín especializado.', 
3500, '2025-08-12', '10:00:00', 'La Paz, Alajuela', 'https://cdn-imgix.headout.com/tour/25577/TOUR-IMAGE/37f64084-d2ff-4f81-8b91-e399eaf665f2-NOG-4.JPG?auto=format&w=900&h=562.5&q=90&ar=16%3A10&crop=faces%2Ccenter&fit=crop'),
('Tour completo ', 
'Experiencia premium: mariposarios + orquídeas con guías expertos.', 
5000, '2025-08-15', '08:30:00', 'La Paz, Alajuela', 'https://media.istockphoto.com/id/849220498/photo/blue-tiger-butterfly-on-a-pink-zinnia-flower-with-green-background.jpg?b=1&s=612x612&w=0&k=20&c=vGMeC-fi_URdA3IhC_3MYb-068m1Jaxw9iwsuQcZmdo=');

-- Consulta (ejemplos)
INSERT INTO Consulta (ID_Usuario, Tema, Estado, Canal, Mensajes) VALUES
(5, 'Consulta', 'Pendiente', 'Chat', JSON_ARRAY(
    JSON_OBJECT('role', 'system', 'text', '¡Bienvenido! Un agente responderá en un máximo de 24 horas. Tema: Consulta', 'time', '10:00'),
    JSON_OBJECT('role', 'cliente', 'text', 'Hola, tengo una duda.', 'time', '10:02')
)),
(6, 'Reclamo', 'Respondido', 'Correo', JSON_ARRAY(
    JSON_OBJECT('role', 'cliente', 'text', 'Mi pedido llegó incompleto.', 'time', '11:10'),
    JSON_OBJECT('role', 'admin', 'text', 'Estamos revisando su caso, le avisaremos pronto.', 'time', '11:15')
));

-- Notificaciones iniciales (ejemplo para usuario 8 si existiera)
INSERT INTO Notificacion (ID_Usuario, Tipo_Notificacion, Mensaje)
VALUES
(5, 'Bienvenida', '¡Bienvenido a Eco Mariposas! Gracias por registrarte.'),
(5, 'Pago Confirmado', 'Tu pago ha sido confirmado exitosamente.'),
(5, 'Pedido Enviado', 'Tu pedido ha sido enviado y está en camino.'),
(5, 'Evento Recordatorio', 'Recuerda tu evento "Tour al mariposario" el 10/08/2025.'),
(5, 'Promoción', '¡Descuento especial del 20% en tu próxima compra!'),
(5, 'Encuesta', '¿Qué te pareció tu compra? Haz clic en el botón para opinar.');

-- ====================================================================
-- 4.8 DATOS INICIALES DEL MÓDULO DE MARIPOSAS
-- ====================================================================

INSERT INTO Especie (Nombre_Cientifico, Nombre_Comun, Descripcion, Imagen_URL) VALUES
('Hamadryas laodamia','Caracolera','Patrón negro/azul metálico','https://static.inaturalist.org/photos/337837645/medium.jpg'),
('Morpho sp.','Morpho azul','Azul metálico brillante','https://contexto.udlap.mx/wp-content/uploads/2021/11/mariposa.jpg'),
('Siproeta stelenes','Malaquita','Verdes con bordes negros','https://static.inaturalist.org/photos/33050984/medium.jpeg'),
('Catonephele mexicana','Catoneféle mexicana','Puntos anaranjados sobre negro','https://pictureinsect.com/wiki-image/1080/153820706105196555.jpeg');

INSERT INTO Especie_Param (ID_Especie)
SELECT ID_Especie FROM Especie;

-- Lotes de ejemplo (para visualizar contadores)
CALL sp_agregar_lote(1, 1, 'Recién Nacida', NULL , 100, NOW());
CALL sp_agregar_lote(1, 2, 'Pupa',         'joven',  40, NOW());
CALL sp_agregar_lote(2, 3, 'Juvenil',      NULL ,    60, NOW());

-- ====================================================================
-- 3. CONSULTAS ÚTILES (Opcional, para panel y pruebas)
-- ====================================================================

-- SELECT * FROM vw_dashboard_mariposarios;
-- SELECT * FROM vw_promedios_desarrollo_especie;
-- SELECT * FROM vw_lotes_detalle WHERE Etapa='Pupa' ORDER BY Fecha_Siguiente_Transicion;
-- SELECT * FROM vw_lotes_detalle WHERE Nombre_Cientifico LIKE '%morpho%';

-- Última semana (historial ingresos)
-- SELECT DATE(Fecha) Dia, SUM(Cantidad) Cantidad
-- FROM Historial_Ingresos
-- WHERE Fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
-- GROUP BY DATE(Fecha) ORDER BY Dia;

-- ====================================================================
-- NOTAS
-- - Para eventos programados: SET GLOBAL event_scheduler = ON;
-- - Para facturas (PHP): composer install
-- ====================================================================

USE mariposarioDB;

CREATE OR REPLACE VIEW parametros_ciclo AS
SELECT 
  ID_Especie,
  Horas_ReciénNacida_A_Juvenil,
  Horas_Juvenil_A_Adulta,
  Horas_Adulta_A_Pupa,
  Horas_Pupa_A_Adulta,
  Mult_ReciénNacida,
  Mult_Juvenil,
  Mult_Adulta,
  Mult_Pupa_Tierna,
  Mult_Pupa_Joven,
  Mult_Pupa_Vieja
FROM Especie_Param;

CREATE OR REPLACE VIEW mariposario_especie AS
SELECT 
  ID_Mariposario,
  ID_Especie,
  SUM(Cantidad) AS Cantidad
FROM Lote_Mariposa
GROUP BY ID_Mariposario, ID_Especie;


