-- =============================================================================
-- Momora AI Database Schema
-- Database: momora_ai
-- Application: Momora AI - Personalized Pregnancy Monitoring & Guidance
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.4+ (XAMPP)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `momora_ai` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `momora_ai`;

-- -----------------------------------------------------------------------------
-- 1. Users Table (Core accounts for Patients, Doctors, Administrators)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure required columns exist if users table was created previously with older schema
SET @col_role = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='users' AND COLUMN_NAME='role');
SET @sql_role = IF(@col_role = 0, 'ALTER TABLE `users` ADD COLUMN `role` ENUM(\'patient\', \'doctor\', \'admin\') NOT NULL DEFAULT \'patient\' AFTER `password_hash`', 'SELECT 1');
PREPARE stmt_role FROM @sql_role; EXECUTE stmt_role; DEALLOCATE PREPARE stmt_role;

SET @col_active = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='users' AND COLUMN_NAME='is_active');
SET @sql_active = IF(@col_active = 0, 'ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`', 'SELECT 1');
PREPARE stmt_active FROM @sql_active; EXECUTE stmt_active; DEALLOCATE PREPARE stmt_active;

SET @col_updated = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='users' AND COLUMN_NAME='updated_at');
SET @sql_updated = IF(@col_updated = 0, 'ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`', 'SELECT 1');
PREPARE stmt_updated FROM @sql_updated; EXECUTE stmt_updated; DEALLOCATE PREPARE stmt_updated;

-- -----------------------------------------------------------------------------
-- 2. Auth Tokens (Secure Session Management)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `device_info` VARCHAR(255) NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_tokens_token` (`token`),
    INDEX `idx_tokens_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Patient Profiles (Demographics, Medical Background, Assigned Doctor)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `patient_code` VARCHAR(20) NULL,
    `phone` VARCHAR(20) NULL,
    `date_of_birth` DATE NULL,
    `gender` VARCHAR(30) DEFAULT 'Female',
    `blood_group` VARCHAR(15) DEFAULT 'O positive',
    `address` TEXT NULL,
    `is_care_team_sharing_on` TINYINT(1) NOT NULL DEFAULT 1,
    `assigned_doctor_id` INT NULL,
    `reminder_summary` VARCHAR(255) DEFAULT 'Medication and appointment alerts',
    `clinical_background` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_profiles_doctor` (`assigned_doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure columns exist in profiles if previously created
SET @col_pcode = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='patient_code');
SET @sql_pcode = IF(@col_pcode = 0, 'ALTER TABLE `profiles` ADD COLUMN `patient_code` VARCHAR(20) NULL AFTER `user_id`', 'SELECT 1');
PREPARE stmt_pcode FROM @sql_pcode; EXECUTE stmt_pcode; DEALLOCATE PREPARE stmt_pcode;

SET @col_bgroup = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='blood_group');
SET @sql_bgroup = IF(@col_bgroup = 0, 'ALTER TABLE `profiles` ADD COLUMN `blood_group` VARCHAR(15) DEFAULT \'O positive\' AFTER `gender`', 'SELECT 1');
PREPARE stmt_bgroup FROM @sql_bgroup; EXECUTE stmt_bgroup; DEALLOCATE PREPARE stmt_bgroup;

SET @col_sharing = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='is_care_team_sharing_on');
SET @sql_sharing = IF(@col_sharing = 0, 'ALTER TABLE `profiles` ADD COLUMN `is_care_team_sharing_on` TINYINT(1) NOT NULL DEFAULT 1 AFTER `address`', 'SELECT 1');
PREPARE stmt_sharing FROM @sql_sharing; EXECUTE stmt_sharing; DEALLOCATE PREPARE stmt_sharing;

SET @col_doc = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='assigned_doctor_id');
SET @sql_doc = IF(@col_doc = 0, 'ALTER TABLE `profiles` ADD COLUMN `assigned_doctor_id` INT NULL AFTER `is_care_team_sharing_on`', 'SELECT 1');
PREPARE stmt_doc FROM @sql_doc; EXECUTE stmt_doc; DEALLOCATE PREPARE stmt_doc;

SET @col_rem = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='reminder_summary');
SET @sql_rem = IF(@col_rem = 0, 'ALTER TABLE `profiles` ADD COLUMN `reminder_summary` VARCHAR(255) DEFAULT \'Medication and appointment alerts\' AFTER `assigned_doctor_id`', 'SELECT 1');
PREPARE stmt_rem FROM @sql_rem; EXECUTE stmt_rem; DEALLOCATE PREPARE stmt_rem;

SET @col_clin = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='momora_ai' AND TABLE_NAME='profiles' AND COLUMN_NAME='clinical_background');
SET @sql_clin = IF(@col_clin = 0, 'ALTER TABLE `profiles` ADD COLUMN `clinical_background` TEXT NULL AFTER `reminder_summary`', 'SELECT 1');
PREPARE stmt_clin FROM @sql_clin; EXECUTE stmt_clin; DEALLOCATE PREPARE stmt_clin;

-- -----------------------------------------------------------------------------
-- 4. Doctor Profiles (Clinician Credentials, License, Clinic)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `specialty` VARCHAR(150) NOT NULL DEFAULT 'Obstetrics',
    `license_id` VARCHAR(100) NOT NULL,
    `clinic` VARCHAR(150) NOT NULL DEFAULT 'Willow Maternity Clinic',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Pregnancy Details (LMP, EDD, Gestational Age, Trimester, Next Visit)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pregnancy_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `last_menstrual_period` DATE NULL,
    `estimated_due_date` DATE NOT NULL,
    `gestational_weeks` INT NOT NULL DEFAULT 24,
    `gestational_days` INT NOT NULL DEFAULT 3,
    `trimester` INT NOT NULL DEFAULT 2,
    `next_visit_date` DATETIME NULL,
    `next_visit_clinic` VARCHAR(150) DEFAULT 'Willow Maternity Clinic',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Blood Pressure Logs (Vitals Tracking & Trend Charting)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blood_pressure_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `systolic` INT NOT NULL,
    `diastolic` INT NOT NULL,
    `pulse` INT NULL DEFAULT 78,
    `notes` VARCHAR(255) NULL,
    `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_bp_user_date` (`user_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Weight Logs (Maternal Weight Tracking)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `weight_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `weight_kg` DECIMAL(5, 2) NOT NULL,
    `baseline_weight_kg` DECIMAL(5, 2) DEFAULT 59.00,
    `notes` VARCHAR(255) NULL,
    `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_weight_user_date` (`user_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. General Health Vitals (Heart rate, Oral temperature)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `health_vitals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `heart_rate` INT NOT NULL DEFAULT 78,
    `temperature` DECIMAL(4, 1) NOT NULL DEFAULT 36.7,
    `temperature_unit` VARCHAR(10) NOT NULL DEFAULT '°C',
    `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Symptom Logs (Symptom Logging, Severity, Duration, AI Advice)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `symptom_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `symptom_name` VARCHAR(150) NOT NULL,
    `severity` ENUM('MILD', 'MODERATE', 'SEVERE') NOT NULL DEFAULT 'MILD',
    `started_text` VARCHAR(100) NOT NULL,
    `duration_text` VARCHAR(100) NOT NULL,
    `ai_guidance` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_symptoms_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Baby Development (Fetal Milestones, Length, Weight Educational Data)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `baby_development` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `week` INT NOT NULL UNIQUE,
    `subtitle` VARCHAR(255) NOT NULL,
    `length_cm_text` VARCHAR(50) NOT NULL,
    `length_sub_text` VARCHAR(100) DEFAULT 'Head to heel · approximate',
    `weight_gram_text` VARCHAR(50) NOT NULL,
    `weight_sub_text` VARCHAR(100) DEFAULT 'educational estimate',
    `highlights` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Nutrition Plans (Trimester Dietary Guidance, Meals & Priorities)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nutrition_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trimester` INT NOT NULL UNIQUE,
    `trimester_title` VARCHAR(255) NOT NULL,
    `featured_meal_category` VARCHAR(100) NOT NULL,
    `featured_meal_title` VARCHAR(150) NOT NULL,
    `featured_meal_details` TEXT NOT NULL,
    `priorities` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. Medications & Reminders (Pill Tracker & Daily Routine)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `dosage_and_time` VARCHAR(150) NOT NULL,
    `doctor_prescription_note` VARCHAR(255) NULL,
    `is_taken` TINYINT(1) NOT NULL DEFAULT 0,
    `taken_time_note` VARCHAR(100) NULL,
    `due_later_text` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 13. Appointments (Clinic & Antenatal Visits)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NULL,
    `doctor_name` VARCHAR(150) NOT NULL,
    `doctor_specialty` VARCHAR(150) DEFAULT 'Obstetrician · Willow Maternity Clinic',
    `appointment_date` DATE NOT NULL,
    `appointment_time` VARCHAR(50) NOT NULL,
    `is_in_person` TINYINT(1) NOT NULL DEFAULT 1,
    `appointment_type` VARCHAR(100) DEFAULT 'Routine antenatal check',
    `location_notes` TEXT NULL,
    `status` ENUM('Scheduled', 'Confirmed', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Confirmed',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_appts_patient` (`patient_id`),
    INDEX `idx_appts_doctor` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 14. Medical Reports (Ultrasound, Lab Results & Visit Summaries Metadata)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `file_type` VARCHAR(50) NOT NULL DEFAULT 'PDF',
    `date_and_size_text` VARCHAR(100) NOT NULL,
    `category` ENUM('ALL', 'ULTRASOUND', 'LAB_RESULTS') NOT NULL DEFAULT 'ALL',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_reports_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 15. Notifications (Alerts, Reminders & Care-team Updates)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `subtitle` TEXT NOT NULL,
    `detail_text` TEXT NULL,
    `type` ENUM('APPOINTMENT', 'REMINDER', 'REPORT_REVIEW', 'WEEKLY_DISCOVERY') NOT NULL DEFAULT 'REMINDER',
    `is_new` TINYINT(1) NOT NULL DEFAULT 1,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 16. AI Chat History (Conversational Guidance & Disclaimer Metadata)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `sender` ENUM('USER', 'AI') NOT NULL,
    `message_text` TEXT NOT NULL,
    `bullet_points` JSON NULL,
    `footer_note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_chat_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 17. Doctor Advice & Consultations (Direct Clinician Guidance)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_advice` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `patient_id` INT NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `advice_text` TEXT NOT NULL,
    `save_copy` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 18. Admin Settings (MFA, Session Timeout, Security Logs)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL UNIQUE,
    `is_mfa_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `session_timeout_minutes` INT NOT NULL DEFAULT 15,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA & TEST ACCOUNTS
-- Password for all seed accounts: Password123!
-- (hash: $2y$10$wO36cI0zK95zXo12hXlCDeYfMsk8GgQ/tqU/c7z0vRjQ6Z7j1sPz2)
-- =============================================================================

-- Seed Doctor: Dr Amelia Chen
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('Dr Amelia Chen', 'amelia.chen@willow.example', '$2y$10$wO36cI0zK95zXo12hXlCDeYfMsk8GgQ/tqU/c7z0vRjQ6Z7j1sPz2', 'doctor', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `role`='doctor';

SET @doc_id = (SELECT `id` FROM `users` WHERE `email` = 'amelia.chen@willow.example' LIMIT 1);

INSERT INTO `doctor_profiles` (`user_id`, `specialty`, `license_id`, `clinic`)
VALUES (@doc_id, 'Obstetrics', 'OBS-19382', 'Willow Maternity Clinic')
ON DUPLICATE KEY UPDATE `specialty`=VALUES(`specialty`), `license_id`=VALUES(`license_id`);

-- Seed Admin: Alex Morgan
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('Alex Morgan', 'admin@willow.example', '$2y$10$wO36cI0zK95zXo12hXlCDeYfMsk8GgQ/tqU/c7z0vRjQ6Z7j1sPz2', 'admin', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `role`='admin';

SET @admin_id = (SELECT `id` FROM `users` WHERE `email` = 'admin@willow.example' LIMIT 1);

INSERT INTO `admin_settings` (`admin_id`, `is_mfa_enabled`, `session_timeout_minutes`)
VALUES (@admin_id, 1, 15)
ON DUPLICATE KEY UPDATE `is_mfa_enabled`=1;

-- Seed Patient: Maya Patel
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `is_active`)
VALUES ('Maya Patel', 'maya.patel@example.com', '$2y$10$wO36cI0zK95zXo12hXlCDeYfMsk8GgQ/tqU/c7z0vRjQ6Z7j1sPz2', 'patient', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `role`='patient';

SET @patient_id = (SELECT `id` FROM `users` WHERE `email` = 'maya.patel@example.com' LIMIT 1);

INSERT INTO `profiles` (`user_id`, `patient_code`, `phone`, `date_of_birth`, `gender`, `blood_group`, `address`, `is_care_team_sharing_on`, `assigned_doctor_id`, `clinical_background`)
VALUES (@patient_id, 'P-13042', '+1 (555) 234-5678', '1997-05-14', 'Female', 'O positive', 'Willow Springs, Apt 4B', 1, @doc_id, 'Age 29 · O positive · First pregnancy (G1 P0). No known drug allergies or chronic conditions reported.')
ON DUPLICATE KEY UPDATE `patient_code`='P-13042', `assigned_doctor_id`=@doc_id;

INSERT INTO `pregnancy_details` (`user_id`, `last_menstrual_period`, `estimated_due_date`, `gestational_weeks`, `gestational_days`, `trimester`, `next_visit_date`, `next_visit_clinic`)
VALUES (@patient_id, '2026-05-09', '2027-02-13', 24, 3, 2, '2026-10-29 10:30:00', 'Willow Clinic')
ON DUPLICATE KEY UPDATE `estimated_due_date`='2027-02-13';

-- Seed Vitals for Maya Patel
INSERT INTO `blood_pressure_logs` (`user_id`, `systolic`, `diastolic`, `pulse`, `notes`, `recorded_at`) VALUES
(@patient_id, 114, 70, 76, 'Morning check', '2026-10-21 08:00:00'),
(@patient_id, 110, 72, 75, 'Morning check', '2026-10-23 08:10:00'),
(@patient_id, 115, 68, 77, 'Morning check', '2026-10-25 08:05:00'),
(@patient_id, 112, 72, 78, 'Resting vitals', '2026-10-27 08:15:00');

INSERT INTO `weight_logs` (`user_id`, `weight_kg`, `baseline_weight_kg`, `recorded_at`) VALUES
(@patient_id, 61.2, 59.0, '2026-09-15 08:00:00'),
(@patient_id, 62.5, 59.0, '2026-09-29 08:00:00'),
(@patient_id, 63.8, 59.0, '2026-10-20 08:00:00'),
(@patient_id, 64.2, 59.0, '2026-10-27 08:10:00');

INSERT INTO `health_vitals` (`user_id`, `heart_rate`, `temperature`, `temperature_unit`, `recorded_at`)
VALUES (@patient_id, 78, 36.7, '°C', '2026-10-27 08:15:00');

-- Seed Daily Care Medications
INSERT INTO `medications` (`user_id`, `name`, `dosage_and_time`, `doctor_prescription_note`, `is_taken`, `taken_time_note`, `due_later_text`) VALUES
(@patient_id, 'Prenatal vitamin', '1 tablet · Daily at 08:00 · With food', NULL, 1, 'Marked taken today at 08:05', NULL),
(@patient_id, 'Vitamin D · 400 IU', '1 tablet · Daily at 20:00', 'As prescribed by Dr. Amelia Chen.', 0, NULL, 'Due later');

-- Seed Appointments
INSERT INTO `appointments` (`patient_id`, `doctor_id`, `doctor_name`, `doctor_specialty`, `appointment_date`, `appointment_time`, `is_in_person`, `appointment_type`, `location_notes`, `status`)
VALUES (@patient_id, @doc_id, 'Dr Amelia Chen', 'Obstetrician · Willow Maternity Clinic', '2026-10-29', '10:30', 1, 'Routine antenatal check', 'Willow Maternity Clinic · Room 3. Bring your pregnancy notes and recent reports.', 'Confirmed');

-- Seed Reports
INSERT INTO `medical_reports` (`user_id`, `title`, `date_and_size_text`, `category`, `file_type`) VALUES
(@patient_id, 'Anatomy ultrasound', '02 Oct 2026 · PDF · 2.4 MB', 'ULTRASOUND', 'PDF'),
(@patient_id, 'Complete blood count', '28 Oct 2026 · PDF · 640 KB', 'LAB_RESULTS', 'PDF'),
(@patient_id, 'Antenatal visit summary', '04 Oct 2026 · PDF · 420 KB', 'ALL', 'PDF');

-- Seed Notifications
INSERT INTO `notifications` (`user_id`, `title`, `subtitle`, `detail_text`, `type`, `is_new`, `is_read`) VALUES
(@patient_id, 'Your appointment is confirmed', 'Dr Amelia Chen · 29 Oct 2026, 10:30', '09:10 · Willow Maternity Clinic · In person', 'APPOINTMENT', 1, 0),
(@patient_id, 'Tonight\'s vitamin D reminder', '400 IU · 1 tablet at 20:00', '08:30 · Reminder scheduled', 'REMINDER', 1, 0),
(@patient_id, 'Your report was reviewed', 'Dr Chen reviewed your blood count.', '08:25 · View care-team notes in Medical Reports', 'REPORT_REVIEW', 1, 0),
(@patient_id, 'A new week to discover', 'Yesterday · Explore today\'s week 24 development.', '', 'WEEKLY_DISCOVERY', 0, 1);

-- Seed Educational Baby Development (Week 24 sample)
INSERT INTO `baby_development` (`week`, `subtitle`, `length_cm_text`, `length_sub_text`, `weight_gram_text`, `weight_sub_text`, `highlights`)
VALUES (24, 'Week 24 · Your baby\'s weekly development.', '30 cm', 'Head to heel · approximate', '600 g', 'educational estimate',
'[{"id":1,"title":"A world of sound","subtitle":"Hearing continues to develop this week.","iconType":"SOUND"},{"id":2,"title":"Little movements","subtitle":"You may notice your baby\'s own rhythm.","iconType":"MOVEMENT"}]')
ON DUPLICATE KEY UPDATE `subtitle`=VALUES(`subtitle`);

-- Seed Educational Nutrition Plan (Trimesters 1, 2, 3)
INSERT INTO `nutrition_plans` (`trimester`, `trimester_title`, `featured_meal_category`, `featured_meal_title`, `featured_meal_details`, `priorities`)
VALUES (2, 'Balanced ideas for your second trimester.', 'LUNCH INSPIRATION', 'Chickpea & quinoa bowl', '25 min · Plant-based · Protein & fiber',
'[{"id":1,"title":"Iron-rich foods","subtitle":"Lentils, beans and well-cooked lean meats.","iconType":"IRON"},{"id":2,"title":"Calcium & protein","subtitle":"Pasteurized yogurt or fortified alternatives.","iconType":"CALCIUM"},{"id":3,"title":"Keep water nearby","subtitle":"Sip regularly; adapt to your care plan.","iconType":"HYDRATION"}]')
ON DUPLICATE KEY UPDATE `trimester_title`=VALUES(`trimester_title`);

