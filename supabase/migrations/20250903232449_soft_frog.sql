-- Migración: Agregar tabla para solicitudes de prueba gratuita
-- Fecha: 2025-01-09
-- Descripción: Crear tabla trial_requests compatible con la estructura existente

-- Crear tabla para solicitudes de prueba gratuita
CREATE TABLE IF NOT EXISTS trial_requests (
    id VARCHAR(36) NOT NULL DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    processed_by VARCHAR(36) DEFAULT NULL,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_trial_requests_user_id (user_id),
    INDEX idx_trial_requests_status (status),
    INDEX idx_trial_requests_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar las claves foráneas después de crear la tabla
-- Esto evita problemas de dependencias circulares
ALTER TABLE trial_requests 
ADD CONSTRAINT fk_trial_requests_user 
FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) 
ON DELETE CASCADE;

ALTER TABLE trial_requests 
ADD CONSTRAINT fk_trial_requests_admin 
FOREIGN KEY (processed_by) REFERENCES user_profiles(user_id) 
ON DELETE SET NULL;

-- Crear vista para mostrar solicitudes con información del usuario
CREATE OR REPLACE VIEW v_trial_requests AS
SELECT 
    tr.id,
    tr.user_id,
    tr.request_date,
    tr.status,
    tr.admin_notes,
    tr.processed_at,
    CONCAT(up.first_name, ' ', up.last_name) as user_name,
    up.email,
    up.clinic_name,
    up.phone,
    up.subscription_status,
    CONCAT(admin.first_name, ' ', admin.last_name) as processed_by_name
FROM trial_requests tr
JOIN user_profiles up ON tr.user_id = up.user_id
LEFT JOIN user_profiles admin ON tr.processed_by = admin.user_id
ORDER BY tr.request_date DESC;