-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 10:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `momora_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

DROP TABLE IF EXISTS `admin_settings`;
CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `is_mfa_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `session_timeout_minutes` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ai_chat_messages`
--

DROP TABLE IF EXISTS `ai_chat_messages`;
CREATE TABLE `ai_chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender` enum('USER','AI') NOT NULL,
  `message_text` text NOT NULL,
  `bullet_points` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bullet_points`)),
  `footer_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(150) NOT NULL,
  `doctor_specialty` varchar(150) DEFAULT 'Obstetrician ?? Willow Maternity Clinic',
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(50) NOT NULL,
  `is_in_person` tinyint(1) NOT NULL DEFAULT 1,
  `appointment_type` varchar(100) DEFAULT 'Routine antenatal check',
  `location_notes` text DEFAULT NULL,
  `status` enum('Scheduled','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Confirmed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `baby_development`
--

DROP TABLE IF EXISTS `baby_development`;
CREATE TABLE `baby_development` (
  `id` int(11) NOT NULL,
  `week` int(11) NOT NULL,
  `subtitle` varchar(255) NOT NULL,
  `length_cm_text` varchar(50) NOT NULL,
  `length_sub_text` varchar(100) DEFAULT 'Head to heel ?? approximate',
  `weight_gram_text` varchar(50) NOT NULL,
  `weight_sub_text` varchar(100) DEFAULT 'educational estimate',
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_pressure_logs`
--

DROP TABLE IF EXISTS `blood_pressure_logs`;
CREATE TABLE `blood_pressure_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `systolic` int(11) NOT NULL,
  `diastolic` int(11) NOT NULL,
  `pulse` int(11) DEFAULT 78,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_advice`
--

DROP TABLE IF EXISTS `doctor_advice`;
CREATE TABLE `doctor_advice` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `advice_text` text NOT NULL,
  `save_copy` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profiles`
--

DROP TABLE IF EXISTS `doctor_profiles`;
CREATE TABLE `doctor_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialty` varchar(150) NOT NULL DEFAULT 'Obstetrics',
  `license_id` varchar(100) NOT NULL,
  `clinic` varchar(150) NOT NULL DEFAULT 'Willow Maternity Clinic',
  `gender` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_vitals`
--

DROP TABLE IF EXISTS `health_vitals`;
CREATE TABLE `health_vitals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `heart_rate` int(11) NOT NULL DEFAULT 78,
  `temperature` decimal(4,1) NOT NULL DEFAULT 36.7,
  `temperature_unit` varchar(10) NOT NULL DEFAULT '??C',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

DROP TABLE IF EXISTS `medical_reports`;
CREATE TABLE `medical_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) NOT NULL DEFAULT 'PDF',
  `date_and_size_text` varchar(100) NOT NULL,
  `category` enum('ALL','ULTRASOUND','LAB_RESULTS') NOT NULL DEFAULT 'ALL',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

DROP TABLE IF EXISTS `medications`;
CREATE TABLE `medications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dosage_and_time` varchar(150) NOT NULL,
  `doctor_prescription_note` varchar(255) DEFAULT NULL,
  `is_taken` tinyint(1) NOT NULL DEFAULT 0,
  `taken_time_note` varchar(100) DEFAULT NULL,
  `due_later_text` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `subtitle` text NOT NULL,
  `detail_text` text DEFAULT NULL,
  `type` enum('APPOINTMENT','REMINDER','REPORT_REVIEW','WEEKLY_DISCOVERY') NOT NULL DEFAULT 'REMINDER',
  `is_new` tinyint(1) NOT NULL DEFAULT 1,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_plans`
--

DROP TABLE IF EXISTS `nutrition_plans`;
CREATE TABLE `nutrition_plans` (
  `id` int(11) NOT NULL,
  `trimester` int(11) NOT NULL,
  `trimester_title` varchar(255) NOT NULL,
  `featured_meal_category` varchar(100) NOT NULL,
  `featured_meal_title` varchar(150) NOT NULL,
  `featured_meal_details` text NOT NULL,
  `priorities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`priorities`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(190) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pregnancy_details`
--

DROP TABLE IF EXISTS `pregnancy_details`;
CREATE TABLE `pregnancy_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_menstrual_period` date DEFAULT NULL,
  `estimated_due_date` date NOT NULL,
  `gestational_weeks` int(11) NOT NULL DEFAULT 24,
  `gestational_days` int(11) NOT NULL DEFAULT 3,
  `trimester` int(11) NOT NULL DEFAULT 2,
  `next_visit_date` datetime DEFAULT NULL,
  `next_visit_clinic` varchar(150) DEFAULT 'Willow Maternity Clinic',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `blood_group` varchar(15) DEFAULT 'O positive',
  `address` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_care_team_sharing_on` tinyint(1) NOT NULL DEFAULT 1,
  `assigned_doctor_id` int(11) DEFAULT NULL,
  `reminder_summary` varchar(255) DEFAULT 'Medication and appointment alerts',
  `clinical_background` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `symptom_logs`
--

DROP TABLE IF EXISTS `symptom_logs`;
CREATE TABLE `symptom_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `symptom_name` varchar(150) NOT NULL,
  `severity` enum('MILD','MODERATE','SEVERE') NOT NULL DEFAULT 'MILD',
  `started_text` varchar(100) NOT NULL,
  `duration_text` varchar(100) NOT NULL,
  `ai_guidance` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL DEFAULT 'patient',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weight_logs`
--

DROP TABLE IF EXISTS `weight_logs`;
CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `baseline_weight_kg` decimal(5,2) DEFAULT 59.00,
  `notes` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_user` (`user_id`,`created_at`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appts_patient` (`patient_id`),
  ADD KEY `idx_appts_doctor` (`doctor_id`);

--
-- Indexes for table `baby_development`
--
ALTER TABLE `baby_development`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `week` (`week`);

--
-- Indexes for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bp_user_date` (`user_id`,`recorded_at`);

--
-- Indexes for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `health_vitals`
--
ALTER TABLE `health_vitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_user` (`user_id`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`);

--
-- Indexes for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trimester` (`trimester`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_resets_token` (`reset_token`),
  ADD KEY `idx_resets_email` (`email`);

--
-- Indexes for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_symptoms_user` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_tokens_token` (`token`),
  ADD KEY `idx_tokens_user` (`user_id`);

--
-- Indexes for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_weight_user_date` (`user_id`,`recorded_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `baby_development`
--
ALTER TABLE `baby_development`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_vitals`
--
ALTER TABLE `health_vitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weight_logs`
--
ALTER TABLE `weight_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `admin_settings_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_chat_messages`
--
ALTER TABLE `ai_chat_messages`
  ADD CONSTRAINT `ai_chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_pressure_logs`
--
ALTER TABLE `blood_pressure_logs`
  ADD CONSTRAINT `blood_pressure_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_advice`
--
ALTER TABLE `doctor_advice`
  ADD CONSTRAINT `doctor_advice_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_advice_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_vitals`
--
ALTER TABLE `health_vitals`
  ADD CONSTRAINT `health_vitals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `medical_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medications`
--
ALTER TABLE `medications`
  ADD CONSTRAINT `medications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pregnancy_details`
--
ALTER TABLE `pregnancy_details`
  ADD CONSTRAINT `pregnancy_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `symptom_logs`
--
ALTER TABLE `symptom_logs`
  ADD CONSTRAINT `symptom_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weight_logs`
--
ALTER TABLE `weight_logs`
  ADD CONSTRAINT `weight_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
