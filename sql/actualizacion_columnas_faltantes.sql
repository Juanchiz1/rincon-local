USE rincon_local;

-- Columnas que le faltan a "lugares" (el código ya las usa en agregar_lugar.php y admin/editar_lugar.php)
ALTER TABLE lugares
    ADD COLUMN descripcion TEXT DEFAULT NULL AFTER direccion,
    ADD COLUMN horario_apertura TIME DEFAULT NULL AFTER latitud,
    ADD COLUMN horario_cierre TIME DEFAULT NULL AFTER horario_apertura,
    ADD COLUMN recomendaciones TEXT DEFAULT NULL AFTER horario_cierre;

-- Columnas que le faltan a "resenas" (usadas en api/guardar_resena.php)
ALTER TABLE resenas
    ADD COLUMN emoji VARCHAR(10) DEFAULT NULL AFTER comentario,
    ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER emoji;

-- Tabla que falta por completo: galería de fotos adicionales por lugar
CREATE TABLE IF NOT EXISTS lugares_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lugar_id INT NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    FOREIGN KEY (lugar_id) REFERENCES lugares(id) ON DELETE CASCADE
);

-- Tabla que falta por completo: administradores del panel /admin
CREATE TABLE IF NOT EXISTS administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);
