/*
  # Crear tabla para comprobantes de transferencia

  1. Nueva tabla
    - `transfer_proofs`
      - `id` (varchar, primary key)
      - `user_id` (varchar, foreign key)
      - `plan_type` (enum: start, clinic, enterprise)
      - `amount` (decimal)
      - `file_name` (varchar)
      - `file_path` (varchar)
      - `file_type` (varchar)
      - `file_size` (int)
      - `status` (enum: pending, approved, rejected)
      - `admin_notes` (text)
      - `processed_by` (varchar, admin user id)
      - `processed_at` (timestamp)
      - `created_at` (timestamp)
      - `updated_at` (timestamp)

  2. Índices
    - Índice en user_id para consultas rápidas
    - Índice en status para filtrar comprobantes
    - Índice en created_at para ordenamiento

  3. Relaciones
    - Foreign key con user_profiles
*/

-- Crear tabla para comprobantes de transferencia
CREATE TABLE IF NOT EXISTS transfer_proofs (
    id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    plan_type ENUM('start', 'clinic', 'enterprise') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    processed_by VARCHAR(36) DEFAULT NULL,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear índices para optimizar consultas
CREATE INDEX idx_transfer_proofs_user_id ON transfer_proofs(user_id);
CREATE INDEX idx_transfer_proofs_status ON transfer_proofs(status);
CREATE INDEX idx_transfer_proofs_created_at ON transfer_proofs(created_at);

-- Agregar claves foráneas
ALTER TABLE transfer_proofs 
ADD CONSTRAINT fk_transfer_proofs_user 
FOREIGN KEY (user_id) REFERENCES user_profiles(user_id) 
ON DELETE CASCADE;

ALTER TABLE transfer_proofs 
ADD CONSTRAINT fk_transfer_proofs_admin 
FOREIGN KEY (processed_by) REFERENCES user_profiles(user_id) 
ON DELETE SET NULL;

-- Insertar algunos datos de ejemplo para testing
INSERT INTO transfer_proofs (
    id, user_id, plan_type, amount, file_name, file_path, file_type, file_size, status, admin_notes
) VALUES 
(
    'sample-proof-1',
    'juan-user-1234-5678-9012-12345678901',
    'clinic',
    24.99,
    'comprobante_juan.jpg',
    'uploads/transfer_proofs/sample_proof_1.jpg',
    'image/jpeg',
    245760,
    'pending',
    NULL
),
(
    'sample-proof-2',
    'fernando-user-1234-5678-9012-1234567',
    'start',
    14.99,
    'transferencia_fernando.pdf',
    'uploads/transfer_proofs/sample_proof_2.pdf',
    'application/pdf',
    156432,
    'approved',
    'Transferencia verificada y aprobada'
);