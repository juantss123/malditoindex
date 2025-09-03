-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-09-2025 a las 00:19:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `dentexaproaaa`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateAppointmentWithReminder` (IN `p_clinic_id` VARCHAR(36), IN `p_patient_id` VARCHAR(36), IN `p_doctor_id` VARCHAR(36), IN `p_appointment_date` DATETIME, IN `p_duration_minutes` INT, IN `p_treatment_type` VARCHAR(100), IN `p_reminder_type` ENUM('email','whatsapp','sms'), IN `p_reminder_hours_before` INT)   BEGIN
    DECLARE appointment_id VARCHAR(36) DEFAULT (UUID());
    DECLARE reminder_id VARCHAR(36) DEFAULT (UUID());
    DECLARE reminder_datetime DATETIME;
    
    -- Calcular fecha del recordatorio
    SET reminder_datetime = DATE_SUB(p_appointment_date, INTERVAL p_reminder_hours_before HOUR);
    
    -- Insertar turno
    INSERT INTO appointments (id, clinic_id, patient_id, doctor_id, appointment_date, duration_minutes, treatment_type, status)
    VALUES (appointment_id, p_clinic_id, p_patient_id, p_doctor_id, p_appointment_date, p_duration_minutes, p_treatment_type, 'scheduled');
    
    -- Insertar recordatorio
    INSERT INTO reminders (id, appointment_id, reminder_type, reminder_time, message, status)
    VALUES (
        reminder_id, 
        appointment_id, 
        p_reminder_type, 
        reminder_datetime,
        CONCAT('Recordatorio: Tenés turno el ', DATE_FORMAT(p_appointment_date, '%d/%m/%Y a las %H:%i')),
        'pending'
    );
    
    SELECT appointment_id as created_appointment_id, reminder_id as created_reminder_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetClinicStats` (IN `p_clinic_id` VARCHAR(36))   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM patients WHERE clinic_id = p_clinic_id) as total_patients,
        (SELECT COUNT(*) FROM appointments WHERE clinic_id = p_clinic_id AND appointment_date >= CURDATE()) as upcoming_appointments,
        (SELECT COUNT(*) FROM appointments WHERE clinic_id = p_clinic_id AND appointment_date >= CURDATE() AND appointment_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as this_week_appointments,
        (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE clinic_id = p_clinic_id AND status = 'paid' AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_revenue,
        (SELECT COUNT(*) FROM invoices WHERE clinic_id = p_clinic_id AND status = 'paid') as total_invoices;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appointments`
--

CREATE TABLE `appointments` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `clinic_id` varchar(36) NOT NULL,
  `patient_id` varchar(36) NOT NULL,
  `doctor_id` varchar(36) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `treatment_type` varchar(100) DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoices`
--

CREATE TABLE `invoices` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `clinic_id` varchar(36) NOT NULL,
  `patient_id` varchar(36) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `invoice_id` varchar(36) NOT NULL,
  `treatment_id` varchar(36) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `invoice_items`
--
DELIMITER $$
CREATE TRIGGER `calculate_invoice_totals` AFTER INSERT ON `invoice_items` FOR EACH ROW BEGIN
    UPDATE invoices 
    SET 
        subtotal = (SELECT COALESCE(SUM(total_price), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id),
        tax_amount = (SELECT COALESCE(SUM(total_price), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id) * 0.21,
        total_amount = (SELECT COALESCE(SUM(total_price), 0) FROM invoice_items WHERE invoice_id = NEW.invoice_id) * 1.21
    WHERE id = NEW.invoice_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medical_records`
--

CREATE TABLE `medical_records` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `patient_id` varchar(36) NOT NULL,
  `appointment_id` varchar(36) DEFAULT NULL,
  `doctor_id` varchar(36) NOT NULL,
  `record_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `patients`
--

CREATE TABLE `patients` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `clinic_id` varchar(36) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reminders`
--

CREATE TABLE `reminders` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `appointment_id` varchar(36) NOT NULL,
  `reminder_type` enum('email','whatsapp','sms') NOT NULL,
  `reminder_time` datetime NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subscription_history`
--

CREATE TABLE `subscription_history` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) NOT NULL,
  `plan` enum('start','clinic','enterprise') NOT NULL,
  `status` enum('active','cancelled','expired') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `treatments`
--

CREATE TABLE `treatments` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `clinic_id` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` varchar(36) NOT NULL DEFAULT uuid(),
  `user_id` varchar(36) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `clinic_name` varchar(255) NOT NULL,
  `license_number` varchar(50) DEFAULT '',
  `specialty` varchar(100) DEFAULT '',
  `team_size` varchar(20) DEFAULT '1',
  `role` enum('user','admin') DEFAULT 'user',
  `subscription_status` enum('trial','active','expired','cancelled') DEFAULT 'trial',
  `subscription_plan` enum('start','clinic','enterprise') DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `trial_start_date` timestamp NULL DEFAULT current_timestamp(),
  `trial_end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `clinic_name`, `license_number`, `specialty`, `team_size`, `role`, `subscription_status`, `subscription_plan`, `password_hash`, `trial_start_date`, `trial_end_date`, `created_at`, `updated_at`) VALUES
('admin-uuid-1234-5678-9012-1234567890', 'admin-user-1234-5678-9012-1234567890', 'Administrador', 'Sistema', 'admin@dentexapro.com', '+54 9 11 1234-5678', 'DentexaPro Admin', 'ADMIN001', 'administracion', '1', 'admin', 'active', NULL, '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', '2025-09-03 22:18:08', '2026-09-03 22:18:08', '2025-09-03 22:18:08', '2025-09-03 22:18:08'),
('fernando-uuid-1234-5678-9012-1234567', 'fernando-user-1234-5678-9012-1234567', 'Dr. Fernando', 'García', 'fernando@fernando.com', '+54 9 11 3456-7890', 'Clínica García', 'MP67890', 'ortodontia', '2-3', 'user', 'active', 'clinic', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', '2025-08-04 22:18:08', '2026-08-04 22:18:08', '2025-08-04 22:18:08', '2025-09-03 22:18:08'),
('juan-uuid-1234-5678-9012-12345678901', 'juan-user-1234-5678-9012-12345678901', 'Dr. Juan', 'Pérez', 'juan@juan.com', '+54 9 11 2345-6789', 'Consultorio Dr. Pérez', 'MP12345', 'general', '1', 'user', 'trial', 'start', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', '2025-08-29 22:18:08', '2025-09-13 22:18:08', '2025-08-29 22:18:08', '2025-09-03 22:18:08');

--
-- Disparadores `user_profiles`
--
DELIMITER $$
CREATE TRIGGER `set_trial_end_date` BEFORE INSERT ON `user_profiles` FOR EACH ROW BEGIN
    IF NEW.trial_end_date IS NULL AND NEW.subscription_status = 'trial' THEN
        SET NEW.trial_end_date = DATE_ADD(NEW.trial_start_date, INTERVAL 15 DAY);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_appointments_summary`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_appointments_summary` (
`id` varchar(36)
,`appointment_date` datetime
,`duration_minutes` int(11)
,`treatment_type` varchar(100)
,`status` enum('scheduled','confirmed','completed','cancelled','no_show')
,`patient_name` varchar(201)
,`patient_phone` varchar(20)
,`doctor_name` varchar(201)
,`clinic_name` varchar(255)
,`notes` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_revenue_summary`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_revenue_summary` (
`clinic_name` varchar(255)
,`clinic_id` varchar(36)
,`total_invoices` bigint(21)
,`total_revenue` decimal(32,2)
,`monthly_revenue` decimal(32,2)
,`avg_invoice_amount` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_users_complete`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_users_complete` (
`id` varchar(36)
,`user_id` varchar(36)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`full_name` varchar(201)
,`email` varchar(255)
,`phone` varchar(20)
,`clinic_name` varchar(255)
,`license_number` varchar(50)
,`specialty` varchar(100)
,`team_size` varchar(20)
,`role` enum('user','admin')
,`subscription_status` enum('trial','active','expired','cancelled')
,`subscription_plan` enum('start','clinic','enterprise')
,`trial_start_date` timestamp
,`trial_end_date` timestamp
,`trial_days_remaining` int(7)
,`user_created_at` timestamp
,`user_updated_at` timestamp
,`total_patients` bigint(21)
,`upcoming_appointments` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_appointments_summary`
--
DROP TABLE IF EXISTS `v_appointments_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_appointments_summary`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_date` AS `appointment_date`, `a`.`duration_minutes` AS `duration_minutes`, `a`.`treatment_type` AS `treatment_type`, `a`.`status` AS `status`, concat(`p`.`first_name`,' ',`p`.`last_name`) AS `patient_name`, `p`.`phone` AS `patient_phone`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `doctor_name`, `up`.`clinic_name` AS `clinic_name`, `a`.`notes` AS `notes` FROM ((`appointments` `a` join `patients` `p` on(`a`.`patient_id` = `p`.`id`)) join `user_profiles` `up` on(`a`.`doctor_id` = `up`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_revenue_summary`
--
DROP TABLE IF EXISTS `v_revenue_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_revenue_summary`  AS SELECT `up`.`clinic_name` AS `clinic_name`, `up`.`user_id` AS `clinic_id`, count(`i`.`id`) AS `total_invoices`, sum(case when `i`.`status` = 'paid' then `i`.`total_amount` else 0 end) AS `total_revenue`, sum(case when `i`.`status` = 'paid' and `i`.`invoice_date` >= current_timestamp() - interval 30 day then `i`.`total_amount` else 0 end) AS `monthly_revenue`, avg(case when `i`.`status` = 'paid' then `i`.`total_amount` else NULL end) AS `avg_invoice_amount` FROM (`user_profiles` `up` left join `invoices` `i` on(`up`.`user_id` = `i`.`clinic_id`)) WHERE `up`.`role` = 'user' GROUP BY `up`.`user_id`, `up`.`clinic_name` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_users_complete`
--
DROP TABLE IF EXISTS `v_users_complete`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_users_complete`  AS SELECT `up`.`id` AS `id`, `up`.`user_id` AS `user_id`, `up`.`first_name` AS `first_name`, `up`.`last_name` AS `last_name`, concat(`up`.`first_name`,' ',`up`.`last_name`) AS `full_name`, `up`.`email` AS `email`, `up`.`phone` AS `phone`, `up`.`clinic_name` AS `clinic_name`, `up`.`license_number` AS `license_number`, `up`.`specialty` AS `specialty`, `up`.`team_size` AS `team_size`, `up`.`role` AS `role`, `up`.`subscription_status` AS `subscription_status`, `up`.`subscription_plan` AS `subscription_plan`, `up`.`trial_start_date` AS `trial_start_date`, `up`.`trial_end_date` AS `trial_end_date`, CASE WHEN `up`.`subscription_status` = 'trial' AND `up`.`trial_end_date` > current_timestamp() THEN to_days(`up`.`trial_end_date`) - to_days(current_timestamp()) ELSE 0 END AS `trial_days_remaining`, `up`.`created_at` AS `user_created_at`, `up`.`updated_at` AS `user_updated_at`, (select count(0) from `patients` `p` where `p`.`clinic_id` = `up`.`user_id`) AS `total_patients`, (select count(0) from `appointments` `a` where `a`.`clinic_id` = `up`.`user_id` and `a`.`appointment_date` >= curdate()) AS `upcoming_appointments` FROM `user_profiles` AS `up` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinic_id` (`clinic_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_appointment_date` (`appointment_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_clinic_id` (`clinic_id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_treatment_id` (`treatment_id`);

--
-- Indices de la tabla `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient_id` (`patient_id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_record_date` (`record_date`);

--
-- Indices de la tabla `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinic_id` (`clinic_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointment_id` (`appointment_id`),
  ADD KEY `idx_reminder_time` (`reminder_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `treatments`
--
ALTER TABLE `treatments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinic_id` (`clinic_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indices de la tabla `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_subscription_status` (`subscription_status`),
  ADD KEY `idx_role` (`role`);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoices_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_invoice_items_treatment` FOREIGN KEY (`treatment_id`) REFERENCES `treatments` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `fk_medical_records_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_medical_records_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_medical_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `fk_reminders_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD CONSTRAINT `fk_subscription_history_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `treatments`
--
ALTER TABLE `treatments`
  ADD CONSTRAINT `fk_treatments_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `user_profiles` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
