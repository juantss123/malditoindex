/*
  # Agregar tabla para solicitudes de prueba gratuita

  1. Nueva tabla
    - `trial_requests`
      - `id` (varchar, primary key)
      - `user_id` (varchar, foreign key)
      - `request_date` (timestamp)
      - `status` (enum: pending, approved, rejected)
      - `admin_notes` (text)
      - `processed_by` (varchar, admin user id)
      - `processed_at` (timestamp)

  2. Índices
    - Índice en user_id para consultas rápidas
    - Índice en status para filtrar solicitudes
    - Índice en request_date para ordenamiento

  3. Relaciones
    - Foreign key con user_profiles
*/

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
    FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES user_profiles(user_id) ON DELETE SET NULL
);

-- Crear índices para optimizar consultas
CREATE INDEX idx_trial_requests_user_id ON trial_requests(user_id);
CREATE INDEX idx_trial_requests_status ON trial_requests(status);
CREATE INDEX idx_trial_requests_date ON trial_requests(request_date);

-- Vista para mostrar solicitudes con información del usuario
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