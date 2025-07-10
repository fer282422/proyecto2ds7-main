-- Primero, asegúrate de usar una base de datos existente o crea una nueva.
-- Por ejemplo, para crear una nueva base de datos llamada 'gaming_noticia_db':
-- CREATE DATABASE IF NOT EXISTS gaming_noticia_db;
-- USE gaming_noticia_db;

-- 1. Tabla para artículos
-- Aquí almacenaremos los detalles de los productos que se venden en tu "Tienda Exclusiva".
-- He añadido un campo 'nombre' que es crucial para poder mapear los productos del HTML.
-- También he añadido 'precio' para que puedas registrar el precio directamente en la base de datos si es necesario.
CREATE DATABASE IF NOT EXISTS articulos;
USE articulos;

CREATE TABLE IF NOT EXISTS articulos (
    id INT PRIMARY KEY AUTO_INCREMENT,   -- Identificador único para cada artículo
    nombre VARCHAR(255) NOT NULL UNIQUE, -- Nombre del artículo (ej. "Camisa 'Ghost of Yōtei'") - UNIQUE para evitar duplicados
    precio DECIMAL(10, 2) NOT NULL,      -- Precio del artículo
    contenido TEXT                       -- Descripción o contenido asociado al artículo (opcional)
);

-- 2. Tabla para usuarios
-- Esta tabla es necesaria para la clave foránea 'usuario_id' en 'articulos_comprados'.
-- Si aún no tienes un sistema de usuarios, puedes usar IDs de usuario temporales o genéricos.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT, -- Identificador único para cada usuario
    nombre_usuario VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL, -- Almacenar contraseñas hasheadas (nunca en texto plano)
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla para artículos comprados
-- Esta tabla registra una compra específica de un artículo por un usuario.
-- La fecha de compra es útil para saber cuándo se realizó la transacción.
CREATE TABLE IF NOT EXISTS articulos_comprados (
    id INT PRIMARY KEY AUTO_INCREMENT, -- Identificador único para cada registro de compra
    articulo_id INT NOT NULL,          -- Clave foránea que referencia el 'id' de la tabla 'articulos'
    usuario_id INT NOT NULL,           -- Clave foránea que referencia el 'id' de la tabla 'usuarios'
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha y hora de la compra

    -- Definir la restricción de clave foránea para 'articulo_id'
    FOREIGN KEY (articulo_id) REFERENCES articulos(id)
        ON DELETE RESTRICT -- Evita borrar un artículo si hay compras asociadas
        ON UPDATE CASCADE,  -- Si el ID de un artículo cambia, se actualiza aquí

    -- Definir la restricción de clave foránea para 'usuario_id'
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT -- Evita borrar un usuario si tiene compras asociadas
        ON UPDATE CASCADE
);

-- 4. Tabla para transacciones de pago (opcional, pero altamente recomendada)
-- Esta tabla almacena todos los detalles sensibles de la transacción de pago,
-- incluyendo los detalles de la tarjeta. Es crucial para un registro financiero completo.
CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    articulo_comprado_id INT, -- Referencia a la compra específica en articulos_comprados
    nombre_completo VARCHAR(255) NOT NULL,
    numero_tarjeta VARCHAR(255) NOT NULL, -- Considera el cifrado para datos sensibles
    vencimiento VARCHAR(10) NOT NULL,
    cvv VARCHAR(5) NOT NULL,              -- Nunca guardar CVV en un entorno real de producción (solo para el proceso de autorización)
    metodo_pago VARCHAR(50),
    monto_total DECIMAL(10, 2) NOT NULL,  -- El precio final de la transacción
    fecha_transaccion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_transaccion VARCHAR(50) DEFAULT 'Pendiente', -- Ej. Pendiente, Completada, Fallida

    FOREIGN KEY (articulo_comprado_id) REFERENCES articulos_comprados(id)
        ON DELETE SET NULL -- Si se elimina el registro de compra, se pone a NULL aquí
        ON UPDATE CASCADE
);
