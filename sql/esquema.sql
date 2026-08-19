CREATE DATABASE IF NOT EXISTS rincon_local CHARACTER SET utf8mb4;
USE rincon_local;

CREATE TABLE lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    categoria VARCHAR(60) NOT NULL,
    direccion VARCHAR(200),
    imagen VARCHAR(255) DEFAULT NULL,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lugar_id INT NOT NULL,
    autor VARCHAR(80) NOT NULL,
    comentario TEXT,
    rating_comida TINYINT NOT NULL,
    rating_servicio TINYINT NOT NULL,
    rating_ambiente TINYINT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lugar_id) REFERENCES lugares(id) ON DELETE CASCADE
);

-- Datos de prueba: lugares reales y reconocidos de Montería, Córdoba.
-- Coordenadas aproximadas; ajustar con Google Maps si se necesita precisión.
INSERT INTO lugares (nombre, categoria, direccion, latitud, longitud) VALUES
('La Ronda del Sinú', 'Parque / Paseo', 'Orilla del río Sinú, Montería', 8.7500, -75.8800),
('Catedral San Jerónimo', 'Lugar histórico', 'Calle 27 con Cra 3, Barrio Chuchurubí', 8.7519, -75.8814),
('Pasaje del Sol', 'Zona de bares y restaurantes', 'Zona norte, Montería', 8.7550, -75.8820),
('Parque Simón Bolívar', 'Parque', 'Centro de Montería', 8.7530, -75.8810),
('Restaurante Baalbeck', 'Restaurante - comida árabe', 'Montería, Córdoba', 8.7540, -75.8790);

CREATE TABLE configuracion (
    clave VARCHAR(50) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL
);

INSERT INTO configuracion (clave, valor) VALUES ('banner_sitio', 'assets/banner_monteria_collage.jpg');